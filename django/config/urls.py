from django.contrib import admin
from django.urls import include, path

admin.site.site_header = "GEOP · Administração"
admin.site.site_title = "GEOP"

urlpatterns = [
    path("admin/", admin.site.urls),
    path("conta/", include("accounts.urls")),
    path("ordens/", include("ordens.urls")),
    path("limpeza/", include("limpeza.urls")),
    path("ambientes/", include("ambientes.urls")),
    path("patrimonio/", include("patrimonio.urls")),
    path("estoque/", include("estoque.urls")),
    path("operacao/", include("operacao.urls")),
    path("conhecimento/", include("conhecimento.urls")),
    path("relatorios/", include("relatorios.urls")),
    path("cftv/", include("cftv.urls")),
    path("chat/", include("chat.urls")),
    path("ia/", include("ia.urls")),
    path("iot/", include("iot.urls")),
    path("", include("core.urls")),
]

handler403 = "core.views.erro_403"
handler404 = "core.views.erro_404"
