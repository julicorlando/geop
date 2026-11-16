from decimal import Decimal

from django.conf import settings
from django.db import models
from django.db.models import Sum
from django.utils import timezone

from core.models import ArquivoBase

User = settings.AUTH_USER_MODEL


class MaterialEstoque(models.Model):
    nome = models.CharField(max_length=160, unique=True)
    unidade = models.CharField(max_length=30, blank=True)
    quantidade_atual = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    estoque_minimo = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    custo_unitario = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    ativo = models.BooleanField(default=True)
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ["nome"]
        verbose_name = "material de estoque"
        verbose_name_plural = "materiais de estoque"

    def __str__(self):
        return f"{self.nome} ({self.unidade})" if self.unidade else self.nome

    @property
    def reservado(self) -> Decimal:
        return self.reservas.filter(status=Reserva.Status.RESERVADA).aggregate(t=Sum("quantidade"))["t"] or Decimal("0")

    @property
    def disponivel(self) -> Decimal:
        return self.quantidade_atual - self.reservado

    @property
    def abaixo_minimo(self) -> bool:
        return self.quantidade_atual <= self.estoque_minimo


class Movimentacao(models.Model):
    class Tipo(models.TextChoices):
        ENTRADA = "ENTRADA", "Entrada"
        SAIDA = "SAIDA", "Saída manual"
        SAIDA_OS = "SAIDA_OS", "Consumo em OS"
        AJUSTE = "AJUSTE", "Ajuste de inventário"
        COMPRA = "COMPRA", "Recebimento de compra"
        ESTORNO = "ESTORNO", "Estorno"

    material = models.ForeignKey(MaterialEstoque, on_delete=models.PROTECT, related_name="movimentacoes")
    ordem = models.ForeignKey("ordens.Ordem", null=True, blank=True, on_delete=models.SET_NULL, related_name="movimentacoes")
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    tipo = models.CharField(max_length=20, choices=Tipo.choices)
    quantidade = models.DecimalField(max_digits=12, decimal_places=2, help_text="Positiva = entrada; negativa = saída.")
    custo_unitario = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    observacao = models.TextField(blank=True)
    criado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-criado_em", "-id"]
        verbose_name = "movimentação"
        verbose_name_plural = "movimentações"


class Reserva(models.Model):
    class Status(models.TextChoices):
        RESERVADA = "RESERVADA", "Reservada"
        CONSUMIDA = "CONSUMIDA", "Consumida"
        APROVACAO_NEGATIVO = "APROVACAO_NEGATIVO", "Aguardando aprovação (saldo insuficiente)"
        REPROVADA = "REPROVADA", "Reprovada"
        CANCELADA = "CANCELADA", "Cancelada"

    ordem = models.ForeignKey("ordens.Ordem", on_delete=models.CASCADE, related_name="reservas")
    material = models.ForeignKey(MaterialEstoque, on_delete=models.PROTECT, related_name="reservas")
    item_os = models.OneToOneField("ordens.Material", null=True, blank=True, on_delete=models.SET_NULL,
                                   related_name="reserva")
    usuario = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    quantidade = models.DecimalField(max_digits=12, decimal_places=2)
    custo_unitario = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    status = models.CharField(max_length=25, choices=Status.choices, default=Status.RESERVADA, db_index=True)
    justificativa = models.TextField(blank=True)
    aprovado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    aprovado_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["-criado_em"]


class Compra(models.Model):
    class Status(models.TextChoices):
        SOLICITADA = "SOLICITADA", "Aguardando aprovação"
        APROVADA = "APROVADA", "Aprovada"
        REPROVADA = "REPROVADA", "Reprovada"
        COMPRADA = "COMPRADA", "Comprada"
        RECEBIDA = "RECEBIDA", "Recebida"
        CANCELADA = "CANCELADA", "Cancelada"

    ordem = models.ForeignKey("ordens.Ordem", null=True, blank=True, on_delete=models.SET_NULL, related_name="compras")
    solicitante = models.ForeignKey(User, on_delete=models.PROTECT, related_name="compras_solicitadas")
    aprovador = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    status = models.CharField(max_length=15, choices=Status.choices, default=Status.SOLICITADA, db_index=True)
    justificativa = models.TextField(blank=True)
    observacao_decisao = models.TextField(blank=True)
    fornecedor = models.CharField(max_length=180, blank=True)
    numero_documento = models.CharField(max_length=100, blank=True)
    valor_previsto = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    valor_final = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    decidido_em = models.DateTimeField(null=True, blank=True)
    comprado_em = models.DateTimeField(null=True, blank=True)
    recebido_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["-criado_em"]

    def __str__(self):
        return f"Compra #{self.pk}"


class CompraItem(models.Model):
    compra = models.ForeignKey(Compra, on_delete=models.CASCADE, related_name="itens")
    material = models.ForeignKey(MaterialEstoque, null=True, blank=True, on_delete=models.SET_NULL)
    descricao = models.CharField(max_length=190)
    quantidade = models.DecimalField(max_digits=12, decimal_places=2, default=1)
    unidade = models.CharField(max_length=30, blank=True)
    custo_unitario_previsto = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    custo_unitario_final = models.DecimalField(max_digits=12, decimal_places=2, null=True, blank=True)


class CustoDocumento(ArquivoBase):
    """Custos além dos materiais: mão de obra, terceiros, frete… com comprovante opcional."""

    class Tipo(models.TextChoices):
        MAO_OBRA = "MAO_OBRA", "Mão de obra"
        TERCEIRO = "TERCEIRO", "Terceiro"
        SERVICO = "SERVICO", "Serviço"
        FRETE = "FRETE", "Frete"
        LOCACAO = "LOCACAO", "Locação"
        OUTRO = "OUTRO", "Outro"

    PASTA = "custos"

    arquivo = models.FileField(max_length=255, blank=True)
    ordem = models.ForeignKey("ordens.Ordem", on_delete=models.CASCADE, related_name="custos")
    tipo = models.CharField(max_length=10, choices=Tipo.choices, default=Tipo.OUTRO)
    fornecedor = models.CharField(max_length=180, blank=True)
    documento = models.CharField(max_length=100, blank=True)
    descricao = models.CharField(max_length=255, blank=True)
    valor = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    registrado_por = models.ForeignKey(User, null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["-criado_em"]
