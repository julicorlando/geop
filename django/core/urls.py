from django.urls import path, re_path

from . import sistema, views

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
    path("notificacoes/api/", views.notificacoes_api, name="notificacoes_api"),
    path("sistema/configuracoes/", sistema.configuracoes, name="configuracoes"),
    path("sistema/saude/", sistema.saude, name="saude"),
    path("sistema/arquivos/", sistema.arquivos, name="arquivos"),
    path("sistema/backups/", sistema.backups, name="backups"),
    path("manifest.webmanifest", views.manifest, name="manifest"),
    path("sw.js", views.service_worker, name="sw"),
    # Links antigos do PHP (QR Codes impressos e links públicos já enviados continuam funcionando).
    re_path(r"^os-publica(?:\.php)?$", views.legado_os_publica),
    re_path(r"^ambiente_publico(?:\.php)?$", views.legado_ambiente_publico),
    re_path(r"^ordem_detalhe(?:\.php)?$", views.legado_ordem_detalhe),
    re_path(r"^acesso_qr(?:\.php)?$", views.legado_credencial),
    re_path(r"^controle_acesso(?:\.php)?$", views.legado_controle),
    re_path(r"^api_iot(?:\.php)?$", views.legado_api_iot),
    re_path(r"^solicitar_acesso(?:\.php)?$", views.legado_solicitar_acesso),
]
