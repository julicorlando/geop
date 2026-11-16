from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.core.exceptions import PermissionDenied
from django.core.paginator import Paginator
from django.db.models import Case, Count, IntegerField, Q, Value, When
from django.http import Http404
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.http import require_POST

from core.arquivos import responder_arquivo
from core.permissions import permissao_requerida

from . import services
from .forms import AnexoForm, AtribuirForm, EditarOrdemForm, FiltroOrdensForm, MaterialForm, OrdemForm, StatusForm
from .models import Anexo, ChecklistItem, Ordem, OrdemAgente, OrdemPatrimonio, SolicitacaoExclusao
from .services import RegraNegocio

ORDENACOES = {"recentes": "-criado_em", "antigas": "criado_em", "prazo": "prazo", "prioridade": "-peso_prioridade"}
PESO_PRIORIDADE = Case(*[When(prioridade=p, then=Value(i)) for i, p in enumerate(["BAIXA", "MEDIA", "ALTA", "CRITICA"])],
                       default=Value(0), output_field=IntegerField())


def _ordem_visivel(request, pk, incluir_excluidas=False):
    qs = Ordem.objects.select_related("local", "categoria", "solicitante", "gestor", "agente", "aprovado_por")
    ordem = get_object_or_404(qs, pk=pk)
    if ordem.excluida_em and not (incluir_excluidas and request.user.is_ti):
        raise Http404
    if not services.pode_ver(request.user, ordem):
        raise PermissionDenied("Você não participa desta ordem.")
    return ordem


@permissao_requerida("os.visualizar_todas", "os.executar", "os.criar", "os.gerenciar")
def lista(request):
    form = FiltroOrdensForm(request.GET or None)
    qs = (Ordem.objects.visiveis_para(request.user)
          .select_related("local", "categoria", "agente")
          .annotate(n_fotos=Count("anexos", distinct=True, filter=Q(anexos__mime__startswith="image/")),
                    peso_prioridade=PESO_PRIORIDADE))
    if form.is_valid():
        d = form.cleaned_data
        if d["q"]:
            qs = qs.filter(Q(numero__icontains=d["q"]) | Q(titulo__icontains=d["q"]) | Q(descricao__icontains=d["q"]))
        if d["tipo"]:
            qs = qs.filter(tipo=d["tipo"])
        if d["status"] == "abertas":
            qs = qs.abertas()
        elif d["status"] == "atrasadas":
            qs = qs.atrasadas()
        elif d["status"]:
            qs = qs.filter(status=d["status"])
        for campo in ("prioridade", "local", "categoria", "agente"):
            if d[campo]:
                qs = qs.filter(**{campo: d[campo]})
    qs = qs.order_by(ORDENACOES.get(request.GET.get("ordem"), "-criado_em"), "-id")
    pagina = Paginator(qs, 25).get_page(request.GET.get("pagina"))
    params = request.GET.copy()
    params.pop("pagina", None)
    return render(request, "ordens/lista.html", {"form": form, "pagina": pagina, "params": params.urlencode()})


@permissao_requerida("os.criar")
def nova(request):
    form = OrdemForm(request.POST or None, request.FILES or None, user=request.user)
    if request.method == "POST" and form.is_valid():
        d = form.cleaned_data
        equipe = {"membros": list(d["equipe"]), "principal": d["asg_principal"], "supervisor": d["supervisor"]}
        try:
            ordem = services.criar_ordem(
                request.user, d, fotos=request.FILES.getlist("fotos"), tarefas=list(d["tarefas"]),
                itens_livres=form.itens_livres_lista(), equipe=equipe, request=request,
            )
        except RegraNegocio as exc:
            form.add_error(None, str(exc))
        else:
            messages.success(request, f"{ordem.get_tipo_display()} {ordem.numero} aberta com sucesso.")
            return redirect(ordem)
    return render(request, "ordens/form.html", {"form": form})


@login_required
def detalhe(request, pk):
    ordem = _ordem_visivel(request, pk, incluir_excluidas=True)
    user = request.user
    if request.method == "POST":
        return _executar_acao(request, ordem)
    gerenciar = services.pode_gerenciar(user)
    executar = services.pode_executar(user, ordem)
    anexos = list(ordem.anexos.select_related("usuario"))
    fotos = [a for a in anexos if a.eh_imagem]
    permitidos = services.status_permitidos(ordem, user)
    contexto = {
        "ordem": ordem,
        "gerenciar": gerenciar,
        "executar": executar,
        "atualizar": gerenciar or executar,
        "fotos_por_tipo": [(t, [f for f in fotos if f.tipo == t.value]) for t in Anexo.Tipo if t != Anexo.Tipo.DOCUMENTO],
        "documentos": [a for a in anexos if not a.eh_imagem],
        "historico": ordem.historico.select_related("usuario")[:200],
        "materiais": ordem.materiais.all(),
        "checklist": ordem.checklist.select_related("concluido_por"),
        "agentes": ordem.agentes.select_related("usuario"),
        "equipe_limpeza": ordem.equipe_limpeza.select_related("membro"),
        "patrimonios": ordem.patrimonios.filter(ativo=True),
        "status_form": StatusForm(permitidos=permitidos) if permitidos else None,
        "anexo_form": AnexoForm(),
        "material_form": MaterialForm(),
        "editar_form": EditarOrdemForm(instance=ordem) if gerenciar and (not ordem.encerrada or user.is_ti) else None,
        "atribuir_form": AtribuirForm(initial={
            "agente": ordem.agente_id,
            "auxiliares": [a.usuario_id for a in ordem.agentes.all() if a.papel == OrdemAgente.Papel.AUXILIAR],
        }) if gerenciar and not ordem.encerrada else None,
        "pendencias": services.pendencias_encerramento(ordem) if gerenciar else [],
        "exclusao_ativa": ordem.solicitacoes_exclusao.filter(estado__in=["PENDENTE", "APROVADA"]).first(),
        "solicitacao_token": ordem.solicitacoes_reabertura.filter(estado="PENDENTE").first(),
        "token_gerado": request.session.pop(f"token_reabertura_{ordem.pk}", None),
        "tipos_foto": [t for t in Anexo.Tipo if t not in (Anexo.Tipo.DOCUMENTO,)],
        "pode_patrimonio": user.pode("patrimonio.vincular") and (gerenciar or executar) and not ordem.encerrada,
    }
    return render(request, "ordens/detalhe.html", contexto)


def _executar_acao(request, ordem):
    user, acao, post = request.user, request.POST.get("acao", ""), request.POST
    try:
        if ordem.excluida_em and acao != "restaurar":
            raise RegraNegocio("Esta ordem está na lixeira. Restaure-a antes de alterá-la.")
        if acao == "status":
            form = StatusForm(post, permitidos=services.status_permitidos(ordem, user))
            if not form.is_valid():
                raise RegraNegocio("Selecione um status válido.")
            services.alterar_status(ordem, user, form.cleaned_data["status"], form.cleaned_data["obs"],
                                    form.cleaned_data["custo_materiais"], request)
            messages.success(request, f"Status alterado para “{ordem.get_status_display()}”.")
        elif acao == "editar":
            form = EditarOrdemForm(post, instance=Ordem.objects.get(pk=ordem.pk))
            if not form.is_valid():
                raise RegraNegocio("Revise os campos: " + "; ".join(f"{k}: {v[0]}" for k, v in form.errors.items()))
            services.editar(ordem, user, form.cleaned_data, request)
            messages.success(request, "Ordem atualizada.")
        elif acao == "atribuir":
            form = AtribuirForm(post)
            if not form.is_valid():
                raise RegraNegocio("Selecione agentes válidos.")
            services.atribuir(ordem, user, form.cleaned_data["agente"], list(form.cleaned_data["auxiliares"]), request)
            messages.success(request, "Responsáveis atualizados.")
        elif acao == "anexo":
            form = AnexoForm(post, request.FILES)
            if not form.is_valid():
                raise RegraNegocio("Selecione o tipo e ao menos um arquivo.")
            anexos = services.anexar(ordem, user, request.FILES.getlist("arquivos"), form.cleaned_data["tipo"],
                                     form.cleaned_data["descricao"], request)
            messages.success(request, f"{len(anexos)} arquivo(s) anexado(s){services.descrever_economia(anexos)}.")
        elif acao == "mover_foto":
            anexo = get_object_or_404(Anexo, pk=post.get("anexo_id"), ordem=ordem)
            services.mover_foto(ordem, user, anexo, post.get("novo_tipo", ""), request)
            messages.success(request, "Foto movida.")
        elif acao == "excluir_anexo":
            anexo = get_object_or_404(Anexo, pk=post.get("anexo_id"), ordem=ordem)
            services.excluir_anexo(ordem, user, anexo, request)
            messages.success(request, "Anexo removido.")
        elif acao == "material":
            form = MaterialForm(post)
            if not form.is_valid():
                raise RegraNegocio("Informe o material e a quantidade.")
            d = form.cleaned_data
            services.registrar_material(ordem, user, d["nome_material"], d["quantidade"], d["unidade"],
                                        d["custo_unitario"], d["observacao"], request)
            messages.success(request, "Material registrado.")
        elif acao == "checklist":
            item = get_object_or_404(ChecklistItem, pk=post.get("item_id"), ordem=ordem)
            services.atualizar_checklist(ordem, user, item, post.get("concluido") == "1", post.get("observacao", ""),
                                         request)
            messages.success(request, "Checklist atualizado.")
        elif acao == "solicitar_token":
            services.solicitar_token(ordem, user, post.get("motivo", ""), request)
            messages.success(request, "Solicitação enviada ao TI.")
        elif acao == "gerar_token":
            token = services.gerar_token(ordem, user, request)
            request.session[f"token_reabertura_{ordem.pk}"] = token
            messages.success(request, "Token de reabertura gerado.")
        elif acao == "reabrir":
            services.reabrir_com_token(ordem, user, post.get("token", ""), post.get("obs", ""), request)
            messages.success(request, "Ordem reaberta.")
        elif acao == "excluir":
            services.solicitar_exclusao(ordem, user, post.get("motivo", ""), request)
            if user.is_ti:
                messages.success(request, f"{ordem.numero} movida para a lixeira.")
                return redirect("ordens:lista")
            messages.success(request, "Solicitação de exclusão enviada para análise.")
        elif acao == "restaurar":
            services.restaurar(ordem, user, request)
            messages.success(request, "Ordem restaurada.")
        elif acao == "vincular_patrimonio":
            from patrimonio.services import vincular

            vincular(ordem, user, post, request)
            messages.success(request, "Patrimônio vinculado.")
        elif acao == "desvincular_patrimonio":
            from patrimonio.services import desvincular

            desvincular(ordem, user, get_object_or_404(OrdemPatrimonio, pk=post.get("patrimonio_id"), ordem=ordem),
                        request)
            messages.success(request, "Patrimônio desvinculado.")
        else:
            raise RegraNegocio("Ação desconhecida.")
    except RegraNegocio as exc:
        messages.error(request, str(exc))
    except PermissionDenied as exc:
        messages.error(request, str(exc) or "Você não tem permissão para esta ação.")
    return redirect(ordem)


@login_required
def arquivo(request, pk, miniatura=False):
    anexo = get_object_or_404(Anexo.objects.select_related("ordem"), pk=pk)
    if anexo.ordem.excluida_em and not request.user.is_ti:
        raise Http404
    if not services.pode_ver(request.user, anexo.ordem):
        raise PermissionDenied
    campo = anexo.miniatura if miniatura and anexo.miniatura else anexo.arquivo
    return responder_arquivo(campo, nome_download=anexo.nome_original, inline=request.GET.get("baixar") != "1")


@login_required
def imprimir(request, pk):
    ordem = _ordem_visivel(request, pk)
    return render(request, "ordens/imprimir.html", {
        "ordem": ordem, "historico": ordem.historico.select_related("usuario")[:100],
        "agentes": ordem.agentes.select_related("usuario"), "materiais": ordem.materiais.all(),
        "fotos": [a for a in ordem.anexos.all() if a.eh_imagem], "checklist": ordem.checklist.all(),
    })


@permissao_requerida("os.excluir_solicitar", "os.excluir_analisar")
def exclusoes(request):
    qs = SolicitacaoExclusao.objects.select_related("ordem", "solicitado_por", "analisado_por", "executado_por")
    if request.method == "POST":
        sol = get_object_or_404(qs, pk=request.POST.get("solicitacao_id"))
        try:
            if request.POST.get("acao") == "executar":
                services.executar_exclusao(sol, request.user, request)
                messages.success(request, f"{sol.ordem.numero} excluída.")
            else:
                services.analisar_exclusao(sol, request.user, request.POST.get("acao") == "aprovar",
                                           request.POST.get("observacao", ""), request)
                messages.success(request, "Análise registrada.")
        except (RegraNegocio, PermissionDenied) as exc:
            messages.error(request, str(exc) or "Sem permissão.")
        return redirect("ordens:exclusoes")
    estado = request.GET.get("estado", "PENDENTE")
    if estado:
        qs = qs.filter(estado=estado)
    return render(request, "ordens/exclusoes.html", {
        "solicitacoes": Paginator(qs, 30).get_page(request.GET.get("pagina")),
        "estado": estado, "estados": SolicitacaoExclusao.Estado.choices,
    })


@login_required
def lixeira(request):
    if not request.user.is_ti:
        raise PermissionDenied
    qs = Ordem.objects.filter(excluida_em__isnull=False).select_related("excluida_por", "local").order_by("-excluida_em")
    return render(request, "ordens/lixeira.html", {"pagina": Paginator(qs, 30).get_page(request.GET.get("pagina"))})


@login_required
@require_POST
def restaurar(request, pk):
    ordem = get_object_or_404(Ordem, pk=pk, excluida_em__isnull=False)
    services.restaurar(ordem, request.user, request)
    messages.success(request, f"{ordem.numero} restaurada.")
    return redirect("ordens:lixeira")


# --------------------------------------------------------------------- link público (sem login)

def _ordem_publica(pk, token):
    ordem = Ordem.objects.select_related("local", "categoria", "gestor", "agente").filter(
        pk=pk, excluida_em__isnull=True).first()
    if not ordem or not ordem.token_publico_valido(token):
        raise Http404("Link inválido.")
    return ordem


def publica(request, pk, token):
    ordem = _ordem_publica(pk, token)
    if request.method == "POST":
        try:
            if request.POST.get("acao") == "solicitar_encerramento":
                services.publica_solicitar_encerramento(ordem, request.POST.get("observacao", ""))
                messages.success(request, "Solicitação enviada. O gestor fará a conferência final.")
            else:
                anexos = services.publica_enviar_fotos(ordem, request.FILES.getlist("fotos"),
                                                       request.POST.get("descricao", ""), request)
                messages.success(request, f"{len(anexos)} foto(s) enviada(s){services.descrever_economia(anexos)}.")
        except RegraNegocio as exc:
            messages.error(request, str(exc))
        return redirect("ordens:publica", pk=pk, token=token)
    anexos = ordem.anexos.order_by("-criado_em")
    return render(request, "ordens/publica.html", {
        "ordem": ordem, "token": token, "fotos": [a for a in anexos if a.eh_imagem],
        "historico": ordem.historico.select_related("usuario")[:50],
        "agentes": ordem.agentes.select_related("usuario"),
    })


def publica_arquivo(request, pk, token, anexo_id, miniatura=False):
    ordem = _ordem_publica(pk, token)
    anexo = get_object_or_404(Anexo, pk=anexo_id, ordem=ordem)
    if not anexo.eh_imagem:
        raise Http404
    return responder_arquivo(anexo.miniatura if miniatura and anexo.miniatura else anexo.arquivo)
