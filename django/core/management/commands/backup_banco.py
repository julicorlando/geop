"""Backup do banco: pg_dump (PostgreSQL) ou dumpdata JSON compactado. Agende 1×/dia:

    30 2 * * *  cd /app && python manage.py backup_banco
"""
import gzip
import hashlib
import io
import os
import shutil
import subprocess

from django.conf import settings
from django.core.management import call_command
from django.core.management.base import BaseCommand, CommandError
from django.db import connection
from django.utils import timezone

from core.models import ExecucaoRotina


def criar_backup():
    pasta = settings.GEOP_BACKUP_DIR
    pasta.mkdir(parents=True, exist_ok=True)
    os.chmod(pasta, 0o750)
    carimbo = timezone.localtime().strftime("%Y%m%d_%H%M%S")
    db = connection.settings_dict
    if connection.vendor == "postgresql" and shutil.which("pg_dump"):
        destino = pasta / f"geop_{carimbo}.sql.gz"
        env = {**os.environ, "PGPASSWORD": db.get("PASSWORD") or ""}
        cmd = ["pg_dump", "--no-owner", "-h", db.get("HOST") or "localhost", "-p", str(db.get("PORT") or 5432),
               "-U", db.get("USER") or "", db["NAME"]]
        with gzip.open(destino, "wb") as gz:
            proc = subprocess.run(cmd, env=env, capture_output=True, check=False)
            if proc.returncode:
                destino.unlink(missing_ok=True)
                raise CommandError(proc.stderr.decode(errors="ignore")[:500])
            gz.write(proc.stdout)
        formato = "pg_dump"
    else:
        destino = pasta / f"geop_{carimbo}.json.gz"
        buf = io.StringIO()
        call_command("dumpdata", "--natural-foreign", "--exclude=contenttypes", "--exclude=auth.permission",
                     "--exclude=sessions", stdout=buf)
        with gzip.open(destino, "wt", encoding="utf-8") as gz:
            gz.write(buf.getvalue())
        formato = "dumpdata"
    os.chmod(destino, 0o640)
    sha = hashlib.sha256(destino.read_bytes()).hexdigest()
    antigos = sorted(pasta.glob("geop_*.gz"))[:-settings.GEOP_BACKUP_MANTER]
    for velho in antigos:
        velho.unlink(missing_ok=True)
    return {"arquivo": destino.name, "tamanho": destino.stat().st_size, "sha256": sha, "formato": formato,
            "removidos": len(antigos)}


class Command(BaseCommand):
    help = "Gera um backup compactado do banco em GEOP_BACKUP_DIR (mantém os últimos GEOP_BACKUP_MANTER)."

    def handle(self, *args, **opts):
        ex = ExecucaoRotina.objects.create(comando="backup")
        try:
            info = criar_backup()
        except Exception as exc:
            ex.status, ex.erro, ex.finalizado_em = "ERRO", str(exc)[:2000], timezone.now()
            ex.save()
            raise
        ex.resumo, ex.finalizado_em = info, timezone.now()
        ex.save()
        self.stdout.write(self.style.SUCCESS(f"Backup {info['arquivo']} ({info['tamanho']} bytes)"))
