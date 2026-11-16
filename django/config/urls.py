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
    path("", include("core.urls")),
]

handler403 = "core.views.erro_403"
handler404 = "core.views.erro_404"
