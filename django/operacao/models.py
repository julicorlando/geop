from datetime import timedelta

from django.conf import settings
from django.db import models
from django.utils import timezone

from core.models import Categoria, Local

User = settings.AUTH_USER_MODEL


class EquipeEstado(models.Model):
    class Estado(models.TextChoices):
        DISPONIVEL = "DISPONIVEL", "Disponível"
        EM_ATENDIMENTO = "EM_ATENDIMENTO", "Em atendimento"
        INTERVALO = "INTERVALO", "Intervalo"
        AUSENTE = "AUSENTE", "Ausente"
        FERIAS = "FERIAS", "Férias"
        AFASTADO = "AFASTADO", "Afastado"

    INDISPONIVEIS = {"AUSENTE", "FERIAS", "AFASTADO"}

    usuario = models.OneToOneField(User, on_delete=models.CASCADE, related_name="estado_equipe")
    estado = models.CharField(max_length=20, choices=Estado.choices, default=Estado.DISPONIVEL)
    capacidade_maxima = models.PositiveSmallIntegerField(default=5, help_text="Máximo de ordens ativas ao mesmo tempo.")
    observacao = models.CharField(max_length=255, blank=True)
    atualizado_em = models.DateTimeField(auto_now=True)

    class Meta:
        verbose_name = "estado da equipe"
        verbose_name_plural = "estado da equipe"


class Especialidade(models.Model):
    usuario = models.ForeignKey(User, on_delete=models.CASCADE, related_name="especialidades")
    especialidade = models.CharField(max_length=100, help_text="Use o nome da categoria (ex.: Elétrica).")
    nivel = models.PositiveSmallIntegerField(default=1, choices=[(1, "Básico"), (2, "Intermediário"), (3, "Avançado")])

    class Meta:
        unique_together = [("usuario", "especialidade")]
        ordering = ["usuario__nome", "especialidade"]


class Escala(models.Model):
    class Tipo(models.TextChoices):
        TRABALHO = "TRABALHO", "Trabalho"
        FOLGA = "FOLGA", "Folga"
        FERIAS = "FERIAS", "Férias"
        PLANTAO = "PLANTAO", "Plantão"

    usuario = models.ForeignKey(User, on_delete=models.CASCADE, related_name="escalas")
    funcao = models.CharField(max_length=80, default="Agente de Manutenção")
    posto = models.CharField(max_length=100, default="Manutenção")
    data_servico = models.DateField(db_index=True)
    tipo = models.CharField(max_length=10, choices=Tipo.choices, default=Tipo.TRABALHO)
    jornada_horas = models.PositiveSmallIntegerField(default=12)
    hora_inicio = models.TimeField(null=True, blank=True)
    hora_fim = models.TimeField(null=True, blank=True)
    criado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")

    class Meta:
        unique_together = [("usuario", "data_servico")]
        ordering = ["data_servico", "usuario__nome"]


class Feriado(models.Model):
    data = models.DateField()
    nome = models.CharField(max_length=160)
    local = models.ForeignKey(Local, null=True, blank=True, on_delete=models.CASCADE)
    ativo = models.BooleanField(default=True)

    class Meta:
        unique_together = [("data", "local")]
        ordering = ["data"]

    def __str__(self):
        return f"{self.data:%d/%m/%Y} — {self.nome}"


class PassagemTurno(models.Model):
    entrega = models.ForeignKey(User, on_delete=models.PROTECT, related_name="passagens_entregues")
    recebe = models.ForeignKey(User, on_delete=models.PROTECT, related_name="passagens_recebidas")
    pendencias = models.TextField(blank=True)
    ocorrencias_criticas = models.TextField(blank=True)
    equipamentos_indisponiveis = models.TextField(blank=True)
    servicos_andamento = models.TextField(blank=True)
    observacoes = models.TextField(blank=True)
    lida_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["-criado_em"]
        verbose_name = "passagem de turno"
        verbose_name_plural = "passagens de turno"


class CronogramaAgente(models.Model):
    class Status(models.TextChoices):
        PLANEJADO = "PLANEJADO", "Planejado"
        EM_ANDAMENTO = "EM_ANDAMENTO", "Em andamento"
        CONCLUIDO = "CONCLUIDO", "Concluído"
        CANCELADO = "CANCELADO", "Cancelado"

    DIAS = [(1, "Segunda"), (2, "Terça"), (3, "Quarta"), (4, "Quinta"), (5, "Sexta"), (6, "Sábado"), (7, "Domingo")]

    agente = models.ForeignKey(User, on_delete=models.CASCADE, related_name="cronograma")
    gestor = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    titulo = models.CharField(max_length=255)
    descricao = models.TextField(blank=True)
    local = models.ForeignKey(Local, null=True, blank=True, on_delete=models.SET_NULL)
    dia_semana = models.PositiveSmallIntegerField(choices=DIAS)
    hora_inicio = models.TimeField()
    hora_fim = models.TimeField()
    semana_inicio = models.DateField(db_index=True)
    status = models.CharField(max_length=15, choices=Status.choices, default=Status.PLANEJADO)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["semana_inicio", "dia_semana", "hora_inicio"]
        verbose_name = "tarefa do cronograma"
        verbose_name_plural = "cronograma dos agentes"

    @property
    def data(self):
        return self.semana_inicio + timedelta(days=self.dia_semana - 1)


class Preventiva(models.Model):
    titulo = models.CharField(max_length=180)
    descricao = models.TextField(blank=True)
    local = models.ForeignKey(Local, on_delete=models.PROTECT)
    categoria = models.ForeignKey(Categoria, on_delete=models.PROTECT)
    agente = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+",
                               help_text="Em branco: o GEOP recomenda um agente disponível.")
    prioridade = models.CharField(max_length=10, default="MEDIA", choices=[
        ("BAIXA", "Baixa"), ("MEDIA", "Média"), ("ALTA", "Alta"), ("CRITICA", "Crítica")])
    intervalo_dias = models.PositiveIntegerField(default=30)
    proxima_execucao = models.DateField(db_index=True)
    ultimo_gerado_em = models.DateTimeField(null=True, blank=True)
    ativo = models.BooleanField(default=True)
    criado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["proxima_execucao", "titulo"]
        verbose_name = "manutenção preventiva"
        verbose_name_plural = "manutenções preventivas"

    def __str__(self):
        return self.titulo


class StatusTempo(models.Model):
    """Quanto tempo cada ordem ficou em cada status (indicadores de gargalo)."""

    ordem = models.ForeignKey("ordens.Ordem", on_delete=models.CASCADE, related_name="tempos_status")
    status = models.CharField(max_length=40)
    iniciado_em = models.DateTimeField()
    finalizado_em = models.DateTimeField(null=True, blank=True)
    segundos = models.PositiveBigIntegerField(null=True, blank=True)
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")

    class Meta:
        ordering = ["iniciado_em"]


class SlaEscalonamento(models.Model):
    ordem = models.ForeignKey("ordens.Ordem", on_delete=models.CASCADE, related_name="escalonamentos")
    nivel = models.CharField(max_length=20)
    destinatario = models.ForeignKey(User, null=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        unique_together = [("ordem", "nivel", "destinatario")]
