from django.contrib import admin

from .models import Anexo, Historico, Ordem, OrdemAgente, SolicitacaoExclusao


class AnexoInline(admin.TabularInline):
    model = Anexo
    extra = 0
    fields = ["tipo", "arquivo", "descricao", "tamanho", "tamanho_original", "criado_em"]
    readonly_fields = ["tamanho", "tamanho_original", "criado_em"]


class AgenteInline(admin.TabularInline):
    model = OrdemAgente
    extra = 0
    raw_id_fields = ["usuario", "criado_por"]


@admin.register(Ordem)
class OrdemAdmin(admin.ModelAdmin):
    list_display = ["numero", "titulo", "tipo", "status", "prioridade", "local", "agente", "criado_em"]
    list_filter = ["tipo", "status", "prioridade", "categoria"]
    search_fields = ["numero", "titulo"]
    raw_id_fields = ["solicitante", "gestor", "agente", "aprovado_por", "excluida_por"]
    inlines = [AgenteInline, AnexoInline]


admin.site.register(Historico, list_display=["ordem", "status_anterior", "status_novo", "usuario", "criado_em"],
                    raw_id_fields=["ordem", "usuario"])
admin.site.register(SolicitacaoExclusao, list_display=["ordem", "estado", "solicitado_por", "criado_em"],
                    raw_id_fields=["ordem", "solicitado_por", "analisado_por", "executado_por"])
