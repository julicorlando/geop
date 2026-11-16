"""Rotinas automáticas do GEOP (substitui bin/cron.php). Agende a cada 15–30 minutos:

    */15 * * * *  cd /app && python manage.py executar_rotinas

Opções: --somente preventivas,sla,...  --rede (verifica câmeras/NVRs/switches por TCP)
"""
import time
import traceback

from django.core.management.base import BaseCommand
from django.utils import timezone

from core.models import ExecucaoRotina


def _rotinas():
    from cftv.services import fechar_plantoes_vencidos
    from iot.services import verificar_offline, verificar_rede
    from operacao import services as op

    return {
        "preventivas": lambda: op.gerar_preventivas()["criadas"],
        "sla": op.alertas_sla,
        "ordens_paradas": op.alertas_ordens_paradas,
        "estoque": op.alertas_estoque,
        "preventivas_proximas": op.alertas_preventivas_proximas,
        "cftv_plantoes": fechar_plantoes_vencidos,
        "iot_offline": verificar_offline,
        "rede": verificar_rede,
    }


class Command(BaseCommand):
    help = "Executa as rotinas automáticas: preventivas, alertas de SLA/estoque, CFTV, IoT e rede."

    def add_arguments(self, parser):
        parser.add_argument("--somente", default="", help="Lista separada por vírgulas de rotinas a executar.")
        parser.add_argument("--rede", action="store_true", help="Inclui a verificação de ativos de rede (TCP).")

    def handle(self, *args, **opts):
        rotinas = _rotinas()
        escolhidas = [r.strip() for r in opts["somente"].split(",") if r.strip()] or \
            [r for r in rotinas if r != "rede" or opts["rede"]]
        execucao = ExecucaoRotina.objects.create(comando="executar_rotinas")
        resumo, erros = {}, []
        for nome in escolhidas:
            if nome not in rotinas:
                self.stderr.write(f"Rotina desconhecida: {nome}")
                continue
            inicio = time.monotonic()
            try:
                resumo[nome] = rotinas[nome]()
                self.stdout.write(f"{nome}: {resumo[nome]} ({time.monotonic() - inicio:.1f}s)")
            except Exception:  # uma rotina com erro não impede as demais
                erros.append(f"{nome}: {traceback.format_exc(limit=3)}")
                self.stderr.write(f"{nome}: ERRO")
        execucao.status = "ERRO" if erros else "OK"
        execucao.resumo, execucao.erro, execucao.finalizado_em = resumo, "\n".join(erros), timezone.now()
        execucao.save()
        self.stdout.write(self.style.SUCCESS("Rotinas concluídas.") if not erros else self.style.WARNING("Concluído com erros."))
