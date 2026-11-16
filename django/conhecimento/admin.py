from django.contrib import admin

from .models import Artigo

admin.site.register(Artigo, list_display=["titulo", "categoria", "status", "atualizado_em"], search_fields=["titulo"],
                    raw_id_fields=["origem_ordem"])
