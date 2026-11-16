"""CFTV: plantões, liberações e controle de acesso (includes/cftv.php, cftv.php, controle_acesso.php…)."""
import hashlib
import hmac
import secrets
from datetime import datetime, time, timedelta

from django.conf import settings
from django.core.exceptions import PermissionDenied
from django.core.mail import send_mail
from django.db import transaction
from django.urls import reverse
from django.utils import timezone

from core.services import auditar, notificar, notificar_permissao

from .models import (EventoAcesso, Liberacao, LinkSolicitacao, PessoaAdicional, RelatorioPlantao, SolicitacaoAcesso,
                     TokenEdicao, cpf_valido, formatar_cpf, normalizar_cpf)


class ErroCFTV(Exception):
    pass


# --------------------------------------------------------------------- plantões

def pode_ver_plantao(user, rel):
    return user.is_ti or rel.operador_id == user.pk or user.pode("cftv.historico")


def pode_editar_plantao(user, rel):
    if user.is_ti:
        return True
    if rel.operador_id != user.pk:
        return False
    if rel.tokens.filter(usado_por=user, liberado_ate__gt=timezone.now()).exists():
        return True
    return rel.status != RelatorioPlantao.Status.FINALIZADO and rel.data_plantao == timezone.localdate()


def prazo_fechamento(rel):
    """Diurno fecha às 17:10 do dia; noturno às 07:10 do dia seguinte."""
    if rel.turno == RelatorioPlantao.Turno.DIURNO:
        return timezone.make_aware(datetime.combine(rel.data_plantao, time(17, 10)))
    return timezone.make_aware(datetime.combine(rel.data_plantao + timedelta(days=1), time(7, 10)))


def fechar_plantoes_vencidos():
    n = 0
    for rel in RelatorioPlantao.objects.exclude(status=RelatorioPlantao.Status.FINALIZADO).filter(
            data_plantao__lte=timezone.localdate()):
        if timezone.now() >= prazo_fechamento(rel):
            rel.status, rel.finalizado_em = RelatorioPlantao.Status.FINALIZADO, timezone.now()
            rel.save(update_fields=["status", "finalizado_em"])
            n += 1
    return n


def criar_plantao(user, data, turno):
    if not user.pode("cftv.operar"):
        raise PermissionDenied
    rel, criado = RelatorioPlantao.objects.get_or_create(
        operador=user, data_plantao=data, turno=turno,
        defaults={"inicio_turno": timezone.localtime().time().replace(second=0, microsecond=0),
                  "areas_prioritarias": "\n".join(AREAS_PRIORITARIAS)})
    return rel, criado


AREAS_PRIORITARIAS = ["Entradas e saídas", "Praça de alimentação", "Estacionamento", "Docas", "Casa de máquinas",
                      "Corredores técnicos", "Administração", "Caixa eletrônico", "Escadas de emergência",
                      "Acessos de funcionários", "Central de resíduos", "Áreas externas"]


def finalizar_plantao(rel, user):
    if not pode_editar_plantao(user, rel):
        raise PermissionDenied
    rel.status, rel.finalizado_em = RelatorioPlantao.Status.FINALIZADO, timezone.now()
    rel.fim_turno = rel.fim_turno or timezone.localtime().time().replace(second=0, microsecond=0)
    rel.save()


def gerar_token_edicao(rel, ti, motivo=""):
    if not ti.is_ti:
        raise PermissionDenied
    token = f"{secrets.randbelow(900000) + 100000}"
    rel.tokens.filter(usado_em__isnull=True).update(expira_em=timezone.now())
    TokenEdicao.objects.create(relatorio=rel, token_hash=TokenEdicao.calcular_hash(rel.pk, token), motivo=motivo,
                               gerado_por=ti, expira_em=timezone.now() + timedelta(hours=24))
    notificar(rel.operador, "CFTV_TOKEN", f"Edição liberada — {rel}", "Peça o código ao TI para reabrir o relatório.",
              rel.get_absolute_url(), f"cftv-token-{rel.pk}-{int(timezone.now().timestamp())}")
    return token


def usar_token_edicao(rel, user, token):
    tk = rel.tokens.filter(usado_em__isnull=True, expira_em__gt=timezone.now()).order_by("-criado_em").first()
    token = "".join(c for c in (token or "") if c.isdigit())
    if not tk or not hmac.compare_digest(tk.token_hash, TokenEdicao.calcular_hash(rel.pk, token)):
        raise ErroCFTV("Código inválido ou expirado.")
    tk.usado_por, tk.usado_em, tk.liberado_ate = user, timezone.now(), timezone.now() + timedelta(hours=24)
    tk.save()


def liberacoes_do_dia(dia=None):
    dia = dia or timezone.localdate()
    return [lib for lib in Liberacao.objects.filter(status=Liberacao.Status.ATIVA, data_acesso__lte=dia)
            .order_by("hora_inicio", "nome_completo") if lib.vale_em(dia)]


# --------------------------------------------------------------------- solicitações públicas

def _protocolo():
    hoje = timezone.localdate()
    while True:
        p = f"AC-{hoje:%Y%m%d}-{secrets.token_hex(2).upper()}"
        if not SolicitacaoAcesso.objects.filter(protocolo=p).exists():
            return p


def hash_ip(ip):
    return hashlib.sha256(f"{settings.SECRET_KEY}|ip|{ip}".encode()).hexdigest()


def solicitar(link, dados, pessoas, ip=""):
    if not link.valido:
        raise ErroCFTV("Este link de solicitação expirou ou já foi utilizado.")
    if not cpf_valido(dados["cpf"]):
        raise ErroCFTV("CPF inválido.")
    fim = dados.get("data_fim") or dados["data_acesso"]
    if dados["data_acesso"] < timezone.localdate():
        raise ErroCFTV("A data de acesso não pode estar no passado.")
    if fim < dados["data_acesso"]:
        raise ErroCFTV("A data final não pode ser anterior à inicial.")
    if (fim - dados["data_acesso"]).days > 90:
        raise ErroCFTV("O período máximo de uma liberação é de 90 dias.")
    with transaction.atomic():
        link = LinkSolicitacao.objects.select_for_update().get(pk=link.pk)
        if not link.valido:
            raise ErroCFTV("Este link de solicitação expirou ou já foi utilizado.")
        sol = SolicitacaoAcesso.objects.create(
            protocolo=_protocolo(), link=link, ip_hash=hash_ip(ip),
            **{**dados, "cpf": normalizar_cpf(dados["cpf"]), "data_fim": fim if fim != dados["data_acesso"] else None})
        for p in pessoas:
            if p.get("nome") and cpf_valido(p.get("cpf")):
                PessoaAdicional.objects.create(solicitacao=sol, nome_completo=p["nome"][:180], cpf=normalizar_cpf(p["cpf"]))
        link.usos += 1
        link.save(update_fields=["usos"])
    notificar_permissao("cftv.liberacoes", "ACESSO_SOLICITADO", f"Solicitação de acesso {sol.protocolo}",
                        f"{sol.nome_completo} — {sol.destino_nome} em {sol.data_acesso:%d/%m}",
                        reverse("cftv:liberacoes"), f"acesso-sol-{sol.pk}")
    return sol


def _email(sol, assunto, mensagem):
    if not sol.email:
        return
    try:
        send_mail(assunto, mensagem, None, [sol.email], fail_silently=True)
    except Exception:  # e-mail nunca bloqueia a decisão
        pass


def decidir(sol, user, aprovar: bool, justificativa="", request=None, url_acompanhamento=""):
    if not user.pode("cftv.liberacoes"):
        raise PermissionDenied
    justificativa = (justificativa or "").strip()
    if not aprovar and not justificativa:
        raise ErroCFTV("Informe a justificativa da reprovação.")
    with transaction.atomic():
        sol = SolicitacaoAcesso.objects.select_for_update().get(pk=sol.pk)
        if sol.status != SolicitacaoAcesso.Status.PENDENTE:
            raise ErroCFTV("Esta solicitação já foi analisada.")
        if aprovar:
            extras = [f"Empresa: {sol.empresa}" if sol.empresa else "", f"Telefone: {sol.telefone}" if sol.telefone else "",
                      f"Responsável: {sol.responsavel_interno}" if sol.responsavel_interno else "",
                      f"Placa: {sol.veiculo_placa}" if sol.veiculo_placa else ""]
            extras += [f"Acompanhante: {p.nome_completo} — CPF {formatar_cpf(p.cpf)}" for p in sol.pessoas.all()]
            sol.liberacao = Liberacao.objects.create(
                data_acesso=sol.data_acesso, data_fim=sol.data_fim, hora_inicio=sol.hora_inicio, hora_fim=sol.hora_fim,
                nome_completo=sol.nome_completo, cpf=sol.cpf, email=sol.email, destino_tipo=sol.destino_tipo,
                destino_nome=sol.destino_nome, motivo=sol.motivo,
                observacoes="\n".join(filter(None, extras + [sol.observacoes])), criado_por=user)
        sol.status = SolicitacaoAcesso.Status.APROVADA if aprovar else SolicitacaoAcesso.Status.REPROVADA
        sol.decisao_justificativa = justificativa or ("Solicitação aprovada." if aprovar else "")
        sol.decidido_por, sol.decidido_em = user, timezone.now()
        sol.save()
        auditar(request, "cftv_solicitacao", sol.pk, sol.status, "status", "PENDENTE", sol.status)
    if aprovar:
        notificar_permissao("controle_acesso.registrar", "ACESSO_APROVADO", "Liberação aprovada",
                            f"{sol.protocolo} — {sol.nome_completo} — {sol.destino_nome}", reverse("cftv:controle"),
                            f"acesso-aprov-{sol.pk}")
        _email(sol, f"Acesso aprovado — {sol.protocolo}",
               f"Olá, {sol.nome_completo}.\n\nSeu acesso a {sol.destino_nome} em {sol.data_acesso:%d/%m/%Y} foi aprovado."
               f"\nApresente o QR Code na portaria: {url_acompanhamento}\n\n{settings.GEOP_NOME_UNIDADE}")
    else:
        _email(sol, f"Acesso não aprovado — {sol.protocolo}",
               f"Olá, {sol.nome_completo}.\n\nSua solicitação não foi aprovada.\nMotivo: {justificativa}\n\n"
               f"{settings.GEOP_NOME_UNIDADE}")
    return sol


# --------------------------------------------------------------------- portaria (entrada e saída)

def registrar_evento(sol, user, tipo=None, observacao="", request=None, agora=None):
    """Valida a credencial e registra ENTRADA/SAÍDA (regras de controle_acesso.php).

    Sem `tipo`, decide sozinho: a primeira leitura do dia é a entrada e a seguinte, a saída.
    Retorna (evento, mensagem). Evento None = leitura repetida em menos de 2 minutos.
    """
    if not user.pode("controle_acesso.registrar"):
        raise PermissionDenied
    agora = agora or timezone.now()
    hoje = timezone.localdate(agora)
    with transaction.atomic():
        sol = SolicitacaoAcesso.objects.select_for_update().get(pk=sol.pk)
        if sol.status != SolicitacaoAcesso.Status.APROVADA:
            raise ErroCFTV("A solicitação não está aprovada.")
        if sol.liberacao and sol.liberacao.status == Liberacao.Status.CANCELADA:
            raise ErroCFTV("Esta liberação foi cancelada.")
        if hoje < sol.data_acesso:
            raise ErroCFTV("A credencial ainda não está válida: o período autorizado não começou.")
        if hoje > sol.fim:
            raise ErroCFTV("Credencial expirada: o período autorizado terminou.")
        ultimo = sol.eventos.filter(registrado_em__date=hoje).order_by("-registrado_em", "-id").first()
        inicio = timezone.make_aware(datetime.combine(hoje, sol.hora_inicio or time.min))
        fim = timezone.make_aware(datetime.combine(hoje, sol.hora_fim or time(23, 59, 59)))
        if fim <= inicio:
            fim += timedelta(days=1)
        antecedencia = timedelta(0) if sol.eh_periodo else timedelta(hours=1)
        tipo = tipo or (EventoAcesso.Tipo.SAIDA if ultimo else EventoAcesso.Tipo.ENTRADA)
        if tipo == EventoAcesso.Tipo.ENTRADA:
            if ultimo:
                if ultimo.tipo == EventoAcesso.Tipo.ENTRADA and agora - ultimo.registrado_em < timedelta(minutes=2):
                    return None, f"Entrada já registrada às {timezone.localtime(ultimo.registrado_em):%H:%M}."
                raise ErroCFTV("A entrada de hoje já foi registrada." if ultimo.tipo == "ENTRADA"
                               else "O acesso de hoje já foi finalizado.")
            if agora < inicio - antecedencia:
                raise ErroCFTV("Entrada ainda não liberada. " + ("Respeite o horário inicial autorizado." if sol.eh_periodo
                               else "É permitida até uma hora antes do horário previsto."))
            if agora > fim:
                raise ErroCFTV("A faixa de horário autorizada para hoje terminou.")
        else:
            if not ultimo or ultimo.tipo != EventoAcesso.Tipo.ENTRADA:
                raise ErroCFTV("Registre a entrada antes da saída." if not ultimo else "O acesso de hoje já foi finalizado.")
            if agora - ultimo.registrado_em < timedelta(minutes=2):
                return None, f"Entrada registrada às {timezone.localtime(ultimo.registrado_em):%H:%M} (leitura repetida)."
        evento = EventoAcesso.objects.create(solicitacao=sol, tipo=tipo, registrado_por=user, registrado_em=agora,
                                             observacao=(observacao or "")[:500])
        auditar(request, "cftv_acesso", sol.pk, f"REGISTRAR_{tipo}", "", None, sol.protocolo)
    return evento, f"{evento.get_tipo_display()} registrada às {timezone.localtime(agora):%H:%M}."
