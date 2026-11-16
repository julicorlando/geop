"""Chat interno: conversas diretas e em grupo, anexos (fotos comprimidas), leitura, edição e exclusão."""
from datetime import timedelta

from django.contrib.auth import get_user_model
from django.core.exceptions import PermissionDenied, ValidationError
from django.db import transaction
from django.db.models import Max, Q
from django.http import Http404, JsonResponse
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from django.views.decorators.http import require_POST

from core.arquivos import responder_arquivo
from core.imaging import processar_upload
from core.models import Configuracao
from core.permissions import permissao_requerida

from .models import Conversa, Mensagem, Participante

EDICAO_MINUTOS = 15


def _habilitado():
    if not Configuracao.obter_bool("chat_habilitado"):
        raise Http404("O chat está desativado.")


def _conversa(user, pk):
    conv = get_object_or_404(Conversa, pk=pk)
    if not conv.participantes.filter(usuario=user).exists():
        raise PermissionDenied
    return conv


def conversa_direta(a, b):
    chave = "-".join(str(x) for x in sorted([a.pk, b.pk]))
    with transaction.atomic():
        conv, criada = Conversa.objects.get_or_create(chave_direta=chave, defaults={"tipo": Conversa.Tipo.DIRETA,
                                                                                    "criador": a})
        if criada:
            Participante.objects.bulk_create([Participante(conversa=conv, usuario=a), Participante(conversa=conv, usuario=b)])
    return conv


def _serializar(m, user):
    return {
        "id": m.pk, "autor": m.remetente.nome, "meu": m.remetente_id == user.pk,
        "texto": "" if m.excluida_em else m.texto, "excluida": bool(m.excluida_em), "editada": bool(m.editada_em),
        "quando": timezone.localtime(m.criado_em).strftime("%d/%m %H:%M"),
        "resposta": ({"autor": m.resposta_a.remetente.nome, "texto": (m.resposta_a.texto or "📎")[:80]}
                     if m.resposta_a_id and m.resposta_a else None),
        "anexo": (f"/chat/arquivo/{m.pk}/" if m.arquivo and not m.excluida_em else None),
        "miniatura": (f"/chat/arquivo/{m.pk}/?mini=1" if m.miniatura and not m.excluida_em else None),
        "nome_anexo": m.nome_original,
    }


def _titulo(conv, user):
    if conv.tipo == Conversa.Tipo.GRUPO:
        return conv.nome or "Grupo"
    outro = next((p.usuario for p in conv.participantes.all() if p.usuario_id != user.pk), None)
    return outro.nome if outro else "Conversa"


@permissao_requerida("chat.acessar")
def inicio(request, pk=None):
    _habilitado()
    user = request.user
    Usuario = get_user_model()
    if request.method == "POST":
        if request.POST.get("acao") == "grupo":
            ids = request.POST.getlist("membros")
            membros = [u for u in Usuario.objects.filter(pk__in=ids, is_active=True) if u.pode("chat.acessar")]
            nome = request.POST.get("nome", "").strip()[:120]
            if not nome or not membros:
                return redirect("chat:inicio")
            conv = Conversa.objects.create(tipo=Conversa.Tipo.GRUPO, nome=nome, criador=user)
            Participante.objects.bulk_create([Participante(conversa=conv, usuario=u) for u in {user, *membros}])
            return redirect("chat:conversa", pk=conv.pk)
        outro = get_object_or_404(Usuario, pk=request.POST.get("usuario"), is_active=True)
        if outro == user or not outro.pode("chat.acessar"):
            raise PermissionDenied
        return redirect("chat:conversa", pk=conversa_direta(user, outro).pk)
    conversa = _conversa(user, pk) if pk else None
    contatos = [u for u in Usuario.objects.filter(is_active=True).exclude(pk=user.pk).select_related("perfil")
                if u.pode("chat.acessar")]
    return render(request, "chat/inicio.html", {"conversa": conversa, "titulo": _titulo(conversa, user) if conversa else "",
                                                "contatos": contatos})


@permissao_requerida("chat.acessar")
def api_conversas(request):
    _habilitado()
    user = request.user
    convs = (Conversa.objects.filter(participantes__usuario=user).prefetch_related("participantes__usuario")
             .annotate(ultima=Max("mensagens__criado_em")).order_by("-atualizado_em")[:100])
    leituras = dict(Participante.objects.filter(usuario=user).values_list("conversa_id", "ultima_leitura"))
    dados = []
    for c in convs:
        ult = leituras.get(c.pk)
        nao_lidas = c.mensagens.exclude(remetente=user).filter(excluida_em__isnull=True)
        if ult:
            nao_lidas = nao_lidas.filter(criado_em__gt=ult)
        ultima = c.mensagens.order_by("-criado_em").first()
        dados.append({"id": c.pk, "titulo": _titulo(c, user), "grupo": c.tipo == Conversa.Tipo.GRUPO,
                      "nao_lidas": nao_lidas.count(),
                      "previa": ("Mensagem apagada" if ultima and ultima.excluida_em else (ultima.texto[:60] if ultima and ultima.texto else ("📎 anexo" if ultima else ""))),
                      "quando": timezone.localtime(c.atualizado_em).strftime("%d/%m %H:%M")})
    return JsonResponse({"conversas": dados, "total_nao_lidas": sum(d["nao_lidas"] for d in dados)})


@permissao_requerida("chat.acessar")
def api_mensagens(request, pk):
    _habilitado()
    conv = _conversa(request.user, pk)
    depois = int(request.GET.get("depois") or 0)
    qs = conv.mensagens.select_related("remetente", "resposta_a__remetente")
    if depois:
        qs = qs.filter(pk__gt=depois)
    else:
        qs = qs.order_by("-criado_em")[:80]
    msgs = sorted(qs, key=lambda m: (m.criado_em, m.pk))
    Participante.objects.filter(conversa=conv, usuario=request.user).update(ultima_leitura=timezone.now())
    # leitura dos outros (para o "✓✓")
    leituras = [p.ultima_leitura for p in conv.participantes.exclude(usuario=request.user) if p.ultima_leitura]
    visto_ate = min(leituras).isoformat() if leituras and len(leituras) == conv.participantes.count() - 1 else None
    alteradas = conv.mensagens.filter(Q(editada_em__gt=timezone.now() - timedelta(minutes=2))
                                      | Q(excluida_em__gt=timezone.now() - timedelta(minutes=2)))
    return JsonResponse({"mensagens": [_serializar(m, request.user) for m in msgs],
                         "alteradas": [_serializar(m, request.user) for m in alteradas],
                         "visto_ate": visto_ate})


@require_POST
@permissao_requerida("chat.acessar")
def api_enviar(request, pk):
    _habilitado()
    conv = _conversa(request.user, pk)
    texto = (request.POST.get("texto") or "").strip()[:4000]
    arquivo = request.FILES.get("arquivo")
    if not texto and not arquivo:
        return JsonResponse({"erro": "Mensagem vazia."}, status=400)
    msg = Mensagem(conversa=conv, remetente=request.user, texto=texto,
                   resposta_a=conv.mensagens.filter(pk=request.POST.get("resposta_a") or None).first())
    if arquivo:
        try:
            msg.aplicar(processar_upload(arquivo, prefixo=f"chat_{conv.pk}_"), arquivo.name)
        except ValidationError as exc:
            return JsonResponse({"erro": exc.messages[0]}, status=400)
    msg.save()
    Conversa.objects.filter(pk=conv.pk).update(atualizado_em=timezone.now())
    from core.services import notificar

    for p in conv.participantes.exclude(usuario=request.user).select_related("usuario"):
        notificar(p.usuario, "CHAT", f"Mensagem de {request.user.nome}", (texto or "📎 anexo")[:120], f"/chat/{conv.pk}/",
                  f"chat-{conv.pk}-{p.usuario_id}-{timezone.now():%Y%m%d%H}")
    return JsonResponse({"mensagem": _serializar(msg, request.user)})


@require_POST
@permissao_requerida("chat.acessar")
def api_alterar(request, pk):
    msg = get_object_or_404(Mensagem.objects.select_related("conversa"), pk=pk, remetente=request.user)
    if msg.excluida_em:
        return JsonResponse({"erro": "Mensagem já apagada."}, status=400)
    if request.POST.get("acao") == "apagar":
        msg.excluida_em = timezone.now()
        msg.save(update_fields=["excluida_em"])
        msg.remover_arquivos()
    else:
        if timezone.now() - msg.criado_em > timedelta(minutes=EDICAO_MINUTOS):
            return JsonResponse({"erro": f"Só é possível editar nos primeiros {EDICAO_MINUTOS} minutos."}, status=400)
        msg.texto = (request.POST.get("texto") or "").strip()[:4000] or msg.texto
        msg.editada_em = timezone.now()
        msg.save(update_fields=["texto", "editada_em"])
    return JsonResponse({"mensagem": _serializar(msg, request.user)})


@permissao_requerida("chat.acessar")
def arquivo(request, pk):
    msg = get_object_or_404(Mensagem, pk=pk, excluida_em__isnull=True)
    _conversa(request.user, msg.conversa_id)
    campo = msg.miniatura if request.GET.get("mini") and msg.miniatura else msg.arquivo
    return responder_arquivo(campo, nome_download=msg.nome_original)
