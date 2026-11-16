from django.urls import path

from . import views

app_name = "cftv"
urlpatterns = [
    path("", views.painel, name="painel"),
    path("plantao/<int:pk>/", views.relatorio, name="relatorio"),
    path("liberacoes/", views.liberacoes, name="liberacoes"),
    path("historico/", views.historico, name="historico"),
    path("controle/", views.controle, name="controle"),
    # públicas
    path("acesso/solicitar/<str:token>/", views.solicitar, name="solicitar"),
    path("acesso/acompanhar/<str:chave>/", views.acompanhar, name="acompanhar"),
    path("acesso/credencial/", views.credencial, name="credencial"),
    path("acesso/consultar/", views.consultar, name="consultar"),
]
