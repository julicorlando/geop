from django.contrib import admin

from .models import OrganogramaNo, PlanoGrupo, PlanoTarefa

admin.site.register(OrganogramaNo, list_display=["nome_colaborador", "cargo", "turno", "ativo"])
admin.site.register(PlanoGrupo, list_display=["nome", "posicao", "ativo"])
admin.site.register(PlanoTarefa, list_display=["descricao", "grupo", "periodicidade", "ativo"], list_filter=["grupo"])
