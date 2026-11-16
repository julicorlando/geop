from django.urls import path

from django.contrib.auth import views as auth_views

from . import views

app_name = "accounts"
urlpatterns = [
    path("entrar/", views.LoginView.as_view(), name="login"),
    path("sair/", views.LogoutView.as_view(), name="logout"),
    path("senha/", views.mudar_senha, name="mudar_senha"),
    path("entrar/verificacao/", views.dois_fatores, name="dois_fatores"),
    path("seguranca/", views.seguranca, name="seguranca"),
    path("ativar/<str:token>/", views.ativar, name="ativar"),
    path("recuperar/", auth_views.PasswordResetView.as_view(
        template_name="accounts/recuperar.html", email_template_name="accounts/recuperar_email.txt",
        subject_template_name="accounts/recuperar_assunto.txt", success_url="/conta/recuperar/enviado/"),
        name="recuperar"),
    path("recuperar/enviado/", auth_views.PasswordResetDoneView.as_view(template_name="accounts/recuperar_enviado.html"),
         name="recuperar_enviado"),
    path("recuperar/<uidb64>/<token>/", auth_views.PasswordResetConfirmView.as_view(
        template_name="accounts/recuperar_confirmar.html", success_url="/conta/entrar/"), name="recuperar_confirmar"),
    path("usuarios/", views.usuarios, name="usuarios"),
    path("usuarios/novo/", views.usuario_form, name="usuario_novo"),
    path("usuarios/<int:pk>/", views.usuario_form, name="usuario_editar"),
    path("perfis/", views.perfis, name="perfis"),
    path("perfis/novo/", views.perfil_form, name="perfil_novo"),
    path("perfis/<int:pk>/", views.perfil_form, name="perfil_editar"),
]
