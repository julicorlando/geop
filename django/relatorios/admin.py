from django.contrib import admin

from .models import EmissaoRelatorio

admin.site.register(EmissaoRelatorio, list_display=["criado_em", "usuario", "tipo"])
