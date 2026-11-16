from django.urls import path

from . import views

app_name = "ambientes"
urlpatterns = [
    path("", views.lista, name="lista"),
    path("novo/", views.ambiente_form, name="novo"),
    path("<int:pk>/editar/", views.ambiente_form, name="editar"),
    path("<int:pk>/qr/", views.imprimir_qr, name="imprimir_qr"),
    path("<int:pk>/qr/regenerar/", views.regenerar_token, name="regenerar"),
    path("acoes/nova/", views.acao_form, name="acao_nova"),
    path("acoes/<int:pk>/", views.acao_form, name="acao_editar"),
    path("validacoes/", views.pendentes, name="pendentes"),
    path("validacoes/<int:pk>/", views.validar, name="validar"),
    path("relatorios/", views.relatorios, name="relatorios"),
    path("foto/<int:pk>/", views.foto, name="foto"),
    path("foto/<int:pk>/mini/", views.foto, {"miniatura": True}, name="foto_mini"),
    path("q/<str:token>/", views.publico, name="publico"),
    path("q/<str:token>/registrar/", views.registro, name="registro"),
    path("q/<str:token>/foto/<int:pk>/", views.foto_publica, name="foto_publica"),
    path("q/<str:token>/foto/<int:pk>/mini/", views.foto_publica, {"miniatura": True}, name="foto_publica_mini"),
]
