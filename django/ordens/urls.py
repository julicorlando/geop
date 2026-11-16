from django.urls import path

from . import views

app_name = "ordens"

urlpatterns = [
    path("", views.lista, name="lista"),
    path("nova/", views.nova, name="nova"),
    path("<int:pk>/", views.detalhe, name="detalhe"),
    path("<int:pk>/imprimir/", views.imprimir, name="imprimir"),
    path("anexo/<int:pk>/", views.arquivo, name="arquivo"),
    path("anexo/<int:pk>/mini/", views.arquivo, {"miniatura": True}, name="miniatura"),
    path("exclusoes/", views.exclusoes, name="exclusoes"),
    path("checklists/", views.checklists, name="checklists"),
    path("assistente/", views.assistente, name="assistente"),
    path("lixeira/", views.lixeira, name="lixeira"),
    path("lixeira/<int:pk>/restaurar/", views.restaurar, name="restaurar"),
    path("publica/<int:pk>/<str:token>/", views.publica, name="publica"),
    path("publica/<int:pk>/<str:token>/foto/<int:anexo_id>/", views.publica_arquivo, name="publica_arquivo"),
    path("publica/<int:pk>/<str:token>/foto/<int:anexo_id>/mini/", views.publica_arquivo, {"miniatura": True},
         name="publica_miniatura"),
]
