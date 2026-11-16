from django.urls import path, re_path

from . import views

app_name = "core"
urlpatterns = [
    path("", views.dashboard, name="dashboard"),
    path("notificacoes/", views.notificacoes, name="notificacoes"),
    path("notificacoes/ler/", views.notificacao_ler, name="notificacoes_ler_todas"),
    path("notificacoes/<int:pk>/ler/", views.notificacao_ler, name="notificacao_ler"),
    path("cadastros/<str:tipo>/", views.cadastros, name="cadastros"),
    path("cadastros/<str:tipo>/novo/", views.cadastro_form, name="cadastro_novo"),
    path("cadastros/<str:tipo>/<int:pk>/", views.cadastro_form, name="cadastro_editar"),
    path("auditoria/", views.auditoria, name="auditoria"),
    # Links antigos do PHP (QR Codes impressos e links públicos já enviados continuam funcionando).
    re_path(r"^os-publica(?:\.php)?$", views.legado_os_publica),
    re_path(r"^ambiente_publico(?:\.php)?$", views.legado_ambiente_publico),
    re_path(r"^acesso_qr(?:\.php)?$", views.legado_ambiente_publico),
    re_path(r"^ordem_detalhe(?:\.php)?$", views.legado_ordem_detalhe),
]
