import hashlib
import hmac

from django.conf import settings
from django.db import models
from django.db.models import Q
from django.urls import reverse
from django.utils import timezone

from core.models import ArquivoBase, Categoria, Local

User = settings.AUTH_USER_MODEL


class Status(models.TextChoices):
    ABERTA = "ABERTA", "Aberta"
    ATRIBUIDA = "ATRIBUIDA", "Atribuída"
    EM_ANDAMENTO = "EM_ANDAMENTO", "Em andamento"
    PAUSADA = "PAUSADA", "Pausada"
    AGUARDANDO_MATERIAL = "AGUARDANDO_MATERIAL", "Aguardando material"
    CONCLUIDA_PELO_AGENTE = "CONCLUIDA_PELO_AGENTE", "Aguardando aprovação"
    REPROVADA = "REPROVADA", "Reprovada"
    ENCERRADA = "ENCERRADA", "Encerrada"


class Prioridade(models.TextChoices):
    BAIXA = "BAIXA", "Baixa"
    MEDIA = "MEDIA", "Média"
    ALTA = "ALTA", "Alta"
    CRITICA = "CRITICA", "Crítica"


STATUS_FINAIS = {Status.ENCERRADA}
STATUS_INATIVOS = {Status.ENCERRADA, Status.CONCLUIDA_PELO_AGENTE, Status.REPROVADA}
STATUS_AGENTE = [Status.EM_ANDAMENTO, Status.PAUSADA, Status.AGUARDANDO_MATERIAL, Status.CONCLUIDA_PELO_AGENTE]
STATUS_GESTOR = [Status.ABERTA, Status.ATRIBUIDA, Status.EM_ANDAMENTO, Status.PAUSADA,
                 Status.AGUARDANDO_MATERIAL, Status.REPROVADA, Status.ENCERRADA]


class OrdemQuerySet(models.QuerySet):
    def ativas(self):
        return self.filter(excluida_em__isnull=True)

    def visiveis_para(self, user):
        qs = self.ativas()
        if user.pode("os.visualizar_todas"):
            return qs
        return qs.filter(Q(agente=user) | Q(agentes__usuario=user) | Q(solicitante=user) | Q(gestor=user)).distinct()

    def abertas(self):
        return self.exclude(status=Status.ENCERRADA)

    def atrasadas(self):
        return self.abertas().exclude(status=Status.CONCLUIDA_PELO_AGENTE).filter(prazo__lt=timezone.now())


class Ordem(models.Model):
    class Tipo(models.TextChoices):
        OS = "OS", "Ordem de serviço"
        OL = "OL", "Ordem de limpeza"

    numero = models.CharField(max_length=30, unique=True)
    tipo = models.CharField(max_length=2, choices=Tipo.choices, default=Tipo.OS, db_index=True)
    titulo = models.CharField(max_length=150)
    descricao = models.TextField()
    local = models.ForeignKey(Local, on_delete=models.PROTECT, related_name="ordens")
    categoria = models.ForeignKey(Categoria, on_delete=models.PROTECT, related_name="ordens")
    prioridade = models.CharField(max_length=10, choices=Prioridade.choices, default=Prioridade.MEDIA, db_index=True)
    status = models.CharField(max_length=25, choices=Status.choices, default=Status.ABERTA, db_index=True)
    solicitante = models.ForeignKey(User, on_delete=models.PROTECT, related_name="ordens_solicitadas")
    gestor = models.ForeignKey(User, on_delete=models.PROTECT, related_name="ordens_geridas")
    agente = models.ForeignKey(User, null=True, blank=True, on_delete=models.PROTECT, related_name="ordens_atribuidas")
    prazo = models.DateTimeField(null=True, blank=True, db_index=True)
    sla_horas = models.PositiveIntegerField(null=True, blank=True)
    valor_previsto_orcamento = models.DecimalField(max_digits=12, decimal_places=2, null=True, blank=True)
    semestre_previsto = models.PositiveSmallIntegerField(null=True, blank=True)
    ano_previsto = models.PositiveSmallIntegerField(null=True, blank=True)
    data_inicio = models.DateTimeField(null=True, blank=True)
    data_conclusao = models.DateTimeField(null=True, blank=True)
    data_encerramento = models.DateTimeField(null=True, blank=True)
    aprovado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.PROTECT, related_name="+")
    observacao_aprovacao = models.TextField(blank=True)
    origem = models.CharField(max_length=30, default="MANUAL")
    custo_materiais = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    custo_mao_obra = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    custo_terceiros = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    custo_total_final = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    qualidade_percentual = models.PositiveSmallIntegerField(null=True, blank=True)
    preventiva = models.ForeignKey("operacao.Preventiva", null=True, blank=True, on_delete=models.SET_NULL,
                                   related_name="ordens")
    duplicidade_referencia = models.ForeignKey("self", null=True, blank=True, on_delete=models.SET_NULL,
                                               related_name="+")
    reabertura_contagem = models.PositiveIntegerField(default=0)
    solicitacao_encerramento = models.BooleanField(default=False)
    solicitacao_encerramento_em = models.DateTimeField(null=True, blank=True)
    solicitacao_encerramento_obs = models.TextField(blank=True)
    criado_em = models.DateTimeField(default=timezone.now, db_index=True)
    atualizado_em = models.DateTimeField(auto_now=True)
    excluida_em = models.DateTimeField(null=True, blank=True, db_index=True)
    excluida_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.PROTECT, related_name="+")

    objects = OrdemQuerySet.as_manager()

    class Meta:
        ordering = ["-criado_em", "-id"]
        indexes = [models.Index(fields=["tipo", "status"])]
        verbose_name = "ordem"
        verbose_name_plural = "ordens"

    def __str__(self):
        return f"{self.numero} — {self.titulo}"

    def get_absolute_url(self):
        return reverse("ordens:detalhe", args=[self.pk])

    @property
    def encerrada(self):
        return self.status == Status.ENCERRADA

    @property
    def atrasada(self):
        return bool(
            self.prazo and self.prazo < timezone.now() and self.status not in (Status.ENCERRADA, Status.CONCLUIDA_PELO_AGENTE)
        )

    @property
    def eh_limpeza(self):
        return self.tipo == self.Tipo.OL

    # Link público (sem login) para o agente enviar fotos/pedir encerramento.
    # Mesmo algoritmo do PHP: HMAC-SHA256("id|numero")[:32].
    @property
    def token_publico(self):
        msg = f"{self.pk}|{self.numero}".encode()
        return hmac.new(settings.GEOP_PUBLIC_SHARE_SECRET.encode(), msg, hashlib.sha256).hexdigest()[:32]

    def token_publico_valido(self, token: str) -> bool:
        return bool(token) and hmac.compare_digest(self.token_publico, token.lower())

    @property
    def url_publica(self):
        return reverse("ordens:publica", args=[self.pk, self.token_publico])

    def participa(self, user) -> bool:
        if not user.is_authenticated:
            return False
        return (
            user.pk in (self.agente_id, self.solicitante_id, self.gestor_id)
            or self.agentes.filter(usuario=user).exists()
        )

    def executor(self, user) -> bool:
        return user.pk == self.agente_id or self.agentes.filter(usuario=user).exists()


class OrdemAgente(models.Model):
    class Papel(models.TextChoices):
        PRINCIPAL = "PRINCIPAL", "Principal"
        AUXILIAR = "AUXILIAR", "Auxiliar"

    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="agentes")
    usuario = models.ForeignKey(User, on_delete=models.PROTECT, related_name="participacoes")
    papel = models.CharField(max_length=10, choices=Papel.choices, default=Papel.AUXILIAR)
    criado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        unique_together = [("ordem", "usuario")]
        ordering = ["papel", "usuario__nome"]


class Anexo(ArquivoBase):
    class Tipo(models.TextChoices):
        FOTO_ANTES = "FOTO_ANTES", "Foto antes"
        FOTO_DURANTE = "FOTO_DURANTE", "Foto durante"
        FOTO_DEPOIS = "FOTO_DEPOIS", "Foto depois"
        DOCUMENTO = "DOCUMENTO", "Documento"
        OUTRO = "OUTRO", "Outro"

    class Origem(models.TextChoices):
        INTERNO = "INTERNO", "Sistema"
        PUBLICO = "PUBLICO", "Link público"
        AMBIENTE = "AMBIENTE", "Ambiente QR"
        LEGADO = "LEGADO", "Importado do PHP"

    PASTA = "ordens"

    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="anexos")
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    tipo = models.CharField(max_length=15, choices=Tipo.choices, default=Tipo.OUTRO)
    origem = models.CharField(max_length=10, choices=Origem.choices, default=Origem.INTERNO)
    descricao = models.TextField(blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["criado_em", "id"]


class Historico(models.Model):
    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="historico")
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    status_anterior = models.CharField(max_length=50, blank=True)
    status_novo = models.CharField(max_length=50, blank=True)
    descricao = models.TextField(blank=True)
    criado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-criado_em", "-id"]
        verbose_name_plural = "históricos"


class Material(models.Model):
    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="materiais")
    nome_material = models.CharField(max_length=150)
    quantidade = models.DecimalField(max_digits=10, decimal_places=2)
    unidade = models.CharField(max_length=30, blank=True)
    custo_unitario = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    observacao = models.TextField(blank=True)
    material_estoque = models.ForeignKey("estoque.MaterialEstoque", null=True, blank=True, on_delete=models.SET_NULL,
                                         related_name="usos")
    situacao = models.CharField(max_length=25, default="CONSUMIDO", choices=[
        ("CONSUMIDO", "Consumido"), ("RESERVADO", "Reservado"), ("AGUARDANDO_APROVACAO", "Aguardando aprovação"),
        ("REPROVADO", "Reprovado"), ("AVULSO", "Fora do estoque")])
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["criado_em"]
        verbose_name_plural = "materiais"

    @property
    def custo_total(self):
        return (self.quantidade or 0) * (self.custo_unitario or 0)


class ChecklistItem(models.Model):
    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="checklist")
    modelo_item = models.ForeignKey("ChecklistModeloItem", null=True, blank=True, on_delete=models.SET_NULL)
    tarefa_plano = models.ForeignKey("limpeza.PlanoTarefa", null=True, blank=True, on_delete=models.SET_NULL)
    descricao = models.CharField(max_length=255)
    obrigatorio = models.BooleanField(default=True)
    exige_foto = models.BooleanField(default=False)
    concluido = models.BooleanField(default=False)
    concluido_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    concluido_em = models.DateTimeField(null=True, blank=True)
    observacao = models.CharField(max_length=255, blank=True)
    posicao = models.PositiveIntegerField(default=0)

    class Meta:
        ordering = ["posicao", "id"]


class ParticipanteLimpeza(models.Model):
    class Papel(models.TextChoices):
        ASG = "ASG", "ASG"
        SUPERVISOR = "SUPERVISOR", "Supervisor"

    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="equipe_limpeza")
    membro = models.ForeignKey("limpeza.OrganogramaNo", on_delete=models.PROTECT, related_name="participacoes")
    papel = models.CharField(max_length=20, choices=Papel.choices, default=Papel.ASG)
    principal = models.BooleanField(default=False)

    class Meta:
        unique_together = [("ordem", "membro")]
        ordering = ["papel", "-principal", "membro__nome_colaborador"]


class SolicitacaoReabertura(models.Model):
    class Estado(models.TextChoices):
        PENDENTE = "PENDENTE", "Pendente"
        ATENDIDA = "ATENDIDA", "Atendida"
        CANCELADA = "CANCELADA", "Cancelada"

    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="solicitacoes_reabertura")
    solicitado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    motivo = models.TextField()
    estado = models.CharField(max_length=10, choices=Estado.choices, default=Estado.PENDENTE, db_index=True)
    atendido_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    atendido_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)


class TokenReabertura(models.Model):
    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="tokens_reabertura")
    token_hash = models.CharField(max_length=128)
    gerado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    solicitacao = models.ForeignKey(SolicitacaoReabertura, null=True, blank=True, on_delete=models.SET_NULL)
    tentativas = models.PositiveSmallIntegerField(default=0)
    usado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    usado_em = models.DateTimeField(null=True, blank=True)
    expira_em = models.DateTimeField()
    criado_em = models.DateTimeField(default=timezone.now)

    @staticmethod
    def calcular_hash(ordem_id: int, token: str) -> str:
        msg = f"reabertura|{ordem_id}|{token}".encode()
        return hmac.new(settings.SECRET_KEY.encode(), msg, hashlib.sha256).hexdigest()


class SolicitacaoExclusao(models.Model):
    class Estado(models.TextChoices):
        PENDENTE = "PENDENTE", "Pendente"
        APROVADA = "APROVADA", "Aprovada"
        REPROVADA = "REPROVADA", "Reprovada"
        EXECUTADA = "EXECUTADA", "Executada"
        CANCELADA = "CANCELADA", "Cancelada"

    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="solicitacoes_exclusao")
    solicitado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    motivo = models.TextField()
    estado = models.CharField(max_length=10, choices=Estado.choices, default=Estado.PENDENTE, db_index=True)
    analisado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.PROTECT, related_name="+")
    analisado_em = models.DateTimeField(null=True, blank=True)
    observacao_analise = models.TextField(blank=True)
    executado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.PROTECT, related_name="+")
    executado_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["-criado_em"]
        verbose_name = "solicitação de exclusão"
        verbose_name_plural = "solicitações de exclusão"


class OrdemPatrimonio(models.Model):
    ordem = models.ForeignKey(Ordem, on_delete=models.CASCADE, related_name="patrimonios")
    patrimonio_externo_id = models.CharField(max_length=190, blank=True)
    codigo_barras = models.CharField(max_length=190, blank=True)
    numero_patrimonio = models.CharField(max_length=190, blank=True)
    descricao = models.CharField(max_length=255, blank=True)
    marca = models.CharField(max_length=120, blank=True)
    modelo = models.CharField(max_length=120, blank=True)
    serie = models.CharField(max_length=190, blank=True)
    setor = models.CharField(max_length=190, blank=True)
    localizacao = models.CharField(max_length=255, blank=True)
    origem = models.CharField(max_length=50, default="MANUAL")
    vinculado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    vinculado_em = models.DateTimeField(default=timezone.now)
    desvinculado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    desvinculado_em = models.DateTimeField(null=True, blank=True)
    ativo = models.BooleanField(default=True)

    class Meta:
        ordering = ["-vinculado_em"]
        verbose_name = "patrimônio vinculado"
        verbose_name_plural = "patrimônios vinculados"


class ChecklistModelo(models.Model):
    """Checklist padrão de uma categoria; cada nova ordem recebe uma cópia (snapshot) dos itens."""

    categoria = models.ForeignKey(Categoria, on_delete=models.CASCADE, related_name="checklist_modelos")
    nome = models.CharField(max_length=160)
    ativo = models.BooleanField(default=True)
    criado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["categoria__nome", "nome"]
        verbose_name = "modelo de checklist"
        verbose_name_plural = "modelos de checklist"

    def __str__(self):
        return f"{self.categoria} — {self.nome}"


class ChecklistModeloItem(models.Model):
    modelo = models.ForeignKey(ChecklistModelo, on_delete=models.CASCADE, related_name="itens")
    descricao = models.CharField(max_length=255)
    obrigatorio = models.BooleanField(default=False)
    exige_foto = models.BooleanField(default=False)
    posicao = models.PositiveSmallIntegerField(default=0)

    class Meta:
        ordering = ["posicao", "id"]


class NumeroSequencia(models.Model):
    tipo = models.CharField(max_length=2)
    ano = models.PositiveSmallIntegerField()
    ultimo = models.PositiveBigIntegerField(default=0)

    class Meta:
        unique_together = [("tipo", "ano")]
