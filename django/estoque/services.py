"""Estoque integrado às OS, compras e custos (gestao_integrada.php, compras.php, custos_os.php)."""
from decimal import Decimal

from django.core.exceptions import PermissionDenied, ValidationError
from django.db import transaction
from django.db.models import F, Q, Sum
from django.urls import reverse
from django.utils import timezone

from core.imaging import processar_upload
from core.services import auditar, notificar, notificar_permissao

from .models import Compra, CompraItem, CustoDocumento, MaterialEstoque, Movimentacao, Reserva


class ErroEstoque(Exception):
    pass


def _ordens_services():
    from ordens import services

    return services


# --------------------------------------------------------------------- consumo na OS

def consumir_na_ordem(ordem, usuario, material, quantidade, *, reservar=False, item_os=None, request=None):
    """Consome (ou reserva) material do estoque para a ordem.

    Sem saldo disponível, cria uma solicitação de estoque negativo para aprovação, como no PHP.
    Retorna a Reserva criada.
    """
    quantidade = Decimal(quantidade)
    if quantidade <= 0:
        raise ErroEstoque("Quantidade inválida.")
    with transaction.atomic():
        material = MaterialEstoque.objects.select_for_update().get(pk=material.pk)
        if not material.ativo:
            raise ErroEstoque("Este material está inativo.")
        disponivel = material.disponivel
        base = {"ordem": ordem, "material": material, "usuario": usuario, "quantidade": quantidade,
                "custo_unitario": material.custo_unitario, "item_os": item_os}
        if disponivel < quantidade:
            reserva = Reserva.objects.create(
                status=Reserva.Status.APROVACAO_NEGATIVO,
                justificativa=f"Saldo disponível: {disponivel}. Solicitado: {quantidade}.", **base)
            if item_os:
                item_os.situacao = "AGUARDANDO_APROVACAO"
                item_os.save(update_fields=["situacao"])
            notificar_permissao("estoque.gerenciar", "MATERIAL_APROVACAO", "Aprovação de estoque negativo",
                                f"{material.nome} para {ordem.numero}. {reserva.justificativa}",
                                reverse("operacao:aprovacoes"), f"estoque-neg-{reserva.pk}", excluir=usuario)
            return reserva
        if reservar:
            reserva = Reserva.objects.create(status=Reserva.Status.RESERVADA, **base)
            if item_os:
                item_os.situacao = "RESERVADO"
                item_os.save(update_fields=["situacao"])
            return reserva
        reserva = Reserva.objects.create(status=Reserva.Status.CONSUMIDA, **base)
        _baixar(reserva, usuario)
        return reserva


def _baixar(reserva, usuario, observacao="Consumo integrado na OS"):
    material = reserva.material
    MaterialEstoque.objects.filter(pk=material.pk).update(quantidade_atual=F("quantidade_atual") - reserva.quantidade)
    Movimentacao.objects.create(material=material, ordem=reserva.ordem, usuario=usuario, tipo=Movimentacao.Tipo.SAIDA_OS,
                                quantidade=-reserva.quantidade, custo_unitario=reserva.custo_unitario,
                                observacao=observacao)
    if reserva.item_os_id:
        type(reserva.item_os).objects.filter(pk=reserva.item_os_id).update(situacao="CONSUMIDO")
    recalcular_custos(reserva.ordem)


def consumir_reserva(reserva, usuario):
    with transaction.atomic():
        reserva = Reserva.objects.select_for_update().get(pk=reserva.pk)
        if reserva.status != Reserva.Status.RESERVADA:
            raise ErroEstoque("A reserva não está ativa.")
        reserva.status = Reserva.Status.CONSUMIDA
        reserva.save(update_fields=["status"])
        _baixar(reserva, usuario, "Consumo de material reservado")


def cancelar_reserva(reserva, usuario):
    with transaction.atomic():
        reserva = Reserva.objects.select_for_update().get(pk=reserva.pk)
        if reserva.status not in (Reserva.Status.RESERVADA, Reserva.Status.APROVACAO_NEGATIVO):
            raise ErroEstoque("Esta reserva não pode ser cancelada.")
        reserva.status = Reserva.Status.CANCELADA
        reserva.save(update_fields=["status"])
        if reserva.item_os_id:
            type(reserva.item_os).objects.filter(pk=reserva.item_os_id).update(situacao="REPROVADO")


def decidir_negativo(reserva, usuario, aprovar: bool, observacao="", request=None):
    if not usuario.pode("estoque.gerenciar"):
        raise PermissionDenied
    with transaction.atomic():
        reserva = Reserva.objects.select_for_update().select_related("ordem", "material").get(pk=reserva.pk)
        if reserva.status != Reserva.Status.APROVACAO_NEGATIVO:
            raise ErroEstoque("Esta solicitação já foi decidida.")
        reserva.aprovado_por, reserva.aprovado_em = usuario, timezone.now()
        reserva.justificativa = (reserva.justificativa + f"\nDecisão: {observacao}").strip()
        if aprovar:
            reserva.status = Reserva.Status.CONSUMIDA
            reserva.save()
            _baixar(reserva, usuario, "Consumo aprovado com saldo negativo")
        else:
            reserva.status = Reserva.Status.REPROVADA
            reserva.save()
            if reserva.item_os_id:
                type(reserva.item_os).objects.filter(pk=reserva.item_os_id).update(situacao="REPROVADO")
        auditar(request, "estoque_reserva", reserva.pk, "APROVAR_NEGATIVO" if aprovar else "REPROVAR_NEGATIVO")
        if reserva.usuario:
            notificar(reserva.usuario, "MATERIAL_DECISAO",
                      f"Material {'aprovado' if aprovar else 'reprovado'} — {reserva.material.nome}",
                      f"{reserva.ordem.numero}. {observacao}", reverse("ordens:detalhe", args=[reserva.ordem_id]),
                      f"estoque-dec-{reserva.pk}")
    return reserva


# --------------------------------------------------------------------- movimentações manuais

def movimentar(material, usuario, tipo, quantidade, custo_unitario=None, observacao="", request=None):
    if not usuario.pode("estoque.gerenciar"):
        raise PermissionDenied
    quantidade = Decimal(quantidade)
    if tipo not in (Movimentacao.Tipo.ENTRADA, Movimentacao.Tipo.SAIDA, Movimentacao.Tipo.AJUSTE):
        raise ErroEstoque("Tipo de movimentação inválido.")
    with transaction.atomic():
        material = MaterialEstoque.objects.select_for_update().get(pk=material.pk)
        if tipo == Movimentacao.Tipo.AJUSTE:
            delta = quantidade - material.quantidade_atual  # ajuste informa o saldo contado
        elif tipo == Movimentacao.Tipo.SAIDA:
            delta = -abs(quantidade)
        else:
            delta = abs(quantidade)
            if custo_unitario is not None and custo_unitario > 0:
                # custo médio ponderado
                total_atual = max(material.quantidade_atual, Decimal(0)) * material.custo_unitario
                novo_total = total_atual + abs(quantidade) * Decimal(custo_unitario)
                novo_saldo = max(material.quantidade_atual, Decimal(0)) + abs(quantidade)
                material.custo_unitario = (novo_total / novo_saldo).quantize(Decimal("0.01")) if novo_saldo else custo_unitario
        material.quantidade_atual += delta
        material.save()
        mov = Movimentacao.objects.create(material=material, usuario=usuario, tipo=tipo, quantidade=delta,
                                          custo_unitario=custo_unitario or material.custo_unitario,
                                          observacao=observacao)
        auditar(request, "estoque", material.pk, "MOVIMENTAR", tipo, None, str(delta))
    return mov


# --------------------------------------------------------------------- compras

def criar_compra(usuario, itens, *, ordem=None, justificativa="", request=None):
    if not usuario.pode("compras.solicitar"):
        raise PermissionDenied
    itens = [i for i in itens if i.get("descricao") and Decimal(i.get("quantidade") or 0) > 0]
    if not itens:
        raise ErroEstoque("Informe ao menos um item.")
    with transaction.atomic():
        compra = Compra.objects.create(ordem=ordem, solicitante=usuario, justificativa=justificativa)
        total = Decimal(0)
        for i in itens:
            item = CompraItem.objects.create(compra=compra, material=i.get("material"), descricao=i["descricao"][:190],
                                             quantidade=i["quantidade"], unidade=i.get("unidade", "")[:30],
                                             custo_unitario_previsto=i.get("custo_unitario") or 0)
            total += item.quantidade * item.custo_unitario_previsto
        compra.valor_previsto = total
        compra.save(update_fields=["valor_previsto"])
        if ordem:
            _ordens_services().historico(ordem, usuario, f"Compra #{compra.pk} solicitada (R$ {total:.2f}).")
        auditar(request, "compra", compra.pk, "SOLICITAR", "valor_previsto", None, total)
        notificar_permissao("compras.gerenciar", "COMPRA_APROVACAO", f"Compra #{compra.pk} aguardando aprovação",
                            justificativa[:200], reverse("estoque:compras"), f"compra-{compra.pk}", excluir=usuario)
    return compra


def decidir_compra(compra, usuario, aprovar: bool, observacao="", request=None):
    if not usuario.pode("compras.gerenciar"):
        raise PermissionDenied
    if compra.status != Compra.Status.SOLICITADA:
        raise ErroEstoque("Esta compra já foi decidida.")
    if compra.solicitante_id == usuario.pk and not usuario.is_ti:
        raise ErroEstoque("O solicitante não pode aprovar a própria compra.")
    compra.status = Compra.Status.APROVADA if aprovar else Compra.Status.REPROVADA
    compra.aprovador, compra.decidido_em, compra.observacao_decisao = usuario, timezone.now(), observacao
    compra.save()
    auditar(request, "compra", compra.pk, "DECIDIR", "status", "SOLICITADA", compra.status)
    notificar(compra.solicitante, "COMPRA_DECISAO", f"Compra #{compra.pk} {compra.get_status_display().lower()}",
              observacao, reverse("estoque:compras"), f"compra-dec-{compra.pk}")


def registrar_compra(compra, usuario, fornecedor, documento, valor_final, request=None):
    if not usuario.pode("compras.gerenciar"):
        raise PermissionDenied
    if compra.status != Compra.Status.APROVADA:
        raise ErroEstoque("Somente compras aprovadas podem ser registradas como compradas.")
    compra.status, compra.comprado_em = Compra.Status.COMPRADA, timezone.now()
    compra.fornecedor, compra.numero_documento = fornecedor[:180], documento[:100]
    compra.valor_final = valor_final or compra.valor_previsto
    compra.save()
    auditar(request, "compra", compra.pk, "COMPRADA", "valor_final", None, compra.valor_final)


def receber_compra(compra, usuario, request=None):
    """Recebimento: dá entrada no estoque dos itens vinculados e lança o custo na OS."""
    if not usuario.pode("compras.gerenciar"):
        raise PermissionDenied
    if compra.status != Compra.Status.COMPRADA:
        raise ErroEstoque("Registre a compra antes do recebimento.")
    with transaction.atomic():
        fator = (compra.valor_final / compra.valor_previsto) if compra.valor_previsto else Decimal(1)
        for item in compra.itens.select_related("material"):
            custo = item.custo_unitario_final if item.custo_unitario_final is not None else \
                (item.custo_unitario_previsto * fator).quantize(Decimal("0.01"))
            if item.material:
                m = MaterialEstoque.objects.select_for_update().get(pk=item.material_id)
                m.quantidade_atual += item.quantidade
                if custo:
                    m.custo_unitario = custo
                m.save()
                Movimentacao.objects.create(material=m, ordem=compra.ordem, usuario=usuario,
                                            tipo=Movimentacao.Tipo.COMPRA, quantidade=item.quantidade,
                                            custo_unitario=custo, observacao=f"Recebimento da compra #{compra.pk}")
        compra.status, compra.recebido_em = Compra.Status.RECEBIDA, timezone.now()
        compra.save()
        if compra.ordem and not compra.itens.filter(material__isnull=False).exists():
            # Compra direta para a OS (sem estoque): vira custo de terceiros/serviço.
            CustoDocumento.objects.create(ordem=compra.ordem, tipo=CustoDocumento.Tipo.TERCEIRO,
                                          fornecedor=compra.fornecedor, documento=compra.numero_documento,
                                          descricao=f"Compra #{compra.pk}", valor=compra.valor_final,
                                          registrado_por=usuario)
            recalcular_custos(compra.ordem)
        auditar(request, "compra", compra.pk, "RECEBIDA")
    notificar(compra.solicitante, "COMPRA_RECEBIDA", f"Compra #{compra.pk} recebida", compra.fornecedor,
              reverse("estoque:compras"), f"compra-rec-{compra.pk}")


# --------------------------------------------------------------------- custos da OS

def recalcular_custos(ordem):
    from ordens.models import Material

    materiais = Material.objects.filter(ordem=ordem, situacao__in=["CONSUMIDO", "AVULSO"]).aggregate(
        t=Sum(F("quantidade") * F("custo_unitario")))["t"] or Decimal(0)
    mao = ordem.custos.filter(tipo=CustoDocumento.Tipo.MAO_OBRA).aggregate(t=Sum("valor"))["t"] or Decimal(0)
    outros = ordem.custos.filter(~Q(tipo=CustoDocumento.Tipo.MAO_OBRA)).aggregate(t=Sum("valor"))["t"] or Decimal(0)
    type(ordem).objects.filter(pk=ordem.pk).update(
        custo_materiais=materiais, custo_mao_obra=mao, custo_terceiros=outros,
        custo_total_final=materiais + mao + outros)
    ordem.custo_materiais, ordem.custo_mao_obra, ordem.custo_terceiros = materiais, mao, outros
    ordem.custo_total_final = materiais + mao + outros
    return ordem.custo_total_final


def adicionar_custo(ordem, usuario, dados, arquivo=None, request=None):
    if not usuario.pode("custos.gerenciar"):
        raise PermissionDenied
    if dados["tipo"] not in CustoDocumento.Tipo.values:
        raise ErroEstoque("Tipo de custo inválido.")
    custo = CustoDocumento(ordem=ordem, tipo=dados["tipo"], fornecedor=dados.get("fornecedor", ""),
                           documento=dados.get("documento", ""), descricao=dados.get("descricao", ""),
                           valor=dados["valor"], registrado_por=usuario)
    if arquivo:
        try:
            custo.aplicar(processar_upload(arquivo, prefixo=f"custo_{ordem.pk}_"), arquivo.name)
        except ValidationError as exc:
            raise ErroEstoque(exc.messages[0]) from exc
    with transaction.atomic():
        custo.save()
        total = recalcular_custos(ordem)
        _ordens_services().historico(ordem, usuario, f"Custo lançado: {custo.get_tipo_display()} R$ {custo.valor:.2f}.")
        auditar(request, "ordem", ordem.pk, "CUSTO", custo.tipo, None, custo.valor)
    return total


def remover_custo(custo, usuario, request=None):
    if not usuario.pode("custos.gerenciar"):
        raise PermissionDenied
    ordem = custo.ordem
    with transaction.atomic():
        custo.delete()
        transaction.on_commit(custo.remover_arquivos)
        recalcular_custos(ordem)
        auditar(request, "ordem", ordem.pk, "REMOVER_CUSTO", custo.tipo, custo.valor, None)
