from django import template
from django.utils.html import format_html

from core.imaging import formatar_bytes

register = template.Library()

COR_STATUS = {
    "ABERTA": "info", "ATRIBUIDA": "primario", "EM_ANDAMENTO": "alerta", "PAUSADA": "neutro",
    "AGUARDANDO_MATERIAL": "neutro", "CONCLUIDA_PELO_AGENTE": "sucesso", "REPROVADA": "perigo", "ENCERRADA": "escuro",
    "AGUARDANDO_VALIDACAO": "alerta", "VALIDADA": "sucesso", "CORRECAO_SOLICITADA": "alerta", "CANCELADA": "neutro",
    "PENDENTE": "alerta", "APROVADA": "sucesso", "EXECUTADA": "escuro",
    "SOLICITADA": "alerta", "COMPRADA": "primario", "RECEBIDA": "sucesso",
    "RASCUNHO": "neutro", "PUBLICADO": "sucesso", "INATIVO": "neutro", "FINALIZADO": "escuro",
    "RESERVADA": "info", "CONSUMIDA": "sucesso", "APROVACAO_NEGATIVO": "alerta", "ATIVA": "sucesso",
    "PLANEJADO": "neutro", "CONCLUIDO": "sucesso", "CANCELADO": "neutro",
}
COR_PRIORIDADE = {"BAIXA": "sucesso", "MEDIA": "info", "ALTA": "alerta", "CRITICA": "perigo"}


@register.simple_tag
def badge_status(obj, campo="status"):
    valor = getattr(obj, campo)
    rotulo = getattr(obj, f"get_{campo}_display")()
    return format_html('<span class="badge badge-{}">{}</span>', COR_STATUS.get(valor, "neutro"), rotulo)


@register.simple_tag
def badge_prioridade(ordem):
    return format_html('<span class="badge badge-{} badge-contorno">{}</span>',
                       COR_PRIORIDADE.get(ordem.prioridade, "neutro"), ordem.get_prioridade_display())


@register.filter
def tamanho(n):
    return formatar_bytes(n)


@register.filter
def pode(user, codigo):
    return bool(user.is_authenticated and user.pode(codigo))


@register.filter
def get(dicionario, chave):
    return (dicionario or {}).get(chave)


@register.filter
def classe_widget(field):
    return field.field.widget.__class__.__name__


@register.filter
def rotulo_status(codigo):
    from ordens.models import Status

    try:
        return Status(codigo).label
    except ValueError:
        return (codigo or "—").replace("_", " ").capitalize()
