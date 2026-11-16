from django.core.paginator import Paginator
from django.db.models import Q
from django.http import JsonResponse
from django.shortcuts import render

from core.permissions import permissao_requerida
from ordens.models import OrdemPatrimonio

from . import services


@permissao_requerida("patrimonio.consultar")
def consultar(request):
    codigo = request.GET.get("codigo", "")
    resultados = services.buscar(codigo) if codigo else []
    historico = OrdemPatrimonio.objects.select_related("ordem", "vinculado_por")
    if codigo:
        historico = historico.filter(Q(numero_patrimonio__icontains=codigo) | Q(codigo_barras__icontains=codigo))
    if request.headers.get("Accept", "").startswith("application/json"):
        return JsonResponse({"configurado": services.configurado(), "resultados": resultados})
    return render(request, "patrimonio/consultar.html", {
        "codigo": codigo, "resultados": resultados, "configurado": services.configurado(),
        "historico": Paginator(historico, 30).get_page(request.GET.get("pagina")),
    })
