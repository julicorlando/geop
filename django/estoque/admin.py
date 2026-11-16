from django.contrib import admin

from .models import Compra, CustoDocumento, MaterialEstoque, Movimentacao, Reserva

admin.site.register(MaterialEstoque, list_display=["nome", "quantidade_atual", "estoque_minimo", "custo_unitario", "ativo"],
                    search_fields=["nome"])
admin.site.register(Movimentacao, list_display=["criado_em", "material", "tipo", "quantidade", "ordem"], raw_id_fields=["ordem"])
admin.site.register(Reserva, list_display=["ordem", "material", "quantidade", "status"], raw_id_fields=["ordem", "item_os"])
admin.site.register(Compra, list_display=["pk", "status", "solicitante", "valor_previsto", "valor_final"], raw_id_fields=["ordem"])
admin.site.register(CustoDocumento, list_display=["ordem", "tipo", "valor"], raw_id_fields=["ordem"])
