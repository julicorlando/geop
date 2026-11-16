from django.conf import settings
from django.db import models
from django.utils import timezone

User = settings.AUTH_USER_MODEL


class Conversa(models.Model):
    usuario = models.ForeignKey(User, on_delete=models.CASCADE, related_name="conversas_ia")
    titulo = models.CharField(max_length=160, default="Nova conversa")
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-atualizado_em"]


class Mensagem(models.Model):
    conversa = models.ForeignKey(Conversa, on_delete=models.CASCADE, related_name="mensagens")
    papel = models.CharField(max_length=10, choices=[("user", "Usuário"), ("assistant", "GEOP IA")])
    conteudo = models.TextField()
    fontes = models.JSONField(default=list, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["criado_em", "id"]


class Execucao(models.Model):
    """Rastreabilidade: cada pergunta, ferramentas usadas, provedor e tempo."""

    usuario = models.ForeignKey(User, null=True, on_delete=models.SET_NULL, related_name="+")
    conversa = models.ForeignKey(Conversa, null=True, blank=True, on_delete=models.SET_NULL, related_name="execucoes")
    tipo = models.CharField(max_length=30, default="CHAT")
    provedor = models.CharField(max_length=30, blank=True)
    modelo = models.CharField(max_length=120, blank=True)
    ferramentas = models.JSONField(default=list, blank=True)
    sucesso = models.BooleanField(default=True)
    erro = models.TextField(blank=True)
    duracao_ms = models.PositiveIntegerField(default=0)
    criado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-criado_em"]
