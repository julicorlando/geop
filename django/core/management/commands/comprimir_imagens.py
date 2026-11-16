"""Reduz as fotos já gravadas que ainda não passaram pela compressão.

    python manage.py comprimir_imagens            # simula e mostra a economia
    python manage.py comprimir_imagens --aplicar  # regrava as fotos

Útil se a mídia foi copiada manualmente do PHP (`--sem-arquivos` na importação)
ou após mudar GEOP_IMAGEM_LADO_MAX / GEOP_IMAGEM_QUALIDADE.
"""
import os

from django.core.files.base import ContentFile
from django.core.management.base import BaseCommand

from ambientes.models import RegistroFoto
from core.imaging import TIPOS_IMAGEM, comprimir_imagem, detectar_mime, formatar_bytes
from ordens.models import Anexo


class Command(BaseCommand):
    help = "Comprime fotos de ordens e ambientes que ainda estão no tamanho original."

    def add_arguments(self, parser):
        parser.add_argument("--aplicar", action="store_true", help="Grava as versões reduzidas (sem isso, só simula).")
        parser.add_argument("--todas", action="store_true", help="Inclui fotos já comprimidas (recomprime).")

    def handle(self, *args, **opts):
        antes = depois = n = 0
        for modelo in (Anexo, RegistroFoto):
            qs = modelo.objects.exclude(arquivo="")
            if not opts["todas"]:
                qs = qs.filter(miniatura="")
            for obj in qs.iterator():
                storage = obj.arquivo.storage
                if not storage.exists(obj.arquivo.name):
                    continue
                with storage.open(obj.arquivo.name, "rb") as fh:
                    dados = fh.read()
                if detectar_mime(dados[:16]) not in TIPOS_IMAGEM:
                    continue
                try:
                    r = comprimir_imagem(dados)
                except Exception as exc:  # imagem corrompida: mantém a original
                    self.stderr.write(f"{modelo.__name__} #{obj.pk}: {exc}")
                    continue
                if len(r["dados"]) >= len(dados) and obj.miniatura:
                    continue
                antes += len(dados)
                depois += len(r["dados"])
                n += 1
                if opts["aplicar"]:
                    base = os.path.splitext(obj.arquivo.name)[0]
                    antigo = obj.arquivo.name
                    obj.arquivo.save(f"{base}.{r['extensao']}", ContentFile(r["dados"]), save=False)
                    obj.miniatura.save(f"{base}_mini.{r['extensao']}", ContentFile(r["miniatura"]), save=False)
                    obj.tamanho_original = obj.tamanho_original or len(dados)
                    obj.tamanho, obj.mime, obj.largura, obj.altura = len(r["dados"]), r["mime"], r["largura"], r["altura"]
                    obj.save()
                    # Fotos copiadas do ambiente para a OS apontam para o mesmo arquivo.
                    for outro in (Anexo, RegistroFoto):
                        outro.objects.filter(arquivo=antigo).update(
                            arquivo=obj.arquivo.name, miniatura=obj.miniatura.name, tamanho=obj.tamanho,
                            mime=obj.mime, largura=obj.largura, altura=obj.altura)
                    if antigo != obj.arquivo.name:
                        storage.delete(antigo)
        acao = "Comprimidas" if opts["aplicar"] else "Seriam comprimidas"
        self.stdout.write(self.style.SUCCESS(f"{acao}: {n} foto(s), {formatar_bytes(antes)} → {formatar_bytes(depois)}."))
