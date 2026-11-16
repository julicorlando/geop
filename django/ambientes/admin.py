from django.contrib import admin

from .models import Ambiente, AmbienteAcao, Registro

admin.site.register(Ambiente, list_display=["nome_publico", "local", "ativo", "frequencia_minutos"])
admin.site.register(AmbienteAcao, list_display=["nome", "categoria", "ativo"])
admin.site.register(Registro, list_display=["ambiente", "asg", "status", "finalizado_em"], list_filter=["status"])
