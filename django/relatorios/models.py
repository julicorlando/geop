from django.conf import settings
from django.db import models
from django.utils import timezone

User = settings.AUTH_USER_MODEL


class CotaRelatorio(models.Model):
    """Franquia de emissões do gestor (PHP: 10 relatórios; renovada com token do TI)."""

    usuario = models.OneToOneField(User, on_delete=models.CASCADE, related_name="cota_relatorios")
    usados = models.PositiveIntegerField(default=0)
    renovada_em = models.DateTimeField(null=True, blank=True)


class EmissaoRelatorio(models.Model):
    usuario = models.ForeignKey(User, on_delete=models.CASCADE, related_name="+")
    tipo = models.CharField(max_length=40)
    filtros = models.JSONField(default=dict, blank=True)
    criado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-criado_em"]


class TokenRelatorio(models.Model):
    """Token gerado pelo TI para renovar a franquia de um gestor."""

    token_hash = models.CharField(max_length=64, unique=True)
    gerado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    destinatario = models.ForeignKey(User, null=True, blank=True, on_delete=models.CASCADE, related_name="+",
                                     help_text="Em branco: qualquer gestor pode usar.")
    expira_em = models.DateTimeField()
    usado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    usado_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)
