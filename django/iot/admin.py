from django.contrib import admin

from .models import AtivoRede, Dispositivo

admin.site.register(Dispositivo, list_display=["identificador", "tipo", "local", "status", "ultima_comunicacao"],
                    exclude=["api_key_hash"])
admin.site.register(AtivoRede, list_display=["nome", "tipo", "endereco_ip", "status"])
