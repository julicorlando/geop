from django.contrib import admin

from .models import Execucao

admin.site.register(Execucao, list_display=["criado_em", "usuario", "tipo", "provedor", "sucesso", "duracao_ms"])
