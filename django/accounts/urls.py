from django.urls import path

from . import views

app_name = "accounts"
urlpatterns = [
    path("entrar/", views.LoginView.as_view(), name="login"),
    path("sair/", views.LogoutView.as_view(), name="logout"),
    path("senha/", views.mudar_senha, name="mudar_senha"),
    path("usuarios/", views.usuarios, name="usuarios"),
    path("usuarios/novo/", views.usuario_form, name="usuario_novo"),
    path("usuarios/<int:pk>/", views.usuario_form, name="usuario_editar"),
    path("perfis/", views.perfis, name="perfis"),
    path("perfis/novo/", views.perfil_form, name="perfil_novo"),
    path("perfis/<int:pk>/", views.perfil_form, name="perfil_editar"),
]
