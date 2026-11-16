import secrets

from django.conf import settings
from django.db import models
from django.urls import reverse
from django.utils import timezone

from core.models import ArquivoBase, Local

User = settings.AUTH_USER_MODEL


def novo_token():
    return secrets.token_hex(24)  # 48 caracteres, como no PHP


class AmbienteAcao(models.Model):
    nome = models.CharField(max_length=160)
    descricao = models.CharField(max_length=255, blank=True)
    categoria = models.CharField(max_length=80, default="LIMPEZA")
    exige_foto = models.BooleanField(default=False)
    exige_observacao = models.BooleanField(default=False)
    ativo = models.BooleanField(default=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["nome"]
        verbose_name = "ação de limpeza"
        verbose_name_plural = "ações de limpeza"

    def __str__(self):
        return self.nome


class Ambiente(models.Model):
    local = models.ForeignKey(Local, on_delete=models.PROTECT, related_name="ambientes")
    nome_publico = models.CharField(max_length=160)
    descricao_publica = models.TextField(blank=True)
    token_publico = models.CharField(max_length=64, unique=True, default=novo_token)
    ativo = models.BooleanField(default=True)
    exibir_historico = models.BooleanField(default=True)
    exibir_fotos = models.BooleanField(default=False)
    historico_limite = models.PositiveSmallIntegerField(default=10)
    frequencia_minutos = models.PositiveIntegerField(default=60)
    exige_foto_asg = models.BooleanField(default=False)
    exige_foto_supervisor = models.BooleanField(default=True)
    acoes = models.ManyToManyField(AmbienteAcao, through="AmbienteAcaoVinculo", blank=True)
    gerado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    ultimo_acesso_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ["nome_publico"]
        verbose_name = "ambiente QR"
        verbose_name_plural = "ambientes QR"

    def __str__(self):
        return self.nome_publico

    def get_absolute_url(self):
        return reverse("ambientes:publico", args=[self.token_publico])

    def ultimo_validado(self):
        return self.registros.filter(status=Registro.Status.VALIDADA).order_by("-validado_em", "-finalizado_em").first()

    def estado_atual(self):
        """Mesma regra de `ambiente_qr_current_state` do PHP."""
        ultimo = self.ultimo_validado()
        if not ultimo:
            return "SEM_REGISTRO", "Sem limpeza validada", "neutro"
        referencia = ultimo.validado_em or ultimo.finalizado_em
        minutos = (timezone.now() - referencia).total_seconds() / 60
        if minutos > max(1, self.frequencia_minutos):
            return "ATRASADO", "Limpeza fora da frequência prevista", "perigo"
        return "HIGIENIZADO", "Ambiente higienizado", "sucesso"

    def acoes_ativas(self):
        return (AmbienteAcaoVinculo.objects.filter(ambiente=self, ativo=True, acao__ativo=True)
                .select_related("acao").order_by("posicao", "acao__nome"))


class AmbienteAcaoVinculo(models.Model):
    ambiente = models.ForeignKey(Ambiente, on_delete=models.CASCADE)
    acao = models.ForeignKey(AmbienteAcao, on_delete=models.CASCADE)
    obrigatoria = models.BooleanField(default=False)
    posicao = models.PositiveSmallIntegerField(default=0)
    ativo = models.BooleanField(default=True)

    class Meta:
        unique_together = [("ambiente", "acao")]
        ordering = ["posicao"]


class Registro(models.Model):
    class Status(models.TextChoices):
        AGUARDANDO_VALIDACAO = "AGUARDANDO_VALIDACAO", "Aguardando validação"
        VALIDADA = "VALIDADA", "Validada"
        REPROVADA = "REPROVADA", "Reprovada"
        CORRECAO_SOLICITADA = "CORRECAO_SOLICITADA", "Correção solicitada"
        CANCELADA = "CANCELADA", "Cancelada"

    ambiente = models.ForeignKey(Ambiente, on_delete=models.PROTECT, related_name="registros")
    ordem = models.ForeignKey("ordens.Ordem", null=True, blank=True, on_delete=models.SET_NULL,
                              related_name="registros_ambiente")
    asg = models.ForeignKey(User, on_delete=models.PROTECT, related_name="registros_limpeza",
                            help_text="Quem lançou o registro.")
    status = models.CharField(max_length=25, choices=Status.choices, default=Status.AGUARDANDO_VALIDACAO, db_index=True)
    iniciado_em = models.DateTimeField(null=True, blank=True)
    finalizado_em = models.DateTimeField(default=timezone.now)
    observacao_asg = models.TextField(blank=True)
    problema_encontrado = models.TextField(blank=True)
    insumos_repostos = models.TextField(blank=True)
    supervisor = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    validado_em = models.DateTimeField(null=True, blank=True)
    observacao_supervisor = models.TextField(blank=True)
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ["-finalizado_em", "-id"]
        indexes = [models.Index(fields=["ambiente", "status", "finalizado_em"])]
        verbose_name = "registro de limpeza"
        verbose_name_plural = "registros de limpeza"

    def __str__(self):
        return f"{self.ambiente} — {self.finalizado_em:%d/%m %H:%M}"

    def equipe_nomes(self):
        return ", ".join(r.nome for r in self.responsaveis.all()) or str(self.asg)


class RegistroItem(models.Model):
    registro = models.ForeignKey(Registro, on_delete=models.CASCADE, related_name="itens")
    acao = models.ForeignKey(AmbienteAcao, null=True, blank=True, on_delete=models.SET_NULL)
    tarefa = models.ForeignKey("limpeza.PlanoTarefa", null=True, blank=True, on_delete=models.SET_NULL)
    descricao = models.CharField(max_length=255)
    concluida = models.BooleanField(default=True)
    observacao = models.CharField(max_length=255, blank=True)


class RegistroResponsavel(models.Model):
    registro = models.ForeignKey(Registro, on_delete=models.CASCADE, related_name="responsaveis")
    membro = models.ForeignKey("limpeza.OrganogramaNo", null=True, blank=True, on_delete=models.SET_NULL)
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    nome = models.CharField(max_length=160)
    principal = models.BooleanField(default=False)

    class Meta:
        ordering = ["-principal", "nome"]


class RegistroFoto(ArquivoBase):
    class Origem(models.TextChoices):
        ASG = "ASG", "ASG"
        SUPERVISOR = "SUPERVISOR", "Supervisor"
        GESTOR = "GESTOR", "Gestor"

    class Tipo(models.TextChoices):
        ANTES = "ANTES", "Antes"
        DURANTE = "DURANTE", "Durante"
        DEPOIS = "DEPOIS", "Depois"
        INSPECAO = "INSPECAO", "Inspeção"
        PROBLEMA = "PROBLEMA", "Problema"

    PASTA = "ambientes"

    registro = models.ForeignKey(Registro, on_delete=models.CASCADE, related_name="fotos")
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    origem = models.CharField(max_length=10, choices=Origem.choices, default=Origem.ASG)
    tipo = models.CharField(max_length=10, choices=Tipo.choices, default=Tipo.DEPOIS)
    descricao = models.CharField(max_length=255, blank=True)
    publica = models.BooleanField(default=False)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["criado_em", "id"]


class Validacao(models.Model):
    registro = models.ForeignKey(Registro, on_delete=models.CASCADE, related_name="validacoes")
    supervisor = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    resultado = models.CharField(max_length=25, choices=[
        ("APROVADA", "Aprovada"), ("REPROVADA", "Reprovada"), ("CORRECAO_SOLICITADA", "Correção solicitada")])
    observacao = models.TextField(blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["-criado_em"]
        verbose_name = "validação"
        verbose_name_plural = "validações"


class AcessoQR(models.Model):
    ambiente = models.ForeignKey(Ambiente, on_delete=models.CASCADE, related_name="acessos")
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    ip_hash = models.CharField(max_length=64, blank=True)
    user_agent_hash = models.CharField(max_length=64, blank=True)
    acessado_em = models.DateTimeField(default=timezone.now, db_index=True)
