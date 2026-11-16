from django.urls import path

from . import views

app_name = "operacao"
urlpatterns = [
    path("preventivas/", views.preventivas, name="preventivas"),
    path("preventivas/nova/", views.preventiva_form, name="preventiva_nova"),
    path("preventivas/<int:pk>/", views.preventiva_form, name="preventiva_editar"),
    path("equipe/", views.equipe, name="equipe"),
    path("escalas/", views.escalas, name="escalas"),
    path("passagens/", views.passagens, name="passagens"),
    path("cronograma/", views.cronograma, name="cronograma"),
    path("minha-rota/", views.minha_rota, name="minha_rota"),
    path("servico-essencial/", views.servico_essencial, name="servico_essencial"),
    path("recomendar-agentes/", views.recomendar, name="recomendar"),
    path("aprovacoes/", views.aprovacoes, name="aprovacoes"),
]
