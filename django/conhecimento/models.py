from django.conf import settings
from django.db import models
from django.urls import reverse
from django.utils import timezone

from core.models import Categoria

User = settings.AUTH_USER_MODEL


class Artigo(models.Model):
    class Status(models.TextChoices):
        RASCUNHO = "RASCUNHO", "Rascunho"
        PUBLICADO = "PUBLICADO", "Publicado"
        INATIVO = "INATIVO", "Inativo"

    titulo = models.CharField(max_length=200)
    categoria = models.ForeignKey(Categoria, null=True, blank=True, on_delete=models.SET_NULL, related_name="artigos")
    problema = models.TextField(blank=True, help_text="Sintomas / como o problema aparece.")
    conteudo = models.TextField("solução / procedimento")
    palavras_chave = models.CharField(max_length=255, blank=True)
    links = models.TextField(blank=True, help_text="Um link por linha (manuais, vídeos, fornecedores).")
    origem_ordem = models.OneToOneField("ordens.Ordem", null=True, blank=True, on_delete=models.SET_NULL,
                                        related_name="artigo_gerado")
    versao = models.CharField(max_length=30, default="1.0")
    responsavel = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    revisar_em = models.DateField(null=True, blank=True)
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PUBLICADO, db_index=True)
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ["-atualizado_em"]

    def __str__(self):
        return self.titulo

    def get_absolute_url(self):
        return reverse("conhecimento:detalhe", args=[self.pk])


class Avaliacao(models.Model):
    artigo = models.ForeignKey(Artigo, on_delete=models.CASCADE, related_name="avaliacoes")
    usuario = models.ForeignKey(User, on_delete=models.CASCADE, related_name="+")
    util = models.BooleanField()
    comentario = models.CharField(max_length=500, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        unique_together = [("artigo", "usuario")]
