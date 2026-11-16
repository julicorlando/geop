from datetime import timedelta

from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.core.exceptions import PermissionDenied
from django.core.paginator import Paginator
from django.db.models import Count, Q
from django.http import Http404
from django.shortcuts import get_object_or_404, redirect, render
from django.urls import reverse
from django.utils import timezone
from django.views.decorators.cache import never_cache

from core.permissions import permissao_requerida
from core.services import auditar

from . import services
from .forms import LiberacaoForm, ObservacaoForm, OcorrenciaForm, PlantaoForm, SolicitacaoPublicaForm
from .models import (ConfirmacaoLiberacao, Destino, EventoAcesso, Liberacao, LinkSolicitacao, Observacao, Ocorrencia,
                     RelatorioPlantao, SolicitacaoAcesso, mascarar_cpf, normalizar_cpf)
from .services import ErroCFTV


# --------------------------------------------------------------------- plantões

@permissao_requerida("cftv.operar", "cftv.historico")
def painel(request):
    user = request.user
    if request.method == "POST" and request.POST.get("acao") == "criar":
        turno = request.POST.get("turno") if request.POST.get("turno") in RelatorioPlantao.Turno.values else "DIURNO"
        rel, criado = services.criar_plantao(user, timezone.localdate(), turno)
        messages.success(request, "Relatório de plantão aberto." if criado else "Você já tem um relatório para este turno.")
        return redirect(rel)
    qs = RelatorioPlantao.objects.select_related("operador").annotate(n_ocorrencias=Count("ocorrencias")).order_by("-data_plantao", "-id")
    if not (user.is_ti or user.pode("cftv.historico")):
        qs = qs.filter(operador=user)
    return render(request, "cftv/painel.html", {
        "relatorios": Paginator(qs, 20).get_page(request.GET.get("pagina")), "turnos": RelatorioPlantao.Turno.choices,
        "liberacoes_hoje": services.liberacoes_do_dia(), "pode_operar": user.pode("cftv.operar")})


@permissao_requerida("cftv.operar", "cftv.historico")
def relatorio(request, pk):
    rel = get_object_or_404(RelatorioPlantao.objects.select_related("operador"), pk=pk)
    user = request.user
    if not services.pode_ver_plantao(user, rel):
        raise PermissionDenied
    editar = services.pode_editar_plantao(user, rel)
    if request.method == "POST":
        acao = request.POST.get("acao")
        try:
            if acao == "gerar_token":
                token = services.gerar_token_edicao(rel, user, request.POST.get("motivo", ""))
                messages.success(request, f"Código de edição: {token} (válido por 24 h). Informe ao operador.")
            elif acao == "usar_token":
                if rel.operador_id != user.pk:
                    raise PermissionDenied
                services.usar_token_edicao(rel, user, request.POST.get("token", ""))
                messages.success(request, "Relatório liberado para edição por 24 h.")
            elif not editar:
                raise ErroCFTV("Este relatório não pode mais ser editado. Peça um código de edição ao TI.")
            elif acao == "salvar":
                form = PlantaoForm(request.POST, instance=rel)
                if form.is_valid():
                    form.save()
                    messages.success(request, "Relatório salvo.")
                else:
                    raise ErroCFTV("Revise os campos do relatório.")
            elif acao == "ocorrencia":
                form = OcorrenciaForm(request.POST)
                if not form.is_valid():
                    raise ErroCFTV("Revise a ocorrência.")
                oc = form.save(commit=False)
                oc.relatorio = rel
                oc.save()
                if oc.nivel == "NIVEL_3":
                    from core.services import notificar_permissao

                    notificar_permissao("cftv.historico", "CFTV_CRITICA", f"Ocorrência crítica — {rel}",
                                        f"{oc.tipo} {oc.local}: {oc.descricao[:150]}", rel.get_absolute_url(),
                                        f"cftv-crit-{oc.pk}", excluir=user)
                messages.success(request, "Ocorrência registrada.")
            elif acao == "observacao":
                form = ObservacaoForm(request.POST)
                if not form.is_valid():
                    raise ErroCFTV("Revise a observação.")
                ob = form.save(commit=False)
                ob.relatorio = rel
                ob.save()
                messages.success(request, "Observação registrada.")
            elif acao == "excluir_ocorrencia":
                Ocorrencia.objects.filter(pk=request.POST.get("id"), relatorio=rel).delete()
            elif acao == "excluir_observacao":
                Observacao.objects.filter(pk=request.POST.get("id"), relatorio=rel).delete()
            elif acao == "confirmar_liberacao":
                lib = get_object_or_404(Liberacao, pk=request.POST.get("liberacao_id"))
                status = request.POST.get("status_confirmacao")
                if status not in dict(ConfirmacaoLiberacao.STATUS):
                    raise ErroCFTV("Situação inválida.")
                ConfirmacaoLiberacao.objects.update_or_create(relatorio=rel, liberacao=lib, defaults={
                    "status_confirmacao": status, "horario_confirmacao": timezone.now(), "confirmado_por": user,
                    "alteracao_observada": request.POST.get("alteracao", "")[:2000]})
                messages.success(request, "Liberação conferida.")
            elif acao == "finalizar":
                services.finalizar_plantao(rel, user)
                messages.success(request, "Relatório finalizado.")
        except (ErroCFTV, PermissionDenied) as exc:
            messages.error(request, str(exc) or "Sem permissão.")
        return redirect(rel)
    confirmadas = {c.liberacao_id: c for c in rel.confirmacoes.all()}
    libs = [(lib, confirmadas.get(lib.pk)) for lib in services.liberacoes_do_dia(rel.data_plantao)]
    return render(request, "cftv/relatorio.html", {
        "rel": rel, "editar": editar, "form": PlantaoForm(instance=rel), "oc_form": OcorrenciaForm(),
        "ob_form": ObservacaoForm(), "ocorrencias": rel.ocorrencias.all(), "observacoes": rel.observacoes.all(),
        "liberacoes": libs, "status_conf": ConfirmacaoLiberacao.STATUS, "prazo": services.prazo_fechamento(rel)})


# --------------------------------------------------------------------- liberações e solicitações

@permissao_requerida("cftv.liberacoes")
def liberacoes(request):
    user = request.user
    form = LiberacaoForm(request.POST or None, initial={"data_acesso": timezone.localdate()})
    if request.method == "POST":
        acao = request.POST.get("acao")
        try:
            if acao == "salvar":
                if not form.is_valid():
                    raise ErroCFTV("Revise a liberação: " + "; ".join(e for es in form.errors.values() for e in es))
                lib = form.save(commit=False)
                lib.criado_por = user
                lib.save()
                auditar(request, "cftv_liberacao", lib.pk, "CRIAR", "", None, lib.nome_completo)
                messages.success(request, "Liberação criada.")
            elif acao == "cancelar":
                lib = get_object_or_404(Liberacao, pk=request.POST.get("id"))
                lib.status, lib.cancelado_por, lib.cancelado_em = Liberacao.Status.CANCELADA, user, timezone.now()
                lib.save()
                messages.success(request, "Liberação cancelada.")
            elif acao in ("aprovar", "reprovar"):
                sol = get_object_or_404(SolicitacaoAcesso, pk=request.POST.get("id"))
                services.decidir(sol, user, acao == "aprovar", request.POST.get("justificativa", ""), request,
                                 request.build_absolute_uri(reverse("cftv:acompanhar", args=[sol.acompanhamento])))
                messages.success(request, f"Solicitação {sol.protocolo} {'aprovada' if acao == 'aprovar' else 'reprovada'}.")
            elif acao == "criar_link":
                horas = max(1, min(24 * 90, int(request.POST.get("horas") or 24)))
                link = LinkSolicitacao.objects.create(
                    titulo=request.POST.get("titulo", "")[:160] or "Solicitação de acesso",
                    destino_padrao=request.POST.get("destino_padrao", "")[:180],
                    expira_em=timezone.now() + timedelta(hours=horas),
                    limite_usos=max(1, int(request.POST.get("limite") or 1)),
                    permanente=bool(request.POST.get("permanente")), criado_por=user)
                messages.success(request, f"Link criado: {request.build_absolute_uri(link.get_absolute_url())}")
            elif acao == "desativar_link":
                LinkSolicitacao.objects.filter(pk=request.POST.get("id")).update(ativo=False)
                messages.success(request, "Link desativado.")
            elif acao == "destino":
                if request.POST.get("nome") and request.POST.get("tipo") in dict(Destino.TIPOS):
                    Destino.objects.get_or_create(tipo=request.POST["tipo"], nome=request.POST["nome"].strip()[:180],
                                                  defaults={"criado_por": user})
            elif acao == "alternar_destino":
                d = get_object_or_404(Destino, pk=request.POST.get("id"))
                d.ativo = not d.ativo
                d.save(update_fields=["ativo"])
        except (ErroCFTV, ValueError) as exc:
            messages.error(request, str(exc))
        return redirect("cftv:liberacoes")
    hoje = timezone.localdate()
    return render(request, "cftv/liberacoes.html", {
        "form": form,
        "pendentes": SolicitacaoAcesso.objects.filter(status="PENDENTE").prefetch_related("pessoas").order_by("data_acesso"),
        "proximas": Liberacao.objects.filter(status="ATIVA").filter(Q(data_fim__gte=hoje) | Q(data_fim__isnull=True,
                                                                                               data_acesso__gte=hoje))
        .order_by("data_acesso", "hora_inicio")[:100],
        "links": LinkSolicitacao.objects.filter(ativo=True, expira_em__gt=timezone.now()),
        "destinos": Destino.objects.all(), "tipos": Destino.TIPOS})


@permissao_requerida("cftv.historico", "cftv.liberacoes")
def historico(request):
    qs = SolicitacaoAcesso.objects.prefetch_related("eventos", "pessoas").select_related("decidido_por")
    q = request.GET.get("q", "").strip()
    if q:
        cpf = normalizar_cpf(q)
        filtro = Q(nome_completo__icontains=q) | Q(protocolo__icontains=q) | Q(destino_nome__icontains=q) | Q(empresa__icontains=q)
        if len(cpf) == 11:
            filtro |= Q(cpf=cpf) | Q(pessoas__cpf=cpf)
        qs = qs.filter(filtro).distinct()
    if request.GET.get("de"):
        qs = qs.filter(data_acesso__gte=request.GET["de"])
    if request.GET.get("ate"):
        qs = qs.filter(data_acesso__lte=request.GET["ate"])
    return render(request, "cftv/historico.html", {"pagina": Paginator(qs, 30).get_page(request.GET.get("pagina")),
                                                   "q": q, "params": request.GET.urlencode()})


# --------------------------------------------------------------------- portaria

@never_cache
@permissao_requerida("controle_acesso.registrar")
def controle(request):
    resultado, erro = None, ""
    codigo = (request.POST.get("codigo") or request.GET.get("codigo") or "").strip()
    if "codigo=" in codigo:  # leu a URL inteira do QR
        from urllib.parse import parse_qs, urlparse

        codigo = (parse_qs(urlparse(codigo).query).get("codigo") or [""])[0]
    if request.method == "POST":
        try:
            if request.POST.get("solicitacao_id"):
                sol = get_object_or_404(SolicitacaoAcesso, pk=request.POST["solicitacao_id"])
                tipo = {"entrada": EventoAcesso.Tipo.ENTRADA, "saida": EventoAcesso.Tipo.SAIDA}.get(request.POST.get("acao"))
            else:
                sol = SolicitacaoAcesso.validar_codigo(codigo)
                tipo = None
                if not sol:
                    raise ErroCFTV("QR Code inválido ou adulterado.")
            evento, msg = services.registrar_evento(sol, request.user, tipo, request.POST.get("observacao", ""), request)
            resultado = {"sol": sol, "evento": evento, "mensagem": msg, "pessoas": sol.pessoas.all()}
        except ErroCFTV as exc:
            erro = str(exc)
    hoje = timezone.localdate()
    aprovadas = [s for s in SolicitacaoAcesso.objects.filter(status="APROVADA", data_acesso__lte=hoje)
                 .filter(Q(data_fim__gte=hoje) | Q(data_fim__isnull=True, data_acesso=hoje)).prefetch_related("eventos")]
    for s in aprovadas:
        s.eventos_hoje = [e for e in s.eventos.all() if timezone.localdate(e.registrado_em) == hoje]
    return render(request, "cftv/controle.html", {"resultado": resultado, "erro": erro, "codigo": codigo,
                                                  "aprovadas": aprovadas})


# --------------------------------------------------------------------- páginas públicas

@never_cache
def solicitar(request, token):
    link = LinkSolicitacao.objects.filter(token=token).first()
    if not link or not link.valido:
        return render(request, "cftv/publico_mensagem.html", {
            "titulo": "Link indisponível", "texto": "Este link de solicitação expirou ou já foi utilizado."}, status=404)
    form = SolicitacaoPublicaForm(request.POST or None, destino_padrao=link.destino_padrao,
                                  destinos=Destino.objects.filter(ativo=True))
    if request.method == "POST" and form.is_valid():
        try:
            sol = services.solicitar(link, form.dados(), form.pessoas(), request.META.get("REMOTE_ADDR", ""))
        except ErroCFTV as exc:
            form.add_error(None, str(exc))
        else:
            return redirect("cftv:acompanhar", chave=sol.acompanhamento)
    return render(request, "cftv/solicitar.html", {"form": form, "link": link,
                                                   "destinos": Destino.objects.filter(ativo=True)})


@never_cache
def acompanhar(request, chave):
    sol = SolicitacaoAcesso.objects.filter(acompanhamento=chave).prefetch_related("pessoas").first()
    if not sol:
        raise Http404
    utilizado = sol.voucher_utilizado()
    qr_url = request.build_absolute_uri(reverse("cftv:controle") + f"?codigo={sol.codigo_qr}")
    return render(request, "cftv/acompanhar.html", {
        "sol": sol, "utilizado": utilizado, "qr_url": qr_url,
        "pessoas": [(p.nome_completo, mascarar_cpf(p.cpf)) for p in sol.pessoas.all()],
        "credencial": reverse("cftv:credencial") + f"?codigo={sol.codigo_qr}"})


@never_cache
def credencial(request):
    sol = SolicitacaoAcesso.validar_codigo(request.GET.get("codigo", ""))
    if not sol or sol.status != SolicitacaoAcesso.Status.APROVADA:
        return render(request, "cftv/publico_mensagem.html", {
            "titulo": "Credencial inválida", "texto": "Credencial inválida ou não aprovada."}, status=404)
    return render(request, "cftv/acompanhar.html", {
        "sol": sol, "utilizado": sol.voucher_utilizado(), "somente_credencial": True,
        "qr_url": request.build_absolute_uri(reverse("cftv:controle") + f"?codigo={sol.codigo_qr}"),
        "pessoas": [(p.nome_completo, mascarar_cpf(p.cpf)) for p in sol.pessoas.all()]})


@never_cache
def consultar(request):
    """Consulta pública por CPF + protocolo (os dois são exigidos, para não expor dados por CPF)."""
    resultado, erro = None, ""
    if request.method == "POST":
        cpf = normalizar_cpf(request.POST.get("cpf"))
        protocolo = request.POST.get("protocolo", "").strip().upper()
        resultado = SolicitacaoAcesso.objects.filter(protocolo=protocolo).filter(Q(cpf=cpf) | Q(pessoas__cpf=cpf)).first()
        if resultado:
            return redirect("cftv:acompanhar", chave=resultado.acompanhamento)
        erro = "Nenhuma solicitação encontrada com este CPF e protocolo."
    return render(request, "cftv/consultar.html", {"erro": erro})


@login_required
def legado_controle(request):
    return redirect(f"{reverse('cftv:controle')}?codigo={request.GET.get('codigo', '')}")


def legado_credencial(request):
    return redirect(f"{reverse('cftv:credencial')}?codigo={request.GET.get('codigo', '')}")
