from django.conf import settings
from django.db import models
from django.utils import timezone

from core.models import ArquivoBase

User = settings.AUTH_USER_MODEL


class Conversa(models.Model):
    class Tipo(models.TextChoices):
        DIRETA = "DIRETA", "Direta"
        GRUPO = "GRUPO", "Grupo"

    tipo = models.CharField(max_length=10, choices=Tipo.choices, default=Tipo.DIRETA)
    nome = models.CharField(max_length=120, blank=True)
    chave_direta = models.CharField(max_length=40, null=True, blank=True, unique=True,
                                    help_text="'<menor id>-<maior id>' para conversas diretas.")
    criador = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-atualizado_em"]


class Participante(models.Model):
    conversa = models.ForeignKey(Conversa, on_delete=models.CASCADE, related_name="participantes")
    usuario = models.ForeignKey(User, on_delete=models.CASCADE, related_name="conversas_chat")
    ultima_leitura = models.DateTimeField(null=True, blank=True)
    entrou_em = models.DateTimeField(default=timezone.now)

    class Meta:
        unique_together = [("conversa", "usuario")]


class Mensagem(ArquivoBase):
    PASTA = "chat"

    arquivo = models.FileField(max_length=255, blank=True)
    conversa = models.ForeignKey(Conversa, on_delete=models.CASCADE, related_name="mensagens")
    remetente = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    texto = models.TextField(blank=True)
    resposta_a = models.ForeignKey("self", null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    editada_em = models.DateTimeField(null=True, blank=True)
    excluida_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["criado_em", "id"]
