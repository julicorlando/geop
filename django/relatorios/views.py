from datetime import date

from django.contrib import messages
from django.core.exceptions import PermissionDenied
from django.http import HttpResponse
from django.shortcuts import redirect, render
from django.urls import reverse
from django.utils import timezone

from core.permissions import permissao_requerida
from core.services import auditar

from . import services
from .forms import FiltroForm


def _filtros(request):
    form = FiltroForm(request.GET or None, user=request.user)
    dados = form.cleaned_data if form.is_valid() else {}
    return form, dados


def _gate_cota(request, tipo, dados):
    if services.registrar_emissao(request.user, tipo, dados):
        return None
    messages.warning(request, "Você atingiu o limite de emissões. Informe um token do TI para renovar a franquia.")
    return redirect(f"{reverse('relatorios:token')}?voltar={request.get_full_path()}")


@permissao_requerida("relatorios.visualizar")
def ordens(request):
    form, dados = _filtros(request)
    qs = services.filtrar(request.user, dados)
    exportar = request.GET.get("exportar")
    if exportar:
        if not request.user.pode("relatorios.exportar"):
            raise PermissionDenied
        bloqueio = _gate_cota(request, "CSV" if exportar == "csv" else "EXECUTIVO", dados)
        if bloqueio:
            return bloqueio
        auditar(request, "relatorio", None, "EMITIR", "tipo", None, exportar)
        if exportar == "csv":
            resp = HttpResponse(services.csv_ordens(qs), content_type="text/csv; charset=utf-8")
            resp["Content-Disposition"] = f'attachment; filename="ordens_{timezone.localdate():%Y%m%d}.csv"'
            return resp
        return render(request, "relatorios/executivo.html", {
            "k": services.indicadores(qs), "grupos": services.agrupamentos(qs), "dados": dados,
            "idade": services.idade_ativas(qs), "evolucao": services.evolucao_mensal(qs),
            "atrasadas": qs.atrasadas().order_by("prazo")[:30], "essenciais": qs.filter(services.ESSENCIAL)[:30],
            "com_fotos": request.GET.get("fotos") == "1",
            "ordens": qs.prefetch_related("anexos")[:60] if request.GET.get("fotos") == "1" else []})
    return render(request, "relatorios/ordens.html", {
        "form": form, "k": services.indicadores(qs), "grupos": services.agrupamentos(qs),
        "ranking": services.ranking_atraso(qs), "atrasadas": qs.atrasadas().order_by("prazo")[:20],
        "essenciais": qs.filter(services.ESSENCIAL).exclude(status="ENCERRADA")[:20],
        "ultimas": qs.order_by("-criado_em")[:20], "tempos": services.tempos_por_status(qs),
        "cota": services.status_cota(request.user), "params": request.GET.urlencode()})


@permissao_requerida("relatorios.financeiro")
def mensal(request):
    try:
        ano, mes = map(int, (request.GET.get("mes") or timezone.localdate().strftime("%Y-%m")).split("-"))
        date(ano, mes, 1)
    except ValueError:
        ano, mes = timezone.localdate().year, timezone.localdate().month
    return render(request, "relatorios/mensal.html", {**services.mensal(request.user, ano, mes),
                                                      "mes_iso": f"{ano:04d}-{mes:02d}"})


@permissao_requerida("relatorios.financeiro")
def financeiro(request):
    form, dados = _filtros(request)
    qs = services.filtrar(request.user, dados)
    from django.db.models import Count, Sum

    porcat = list(qs.order_by().values("categoria__nome").annotate(qtd=Count("id"), custo=Sum("custo_total_final"),
                                                                  previsto=Sum("valor_previsto_orcamento"))
                  .order_by("-custo")[:15])
    porlocal = list(qs.order_by().values("local__nome").annotate(qtd=Count("id"), custo=Sum("custo_total_final"))
                    .order_by("-custo")[:15])
    return render(request, "relatorios/financeiro.html", {
        "form": form, "custos": qs.aggregate(materiais=Sum("custo_materiais"), mao=Sum("custo_mao_obra"),
                                             terceiros=Sum("custo_terceiros"), total=Sum("custo_total_final"),
                                             previsto=Sum("valor_previsto_orcamento"), n=Count("id")),
        "categorias": porcat, "locais": porlocal, "top": qs.order_by("-custo_total_final")[:20],
        "evolucao": services.evolucao_mensal(qs)})


@permissao_requerida("relatorios.visualizar")
def reincidencias(request):
    try:
        dias = max(7, min(3650, int(request.GET.get("dias", 90))))
    except ValueError:
        dias = 90
    locais, patrimonios = services.reincidencias(request.user, dias)
    return render(request, "relatorios/reincidencias.html", {"locais": locais, "patrimonios": patrimonios, "dias": dias,
                                                             "opcoes": [30, 60, 90, 180, 365, 730]})


@permissao_requerida("relatorios.exportar", "usuarios.gerenciar")
def token(request):
    voltar = request.GET.get("voltar") or request.POST.get("voltar") or reverse("relatorios:ordens")
    if not voltar.startswith("/"):
        voltar = reverse("relatorios:ordens")
    gerado = None
    if request.method == "POST":
        if request.POST.get("acao") == "gerar":
            if not request.user.is_ti:
                raise PermissionDenied
            from accounts.models import Usuario

            dest = Usuario.objects.filter(pk=request.POST.get("destinatario") or None).first()
            gerado = services.gerar_token(request.user, dest)
            auditar(request, "relatorio_token", None, "GERAR", "destinatario", None, dest.pk if dest else "qualquer")
        elif services.usar_token(request.user, request.POST.get("token", "")):
            messages.success(request, f"Franquia renovada: {services.limite_cota()} emissões disponíveis.")
            return redirect(voltar)
        else:
            messages.error(request, "Token inválido, expirado ou já utilizado.")
    gestores = []
    if request.user.is_ti:
        from accounts.models import Usuario

        gestores = [u for u in Usuario.objects.filter(is_active=True).select_related("perfil") if services.sujeito_a_cota(u)]
    return render(request, "relatorios/token.html", {"voltar": voltar, "gerado": gerado, "gestores": gestores,
                                                     "cota": services.status_cota(request.user)})
