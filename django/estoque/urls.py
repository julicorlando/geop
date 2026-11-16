from django.urls import path

from . import views

app_name = "estoque"
urlpatterns = [
    path("", views.lista, name="lista"),
    path("novo/", views.material_form, name="novo"),
    path("<int:pk>/", views.material_detalhe, name="material"),
    path("<int:pk>/editar/", views.material_form, name="editar"),
    path("movimentacoes/", views.movimentacoes, name="movimentacoes"),
    path("compras/", views.compras, name="compras"),
    path("custos/ordem/<int:pk>/", views.custos, name="custos"),
    path("custos/arquivo/<int:pk>/", views.custo_arquivo, name="custo_arquivo"),
]
