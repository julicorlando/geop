"""IoT (api_iot.php) e monitoramento de ativos de rede (ativos_rede)."""
import socket
import time
from datetime import timedelta
from decimal import Decimal, InvalidOperation

from django.db import transaction
from django.utils import timezone

from core.services import notificar_permissao
from ordens.models import Status

from .models import AtivoRede, Dispositivo, Leitura


def _ordem_aberta(ordem):
    return ordem is not None and ordem.excluida_em is None and ordem.status != Status.ENCERRADA


def _abrir_ordem(titulo, descricao, local, categoria, prioridade, origem):
    from core.models import Categoria
    from ordens.services import criar_ordem_sistema

    categoria = categoria or Categoria.objects.filter(ativo=True).order_by("pk").first()
    if not local or not categoria:
        return None
    return criar_ordem_sistema({"titulo": titulo[:150], "descricao": descricao, "local": local, "categoria": categoria,
                                "prioridade": prioridade, "origem": origem})


def receber_leitura(dispositivo, dados):
    """Registra a leitura; fora dos limites abre (uma única) OS e notifica quem gerencia IoT."""
    tipo = str(dados.get("tipo") or dispositivo.tipo)[:80]
    valor = dados.get("valor")
    try:
        numero = Decimal(str(valor)) if valor is not None and not isinstance(valor, bool) else None
    except (InvalidOperation, ValueError):
        numero = None
    alerta = numero is not None and ((dispositivo.limite_min is not None and numero < dispositivo.limite_min)
                                     or (dispositivo.limite_max is not None and numero > dispositivo.limite_max))
    with transaction.atomic():
        leitura = Leitura.objects.create(dispositivo=dispositivo, tipo=tipo, valor_numero=numero,
                                         valor_texto=str(dados.get("texto") or ("" if numero is not None else valor or ""))[:255],
                                         payload=dados if isinstance(dados, dict) else {}, alerta=alerta)
        d = Dispositivo.objects.select_for_update().get(pk=dispositivo.pk)
        d.ultima_comunicacao, d.ultimo_valor = timezone.now(), numero
        if dados.get("bateria") is not None:
            try:
                d.bateria = Decimal(str(dados["bateria"]))
            except (InvalidOperation, ValueError):
                pass
        d.status = "ALERTA" if alerta else "ONLINE"
        if alerta and not _ordem_aberta(d.ordem_alerta):
            d.ordem_alerta = _abrir_ordem(
                f"[IoT] {d.identificador}: {tipo} fora do limite",
                f"Leitura {numero} fora da faixa {d.limite_min if d.limite_min is not None else '-∞'} a "
                f"{d.limite_max if d.limite_max is not None else '+∞'} em {timezone.localtime():%d/%m/%Y %H:%M}.",
                d.local, d.categoria_os, "ALTA", "IOT")
            notificar_permissao("iot.gerenciar", "IOT_ALERTA", f"Alerta IoT — {d.identificador}",
                                f"{tipo} = {numero}", "/iot/", f"iot-{d.pk}-{leitura.pk}")
        d.save()
    return leitura


def verificar_offline():
    n = 0
    for d in Dispositivo.objects.filter(ativo=True, ultima_comunicacao__isnull=False).exclude(status="OFFLINE"):
        if d.ultima_comunicacao < timezone.now() - timedelta(minutes=d.offline_minutos):
            d.status = "OFFLINE"
            d.save(update_fields=["status"])
            notificar_permissao("iot.gerenciar", "IOT_OFFLINE", f"Dispositivo offline — {d.identificador}",
                                f"Sem comunicação desde {timezone.localtime(d.ultima_comunicacao):%d/%m %H:%M}.", "/iot/",
                                f"iot-off-{d.pk}-{d.ultima_comunicacao:%Y%m%d%H%M}")
            n += 1
    return n


def testar_tcp(host, porta, timeout=3.0):
    inicio = time.monotonic()
    try:
        with socket.create_connection((host, porta), timeout=timeout):
            return True, int((time.monotonic() - inicio) * 1000)
    except OSError:
        return False, None


def verificar_rede(testador=testar_tcp):
    """Falhas consecutivas acima do limite → status FALHA, OS automática (uma por incidente) e notificação."""
    n = 0
    for a in AtivoRede.objects.filter(ativo=True).select_related("local"):
        ok, latencia = testador(a.endereco_ip, a.porta)
        a.ultima_verificacao, a.latencia_ms = timezone.now(), latencia
        if ok:
            a.falhas_consecutivas, a.status = 0, "ONLINE"
        else:
            a.falhas_consecutivas += 1
            if a.falhas_consecutivas >= a.falhas_para_alerta:
                if a.status != "FALHA":
                    notificar_permissao("iot.gerenciar", "REDE_FALHA", f"{a.get_tipo_display()} sem resposta — {a.nome}",
                                        f"{a.endereco_ip}:{a.porta} falhou {a.falhas_consecutivas} vezes seguidas.", "/iot/",
                                        f"rede-{a.pk}-{timezone.now():%Y%m%d%H}")
                if not _ordem_aberta(a.ordem_alerta):
                    a.ordem_alerta = _abrir_ordem(
                        f"[Rede] {a.nome} sem resposta", f"{a.get_tipo_display()} {a.endereco_ip}:{a.porta} sem resposta em "
                        f"{a.falhas_consecutivas} verificações seguidas.", a.local, None,
                        "CRITICA" if a.criticidade == "CRITICA" else "ALTA", "MONITORAMENTO")
                a.status = "FALHA"
                n += 1
            else:
                a.status = "INSTAVEL"
        a.save()
    return n
