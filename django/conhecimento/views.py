from django.contrib import messages
from django.core.paginator import Paginator
from django.shortcuts import get_object_or_404, redirect, render

from core.models import Categoria
from core.permissions import permissao_requerida
from core.services import auditar

from . import services
from .forms import ArtigoForm
from .models import Artigo, Avaliacao


@permissao_requerida("conhecimento.visualizar", "conhecimento.gerenciar")
def lista(request):
    categoria = Categoria.objects.filter(pk=request.GET.get("categoria") or None).first()
    qs = services.buscar(request.GET.get("q", ""), categoria, incluir_inativos=request.user.pode("conhecimento.gerenciar"))
    return render(request, "conhecimento/lista.html", {
        "pagina": Paginator(qs, 20).get_page(request.GET.get("pagina")), "categorias": Categoria.objects.all(),
        "q": request.GET.get("q", ""), "categoria": categoria})


@permissao_requerida("conhecimento.visualizar", "conhecimento.gerenciar")
def detalhe(request, pk):
    artigo = get_object_or_404(Artigo.objects.select_related("categoria", "responsavel", "origem_ordem"), pk=pk)
    if artigo.status != Artigo.Status.PUBLICADO and not request.user.pode("conhecimento.gerenciar"):
        return redirect("conhecimento:lista")
    if request.method == "POST":
        Avaliacao.objects.update_or_create(artigo=artigo, usuario=request.user, defaults={
            "util": request.POST.get("util") == "1", "comentario": request.POST.get("comentario", "")[:500]})
        messages.success(request, "Obrigado pela avaliação.")
        return redirect(artigo)
    return render(request, "conhecimento/detalhe.html", {
        "artigo": artigo, "links": [l for l in artigo.links.splitlines() if l.strip().startswith(("http://", "https://"))],
        "avaliacoes": artigo.avaliacoes.select_related("usuario"),
        "minha": artigo.avaliacoes.filter(usuario=request.user).first()})


@permissao_requerida("conhecimento.gerenciar")
def form(request, pk=None):
    obj = get_object_or_404(Artigo, pk=pk) if pk else None
    f = ArtigoForm(request.POST or None, instance=obj)
    if request.method == "POST" and f.is_valid():
        artigo = f.save(commit=False)
        artigo.responsavel = artigo.responsavel or request.user
        artigo.save()
        auditar(request, "artigo", artigo.pk, "SALVAR", "", None, artigo.titulo)
        messages.success(request, "Artigo salvo.")
        return redirect(artigo)
    return render(request, "core/form_simples.html", {"form": f, "titulo": "Artigo", "voltar": "conhecimento:lista"})
