from django.urls import path

from . import views

app_name = "chat"
urlpatterns = [
    path("", views.inicio, name="inicio"),
    path("<int:pk>/", views.inicio, name="conversa"),
    path("api/conversas/", views.api_conversas, name="api_conversas"),
    path("api/<int:pk>/mensagens/", views.api_mensagens, name="api_mensagens"),
    path("api/<int:pk>/enviar/", views.api_enviar, name="api_enviar"),
    path("api/mensagem/<int:pk>/", views.api_alterar, name="api_alterar"),
    path("arquivo/<int:pk>/", views.arquivo, name="arquivo"),
]
