import hashlib
import secrets

from django.conf import settings
from django.db import models
from django.utils import timezone

from core.models import Local

User = settings.AUTH_USER_MODEL


def hash_chave(chave: str) -> str:
    return hashlib.sha256(chave.encode()).hexdigest()


class Dispositivo(models.Model):
    identificador = models.CharField(max_length=100, unique=True)
    tipo = models.CharField(max_length=80, help_text="Ex.: temperatura, nível de reservatório, energia.")
    local = models.ForeignKey(Local, null=True, blank=True, on_delete=models.SET_NULL)
    categoria_os = models.ForeignKey("core.Categoria", null=True, blank=True, on_delete=models.SET_NULL,
                                     help_text="Categoria da OS aberta automaticamente em alerta.")
    limite_min = models.DecimalField(max_digits=20, decimal_places=4, null=True, blank=True)
    limite_max = models.DecimalField(max_digits=20, decimal_places=4, null=True, blank=True)
    offline_minutos = models.PositiveIntegerField(default=60, help_text="Sem comunicação por este tempo = offline.")
    api_key_hash = models.CharField(max_length=64, unique=True)
    status = models.CharField(max_length=20, default="NOVO")
    ultima_comunicacao = models.DateTimeField(null=True, blank=True)
    ultimo_valor = models.DecimalField(max_digits=20, decimal_places=4, null=True, blank=True)
    bateria = models.DecimalField(max_digits=5, decimal_places=2, null=True, blank=True)
    ordem_alerta = models.ForeignKey("ordens.Ordem", null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    ativo = models.BooleanField(default=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["identificador"]
        verbose_name = "dispositivo IoT"
        verbose_name_plural = "dispositivos IoT"

    def __str__(self):
        return self.identificador

    def nova_chave(self) -> str:
        chave = secrets.token_urlsafe(32)
        self.api_key_hash = hash_chave(chave)
        return chave


class Leitura(models.Model):
    dispositivo = models.ForeignKey(Dispositivo, on_delete=models.CASCADE, related_name="leituras")
    tipo = models.CharField(max_length=80)
    valor_numero = models.DecimalField(max_digits=20, decimal_places=4, null=True, blank=True)
    valor_texto = models.CharField(max_length=255, blank=True)
    payload = models.JSONField(default=dict, blank=True)
    alerta = models.BooleanField(default=False)
    recebida_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-recebida_em"]


class AtivoRede(models.Model):
    """Câmeras, NVRs, switches… monitorados por conexão TCP (comando `executar_rotinas`)."""

    TIPOS = [("CAMERA", "Câmera"), ("NVR", "NVR"), ("DVR", "DVR"), ("SWITCH", "Switch"),
             ("ACCESS_POINT", "Access point"), ("LINK", "Link de internet"), ("SERVIDOR", "Servidor")]

    tipo = models.CharField(max_length=15, choices=TIPOS)
    nome = models.CharField(max_length=160)
    endereco_ip = models.CharField(max_length=255, unique=True, help_text="IP ou nome do host.")
    porta = models.PositiveIntegerField(default=80)
    local = models.ForeignKey(Local, null=True, blank=True, on_delete=models.SET_NULL)
    criticidade = models.CharField(max_length=10, default="MEDIA", choices=[
        ("BAIXA", "Baixa"), ("MEDIA", "Média"), ("ALTA", "Alta"), ("CRITICA", "Crítica")])
    falhas_para_alerta = models.PositiveSmallIntegerField(default=3)
    falhas_consecutivas = models.PositiveSmallIntegerField(default=0)
    status = models.CharField(max_length=15, default="DESCONHECIDO")
    ultima_verificacao = models.DateTimeField(null=True, blank=True)
    latencia_ms = models.PositiveIntegerField(null=True, blank=True)
    ordem_alerta = models.ForeignKey("ordens.Ordem", null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    ativo = models.BooleanField(default=True)

    class Meta:
        ordering = ["tipo", "nome"]
        verbose_name = "ativo de rede"
        verbose_name_plural = "ativos de rede"

    def __str__(self):
        return self.nome
