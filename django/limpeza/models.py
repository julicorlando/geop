from django.conf import settings
from django.db import models
from django.utils import timezone

from core.models import normalizar

TERMOS_ASG = ("asg", "auxiliar de servicos gerais", "servicos gerais", "limpeza", "higienizacao")


class OrganogramaNo(models.Model):
    """Colaborador da equipe de limpeza (organograma)."""

    parent = models.ForeignKey("self", null=True, blank=True, on_delete=models.SET_NULL, related_name="filhos",
                               verbose_name="superior")
    usuario = models.ForeignKey(settings.AUTH_USER_MODEL, null=True, blank=True, on_delete=models.SET_NULL,
                                related_name="nos_limpeza")
    nome_colaborador = models.CharField(max_length=160)
    cargo = models.CharField(max_length=120)
    setor = models.CharField(max_length=120, blank=True)
    turno = models.CharField(max_length=80, blank=True)
    contato = models.CharField(max_length=120, blank=True)
    observacao = models.TextField(blank=True)
    posicao = models.PositiveSmallIntegerField(default=0)
    ativo = models.BooleanField(default=True)
    criado_por = models.ForeignKey(settings.AUTH_USER_MODEL, null=True, blank=True, on_delete=models.SET_NULL,
                                   related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ["posicao", "nome_colaborador"]
        verbose_name = "colaborador da limpeza"
        verbose_name_plural = "organograma da limpeza"

    def __str__(self):
        return f"{self.nome_colaborador} — {self.cargo}"

    @property
    def eh_supervisor(self):
        perfil = self.usuario.perfil_codigo if self.usuario_id else ""
        return "supervisor" in normalizar(self.cargo) or perfil == "SUPERVISOR_ASG"

    @property
    def eh_asg(self):
        if self.eh_supervisor:
            return False
        texto = normalizar(f"{self.cargo} {self.setor}").strip()
        return not texto or any(t in texto for t in TERMOS_ASG)


class PlanoGrupo(models.Model):
    chave = models.CharField(max_length=80, unique=True)
    nome = models.CharField(max_length=220)
    descricao = models.TextField(blank=True)
    posicao = models.PositiveSmallIntegerField(default=0)
    ativo = models.BooleanField(default=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["posicao", "nome"]
        verbose_name = "grupo do plano de limpeza"
        verbose_name_plural = "grupos do plano de limpeza"

    def __str__(self):
        return self.nome


class PlanoTarefa(models.Model):
    grupo = models.ForeignKey(PlanoGrupo, on_delete=models.CASCADE, related_name="tarefas")
    chave = models.CharField(max_length=80)
    descricao = models.CharField(max_length=500)
    observacao = models.TextField(blank=True)
    periodicidade = models.CharField(max_length=40, default="Diária")
    exige_foto = models.BooleanField(default=False)
    exige_observacao = models.BooleanField(default=False)
    posicao = models.PositiveSmallIntegerField(default=0)
    ativo = models.BooleanField(default=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["grupo__posicao", "posicao", "descricao"]
        unique_together = [("grupo", "chave")]
        verbose_name = "tarefa do plano de limpeza"
        verbose_name_plural = "tarefas do plano de limpeza"

    def __str__(self):
        return self.descricao
