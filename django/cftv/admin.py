from django.contrib import admin

from .models import Destino, Liberacao, LinkSolicitacao, RelatorioPlantao, SolicitacaoAcesso

admin.site.register(RelatorioPlantao, list_display=["data_plantao", "turno", "operador", "status"])
admin.site.register(Liberacao, list_display=["nome_completo", "destino_nome", "data_acesso", "status"])
admin.site.register(SolicitacaoAcesso, list_display=["protocolo", "nome_completo", "status", "data_acesso"],
                    search_fields=["protocolo", "nome_completo", "cpf"])
admin.site.register(LinkSolicitacao, list_display=["titulo", "expira_em", "usos", "limite_usos", "ativo"])
admin.site.register(Destino, list_display=["nome", "tipo", "ativo"])
