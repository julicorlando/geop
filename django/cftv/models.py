import hashlib
import hmac
import secrets

from django.conf import settings
from django.db import models
from django.urls import reverse
from django.utils import timezone

User = settings.AUTH_USER_MODEL


def gerar_token():
    return secrets.token_urlsafe(24)


def normalizar_cpf(cpf) -> str:
    return "".join(c for c in str(cpf or "") if c.isdigit())[:11]


def formatar_cpf(cpf) -> str:
    c = normalizar_cpf(cpf)
    return f"{c[:3]}.{c[3:6]}.{c[6:9]}-{c[9:]}" if len(c) == 11 else c


def cpf_valido(cpf) -> bool:
    c = normalizar_cpf(cpf)
    if len(c) != 11 or c == c[0] * 11:
        return False
    for i in (9, 10):
        soma = sum(int(c[j]) * ((i + 1) - j) for j in range(i))
        if (soma * 10 % 11) % 10 != int(c[i]):
            return False
    return True


def mascarar_cpf(cpf) -> str:
    c = normalizar_cpf(cpf)
    return f"***.***.***-{c[-2:]}" if len(c) == 11 else "***"


# --------------------------------------------------------------------- plantões (relatório do operador)

class RelatorioPlantao(models.Model):
    class Turno(models.TextChoices):
        DIURNO = "DIURNO", "Diurno"
        NOTURNO = "NOTURNO", "Noturno"

    class Status(models.TextChoices):
        EM_ANDAMENTO = "EM_ANDAMENTO", "Em andamento"
        FINALIZADO = "FINALIZADO", "Finalizado"

    data_plantao = models.DateField(db_index=True)
    turno = models.CharField(max_length=10, choices=Turno.choices, default=Turno.DIURNO)
    operador = models.ForeignKey(User, on_delete=models.PROTECT, related_name="plantoes_cftv")
    status = models.CharField(max_length=15, choices=Status.choices, default=Status.EM_ANDAMENTO)
    inicio_turno = models.TimeField(null=True, blank=True)
    fim_turno = models.TimeField(null=True, blank=True)
    passagem_servico = models.TextField(blank=True)
    cameras_funcionando = models.BooleanField(default=False)
    nvrs_gravando = models.BooleanField(default=False)
    horario_sincronizado = models.BooleanField(default=False)
    radios_ok = models.BooleanField(default=False)
    falhas_livro = models.TextField("falhas / livro de registro", blank=True)
    cameras_offline = models.PositiveIntegerField(default=0)
    rondas_virtuais = models.PositiveIntegerField(default=0)
    eventos_preventivos = models.PositiveIntegerField(default=0)
    fluxo_clientes = models.PositiveIntegerField(default=0)
    tempo_medio_resposta_min = models.PositiveIntegerField(null=True, blank=True)
    areas_prioritarias = models.TextField(blank=True)
    situacoes_suspeitas = models.TextField(blank=True)
    ocorrencias_resumo = models.TextField(blank=True)
    observacoes_gerais = models.TextField(blank=True)
    plano_melhoria = models.TextField(blank=True)
    finalizado_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ["-data_plantao", "-id"]
        verbose_name = "relatório de plantão"
        verbose_name_plural = "relatórios de plantão"

    def __str__(self):
        return f"Plantão {self.data_plantao:%d/%m/%Y} ({self.get_turno_display()})"

    def get_absolute_url(self):
        return reverse("cftv:relatorio", args=[self.pk])


class Ocorrencia(models.Model):
    NIVEIS = [("NIVEL_1", "Nível 1 — Informativa"), ("NIVEL_2", "Nível 2 — Atenção"), ("NIVEL_3", "Nível 3 — Crítica")]

    relatorio = models.ForeignKey(RelatorioPlantao, on_delete=models.CASCADE, related_name="ocorrencias")
    horario = models.DateTimeField(null=True, blank=True)
    nivel = models.CharField(max_length=10, choices=NIVEIS, default="NIVEL_1")
    tipo = models.CharField(max_length=100, blank=True)
    local = models.CharField(max_length=160, blank=True)
    descricao = models.TextField(blank=True)
    envolvidos = models.TextField(blank=True)
    imagens = models.TextField("câmeras / imagens", blank=True)
    tempo_resposta_min = models.PositiveIntegerField(null=True, blank=True)
    equipes_acionadas = models.TextField(blank=True)
    encerramento = models.TextField(blank=True)
    preservar_imagens = models.BooleanField(default=False)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["horario", "id"]


class Observacao(models.Model):
    EQUIPES = ["Fiscal de Mall", "Segurança terceirizada", "Limpeza", "Manutenção", "Brigada/Bombeiro civil",
               "Estacionamento", "Prestadores de serviço", "Lojistas em áreas comuns"]
    ITENS = ["Postura operacional", "Ronda / roteiro", "Uso de celular", "Abandono de posto", "Tempo parado",
             "Uniforme / EPI", "Sinalização de área", "Controle de acesso", "Tempo de resposta", "Apoio em ocorrência",
             "Procedimento de segurança"]
    CLASSIFICACOES = [("CONFORME", "Conforme"), ("INFORMATIVA", "Informativa"),
                      ("NAO_CONFORMIDADE", "Não conformidade"), ("CRITICA", "Crítica")]

    relatorio = models.ForeignKey(RelatorioPlantao, on_delete=models.CASCADE, related_name="observacoes")
    equipe = models.CharField(max_length=100)
    horario = models.DateTimeField(null=True, blank=True)
    local = models.CharField(max_length=160, blank=True)
    item = models.CharField(max_length=120, blank=True)
    classificacao = models.CharField(max_length=20, choices=CLASSIFICACOES, default="INFORMATIVA")
    descricao = models.TextField(blank=True)
    tempo_resposta_min = models.PositiveIntegerField(null=True, blank=True)
    providencia = models.TextField(blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["horario", "id"]


class TokenEdicao(models.Model):
    """O TI libera a edição de um plantão já fechado por 24 h (código de 6 dígitos)."""

    relatorio = models.ForeignKey(RelatorioPlantao, on_delete=models.CASCADE, related_name="tokens")
    token_hash = models.CharField(max_length=64)
    motivo = models.TextField(blank=True)
    gerado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    usado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    usado_em = models.DateTimeField(null=True, blank=True)
    liberado_ate = models.DateTimeField(null=True, blank=True)
    expira_em = models.DateTimeField()
    criado_em = models.DateTimeField(default=timezone.now)

    @staticmethod
    def calcular_hash(relatorio_id, token):
        return hmac.new(settings.SECRET_KEY.encode(), f"cftv|{relatorio_id}|{token}".encode(), hashlib.sha256).hexdigest()


# --------------------------------------------------------------------- liberações de acesso

class Destino(models.Model):
    TIPOS = [("LOJA_MALL", "Loja do Mall"), ("ADMINISTRACAO", "Administração"),
             ("PRACA_ALIMENTACAO", "Loja da Praça de Alimentação"), ("AREA_SERVICO", "Área de Serviço"),
             ("EMPRESA", "Empresa/prestador"), ("SETOR", "Setor")]

    tipo = models.CharField(max_length=20, choices=TIPOS)
    nome = models.CharField(max_length=180)
    ativo = models.BooleanField(default=True)
    criado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["tipo", "nome"]
        unique_together = [("tipo", "nome")]

    def __str__(self):
        return self.nome


class Liberacao(models.Model):
    """Autorização de acesso (criada manualmente ou ao aprovar uma solicitação pública)."""

    class Status(models.TextChoices):
        ATIVA = "ATIVA", "Ativa"
        CANCELADA = "CANCELADA", "Cancelada"

    data_acesso = models.DateField(db_index=True)
    data_fim = models.DateField(null=True, blank=True)
    hora_inicio = models.TimeField(null=True, blank=True)
    hora_fim = models.TimeField(null=True, blank=True)
    nome_completo = models.CharField(max_length=180)
    cpf = models.CharField(max_length=11)
    email = models.EmailField(max_length=190, blank=True)
    destino_tipo = models.CharField(max_length=20, choices=Destino.TIPOS, default="LOJA_MALL")
    destino_nome = models.CharField(max_length=180)
    motivo = models.TextField(blank=True)
    observacoes = models.TextField(blank=True)
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.ATIVA)
    criado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)
    cancelado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    cancelado_em = models.DateTimeField(null=True, blank=True)

    class Meta:
        ordering = ["-data_acesso", "hora_inicio"]
        verbose_name = "liberação de acesso"
        verbose_name_plural = "liberações de acesso"

    @property
    def fim(self):
        return self.data_fim or self.data_acesso

    def vale_em(self, dia):
        return self.status == self.Status.ATIVA and self.data_acesso <= dia <= self.fim


class ConfirmacaoLiberacao(models.Model):
    STATUS = [("CONFIRMADA", "Confirmada conforme liberação"), ("CONFIRMADA_COM_ALTERACAO", "Confirmada com alteração"),
              ("NAO_COMPARECEU", "Não compareceu"), ("DIVERGENTE", "Divergente / não liberada")]

    relatorio = models.ForeignKey(RelatorioPlantao, on_delete=models.CASCADE, related_name="confirmacoes")
    liberacao = models.ForeignKey(Liberacao, on_delete=models.CASCADE, related_name="confirmacoes")
    status_confirmacao = models.CharField(max_length=30, choices=STATUS)
    horario_confirmacao = models.DateTimeField(null=True, blank=True)
    alteracao_observada = models.TextField(blank=True)
    confirmado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        unique_together = [("relatorio", "liberacao")]


class LinkSolicitacao(models.Model):
    """Link público para terceiros pedirem acesso (com validade e limite de usos)."""

    token = models.CharField(max_length=48, unique=True, default=gerar_token)
    titulo = models.CharField(max_length=160, default="Solicitação de acesso")
    destino_padrao = models.CharField(max_length=180, blank=True)
    expira_em = models.DateTimeField()
    limite_usos = models.PositiveIntegerField(default=1)
    usos = models.PositiveIntegerField(default=0)
    ativo = models.BooleanField(default=True)
    permanente = models.BooleanField(default=False, help_text="Link fixo da recepção (sem limite de usos).")
    criado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["-criado_em"]

    @property
    def valido(self):
        return self.ativo and self.expira_em > timezone.now() and (self.permanente or self.usos < self.limite_usos)

    def get_absolute_url(self):
        return reverse("cftv:solicitar", args=[self.token])


class SolicitacaoAcesso(models.Model):
    class Status(models.TextChoices):
        PENDENTE = "PENDENTE", "Pendente"
        APROVADA = "APROVADA", "Aprovada"
        REPROVADA = "REPROVADA", "Reprovada"
        CANCELADA = "CANCELADA", "Cancelada"

    protocolo = models.CharField(max_length=30, unique=True)
    link = models.ForeignKey(LinkSolicitacao, null=True, blank=True, on_delete=models.SET_NULL,
                             related_name="solicitacoes")
    acompanhamento = models.CharField(max_length=48, unique=True, default=gerar_token)
    nome_completo = models.CharField(max_length=180)
    cpf = models.CharField(max_length=11, db_index=True)
    telefone = models.CharField(max_length=30, blank=True)
    email = models.EmailField(max_length=190, blank=True)
    empresa = models.CharField(max_length=180, blank=True)
    data_acesso = models.DateField()
    data_fim = models.DateField(null=True, blank=True)
    hora_inicio = models.TimeField(null=True, blank=True)
    hora_fim = models.TimeField(null=True, blank=True)
    destino_tipo = models.CharField(max_length=20, choices=Destino.TIPOS, default="LOJA_MALL")
    destino_nome = models.CharField(max_length=180)
    responsavel_interno = models.CharField(max_length=180, blank=True)
    motivo = models.TextField(blank=True)
    veiculo_placa = models.CharField(max_length=20, blank=True)
    observacoes = models.TextField(blank=True)
    status = models.CharField(max_length=10, choices=Status.choices, default=Status.PENDENTE, db_index=True)
    decisao_justificativa = models.TextField(blank=True)
    decidido_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    decidido_em = models.DateTimeField(null=True, blank=True)
    liberacao = models.OneToOneField(Liberacao, null=True, blank=True, on_delete=models.SET_NULL,
                                     related_name="solicitacao")
    ip_hash = models.CharField(max_length=64, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["-criado_em"]
        verbose_name = "solicitação de acesso"
        verbose_name_plural = "solicitações de acesso"

    def __str__(self):
        return f"{self.protocolo} — {self.nome_completo}"

    @property
    def fim(self):
        return self.data_fim or self.data_acesso

    @property
    def eh_periodo(self):
        return self.fim > self.data_acesso

    # Credencial (QR): "<id>.<hmac>" — mesmo formato do PHP.
    @property
    def codigo_qr(self):
        assinatura = hmac.new(settings.GEOP_QR_SECRET.encode(), str(self.pk).encode(), hashlib.sha256).hexdigest()
        return f"{self.pk}.{assinatura}"

    @classmethod
    def validar_codigo(cls, codigo: str):
        partes = (codigo or "").strip().split(".")
        if len(partes) != 2 or not partes[0].isdigit():
            return None
        obj = cls.objects.filter(pk=int(partes[0])).first()
        if obj and hmac.compare_digest(obj.codigo_qr, codigo.strip()):
            return obj
        return None

    def voucher_utilizado(self, hoje=None):
        hoje = hoje or timezone.localdate()
        entradas = self.eventos.filter(tipo=EventoAcesso.Tipo.ENTRADA)
        if not self.eh_periodo:
            return entradas.exists()
        if hoje > self.fim:
            return True
        if hoje < self.data_acesso:
            return False
        return entradas.filter(registrado_em__date=hoje).exists()


class PessoaAdicional(models.Model):
    solicitacao = models.ForeignKey(SolicitacaoAcesso, on_delete=models.CASCADE, related_name="pessoas")
    nome_completo = models.CharField(max_length=180)
    cpf = models.CharField(max_length=11)


class EventoAcesso(models.Model):
    class Tipo(models.TextChoices):
        ENTRADA = "ENTRADA", "Entrada"
        SAIDA = "SAIDA", "Saída"

    solicitacao = models.ForeignKey(SolicitacaoAcesso, on_delete=models.CASCADE, related_name="eventos")
    tipo = models.CharField(max_length=10, choices=Tipo.choices)
    registrado_por = models.ForeignKey(User, on_delete=models.PROTECT, related_name="+")
    registrado_em = models.DateTimeField(default=timezone.now, db_index=True)
    observacao = models.CharField(max_length=500, blank=True)

    class Meta:
        ordering = ["registrado_em"]
