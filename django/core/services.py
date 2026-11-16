"""Auditoria e notificações (equivalentes a `auditoria_log` e `gestao_notificar` do PHP)."""
import logging

from django.db import IntegrityError, transaction

from .models import AuditoriaEvento, Notificacao

log = logging.getLogger(__name__)


def ip_do_request(request):
    if request is None:
        return None
    # REMOTE_ADDR já chega correto quando o proxy reverso é configurado (ver README).
    return request.META.get("REMOTE_ADDR") or None


def auditar(request, entidade, entidade_id, acao, campo="", antes=None, depois=None, usuario=None):
    user = usuario or (request.user if request is not None and request.user.is_authenticated else None)
    return AuditoriaEvento.objects.create(
        entidade=entidade,
        entidade_id=entidade_id,
        acao=acao,
        campo=campo or "",
        valor_anterior="" if antes is None else str(antes)[:5000],
        valor_novo="" if depois is None else str(depois)[:5000],
        usuario=user,
        ip=ip_do_request(request),
        user_agent=(request.META.get("HTTP_USER_AGENT", "")[:255] if request is not None else ""),
    )


def notificar(usuario, tipo, titulo, mensagem="", url="", chave=None):
    """Cria uma notificação; com `chave`, ignora silenciosamente duplicatas."""
    if usuario is None or not getattr(usuario, "is_active", False):
        return None
    try:
        with transaction.atomic():
            return Notificacao.objects.create(
                usuario=usuario, tipo=tipo, titulo=titulo[:180], mensagem=mensagem, url=url[:255], chave=chave
            )
    except IntegrityError:
        return None


def notificar_permissao(codigo, tipo, titulo, mensagem="", url="", chave_base=None, excluir=None):
    """Notifica todos os usuários ativos que possuem a permissão (TI incluso)."""
    from django.contrib.auth import get_user_model

    Usuario = get_user_model()
    for u in Usuario.objects.filter(is_active=True).select_related("perfil"):
        if excluir and u.pk == getattr(excluir, "pk", excluir):
            continue
        if u.pode(codigo):
            notificar(u, tipo, titulo, mensagem, url, f"{chave_base}-{u.pk}" if chave_base else None)
