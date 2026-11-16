from decimal import Decimal, InvalidOperation

from django.contrib import messages
from django.core.exceptions import PermissionDenied
from django.core.paginator import Paginator
from django.db.models import F, Q
from django.shortcuts import get_object_or_404, redirect, render

from core.arquivos import responder_arquivo
from core.permissions import permissao_requerida
from ordens.models import Ordem
from ordens.services import pode_ver

from . import services
from .forms import CustoForm, MaterialForm, MovimentacaoForm
from .models import Compra, CustoDocumento, MaterialEstoque, Movimentacao, Reserva
from .services import ErroEstoque


@permissao_requerida("estoque.gerenciar")
def lista(request):
    qs = MaterialEstoque.objects.all()
    q = request.GET.get("q", "").strip()
    if q:
        qs = qs.filter(nome__icontains=q)
    if request.GET.get("filtro") == "baixo":
        qs = qs.filter(ativo=True, quantidade_atual__lte=F("estoque_minimo"))
    return render(request, "estoque/lista.html", {
        "materiais": qs, "q": q, "form_mov": MovimentacaoForm(),
        "baixos": MaterialEstoque.objects.filter(ativo=True, quantidade_atual__lte=F("estoque_minimo")).count(),
        "pendentes": Reserva.objects.filter(status=Reserva.Status.APROVACAO_NEGATIVO).count()})


@permissao_requerida("estoque.gerenciar")
def material_form(request, pk=None):
    obj = get_object_or_404(MaterialEstoque, pk=pk) if pk else None
    form = MaterialForm(request.POST or None, instance=obj)
    if request.method == "POST" and form.is_valid():
        form.save()
        messages.success(request, "Material salvo.")
        return redirect("estoque:lista")
    return render(request, "core/form_simples.html", {"form": form, "titulo": "Material", "voltar": "estoque:lista"})


@permissao_requerida("estoque.gerenciar")
def material_detalhe(request, pk):
    material = get_object_or_404(MaterialEstoque, pk=pk)
    form = MovimentacaoForm(request.POST or None)
    if request.method == "POST" and form.is_valid():
        d = form.cleaned_data
        try:
            services.movimentar(material, request.user, d["tipo"], d["quantidade"], d["custo_unitario"],
                                d["observacao"], request)
            messages.success(request, "Movimentação registrada.")
        except ErroEstoque as exc:
            messages.error(request, str(exc))
        return redirect("estoque:material", pk=pk)
    return render(request, "estoque/material.html", {
        "material": material, "form": form,
        "movimentacoes": Paginator(material.movimentacoes.select_related("usuario", "ordem"), 30).get_page(
            request.GET.get("pagina")),
        "reservas": material.reservas.filter(status__in=["RESERVADA", "APROVACAO_NEGATIVO"]).select_related("ordem")})


@permissao_requerida("compras.solicitar", "compras.gerenciar")
def compras(request):
    user = request.user
    if request.method == "POST":
        acao = request.POST.get("acao")
        try:
            if acao == "criar":
                itens = []
                for i in range(1, 6):
                    desc = request.POST.get(f"item_{i}_descricao", "").strip()
                    mat = MaterialEstoque.objects.filter(pk=request.POST.get(f"item_{i}_material") or None).first()
                    if not desc and mat:
                        desc = mat.nome
                    if not desc:
                        continue
                    try:
                        qtd = Decimal(request.POST.get(f"item_{i}_quantidade", "1").replace(",", ".") or "1")
                        custo = Decimal(request.POST.get(f"item_{i}_custo", "0").replace(",", ".") or "0")
                    except InvalidOperation:
                        raise ErroEstoque(f"Valores inválidos no item {i}.")
                    itens.append({"descricao": desc, "material": mat, "quantidade": qtd, "custo_unitario": custo,
                                  "unidade": mat.unidade if mat else request.POST.get(f"item_{i}_unidade", "")})
                ordem = Ordem.objects.ativas().filter(pk=request.POST.get("ordem") or None).first()
                c = services.criar_compra(user, itens, ordem=ordem, justificativa=request.POST.get("justificativa", ""),
                                          request=request)
                messages.success(request, f"Compra #{c.pk} enviada para aprovação.")
            else:
                compra = get_object_or_404(Compra, pk=request.POST.get("compra_id"))
                if acao in ("aprovar", "reprovar"):
                    services.decidir_compra(compra, user, acao == "aprovar", request.POST.get("observacao", ""), request)
                elif acao == "comprada":
                    valor = Decimal((request.POST.get("valor_final") or "0").replace(",", "."))
                    services.registrar_compra(compra, user, request.POST.get("fornecedor", ""),
                                              request.POST.get("documento", ""), valor, request)
                elif acao == "receber":
                    services.receber_compra(compra, user, request)
                elif acao == "cancelar":
                    if compra.solicitante_id != user.pk and not user.pode("compras.gerenciar"):
                        raise PermissionDenied
                    if compra.status not in (Compra.Status.SOLICITADA, Compra.Status.APROVADA):
                        raise ErroEstoque("Esta compra não pode mais ser cancelada.")
                    compra.status = Compra.Status.CANCELADA
                    compra.save(update_fields=["status"])
                messages.success(request, "Compra atualizada.")
        except (ErroEstoque, PermissionDenied, InvalidOperation) as exc:
            messages.error(request, str(exc) or "Sem permissão.")
        return redirect("estoque:compras")
    qs = Compra.objects.select_related("ordem", "solicitante", "aprovador").prefetch_related("itens")
    if not user.pode("compras.gerenciar"):
        qs = qs.filter(solicitante=user)
    if request.GET.get("status"):
        qs = qs.filter(status=request.GET["status"])
    return render(request, "estoque/compras.html", {
        "pagina": Paginator(qs, 25).get_page(request.GET.get("pagina")), "status_lista": Compra.Status.choices,
        "materiais": MaterialEstoque.objects.filter(ativo=True),
        "ordens": Ordem.objects.visiveis_para(user).abertas().only("pk", "numero", "titulo")[:300],
        "linhas": range(1, 6)})


@permissao_requerida("custos.gerenciar")
def custos(request, pk):
    ordem = get_object_or_404(Ordem.objects.ativas(), pk=pk)
    form = CustoForm(request.POST or None, request.FILES or None)
    if request.method == "POST":
        try:
            if request.POST.get("acao") == "orcamento":
                valor = Decimal((request.POST.get("valor_previsto") or "0").replace(",", "."))
                Ordem.objects.filter(pk=ordem.pk).update(valor_previsto_orcamento=valor)
                messages.success(request, "Orçamento atualizado.")
            elif request.POST.get("acao") == "remover":
                services.remover_custo(get_object_or_404(CustoDocumento, pk=request.POST.get("custo_id"), ordem=ordem),
                                       request.user, request)
                messages.success(request, "Custo removido.")
            elif form.is_valid():
                services.adicionar_custo(ordem, request.user, form.cleaned_data, request.FILES.get("comprovante"), request)
                messages.success(request, "Custo lançado.")
            else:
                raise ErroEstoque("Revise os campos do custo.")
        except (ErroEstoque, InvalidOperation) as exc:
            messages.error(request, str(exc))
        return redirect("estoque:custos", pk=pk)
    services.recalcular_custos(ordem)
    return render(request, "estoque/custos.html", {
        "ordem": ordem, "form": form, "custos": ordem.custos.select_related("registrado_por"),
        "materiais": ordem.materiais.all(), "compras": ordem.compras.all()})


@permissao_requerida("custos.gerenciar", "os.visualizar_todas")
def custo_arquivo(request, pk):
    custo = get_object_or_404(CustoDocumento.objects.select_related("ordem"), pk=pk)
    if not pode_ver(request.user, custo.ordem):
        raise PermissionDenied
    return responder_arquivo(custo.arquivo, nome_download=custo.nome_original)


@permissao_requerida("estoque.gerenciar")
def movimentacoes(request):
    qs = Movimentacao.objects.select_related("material", "usuario", "ordem")
    if request.GET.get("tipo"):
        qs = qs.filter(tipo=request.GET["tipo"])
    if request.GET.get("q"):
        qs = qs.filter(Q(material__nome__icontains=request.GET["q"]) | Q(ordem__numero__icontains=request.GET["q"]))
    return render(request, "estoque/movimentacoes.html", {
        "pagina": Paginator(qs, 50).get_page(request.GET.get("pagina")), "tipos": Movimentacao.Tipo.choices})
