"""Regras de negócio das ordens (OS/OL), portadas de ordem_form.php e ordem_detalhe.php."""
from __future__ import annotations

import hmac
import secrets
from datetime import timedelta

from django.conf import settings
from django.core.exceptions import PermissionDenied, ValidationError
from django.db import transaction
from django.db.models import F
from django.urls import reverse
from django.utils import timezone

from core.imaging import processar_upload
from core.services import auditar, notificar, notificar_permissao

from .models import (
    STATUS_AGENTE, STATUS_GESTOR, Anexo, ChecklistItem, Historico, Material, NumeroSequencia, Ordem,
    OrdemAgente, ParticipanteLimpeza, SolicitacaoExclusao, SolicitacaoReabertura, Status, TokenReabertura,
)


class RegraNegocio(Exception):
    """Erro de regra que deve ser exibido ao usuário."""


# --------------------------------------------------------------------- utilitários

def gerar_numero(tipo: str) -> str:
    """OS-2026-0000001 / OL-2026-0000001, sequência anual atômica por tipo."""
    ano = timezone.localdate().year
    with transaction.atomic():
        seq, _ = NumeroSequencia.objects.select_for_update().get_or_create(tipo=tipo, ano=ano)
        NumeroSequencia.objects.filter(pk=seq.pk).update(ultimo=F("ultimo") + 1)
        seq.refresh_from_db()
    return f"{tipo}-{ano:04d}-{seq.ultimo:07d}"


def sla_horas(prioridade: str) -> int:
    return settings.GEOP_SLA_HORAS.get(prioridade, 48)


def calcular_prazo(prioridade: str, base=None):
    return (base or timezone.now()) + timedelta(hours=sla_horas(prioridade))


def historico(ordem, usuario, descricao, anterior=None, novo=None):
    return Historico.objects.create(
        ordem=ordem,
        usuario=usuario if getattr(usuario, "is_authenticated", False) else None,
        status_anterior=anterior if anterior is not None else ordem.status,
        status_novo=novo if novo is not None else ordem.status,
        descricao=descricao,
    )


def registrar_tempo_status(ordem, novo_status, usuario=None):
    from operacao.models import StatusTempo

    agora = timezone.now()
    for aberto in StatusTempo.objects.filter(ordem=ordem, finalizado_em__isnull=True):
        aberto.finalizado_em = agora
        aberto.segundos = int((agora - aberto.iniciado_em).total_seconds())
        aberto.save(update_fields=["finalizado_em", "segundos"])
    StatusTempo.objects.create(ordem=ordem, status=novo_status, iniciado_em=agora,
                               usuario=usuario if getattr(usuario, "is_authenticated", False) else None)


def calcular_qualidade(ordem, salvar=True):
    """Pontuação de qualidade da OS (regra de geop_v8_quality do PHP)."""
    tipos = set(ordem.anexos.values_list("tipo", flat=True))
    pendentes = ordem.checklist.filter(obrigatorio=True, concluido=False).count()
    conclusao = (ordem.observacao_aprovacao or ordem.solicitacao_encerramento_obs or "").strip()
    itens = [
        ("Descrição detalhada", len((ordem.descricao or "").strip()) >= 20, 12),
        ("Responsável definido", bool(ordem.agente_id), 10),
        ("Foto antes", "FOTO_ANTES" in tipos, 10),
        ("Foto depois", "FOTO_DEPOIS" in tipos, 15),
        ("Checklist obrigatório concluído", pendentes == 0, 15),
        ("Materiais/custos conferidos", True, 8),
        ("Resultado da conclusão registrado", len(conclusao) >= 5, 12),
        ("Prazo/SLA definido", bool(ordem.prazo), 8),
        ("Patrimônio vinculado", ordem.patrimonios.filter(ativo=True).exists(), 10),
    ]
    maximo = sum(p for _, _, p in itens)
    pct = round(sum(p for _, ok, p in itens if ok) * 100 / maximo)
    if salvar and ordem.qualidade_percentual != pct:
        type(ordem).objects.filter(pk=ordem.pk).update(qualidade_percentual=pct)
        ordem.qualidade_percentual = pct
    return pct, [{"rotulo": r, "ok": ok, "peso": p} for r, ok, p in itens]


def carga_ativa(usuario):
    return Ordem.objects.ativas().filter(agente=usuario).exclude(
        status__in=[Status.ENCERRADA, Status.CONCLUIDA_PELO_AGENTE, Status.REPROVADA]).count()


def verificar_capacidade(agente):
    """Agente indisponível (férias, ausente, afastado) ou com a carga máxima não recebe novas ordens."""
    from operacao.models import EquipeEstado

    estado = EquipeEstado.objects.filter(usuario=agente).first()
    if estado and estado.estado in EquipeEstado.INDISPONIVEIS:
        raise RegraNegocio(f"{agente.nome} está {estado.get_estado_display().lower()} e não pode receber ordens.")
    capacidade = estado.capacidade_maxima if estado else 5
    if carga_ativa(agente) >= capacidade:
        raise RegraNegocio(f"{agente.nome} atingiu a capacidade máxima de {capacidade} ordens ativas.")


def url_ordem(ordem):
    return reverse("ordens:detalhe", args=[ordem.pk])


def pode_gerenciar(user) -> bool:
    return user.pode("os.gerenciar")


def pode_executar(user, ordem) -> bool:
    return user.pode("os.executar") and ordem.executor(user)


def pode_ver(user, ordem) -> bool:
    return user.pode("os.visualizar_todas") or ordem.participa(user)


def _bloquear_se_encerrada(ordem, user, acao="alterada"):
    if ordem.encerrada and not user.is_ti:
        raise RegraNegocio(f"Ordem encerrada não pode ser {acao} sem reabertura autorizada pelo TI.")


# --------------------------------------------------------------------- fotos e anexos

def salvar_arquivos(ordem, usuario, arquivos, *, tipo=Anexo.Tipo.OUTRO, descricao="", origem=Anexo.Origem.INTERNO,
                    somente_imagens=False):
    """Valida, comprime e grava os arquivos. Tudo ou nada: se um falhar, nenhum é salvo."""
    arquivos = [a for a in arquivos if a]
    limite = settings.GEOP_FOTOS_POR_ENVIO
    if len(arquivos) > limite:
        raise RegraNegocio(f"Envie no máximo {limite} arquivos por vez.")
    processados = []
    for arquivo in arquivos:
        try:
            processados.append((arquivo.name, processar_upload(arquivo, permitir_pdf=not somente_imagens,
                                                               prefixo=f"{ordem.tipo.lower()}_{ordem.pk}_")))
        except ValidationError as exc:
            raise RegraNegocio(f"{arquivo.name}: {exc.messages[0]}") from exc
    salvos = []
    for nome, proc in processados:
        anexo = Anexo(ordem=ordem, usuario=usuario if getattr(usuario, "is_authenticated", False) else None,
                      tipo=tipo if proc.eh_imagem or tipo == Anexo.Tipo.DOCUMENTO else Anexo.Tipo.DOCUMENTO,
                      origem=origem, descricao=descricao)
        anexo.aplicar(proc, nome)
        anexo.save()
        salvos.append(anexo)
    return salvos


def descrever_economia(anexos) -> str:
    original = sum(a.tamanho_original for a in anexos)
    final = sum(a.tamanho for a in anexos)
    if not original or final >= original:
        return ""
    from core.imaging import formatar_bytes

    return f" ({formatar_bytes(original)} → {formatar_bytes(final)})"


def anexar(ordem, user, arquivos, tipo, descricao, request=None):
    if not (pode_gerenciar(user) or pode_executar(user, ordem)):
        raise PermissionDenied
    _bloquear_se_encerrada(ordem, user, "receber anexos")
    if tipo not in Anexo.Tipo.values:
        raise RegraNegocio("Tipo de anexo inválido.")
    with transaction.atomic():
        anexos = salvar_arquivos(ordem, user, arquivos, tipo=tipo, descricao=descricao)
        if not anexos:
            raise RegraNegocio("Selecione ao menos um arquivo.")
        historico(ordem, user, f"{len(anexos)} anexo(s) enviado(s).")
        auditar(request, "ordem", ordem.pk, "ANEXO", "arquivo", None, ", ".join(a.arquivo.name for a in anexos))
    return anexos


def mover_foto(ordem, user, anexo, novo_tipo, request=None):
    if not pode_gerenciar(user):
        raise PermissionDenied
    if not anexo.eh_imagem or novo_tipo not in (Anexo.Tipo.FOTO_ANTES, Anexo.Tipo.FOTO_DURANTE,
                                                Anexo.Tipo.FOTO_DEPOIS, Anexo.Tipo.OUTRO):
        raise RegraNegocio("Somente fotos podem ser movidas entre Antes, Durante e Depois.")
    if anexo.tipo == novo_tipo:
        raise RegraNegocio("A foto já está nesta categoria.")
    anterior = anexo.tipo
    anexo.tipo = novo_tipo
    anexo.save(update_fields=["tipo"])
    historico(ordem, user, f"Foto movida de {Anexo.Tipo(anterior).label} para {anexo.get_tipo_display()}.")
    auditar(request, "ordem", ordem.pk, "MOVER_FOTO", "anexo.tipo", anterior, novo_tipo)


def excluir_anexo(ordem, user, anexo, request=None):
    permitido = pode_gerenciar(user) or (
        pode_executar(user, ordem) and anexo.usuario_id == user.pk and not ordem.encerrada
    )
    if not permitido:
        raise PermissionDenied("Você não tem permissão para apagar este anexo.")
    descricao = f"{anexo.get_tipo_display()} — {anexo.nome_original or anexo.arquivo.name}"
    with transaction.atomic():
        anexo.delete()
        transaction.on_commit(anexo.remover_arquivos)
        historico(ordem, user, f"Anexo removido ({anexo.get_tipo_display()}).")
        auditar(request, "ordem", ordem.pk, "EXCLUIR_ANEXO", "anexo", descricao, None)


# --------------------------------------------------------------------- criação

def criar_ordem_sistema(dados):
    """Ordem aberta por rotina automática (preventiva, IoT, rede). O solicitante é o gestor indicado ou o TI."""
    from django.contrib.auth import get_user_model

    Usuario = get_user_model()
    responsavel = dados.get("gestor") or Usuario.objects.filter(is_active=True, perfil__codigo="TI").order_by("pk").first() \
        or Usuario.objects.filter(is_active=True, is_superuser=True).order_by("pk").first()
    if responsavel is None:
        raise RegraNegocio("Cadastre ao menos um usuário TI ativo para receber as ordens automáticas.")
    dados = {**dados, "gestor": responsavel, "ignorar_capacidade": True}
    return criar_ordem(responsavel, dados, verificar_permissao=False)


def criar_ordem(user, dados, *, fotos=(), tarefas=(), itens_livres=(), equipe=None, request=None,
                verificar_permissao=True):
    if verificar_permissao and not user.pode("os.criar"):
        raise PermissionDenied("Seu perfil não pode abrir ordens.")
    categoria = dados["categoria"]
    tipo = Ordem.Tipo.OL if categoria.eh_limpeza else Ordem.Tipo.OS
    prioridade = dados["prioridade"]
    agente = dados.get("agente")
    gestor = dados.get("gestor") or user
    if agente and not user.is_ti and not dados.get("ignorar_capacidade"):
        verificar_capacidade(agente)
    with transaction.atomic():
        ordem = Ordem.objects.create(
            numero=gerar_numero(tipo),
            tipo=tipo,
            titulo=dados["titulo"],
            descricao=dados["descricao"],
            local=dados["local"],
            categoria=categoria,
            prioridade=prioridade,
            status=Status.ATRIBUIDA if agente else Status.ABERTA,
            solicitante=user,
            gestor=gestor,
            agente=agente,
            prazo=dados.get("prazo") or calcular_prazo(prioridade),
            sla_horas=sla_horas(prioridade),
            valor_previsto_orcamento=dados.get("valor_previsto_orcamento"),
            origem=dados.get("origem", "MANUAL"),
            preventiva=dados.get("preventiva"),
            duplicidade_referencia=dados.get("duplicidade_referencia"),
        )
        registrar_tempo_status(ordem, ordem.status, user)
        if agente:
            OrdemAgente.objects.create(ordem=ordem, usuario=agente, papel=OrdemAgente.Papel.PRINCIPAL, criado_por=user)
        for aux in dados.get("auxiliares") or []:
            if aux != agente:
                OrdemAgente.objects.get_or_create(ordem=ordem, usuario=aux,
                                                  defaults={"papel": OrdemAgente.Papel.AUXILIAR, "criado_por": user})
        salvar_checklist(ordem, tarefas, itens_livres)
        instanciar_checklist_categoria(ordem)
        if tipo == Ordem.Tipo.OL and equipe:
            salvar_equipe_limpeza(ordem, **equipe)
        anexos = salvar_arquivos(ordem, user, fotos, tipo=Anexo.Tipo.FOTO_ANTES,
                                 descricao="Foto anexada na abertura da ordem.", somente_imagens=True)
        historico(ordem, user, "Ordem aberta." + (f" {len(anexos)} foto(s) anexada(s)." if anexos else ""), "", ordem.status)
        auditar(request, "ordem", ordem.pk, "CRIAR", "numero", None, ordem.numero)
        for u in {agente, *(dados.get("auxiliares") or [])} - {None}:
            notificar(u, "OS_ATRIBUIDA", f"{ordem.numero} atribuída para você", ordem.titulo, url_ordem(ordem),
                      f"os-atribuida-{ordem.pk}-{u.pk}")
        if gestor != user:
            notificar(gestor, "OS_NOVA", f"Nova ordem {ordem.numero}", ordem.titulo, url_ordem(ordem), f"os-nova-{ordem.pk}")
    return ordem


def salvar_checklist(ordem, tarefas, itens_livres):
    ordem.checklist.all().delete()
    posicao = 1
    for tarefa in tarefas:
        ChecklistItem.objects.create(ordem=ordem, tarefa_plano=tarefa, descricao=tarefa.descricao[:255],
                                     exige_foto=tarefa.exige_foto, posicao=posicao)
        posicao += 1
    for texto in itens_livres:
        texto = (texto or "").strip()
        if texto:
            ChecklistItem.objects.create(ordem=ordem, descricao=texto[:255], posicao=posicao)
            posicao += 1


def instanciar_checklist_categoria(ordem):
    """Copia o checklist ativo da categoria para a ordem (snapshot, preserva o histórico)."""
    from .models import ChecklistModelo

    modelo = ChecklistModelo.objects.filter(categoria=ordem.categoria, ativo=True).order_by("-id").first()
    if not modelo:
        return 0
    base = ordem.checklist.count()
    itens = list(modelo.itens.all())
    for i, item in enumerate(itens, start=1):
        ChecklistItem.objects.create(ordem=ordem, modelo_item=item, descricao=item.descricao, obrigatorio=item.obrigatorio,
                                     exige_foto=item.exige_foto, posicao=base + i)
    return len(itens)


def salvar_equipe_limpeza(ordem, membros=(), principal=None, supervisor=None):
    ordem.equipe_limpeza.all().delete()
    membros = [m for m in dict.fromkeys(membros) if m != supervisor]
    if principal and principal not in membros:
        membros.append(principal)
    if any(not m.ativo for m in [*membros, supervisor] if m):
        raise RegraNegocio("Um dos colaboradores selecionados não está ativo no organograma da limpeza.")
    for m in membros:
        ParticipanteLimpeza.objects.create(ordem=ordem, membro=m, papel=ParticipanteLimpeza.Papel.ASG,
                                           principal=(m == principal))
    if supervisor:
        ParticipanteLimpeza.objects.create(ordem=ordem, membro=supervisor, papel=ParticipanteLimpeza.Papel.SUPERVISOR)


# --------------------------------------------------------------------- edição e atribuição

CAMPOS_EDITAVEIS = {"titulo": "Título", "descricao": "Descrição", "local": "Local", "categoria": "Categoria",
                    "prioridade": "Prioridade", "prazo": "Prazo"}


def editar(ordem, user, dados, request=None):
    if not pode_gerenciar(user):
        raise PermissionDenied
    _bloquear_se_encerrada(ordem, user, "editada")
    alterados = []
    with transaction.atomic():
        for campo, rotulo in CAMPOS_EDITAVEIS.items():
            novo = dados.get(campo)
            if campo == "prazo" and not novo:
                novo = calcular_prazo(dados.get("prioridade", ordem.prioridade), ordem.criado_em)
            antigo = getattr(ordem, campo)
            if novo != antigo:
                alterados.append(rotulo)
                auditar(request, "ordem", ordem.pk, "EDITAR", campo, antigo, novo)
                setattr(ordem, campo, novo)
        ordem.sla_horas = sla_horas(ordem.prioridade)
        nova_categoria_tipo = Ordem.Tipo.OL if ordem.categoria.eh_limpeza else Ordem.Tipo.OS
        if nova_categoria_tipo != ordem.tipo:
            ordem.tipo = nova_categoria_tipo  # o número é mantido, como no PHP
        ordem.save()
        historico(ordem, user, "Ordem editada. Campos alterados: " + (", ".join(alterados) if alterados else "nenhum") + ".")


def atribuir(ordem, user, agente, auxiliares=(), request=None):
    if not pode_gerenciar(user):
        raise PermissionDenied
    _bloquear_se_encerrada(ordem, user)
    if agente and agente.pk != ordem.agente_id and not user.is_ti:
        verificar_capacidade(agente)
    anterior = ordem.status
    auxiliares = [a for a in dict.fromkeys(auxiliares) if a != agente]
    with transaction.atomic():
        ordem.agente = agente
        if ordem.status in (Status.ABERTA, Status.ATRIBUIDA):
            ordem.status = Status.ATRIBUIDA if agente else Status.ABERTA
        ordem.save(update_fields=["agente", "status", "atualizado_em"])
        if anterior != ordem.status:
            registrar_tempo_status(ordem, ordem.status, user)
        ordem.agentes.all().delete()
        if agente:
            OrdemAgente.objects.create(ordem=ordem, usuario=agente, papel=OrdemAgente.Papel.PRINCIPAL, criado_por=user)
        for aux in auxiliares:
            OrdemAgente.objects.create(ordem=ordem, usuario=aux, papel=OrdemAgente.Papel.AUXILIAR, criado_por=user)
        nomes = ", ".join(a.nome for a in auxiliares) or "nenhum"
        historico(ordem, user, f"Responsável: {agente.nome if agente else 'nenhum'}. Auxiliar(es): {nomes}.",
                  anterior, ordem.status)
        auditar(request, "ordem", ordem.pk, "ATRIBUIR", "agente", None, agente.pk if agente else None)
        for u in [agente, *auxiliares]:
            if u:
                notificar(u, "OS_ATRIBUIDA", f"{ordem.numero} atribuída para você", ordem.titulo, url_ordem(ordem),
                          f"os-atribuida-{ordem.pk}-{u.pk}-{int(timezone.now().timestamp())}")


# --------------------------------------------------------------------- status

def status_permitidos(ordem, user):
    if ordem.encerrada:
        return []
    if pode_gerenciar(user):
        return list(STATUS_GESTOR)
    if pode_executar(user, ordem):
        return list(STATUS_AGENTE)
    return []


def pendencias_encerramento(ordem):
    faltas = []
    tipos = set(ordem.anexos.values_list("tipo", flat=True))
    cat = ordem.categoria
    if cat.exige_foto_antes and "FOTO_ANTES" not in tipos:
        faltas.append("foto antes")
    if cat.exige_foto_durante and "FOTO_DURANTE" not in tipos:
        faltas.append("foto durante")
    if cat.exige_foto_depois and "FOTO_DEPOIS" not in tipos:
        faltas.append("foto depois")
    if ordem.checklist.filter(obrigatorio=True, concluido=False).exists():
        faltas.append("itens obrigatórios do checklist")
    return faltas


def alterar_status(ordem, user, novo, obs="", custo_materiais=None, request=None):
    permitidos = status_permitidos(ordem, user)
    if ordem.encerrada:
        raise RegraNegocio("Para reabrir uma ordem encerrada, use o token gerado pelo TI.")
    if novo not in permitidos:
        raise PermissionDenied("Você não pode aplicar este status.")
    obs = (obs or "").strip()
    if novo == Status.REPROVADA and not obs:
        raise RegraNegocio("A reprovação exige uma justificativa.")
    if novo == Status.ENCERRADA:
        faltas = pendencias_encerramento(ordem)
        if faltas:
            raise RegraNegocio("Não é possível encerrar: falta " + ", ".join(faltas) + ".")
    anterior = ordem.status
    agora = timezone.now()
    with transaction.atomic():
        ordem.status = novo
        if novo == Status.EM_ANDAMENTO and not ordem.data_inicio:
            ordem.data_inicio = agora
        if novo == Status.CONCLUIDA_PELO_AGENTE:
            ordem.data_conclusao = agora
        if novo == Status.ENCERRADA:
            ordem.data_conclusao = ordem.data_conclusao or agora
            ordem.data_encerramento = agora
            ordem.aprovado_por = user
            ordem.observacao_aprovacao = obs
            ordem.solicitacao_encerramento = False
        ordem.save()
        if novo == Status.ENCERRADA:
            _fechar_custos(ordem, user, custo_materiais)
        registrar_tempo_status(ordem, novo, user)
        texto = obs or "Status atualizado."
        if novo == Status.ENCERRADA:
            texto += f" | Custo de materiais: R$ {ordem.custo_materiais:.2f}".replace(".", ",")
        historico(ordem, user, texto, anterior, novo)
        auditar(request, "ordem", ordem.pk, "ALTERAR_STATUS", "status", anterior, novo)
        if novo == Status.ENCERRADA:
            calcular_qualidade(ordem)
            from conhecimento.services import gerar_artigo_da_ordem

            gerar_artigo_da_ordem(ordem, user)
        url = url_ordem(ordem)
        if novo == Status.CONCLUIDA_PELO_AGENTE:
            notificar(ordem.gestor, "APROVACAO_CONCLUSAO", "Conclusão aguardando aprovação",
                      f"{ordem.numero} — {ordem.titulo}", url, f"conclusao-{ordem.pk}-{int(agora.timestamp())}")
        elif novo == Status.REPROVADA and ordem.agente:
            notificar(ordem.agente, "OS_REPROVADA", "Conclusão reprovada", obs, url,
                      f"reprovada-{ordem.pk}-{int(agora.timestamp())}")
        elif ordem.agente and ordem.agente != user:
            notificar(ordem.agente, "OS_STATUS", f"{ordem.numero}: {ordem.get_status_display()}", texto, url,
                      f"status-{ordem.pk}-{int(agora.timestamp())}")


# --------------------------------------------------------------------- materiais e checklist

def _fechar_custos(ordem, user, custo_materiais=None):
    """No encerramento: consome as reservas pendentes e consolida os custos da ordem."""
    from estoque.models import Reserva
    from estoque.services import consumir_reserva, recalcular_custos

    for reserva in ordem.reservas.filter(status=Reserva.Status.RESERVADA):
        consumir_reserva(reserva, user)
    recalcular_custos(ordem)
    if custo_materiais is not None:
        ordem.custo_materiais = custo_materiais
        ordem.custo_total_final = custo_materiais + ordem.custo_mao_obra + ordem.custo_terceiros
        type(ordem).objects.filter(pk=ordem.pk).update(custo_materiais=ordem.custo_materiais,
                                                       custo_total_final=ordem.custo_total_final)


def registrar_material(ordem, user, nome, quantidade, unidade="", custo_unitario=0, observacao="", request=None,
                       material_estoque=None, reservar=False):
    if not (pode_gerenciar(user) or pode_executar(user, ordem)):
        raise PermissionDenied
    _bloquear_se_encerrada(ordem, user)
    from estoque.services import ErroEstoque, consumir_na_ordem, recalcular_custos

    with transaction.atomic():
        if material_estoque:
            mat = Material.objects.create(
                ordem=ordem, nome_material=material_estoque.nome[:150], quantidade=quantidade,
                unidade=material_estoque.unidade, custo_unitario=material_estoque.custo_unitario,
                observacao=observacao, usuario=user, material_estoque=material_estoque, situacao="CONSUMIDO")
            try:
                reserva = consumir_na_ordem(ordem, user, material_estoque, quantidade, reservar=reservar, item_os=mat,
                                            request=request)
            except ErroEstoque as exc:
                raise RegraNegocio(str(exc)) from exc
            situacao = {"APROVACAO_NEGATIVO": "aguardando aprovação (saldo insuficiente)",
                        "RESERVADA": "reservado"}.get(reserva.status, "baixado do estoque")
        else:
            mat = Material.objects.create(ordem=ordem, nome_material=nome, quantidade=quantidade, unidade=unidade,
                                          custo_unitario=custo_unitario or 0, observacao=observacao, usuario=user,
                                          situacao="AVULSO")
            recalcular_custos(ordem)
            situacao = "registrado"
        historico(ordem, user, f"Material {situacao}: {mat.nome_material} ({quantidade} {mat.unidade}).")
        auditar(request, "ordem", ordem.pk, "MATERIAL", "materiais", None, mat.nome_material)
    return mat


def atualizar_checklist(ordem, user, item, concluido, observacao="", request=None):
    if not (pode_gerenciar(user) or pode_executar(user, ordem)):
        raise PermissionDenied
    _bloquear_se_encerrada(ordem, user)
    if concluido and item.exige_foto and not ordem.anexos.filter(
        tipo__in=[Anexo.Tipo.FOTO_DEPOIS, Anexo.Tipo.FOTO_DURANTE, Anexo.Tipo.OUTRO]
    ).exists():
        raise RegraNegocio("Este item exige evidência fotográfica. Envie uma foto antes de concluí-lo.")
    item.concluido = concluido
    item.observacao = (observacao or "")[:255]
    item.concluido_por = user if concluido else None
    item.concluido_em = timezone.now() if concluido else None
    item.save()
    historico(ordem, user, f"Checklist: {item.descricao} — {'concluído' if concluido else 'reaberto'}.")
    auditar(request, "ordem", ordem.pk, "CHECKLIST", f"item_{item.pk}", not concluido, concluido)


# --------------------------------------------------------------------- reabertura com token

def solicitar_token(ordem, user, motivo, request=None):
    if user.is_ti:
        raise RegraNegocio("O TI pode gerar o token diretamente nesta ordem.")
    if not ordem.encerrada:
        raise RegraNegocio("O token só pode ser solicitado para ordem encerrada.")
    if not (user.pode("os.reabrir") and (pode_gerenciar(user) or ordem.participa(user))):
        raise PermissionDenied
    motivo = (motivo or "").strip() or "Solicitação de token para reabertura."
    if ordem.solicitacoes_reabertura.filter(estado=SolicitacaoReabertura.Estado.PENDENTE).exists():
        return None
    sol = SolicitacaoReabertura.objects.create(ordem=ordem, solicitado_por=user, motivo=motivo)
    historico(ordem, user, f"Solicitou token de reabertura ao TI: {motivo}")
    auditar(request, "ordem", ordem.pk, "SOLICITAR_TOKEN_REABERTURA", "", None, motivo)
    notificar_permissao("usuarios.gerenciar", "TOKEN_REABERTURA", f"Token de reabertura solicitado — {ordem.numero}",
                        motivo, url_ordem(ordem), f"token-sol-{sol.pk}")
    return sol


def gerar_token(ordem, user, request=None) -> str:
    if not user.is_ti:
        raise PermissionDenied
    if not ordem.encerrada:
        raise RegraNegocio("O token só pode ser gerado para ordem encerrada.")
    token = f"{secrets.randbelow(900000) + 100000}"
    agora = timezone.now()
    with transaction.atomic():
        ordem.tokens_reabertura.filter(usado_em__isnull=True).update(usado_em=agora, usado_por=user)
        sol = ordem.solicitacoes_reabertura.filter(estado=SolicitacaoReabertura.Estado.PENDENTE).first()
        TokenReabertura.objects.create(
            ordem=ordem, token_hash=TokenReabertura.calcular_hash(ordem.pk, token), gerado_por=user, solicitacao=sol,
            expira_em=agora + timedelta(hours=settings.GEOP_TOKEN_REABERTURA_HORAS),
        )
        if sol:
            sol.estado = SolicitacaoReabertura.Estado.ATENDIDA
            sol.atendido_por, sol.atendido_em = user, agora
            sol.save()
            notificar(sol.solicitado_por, "TOKEN_REABERTURA", f"Token gerado para {ordem.numero}",
                      "Solicite o token ao TI para reabrir a ordem.", url_ordem(ordem), f"token-ok-{sol.pk}")
        historico(ordem, user, f"TI gerou token de reabertura (validade: {settings.GEOP_TOKEN_REABERTURA_HORAS} h).")
        auditar(request, "ordem", ordem.pk, "GERAR_TOKEN_REABERTURA")
    return token


def reabrir_com_token(ordem, user, token, obs="", request=None):
    if not (pode_gerenciar(user) or pode_executar(user, ordem)):
        raise PermissionDenied("Você não tem permissão para reabrir esta ordem.")
    if not ordem.encerrada:
        raise RegraNegocio("Esta ordem não está encerrada.")
    token = "".join(ch for ch in (token or "") if ch.isdigit())
    if not token:
        raise RegraNegocio("Informe o token de reabertura.")
    with transaction.atomic():
        tk = (ordem.tokens_reabertura.select_for_update()
              .filter(usado_em__isnull=True, expira_em__gte=timezone.now()).order_by("-criado_em").first())
        valido = bool(tk) and tk.tentativas < settings.GEOP_TOKEN_REABERTURA_TENTATIVAS and hmac.compare_digest(
            tk.token_hash, TokenReabertura.calcular_hash(ordem.pk, token))
        if not valido:
            # A tentativa errada é gravada (o erro é levantado só após o commit).
            if tk:
                TokenReabertura.objects.filter(pk=tk.pk).update(tentativas=F("tentativas") + 1)
            auditar(request, "ordem", ordem.pk, "TOKEN_REABERTURA_INVALIDO")
        else:
            anterior = ordem.status
            ordem.status = Status.ATRIBUIDA if ordem.agente_id else Status.ABERTA
            ordem.data_encerramento = None
            ordem.observacao_aprovacao = ""
            ordem.reabertura_contagem += 1
            ordem.save()
            tk.usado_em, tk.usado_por = timezone.now(), user
            tk.save()
            registrar_tempo_status(ordem, ordem.status, user)
            texto = (obs or "").strip() or "Ordem reaberta com token autorizado pelo TI."
            historico(ordem, user, texto, anterior, ordem.status)
            auditar(request, "ordem", ordem.pk, "REABRIR", "status", anterior, ordem.status)
            notificar_permissao("os.gerenciar", "OS_REABERTA", f"{ordem.numero} reaberta", texto, url_ordem(ordem),
                                f"reaberta-{ordem.pk}-{ordem.reabertura_contagem}", excluir=user)
    if not valido:
        raise RegraNegocio("Token inválido, expirado ou já utilizado.")


# --------------------------------------------------------------------- exclusão (dupla aprovação)

def solicitar_exclusao(ordem, user, motivo, request=None):
    motivo = (motivo or "").strip()
    if not motivo:
        raise RegraNegocio("Informe o motivo da exclusão.")
    if user.is_ti:
        return excluir_direto(ordem, user, motivo, request)
    if not user.pode("os.excluir_solicitar"):
        raise PermissionDenied
    if ordem.solicitacoes_exclusao.filter(estado__in=["PENDENTE", "APROVADA"]).exists():
        raise RegraNegocio("Já existe uma solicitação de exclusão em andamento para esta ordem.")
    sol = SolicitacaoExclusao.objects.create(ordem=ordem, solicitado_por=user, motivo=motivo)
    historico(ordem, user, f"Solicitou a exclusão da ordem: {motivo}")
    auditar(request, "ordem", ordem.pk, "SOLICITAR_EXCLUSAO", "", None, motivo)
    notificar_permissao("os.excluir_analisar", "EXCLUSAO_SOLICITADA", f"Exclusão solicitada — {ordem.numero}", motivo,
                        reverse("ordens:exclusoes"), f"exclusao-{sol.pk}", excluir=user)
    return sol


def analisar_exclusao(sol, user, aprovar: bool, observacao="", request=None):
    if not user.pode("os.excluir_analisar"):
        raise PermissionDenied
    with transaction.atomic():
        sol = SolicitacaoExclusao.objects.select_for_update().get(pk=sol.pk)
        if sol.estado != SolicitacaoExclusao.Estado.PENDENTE:
            raise RegraNegocio("Esta solicitação já foi analisada.")
        if sol.solicitado_por_id == user.pk:
            raise RegraNegocio("O solicitante não pode analisar a própria solicitação.")
        sol.estado = SolicitacaoExclusao.Estado.APROVADA if aprovar else SolicitacaoExclusao.Estado.REPROVADA
        sol.analisado_por, sol.analisado_em, sol.observacao_analise = user, timezone.now(), observacao[:2000]
        sol.save()
        historico(sol.ordem, user, f"Solicitação de exclusão {sol.get_estado_display().lower()}. {observacao}".strip())
        auditar(request, "ordem", sol.ordem_id, "ANALISAR_EXCLUSAO", "estado", "PENDENTE", sol.estado)
        notificar(sol.solicitado_por, "EXCLUSAO_ANALISADA", f"Exclusão {sol.get_estado_display().lower()} — {sol.ordem.numero}",
                  observacao, reverse("ordens:exclusoes"), f"exclusao-analise-{sol.pk}")
        if aprovar:
            notificar_permissao("usuarios.gerenciar", "EXCLUSAO_APROVADA", f"Exclusão aprovada — {sol.ordem.numero}",
                                "Aguardando execução pelo TI.", reverse("ordens:exclusoes"), f"exclusao-exec-{sol.pk}")
    return sol


def executar_exclusao(sol, user, request=None):
    if not user.is_ti:
        raise PermissionDenied
    with transaction.atomic():
        sol = SolicitacaoExclusao.objects.select_for_update().get(pk=sol.pk)
        if sol.estado != SolicitacaoExclusao.Estado.APROVADA:
            raise RegraNegocio("Somente solicitações aprovadas podem ser executadas.")
        sol.estado, sol.executado_por, sol.executado_em = SolicitacaoExclusao.Estado.EXECUTADA, user, timezone.now()
        sol.save()
        _soft_delete(sol.ordem, user, f"Exclusão executada (solicitação #{sol.pk}).", request)


def excluir_direto(ordem, user, motivo, request=None):
    if not user.is_ti:
        raise PermissionDenied
    with transaction.atomic():
        sol = SolicitacaoExclusao.objects.create(
            ordem=ordem, solicitado_por=user, motivo=motivo, estado=SolicitacaoExclusao.Estado.EXECUTADA,
            analisado_por=user, analisado_em=timezone.now(), executado_por=user, executado_em=timezone.now(),
            observacao_analise="Exclusão direta pelo TI.",
        )
        _soft_delete(ordem, user, f"Ordem excluída pelo TI: {motivo}", request)
    return sol


def _soft_delete(ordem, user, texto, request):
    ordem.excluida_em, ordem.excluida_por = timezone.now(), user
    ordem.save(update_fields=["excluida_em", "excluida_por", "atualizado_em"])
    historico(ordem, user, texto)
    auditar(request, "ordem", ordem.pk, "EXCLUIR", "excluida_em", None, ordem.excluida_em)


def restaurar(ordem, user, request=None):
    if not user.is_ti:
        raise PermissionDenied
    ordem.excluida_em, ordem.excluida_por = None, None
    ordem.save(update_fields=["excluida_em", "excluida_por", "atualizado_em"])
    historico(ordem, user, "Ordem restaurada da lixeira.")
    auditar(request, "ordem", ordem.pk, "RESTAURAR")


# --------------------------------------------------------------------- link público

def publica_enviar_fotos(ordem, arquivos, descricao, request=None):
    if ordem.encerrada:
        raise RegraNegocio("Esta ordem já está encerrada e não aceita novas fotos.")
    with transaction.atomic():
        anexos = salvar_arquivos(ordem, None, arquivos, tipo=Anexo.Tipo.FOTO_DEPOIS,
                                 descricao=(descricao or "Foto enviada pelo link público.").strip()[:500],
                                 origem=Anexo.Origem.PUBLICO, somente_imagens=True)
        if not anexos:
            raise RegraNegocio("Selecione pelo menos uma foto.")
        historico(ordem, None, f"{len(anexos)} foto(s) enviada(s) pelo link público.")
    return anexos


def publica_solicitar_encerramento(ordem, obs):
    obs = (obs or "").strip()
    if ordem.encerrada:
        raise RegraNegocio("Esta ordem já está encerrada.")
    if not obs:
        raise RegraNegocio("Descreva o serviço realizado.")
    ordem.solicitacao_encerramento = True
    ordem.solicitacao_encerramento_em = timezone.now()
    ordem.solicitacao_encerramento_obs = obs[:5000]
    ordem.save(update_fields=["solicitacao_encerramento", "solicitacao_encerramento_em",
                              "solicitacao_encerramento_obs", "atualizado_em"])
    historico(ordem, None, f"Encerramento solicitado pelo link público: {obs}")
    notificar(ordem.gestor, "ENCERRAMENTO_SOLICITADO", f"Encerramento solicitado — {ordem.numero}", obs,
              url_ordem(ordem), f"enc-publico-{ordem.pk}-{int(timezone.now().timestamp())}")
