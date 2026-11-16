from datetime import timedelta

from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.core.exceptions import PermissionDenied
from django.db import transaction
from django.db.models import Count, Q
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from django.views.decorators.http import require_POST

from core.arquivos import responder_arquivo
from core.permissions import permissao_requerida
from core.services import auditar
from ordens.services import RegraNegocio

from . import services
from .forms import AcaoForm, AmbienteForm, RegistroForm, ValidacaoForm
from .models import Ambiente, AmbienteAcao, AmbienteAcaoVinculo, Registro, RegistroFoto, novo_token


@permissao_requerida("ambientes.visualizar", "ambientes.gerenciar")
def lista(request):
    ambientes = Ambiente.objects.select_related("local").annotate(
        pendentes=Count("registros", filter=Q(registros__status=Registro.Status.AGUARDANDO_VALIDACAO))).order_by("nome_publico")
    for a in ambientes:
        a.estado = a.estado_atual()
    return render(request, "ambientes/lista.html", {"ambientes": ambientes, "acoes": AmbienteAcao.objects.all()})


@permissao_requerida("ambientes.gerenciar")
def ambiente_form(request, pk=None):
    obj = get_object_or_404(Ambiente, pk=pk) if pk else None
    form = AmbienteForm(request.POST or None, instance=obj)
    if request.method == "POST" and form.is_valid():
        with transaction.atomic():
            amb = form.save(commit=False)
            if not amb.pk:
                amb.gerado_por = request.user
            amb.save()
            selecionadas = set(form.cleaned_data["acoes_selecionadas"]) | set(form.cleaned_data["acoes_obrigatorias"])
            obrigatorias = set(form.cleaned_data["acoes_obrigatorias"])
            AmbienteAcaoVinculo.objects.filter(ambiente=amb).exclude(acao__in=selecionadas).delete()
            for pos, acao in enumerate(sorted(selecionadas, key=lambda a: a.nome)):
                AmbienteAcaoVinculo.objects.update_or_create(
                    ambiente=amb, acao=acao, defaults={"obrigatoria": acao in obrigatorias, "posicao": pos, "ativo": True})
            auditar(request, "ambiente", amb.pk, "SALVAR", "", None, amb.nome_publico)
        messages.success(request, "Ambiente salvo.")
        return redirect("ambientes:lista")
    return render(request, "core/form_simples.html", {"form": form, "titulo": "Ambiente QR", "voltar": "ambientes:lista"})


@permissao_requerida("ambientes.gerenciar")
def acao_form(request, pk=None):
    obj = get_object_or_404(AmbienteAcao, pk=pk) if pk else None
    form = AcaoForm(request.POST or None, instance=obj)
    if request.method == "POST" and form.is_valid():
        form.save()
        messages.success(request, "Ação salva.")
        return redirect("ambientes:lista")
    return render(request, "core/form_simples.html", {"form": form, "titulo": "Ação de limpeza", "voltar": "ambientes:lista"})


@permissao_requerida("ambientes.gerar_qr")
@require_POST
def regenerar_token(request, pk):
    amb = get_object_or_404(Ambiente, pk=pk)
    amb.token_publico = novo_token()
    amb.save(update_fields=["token_publico", "atualizado_em"])
    auditar(request, "ambiente", amb.pk, "REGENERAR_QR")
    messages.success(request, "Novo QR gerado. Imprima e substitua a etiqueta antiga — ela deixou de funcionar.")
    return redirect("ambientes:lista")


@permissao_requerida("ambientes.gerar_qr")
def imprimir_qr(request, pk):
    amb = get_object_or_404(Ambiente.objects.select_related("local"), pk=pk)
    url = request.build_absolute_uri(amb.get_absolute_url())
    return render(request, "ambientes/qr_imprimir.html", {"ambiente": amb, "url": url})


def publico(request, token):
    amb = get_object_or_404(Ambiente.objects.select_related("local"), token_publico=token, ativo=True)
    services.registrar_acesso(amb, request)
    registros = []
    if amb.exibir_historico:
        registros = (amb.registros.filter(status=Registro.Status.VALIDADA)
                     .prefetch_related("responsaveis", "itens", "fotos")[: amb.historico_limite])
    user = request.user
    return render(request, "ambientes/publico.html", {
        "ambiente": amb, "estado": amb.estado_atual(), "registros": registros,
        "pode_registrar": user.is_authenticated and user.pode("ambientes.registrar"),
        "pode_validar": user.is_authenticated and user.pode("ambientes.validar"),
        "pendentes": amb.registros.filter(status=Registro.Status.AGUARDANDO_VALIDACAO).count()
        if user.is_authenticated else 0,
    })


@permissao_requerida("ambientes.registrar")
def registro(request, token):
    amb = get_object_or_404(Ambiente.objects.select_related("local"), token_publico=token, ativo=True)
    ordens = services.ordens_abertas_do_local(amb.local)
    form = RegistroForm(request.POST or None, request.FILES or None, ambiente=amb, ordens=ordens)
    if request.method == "POST" and form.is_valid():
        try:
            services.criar_registro(amb, request.user, form.cleaned_data, request.FILES.getlist("fotos"), request)
        except RegraNegocio as exc:
            form.add_error(None, str(exc))
        else:
            messages.success(request, "Registro enviado para validação. Obrigado!")
            return redirect("ambientes:publico", token=token)
    recentes = amb.registros.select_related("asg", "ordem").prefetch_related("responsaveis")[:8]
    return render(request, "ambientes/registro.html", {"ambiente": amb, "form": form, "recentes": recentes,
                                                       "vinculos": amb.acoes_ativas()})


@permissao_requerida("ambientes.validar")
def pendentes(request):
    qs = (Registro.objects.filter(status=Registro.Status.AGUARDANDO_VALIDACAO)
          .select_related("ambiente", "asg", "ordem").prefetch_related("responsaveis")
          .annotate(n_fotos=Count("fotos")).order_by("finalizado_em"))
    return render(request, "ambientes/pendentes.html", {"registros": qs})


@permissao_requerida("ambientes.validar")
def validar(request, pk):
    reg = get_object_or_404(Registro.objects.select_related("ambiente", "asg", "ordem", "supervisor"), pk=pk)
    form = ValidacaoForm(request.POST or None, request.FILES or None)
    if request.method == "POST" and form.is_valid():
        d = form.cleaned_data
        try:
            services.validar(reg, request.user, d["resultado"], d["observacao"], request.FILES.getlist("fotos"),
                             d["publicar_fotos"], request)
        except RegraNegocio as exc:
            form.add_error(None, str(exc))
        else:
            messages.success(request, "Validação registrada.")
            return redirect("ambientes:pendentes")
    return render(request, "ambientes/validar.html", {
        "registro": reg, "form": form, "fotos": reg.fotos.all(), "itens": reg.itens.all(),
        "responsaveis": reg.responsaveis.all(), "validacoes": reg.validacoes.select_related("supervisor"),
    })


@permissao_requerida("ambientes.relatorios")
def relatorios(request):
    try:
        dias = max(1, min(366, int(request.GET.get("dias", 30))))
    except ValueError:
        dias = 30
    desde = timezone.now() - timedelta(days=dias)
    filtro = Q(registros__finalizado_em__gte=desde)
    linhas = Ambiente.objects.select_related("local").annotate(
        total=Count("registros", filter=filtro),
        validadas=Count("registros", filter=filtro & Q(registros__status=Registro.Status.VALIDADA)),
        reprovadas=Count("registros", filter=filtro & Q(registros__status__in=["REPROVADA", "CORRECAO_SOLICITADA"])),
        pendentes=Count("registros", filter=filtro & Q(registros__status=Registro.Status.AGUARDANDO_VALIDACAO)),
    ).order_by("nome_publico")
    return render(request, "ambientes/relatorios.html", {"linhas": linhas, "dias": dias})


@login_required
def foto(request, pk, miniatura=False):
    f = get_object_or_404(RegistroFoto.objects.select_related("registro"), pk=pk)
    if not request.user.pode_algum("ambientes.visualizar", "ambientes.validar") and f.registro.asg_id != request.user.pk:
        raise PermissionDenied
    return responder_arquivo(f.miniatura if miniatura and f.miniatura else f.arquivo)


def foto_publica(request, token, pk, miniatura=False):
    f = get_object_or_404(RegistroFoto.objects.select_related("registro__ambiente"), pk=pk, publica=True,
                          registro__status=Registro.Status.VALIDADA, registro__ambiente__token_publico=token,
                          registro__ambiente__exibir_fotos=True, registro__ambiente__ativo=True)
    return responder_arquivo(f.miniatura if miniatura and f.miniatura else f.arquivo)
