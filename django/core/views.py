from datetime import timedelta

from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.core.paginator import Paginator
from django.db.models import Count, Q
from django.http import Http404
from django.shortcuts import get_object_or_404, redirect, render
from django.urls import reverse
from django.utils import timezone
from django.utils.http import url_has_allowed_host_and_scheme
from django.views.decorators.http import require_POST

from accounts.models import Setor
from ordens.models import Ordem, SolicitacaoExclusao, SolicitacaoReabertura, Status

from .forms import CategoriaForm, LocalForm
from .models import AuditoriaEvento, Categoria, Local, Notificacao
from .permissions import permissao_requerida


@login_required
def dashboard(request):
    user = request.user
    contexto = {}
    if user.pode_algum("os.visualizar_todas", "os.executar", "os.criar", "os.gerenciar"):
        qs = Ordem.objects.visiveis_para(user)
        agora = timezone.now()
        abertas = qs.abertas()
        contexto.update({
            "total_abertas": abertas.count(),
            "atrasadas": qs.atrasadas().count(),
            "aguardando_aprovacao": qs.filter(status=Status.CONCLUIDA_PELO_AGENTE).count(),
            "encerradas_30d": qs.filter(status=Status.ENCERRADA, data_encerramento__gte=agora - timedelta(days=30)).count(),
            "por_status": [
                {"status": s, "rotulo": Status(s).label, "total": t}
                for s, t in abertas.values_list("status").annotate(t=Count("id")).order_by("-t")
            ],
            "por_prioridade": {p: t for p, t in abertas.values_list("prioridade").annotate(t=Count("id"))},
            "minhas": qs.filter(Q(agente=user) | Q(agentes__usuario=user)).abertas().distinct()
                       .select_related("local")[:8],
            "urgentes": abertas.filter(Q(prioridade="CRITICA") | Q(prazo__lt=agora))
                        .exclude(status=Status.CONCLUIDA_PELO_AGENTE).select_related("local", "agente")
                        .order_by("prazo")[:8],
            "encerramento_solicitado": qs.filter(solicitacao_encerramento=True).exclude(status=Status.ENCERRADA)[:8]
            if user.pode("os.gerenciar") else [],
        })
        maximo = max([i["total"] for i in contexto["por_status"]] or [1])
        for item in contexto["por_status"]:
            item["pct"] = round(item["total"] * 100 / maximo)
    if user.is_ti:
        contexto["tokens_pendentes"] = SolicitacaoReabertura.objects.filter(estado="PENDENTE").select_related("ordem")[:8]
        contexto["exclusoes_aprovadas"] = SolicitacaoExclusao.objects.filter(estado="APROVADA").count()
    if user.pode("os.excluir_analisar"):
        contexto["exclusoes_pendentes"] = SolicitacaoExclusao.objects.filter(estado="PENDENTE").exclude(
            solicitado_por=user).count()
    if user.pode("ambientes.validar"):
        from ambientes.models import Registro

        contexto["limpezas_pendentes"] = Registro.objects.filter(status=Registro.Status.AGUARDANDO_VALIDACAO).count()
    return render(request, "core/dashboard.html", contexto)


@login_required
def notificacoes(request):
    qs = request.user.notificacoes.all()
    if request.GET.get("filtro") == "nao_lidas":
        qs = qs.filter(lida_em__isnull=True)
    return render(request, "core/notificacoes.html", {"pagina": Paginator(qs, 30).get_page(request.GET.get("pagina"))})


@login_required
@require_POST
def notificacao_ler(request, pk=None):
    qs = request.user.notificacoes.filter(lida_em__isnull=True)
    if pk:
        n = get_object_or_404(Notificacao, pk=pk, usuario=request.user)
        qs.filter(pk=n.pk).update(lida_em=timezone.now())
        if n.url and url_has_allowed_host_and_scheme(n.url, allowed_hosts={request.get_host()}):
            return redirect(n.url)
    else:
        qs.update(lida_em=timezone.now())
    return redirect("core:notificacoes")


CADASTROS = {
    "locais": (Local, LocalForm, "Locais"),
    "categorias": (Categoria, CategoriaForm, "Categorias"),
}


@permissao_requerida("cadastros.gerenciar")
def cadastros(request, tipo="locais"):
    if tipo == "setores":
        itens = Setor.objects.annotate(n=Count("usuarios")).order_by("nome")
        return render(request, "core/cadastros.html", {"tipo": tipo, "titulo": "Setores", "itens": itens})
    if tipo not in CADASTROS:
        raise Http404
    modelo, _, titulo = CADASTROS[tipo]
    itens = modelo.objects.annotate(n=Count("ordens")).order_by("nome")
    return render(request, "core/cadastros.html", {"tipo": tipo, "titulo": titulo, "itens": itens})


@permissao_requerida("cadastros.gerenciar")
def cadastro_form(request, tipo, pk=None):
    if tipo == "setores":
        from django.forms import modelform_factory

        modelo, form_cls = Setor, modelform_factory(Setor, fields=["nome", "descricao", "ativo"])
    elif tipo in CADASTROS:
        modelo, form_cls, _ = CADASTROS[tipo]
    else:
        raise Http404
    obj = get_object_or_404(modelo, pk=pk) if pk else None
    form = form_cls(request.POST or None, instance=obj)
    if request.method == "POST" and form.is_valid():
        form.save()
        messages.success(request, "Cadastro salvo.")
        return redirect("core:cadastros", tipo=tipo)
    return render(request, "core/form_simples.html", {
        "form": form, "titulo": modelo._meta.verbose_name.capitalize(), "voltar_url": reverse("core:cadastros", args=[tipo])})


@permissao_requerida("auditoria.visualizar")
def auditoria(request):
    qs = AuditoriaEvento.objects.select_related("usuario")
    for campo in ("entidade", "acao"):
        if request.GET.get(campo):
            qs = qs.filter(**{campo: request.GET[campo]})
    if request.GET.get("entidade_id"):
        qs = qs.filter(entidade_id=request.GET["entidade_id"])
    return render(request, "core/auditoria.html", {"pagina": Paginator(qs, 50).get_page(request.GET.get("pagina"))})


# ------------------------------------------------------- compatibilidade com links antigos do PHP

def legado_os_publica(request):
    try:
        pk = int(request.GET.get("id", 0))
    except ValueError:
        raise Http404
    token = request.GET.get("t") or request.GET.get("token") or ""
    return redirect("ordens:publica", pk=pk, token=token.lower()[:64] or "x", permanent=True)


def legado_ambiente_publico(request):
    token = (request.GET.get("t") or "").lower()
    if not token:
        raise Http404
    return redirect("ambientes:publico", token=token[:64], permanent=True)


def legado_ordem_detalhe(request):
    try:
        return redirect("ordens:detalhe", pk=int(request.GET.get("id", 0)), permanent=True)
    except ValueError:
        raise Http404


def erro_403(request, exception=None):
    return render(request, "403.html", {"mensagem": str(exception or "")}, status=403)


def erro_404(request, exception=None):
    return render(request, "404.html", status=404)


def legado_credencial(request):
    from cftv.views import legado_credencial as v

    return v(request)


def legado_controle(request):
    from cftv.views import legado_controle as v

    return v(request)


@login_required
def notificacoes_api(request):
    from django.http import JsonResponse

    qs = request.user.notificacoes.filter(lida_em__isnull=True)
    return JsonResponse({"nao_lidas": qs.count(), "ultimas": [
        {"id": n.pk, "titulo": n.titulo, "mensagem": n.mensagem[:140], "url": n.url,
         "quando": timezone.localtime(n.criado_em).strftime("%d/%m %H:%M")} for n in qs[:5]]})


def manifest(request):
    from django.conf import settings
    from django.http import JsonResponse
    from django.templatetags.static import static

    return JsonResponse({
        "name": f"GEOP · {settings.GEOP_NOME_UNIDADE}", "short_name": "GEOP", "start_url": "/", "scope": "/",
        "display": "standalone", "background_color": "#0b2540", "theme_color": "#0b2540", "lang": "pt-BR",
        "icons": [{"src": static("icon.svg"), "sizes": "any", "type": "image/svg+xml", "purpose": "any maskable"}],
    }, content_type="application/manifest+json")


def service_worker(request):
    from django.http import HttpResponse

    js = """const CACHE='geop-v1';
self.addEventListener('install',e=>{self.skipWaiting();});
self.addEventListener('activate',e=>{e.waitUntil(caches.keys().then(ks=>Promise.all(ks.filter(k=>k!==CACHE).map(k=>caches.delete(k)))));self.clients.claim();});
self.addEventListener('fetch',e=>{
  const r=e.request; if(r.method!=='GET') return;
  const u=new URL(r.url); if(u.origin!==location.origin) return;
  if(u.pathname.startsWith('/static/')){e.respondWith(caches.open(CACHE).then(c=>c.match(r).then(m=>m||fetch(r).then(resp=>{c.put(r,resp.clone());return resp;}))));return;}
  if(r.mode==='navigate'){e.respondWith(fetch(r).catch(()=>new Response('<meta charset=utf-8><meta name=viewport content="width=device-width"><body style="font-family:system-ui;padding:2rem;text-align:center"><h1>Sem conexão</h1><p>O GEOP precisa de internet. Tente novamente em instantes.</p></body>',{headers:{'Content-Type':'text/html; charset=utf-8'}})));}
});"""
    return HttpResponse(js, content_type="application/javascript")


def legado_api_iot(request):
    from iot.views import api_leituras

    return api_leituras(request)


legado_api_iot.csrf_exempt = True


def legado_solicitar_acesso(request):
    return redirect("cftv:consultar")
