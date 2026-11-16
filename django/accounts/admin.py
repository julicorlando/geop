from django.contrib import admin
from django.contrib.auth.admin import UserAdmin

from .models import Perfil, Permissao, Setor, Usuario


@admin.register(Usuario)
class UsuarioAdmin(UserAdmin):
    ordering = ["nome"]
    list_display = ["nome", "email", "perfil", "setor", "is_active", "last_login"]
    list_filter = ["perfil", "setor", "is_active", "is_staff"]
    search_fields = ["nome", "email"]
    fieldsets = [
        (None, {"fields": ["email", "password"]}),
        ("Dados", {"fields": ["nome", "telefone", "cargo", "perfil", "setor"]}),
        ("Acesso", {"fields": ["is_active", "is_staff", "is_superuser", "forcar_troca_senha", "last_login"]}),
    ]
    add_fieldsets = [(None, {"classes": ["wide"], "fields": ["email", "nome", "perfil", "password1", "password2"]})]
    readonly_fields = ["last_login"]
    filter_horizontal = []


@admin.register(Perfil)
class PerfilAdmin(admin.ModelAdmin):
    list_display = ["nome", "codigo", "ativo", "administrativo", "sistema"]
    filter_horizontal = ["permissoes"]


admin.site.register(Permissao, list_display=["codigo", "nome", "modulo"])
admin.site.register(Setor, list_display=["nome", "ativo"])
