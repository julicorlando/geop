from datetime import date, timedelta

from django.contrib import messages
from django.contrib.auth import get_user_model
from django.contrib.auth.decorators import login_required
from django.core.exceptions import PermissionDenied
from django.db.models import Count, Q
from django.http import JsonResponse
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone

from core.models import Categoria, Local
from core.permissions import permissao_requerida
from core.services import auditar, notificar
from ordens import services as os_services
from ordens.models import Anexo, Ordem, Status
from ordens.services import RegraNegocio

from . import services
from .forms import CronogramaForm, EscalaForm, FeriadoForm, PassagemForm, PreventivaForm, ServicoEssencialForm
from .models import CronogramaAgente, EquipeEstado, Escala, Especialidade, Feriado, PassagemTurno, Preventiva


# --------------------------------------------------------------------- preventivas

@permissao_requerida("preventivas.gerenciar")
def preventivas(request):
    if request.method == "POST" and request.POST.get("acao") == "gerar":
        r = services.gerar_preventivas()
        messages.success(request, f"{r['criadas']} ordem(ns) preventiva(s) gerada(s): {', '.join(r['ordens']) or '—'}.")
        return redirect("operacao:preventivas")
    hoje = timezone.localdate()
    return render(request, "operacao/preventivas.html", {
        "preventivas": Preventiva.objects.select_related("local", "categoria", "agente").annotate(n_ordens=Count("ordens")).order_by("proxima_execucao", "titulo"),
        "hoje": hoje, "vencidas": Preventiva.objects.filter(ativo=True, proxima_execucao__lte=hoje).count()})


@permissao_requerida("preventivas.gerenciar")
def preventiva_form(request, pk=None):
    obj = get_object_or_404(Preventiva, pk=pk) if pk else None
    form = PreventivaForm(request.POST or None, instance=obj, initial={"proxima_execucao": timezone.localdate()})
    if request.method == "POST" and form.is_valid():
        p = form.save(commit=False)
        p.criado_por = p.criado_por or request.user
        p.save()
        auditar(request, "preventiva", p.pk, "SALVAR", "", None, p.titulo)
        messages.success(request, "Preventiva salva.")
        return redirect("operacao:preventivas")
    return render(request, "core/form_simples.html", {"form": form, "titulo": "Manutenção preventiva",
                                                      "voltar": "operacao:preventivas"})


# --------------------------------------------------------------------- equipe (gestão integrada)

@permissao_requerida("equipe.gerenciar")
def equipe(request):
    Usuario = get_user_model()
    if request.method == "POST":
        acao = request.POST.get("acao")
        alvo = Usuario.objects.filter(pk=request.POST.get("usuario") or None).first()
        if acao == "estado" and alvo:
            est, _ = EquipeEstado.objects.get_or_create(usuario=alvo)
            if request.POST.get("estado") in EquipeEstado.Estado.values:
                est.estado = request.POST["estado"]
            try:
                est.capacidade_maxima = max(1, min(50, int(request.POST.get("capacidade") or est.capacidade_maxima)))
            except ValueError:
                pass
            est.observacao = request.POST.get("observacao", "")[:255]
            est.save()
            auditar(request, "equipe", alvo.pk, "ESTADO", "estado", None, est.estado)
            messages.success(request, f"Estado de {alvo.nome} atualizado.")
        elif acao == "especialidade" and alvo and request.POST.get("especialidade"):
            Especialidade.objects.update_or_create(usuario=alvo, especialidade=request.POST["especialidade"][:100],
                                                   defaults={"nivel": int(request.POST.get("nivel") or 1)})
            messages.success(request, "Especialidade salva.")
        elif acao == "remover_especialidade":
            Especialidade.objects.filter(pk=request.POST.get("id")).delete()
        elif acao == "feriado":
            form = FeriadoForm(request.POST)
            if form.is_valid():
                form.save()
                messages.success(request, "Feriado cadastrado.")
            else:
                messages.error(request, "Feriado inválido ou já cadastrado.")
        return redirect("operacao:equipe")
    estados = {e.usuario_id: e for e in EquipeEstado.objects.all()}
    cargas = dict(Ordem.objects.ativas().exclude(status__in=services.STATUS_ATIVOS_EXCLUIDOS)
                  .values_list("agente").annotate(n=Count("id")))
    membros = []
    for u in services.executores().order_by("nome").prefetch_related("especialidades"):
        e = estados.get(u.pk)
        membros.append({"usuario": u, "estado": e.estado if e else "DISPONIVEL", "capacidade": e.capacidade_maxima if e else 5,
                        "observacao": e.observacao if e else "", "carga": cargas.get(u.pk, 0),
                        "especialidades": list(u.especialidades.all())})
    return render(request, "operacao/equipe.html", {
        "membros": membros, "estados": EquipeEstado.Estado.choices, "categorias": Categoria.objects.filter(ativo=True),
        "feriados": Feriado.objects.filter(data__gte=timezone.localdate() - timedelta(days=30)).select_related("local"),
        "feriado_form": FeriadoForm()})


@permissao_requerida("equipe.gerenciar")
def escalas(request):
    try:
        mes = date.fromisoformat((request.GET.get("mes") or timezone.localdate().strftime("%Y-%m")) + "-01")
    except ValueError:
        mes = timezone.localdate().replace(day=1)
    form = EscalaForm(request.POST or None, initial={"data_servico": timezone.localdate()})
    if request.method == "POST":
        if request.POST.get("acao") == "remover":
            Escala.objects.filter(pk=request.POST.get("id")).delete()
            messages.success(request, "Escala removida.")
        elif form.is_valid():
            d = form.cleaned_data
            Escala.objects.update_or_create(usuario=d["usuario"], data_servico=d["data_servico"], defaults={
                **{k: v for k, v in d.items() if k not in ("usuario", "data_servico")}, "criado_por": request.user})
            messages.success(request, "Escala salva.")
        else:
            messages.error(request, "Revise os dados da escala.")
        return redirect(f"{request.path}?mes={mes:%Y-%m}")
    fim = (mes + timedelta(days=32)).replace(day=1)
    dias = [mes + timedelta(days=i) for i in range((fim - mes).days)]
    escalas_mes = Escala.objects.filter(data_servico__gte=mes, data_servico__lt=fim).select_related("usuario")
    grade = {}
    for e in escalas_mes:
        grade.setdefault(e.usuario, {})[e.data_servico] = e
    linhas = [{"usuario": u, "dias": [grade[u].get(d) for d in dias]} for u in sorted(grade, key=lambda x: x.nome)]
    return render(request, "operacao/escalas.html", {
        "mes": mes, "anterior": (mes - timedelta(days=1)).replace(day=1), "proximo": fim, "dias": dias,
        "linhas": linhas, "form": form, "lista": escalas_mes})


@login_required
def passagens(request):
    form = PassagemForm(request.POST or None, user=request.user)
    if request.method == "POST":
        if request.POST.get("acao") == "lida":
            PassagemTurno.objects.filter(pk=request.POST.get("id"), recebe=request.user).update(lida_em=timezone.now())
            return redirect("operacao:passagens")
        if form.is_valid():
            p = form.save(commit=False)
            p.entrega = request.user
            p.save()
            notificar(p.recebe, "PASSAGEM_TURNO", f"Passagem de turno de {request.user.nome}", p.pendencias[:200],
                      "/operacao/passagens/", f"passagem-{p.pk}")
            messages.success(request, "Passagem de turno registrada.")
            return redirect("operacao:passagens")
    recebidas = PassagemTurno.objects.filter(recebe=request.user).select_related("entrega")[:30]
    enviadas = PassagemTurno.objects.filter(entrega=request.user).select_related("recebe")[:30]
    return render(request, "operacao/passagens.html", {"form": form, "recebidas": recebidas, "enviadas": enviadas})


# --------------------------------------------------------------------- cronograma semanal

def _inicio_semana(d):
    return d - timedelta(days=d.weekday())


@login_required
def cronograma(request):
    user = request.user
    gerenciar = user.pode("cronograma.gerenciar")
    if not gerenciar and not user.pode("os.executar"):
        raise PermissionDenied
    try:
        semana = _inicio_semana(date.fromisoformat(request.GET.get("semana", "")))
    except ValueError:
        semana = _inicio_semana(timezone.localdate())
    form = CronogramaForm(request.POST or None) if gerenciar else None
    if request.method == "POST":
        acao = request.POST.get("acao")
        if acao == "criar" and gerenciar and form.is_valid():
            item = form.save(commit=False)
            item.gestor, item.semana_inicio = user, semana
            item.save()
            notificar(item.agente, "CRONOGRAMA", "Nova tarefa no seu cronograma",
                      f"{item.get_dia_semana_display()} {item.hora_inicio:%H:%M} — {item.titulo}",
                      f"/operacao/cronograma/?semana={semana}", f"crono-{item.pk}")
            messages.success(request, "Tarefa adicionada.")
        elif acao in ("status", "excluir"):
            item = get_object_or_404(CronogramaAgente, pk=request.POST.get("id"))
            if not gerenciar and item.agente_id != user.pk:
                raise PermissionDenied
            if acao == "excluir" and gerenciar:
                item.delete()
            elif request.POST.get("status") in CronogramaAgente.Status.values:
                item.status = request.POST["status"]
                item.save(update_fields=["status"])
        elif form is not None and form.errors:
            messages.error(request, "Revise a tarefa: " + "; ".join(e for errs in form.errors.values() for e in errs))
        return redirect(f"{request.path}?semana={semana}")
    qs = CronogramaAgente.objects.filter(semana_inicio=semana).select_related("agente", "local")
    if not gerenciar:
        qs = qs.filter(agente=user)
    dias = [(n, nome, semana + timedelta(days=n - 1), [i for i in qs if i.dia_semana == n])
            for n, nome in CronogramaAgente.DIAS]
    return render(request, "operacao/cronograma.html", {
        "semana": semana, "anterior": semana - timedelta(days=7), "proxima": semana + timedelta(days=7),
        "dias": dias, "form": form, "gerenciar": gerenciar, "status": CronogramaAgente.Status.choices})


# --------------------------------------------------------------------- agente

@permissao_requerida("os.executar")
def minha_rota(request):
    user = request.user
    if request.method == "POST":
        ordem = get_object_or_404(Ordem.objects.ativas(), pk=request.POST.get("ordem_id"))
        try:
            if request.POST.get("acao") == "status":
                os_services.alterar_status(ordem, user, request.POST.get("status", ""), request.POST.get("obs", ""),
                                           request=request)
                messages.success(request, f"{ordem.numero}: {ordem.get_status_display()}.")
            elif request.POST.get("acao") == "foto":
                anexos = os_services.anexar(ordem, user, request.FILES.getlist("fotos"),
                                            request.POST.get("tipo", Anexo.Tipo.FOTO_DEPOIS), "", request)
                messages.success(request, f"{len(anexos)} foto(s) enviada(s){os_services.descrever_economia(anexos)}.")
        except (RegraNegocio, PermissionDenied) as exc:
            messages.error(request, str(exc) or "Sem permissão.")
        return redirect("operacao:minha_rota")
    peso = {"CRITICA": 0, "ALTA": 1, "MEDIA": 2, "BAIXA": 3}
    ordens = list(Ordem.objects.ativas().filter(Q(agente=user) | Q(agentes__usuario=user)).distinct()
                  .exclude(status__in=[Status.ENCERRADA, Status.CONCLUIDA_PELO_AGENTE]).select_related("local", "categoria"))
    ordens.sort(key=lambda o: (peso.get(o.prioridade, 9), o.prazo or timezone.now() + timedelta(days=3650)))
    hoje = timezone.localdate()
    return render(request, "operacao/minha_rota.html", {
        "ordens": ordens, "status_agente": [(s, Status(s).label) for s in os_services.STATUS_AGENTE],
        "cronograma": CronogramaAgente.objects.filter(agente=user, semana_inicio=_inicio_semana(hoje),
                                                      dia_semana=hoje.isoweekday()).select_related("local"),
        "tipos_foto": [(Anexo.Tipo.FOTO_ANTES, "Antes"), (Anexo.Tipo.FOTO_DURANTE, "Durante"),
                       (Anexo.Tipo.FOTO_DEPOIS, "Depois")]})


@permissao_requerida("os.criar")
def servico_essencial(request):
    form = ServicoEssencialForm(request.POST or None)
    if request.method == "POST" and form.is_valid():
        d = form.cleaned_data
        agente = d["agente"]
        if not agente:
            rec = services.recomendar_agentes(d["categoria"], d["local"], 1)
            agente = get_user_model().objects.filter(pk=rec[0]["id"]).first() if rec else None
        try:
            ordem = os_services.criar_ordem(request.user, {
                "titulo": f"[SERVICO ESSENCIAL] {d['titulo']}", "descricao": d["descricao"], "local": d["local"],
                "categoria": d["categoria"], "prioridade": "CRITICA", "agente": agente, "origem": "ESSENCIAL",
                "ignorar_capacidade": True}, fotos=request.FILES.getlist("fotos"), request=request)
        except RegraNegocio as exc:
            form.add_error(None, str(exc))
        else:
            messages.success(request, f"{ordem.numero} aberta como serviço essencial"
                                      f"{' e atribuída a ' + agente.nome if agente else ''}.")
            return redirect(ordem)
    return render(request, "operacao/servico_essencial.html", {"form": form})


@login_required
def recomendar(request):
    cat = Categoria.objects.filter(pk=request.GET.get("categoria") or None).first()
    local = Local.objects.filter(pk=request.GET.get("local") or None).first()
    return JsonResponse({"ok": True, "agentes": services.recomendar_agentes(cat, local, 8)})


# --------------------------------------------------------------------- central de aprovações

@permissao_requerida("aprovacoes.central", "estoque.gerenciar", "compras.gerenciar", "os.excluir_analisar")
def aprovacoes(request):
    if request.method == "POST":
        from estoque import services as est
        from estoque.models import Reserva

        reserva = get_object_or_404(Reserva, pk=request.POST.get("reserva_id"))
        try:
            est.decidir_negativo(reserva, request.user, request.POST.get("acao") == "aprovar",
                                 request.POST.get("observacao", ""), request)
            messages.success(request, "Decisão registrada.")
        except (est.ErroEstoque, PermissionDenied) as exc:
            messages.error(request, str(exc) or "Sem permissão.")
        return redirect("operacao:aprovacoes")
    grupos = services.pendencias_aprovacao(request.user)
    return render(request, "operacao/aprovacoes.html", {"grupos": grupos, "total": sum(len(g[2]) for g in grupos)})
