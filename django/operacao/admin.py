from django.contrib import admin

from .models import CronogramaAgente, EquipeEstado, Escala, Especialidade, Feriado, PassagemTurno, Preventiva

admin.site.register(Preventiva, list_display=["titulo", "local", "proxima_execucao", "intervalo_dias", "ativo"])
admin.site.register(EquipeEstado, list_display=["usuario", "estado", "capacidade_maxima"])
admin.site.register(Especialidade, list_display=["usuario", "especialidade", "nivel"])
admin.site.register(Escala, list_display=["usuario", "data_servico", "tipo"])
admin.site.register(Feriado, list_display=["data", "nome", "local"])
admin.site.register(PassagemTurno, list_display=["criado_em", "entrega", "recebe", "lida_em"])
admin.site.register(CronogramaAgente, list_display=["semana_inicio", "dia_semana", "agente", "titulo", "status"])
