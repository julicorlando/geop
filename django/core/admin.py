from django.contrib import admin

from .models import AuditoriaEvento, Categoria, Local, Notificacao

admin.site.register(Local, list_display=["nome", "ativo"], search_fields=["nome"])
admin.site.register(Categoria, list_display=["nome", "ativo", "exige_foto_antes", "exige_foto_depois"])
admin.site.register(Notificacao, list_display=["titulo", "usuario", "tipo", "criado_em", "lida_em"],
                    list_filter=["tipo"], raw_id_fields=["usuario"])


@admin.register(AuditoriaEvento)
class AuditoriaAdmin(admin.ModelAdmin):
    list_display = ["criado_em", "usuario", "acao", "entidade", "entidade_id", "campo"]
    list_filter = ["acao", "entidade"]
    search_fields = ["valor_anterior", "valor_novo"]

    def has_change_permission(self, request, obj=None):
        return False

    def has_delete_permission(self, request, obj=None):
        return False

    def has_add_permission(self, request):
        return False
