from django.contrib import messages
from django.shortcuts import get_object_or_404, redirect, render

from core.permissions import permissao_requerida
from core.services import auditar

from .forms import GrupoForm, OrganogramaForm, TarefaForm
from .models import OrganogramaNo, PlanoGrupo, PlanoTarefa


def _arvore(nos):
    filhos = {}
    for no in nos:
        filhos.setdefault(no.parent_id, []).append(no)

    def montar(pid, nivel=0):
        for no in filhos.get(pid, []):
            no.nivel = nivel
            yield no
            yield from montar(no.pk, nivel + 1)

    ids = {n.pk for n in nos}
    raizes = [n for n in nos if n.parent_id is None or n.parent_id not in ids]
    resultado = []
    for raiz in raizes:
        raiz.nivel = 0
        resultado.append(raiz)
        resultado.extend(montar(raiz.pk, 1))
    return resultado


@permissao_requerida("limpeza.acessar", "limpeza.organograma")
def organograma(request):
    nos = list(OrganogramaNo.objects.select_related("usuario", "parent"))
    return render(request, "limpeza/organograma.html", {"nos": _arvore(nos)})


@permissao_requerida("limpeza.organograma")
def organograma_form(request, pk=None):
    obj = get_object_or_404(OrganogramaNo, pk=pk) if pk else None
    form = OrganogramaForm(request.POST or None, instance=obj)
    if request.method == "POST" and form.is_valid():
        no = form.save(commit=False)
        if not no.pk:
            no.criado_por = request.user
        no.save()
        auditar(request, "organograma_limpeza", no.pk, "SALVAR", "", None, str(no))
        messages.success(request, "Colaborador salvo.")
        return redirect("limpeza:organograma")
    return render(request, "core/form_simples.html", {
        "form": form, "titulo": "Colaborador da limpeza", "voltar": "limpeza:organograma"})


@permissao_requerida("limpeza.acessar", "limpeza.plano")
def plano(request):
    grupos = PlanoGrupo.objects.prefetch_related("tarefas")
    return render(request, "limpeza/plano.html", {"grupos": grupos})


@permissao_requerida("limpeza.plano")
def grupo_form(request, pk=None):
    obj = get_object_or_404(PlanoGrupo, pk=pk) if pk else None
    form = GrupoForm(request.POST or None, instance=obj)
    if request.method == "POST" and form.is_valid():
        form.save()
        messages.success(request, "Grupo salvo.")
        return redirect("limpeza:plano")
    return render(request, "core/form_simples.html", {"form": form, "titulo": "Grupo do plano", "voltar": "limpeza:plano"})


@permissao_requerida("limpeza.plano")
def tarefa_form(request, pk=None):
    obj = get_object_or_404(PlanoTarefa, pk=pk) if pk else None
    form = TarefaForm(request.POST or None, instance=obj, initial={"grupo": request.GET.get("grupo")})
    if request.method == "POST" and form.is_valid():
        form.save()
        messages.success(request, "Tarefa salva.")
        return redirect("limpeza:plano")
    return render(request, "core/form_simples.html", {"form": form, "titulo": "Tarefa do plano", "voltar": "limpeza:plano"})
