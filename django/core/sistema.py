"""Telas do TI: configurações, saúde do sistema, integridade de arquivos e backups."""
import os
from pathlib import Path
import shutil
from datetime import datetime, timedelta

from django.conf import settings
from django.contrib import messages
from django.core.exceptions import PermissionDenied
from django.db import connection
from django.db.migrations.executor import MigrationExecutor
from django.http import FileResponse, Http404
from django.shortcuts import redirect, render
from django.utils import timezone

from .imaging import formatar_bytes
from .models import ArquivoBase, Configuracao, ExecucaoRotina
from .services import auditar


def _somente_ti(request):
    if not request.user.is_authenticated or not request.user.is_ti:
        raise PermissionDenied


CONFIGURAVEIS = [
    ("chat_habilitado", "Chat interno habilitado", "bool"),
    ("os_sem_atualizacao_horas", "Avisar ordens sem atualização após (horas)", "int"),
    ("sla_alerta_horas", "Avisar SLA a vencer com antecedência de (horas)", "int"),
    ("relatorios_limite_gestor", "Emissões de relatório por franquia do gestor", "int"),
]


def configuracoes(request):
    _somente_ti(request)
    if request.method == "POST":
        for chave, _, tipo in CONFIGURAVEIS:
            if tipo == "bool":
                valor = "1" if request.POST.get(chave) else "0"
            else:
                try:
                    valor = str(max(0, int(request.POST.get(chave) or 0)))
                except ValueError:
                    continue
            Configuracao.definir(chave, valor, request.user)
        auditar(request, "configuracao", None, "SALVAR")
        messages.success(request, "Configurações salvas.")
        return redirect("core:configuracoes")
    itens = [{"chave": c, "rotulo": r, "tipo": t, "valor": Configuracao.obter(c)} for c, r, t in CONFIGURAVEIS]
    return render(request, "core/configuracoes.html", {"itens": itens})


def _arquivos_referenciados():
    nomes = set()
    for modelo in ArquivoBase.__subclasses__():
        if modelo._meta.abstract:
            continue
        for a, m in modelo.objects.values_list("arquivo", "miniatura"):
            nomes.update(n for n in (a, m) if n)
    return nomes


def integridade():
    raiz = Path(settings.MEDIA_ROOT)
    referenciados = _arquivos_referenciados()
    existentes, tamanho = set(), 0
    if raiz.exists():
        for base, _, arquivos in os.walk(raiz):
            for nome in arquivos:
                caminho = os.path.join(base, nome)
                existentes.add(os.path.relpath(caminho, raiz).replace(os.sep, "/"))
                tamanho += os.path.getsize(caminho)
    return {"referenciados": len(referenciados), "ausentes": sorted(referenciados - existentes),
            "orfaos": sorted(existentes - referenciados), "tamanho": formatar_bytes(tamanho)}


def saude(request):
    _somente_ti(request)
    checagens = []

    def add(nome, ok, detalhe, alerta=False):
        checagens.append({"nome": nome, "status": "OK" if ok else ("ALERTA" if alerta else "ERRO"), "detalhe": detalhe})

    try:
        with connection.cursor() as c:
            c.execute("SELECT 1")
        add("Banco de dados", True, f"{connection.vendor} conectado")
    except Exception as exc:
        add("Banco de dados", False, str(exc))
    pendentes = MigrationExecutor(connection).migration_plan(MigrationExecutor(connection).loader.graph.leaf_nodes())
    add("Migrações", not pendentes, "em dia" if not pendentes else f"{len(pendentes)} pendente(s): rode migrate")
    media = Path(settings.MEDIA_ROOT)
    add("Pasta de mídia", media.exists() and os.access(media, os.W_OK), str(media))
    if media.exists():
        uso = shutil.disk_usage(media)
        pct = round(uso.used * 100 / uso.total)
        add("Disco", pct < 90, f"{pct}% usado · {formatar_bytes(uso.free)} livres", alerta=True)
    ult = ExecucaoRotina.objects.filter(comando="executar_rotinas").first()
    add("Rotinas automáticas", bool(ult and ult.iniciado_em > timezone.now() - timedelta(hours=2) and ult.status == "OK"),
        f"última: {timezone.localtime(ult.iniciado_em):%d/%m %H:%M} ({ult.status})" if ult else "nunca executadas — agende executar_rotinas",
        alerta=True)
    bkp = ExecucaoRotina.objects.filter(comando="backup", status="OK").first()
    add("Backup", bool(bkp and bkp.iniciado_em > timezone.now() - timedelta(days=2)),
        f"último: {timezone.localtime(bkp.iniciado_em):%d/%m %H:%M}" if bkp else "nenhum backup registrado", alerta=True)
    add("E-mail", "console" not in settings.EMAIL_BACKEND, settings.EMAIL_HOST or "não configurado (convites por link)",
        alerta=True)
    add("Modo debug", not settings.DEBUG, "desligado" if not settings.DEBUG else "LIGADO — não use em produção")
    return render(request, "core/saude.html", {"checagens": checagens, "rotinas": ExecucaoRotina.objects.all()[:20]})


def arquivos(request):
    _somente_ti(request)
    return render(request, "core/integridade.html", {"r": integridade()})


def backups(request):
    _somente_ti(request)
    pasta = settings.GEOP_BACKUP_DIR
    if request.GET.get("baixar"):
        nome = os.path.basename(request.GET["baixar"])
        caminho = pasta / nome
        if not nome.startswith("geop_") or not caminho.is_file():
            raise Http404
        auditar(request, "backup", None, "DOWNLOAD", "", None, nome)
        return FileResponse(open(caminho, "rb"), as_attachment=True, filename=nome)
    if request.method == "POST":
        from .management.commands.backup_banco import criar_backup

        ex = ExecucaoRotina.objects.create(comando="backup")
        try:
            info = criar_backup()
            ex.resumo = info
            messages.success(request, f"Backup criado: {info['arquivo']}.")
        except Exception as exc:
            ex.status, ex.erro = "ERRO", str(exc)[:2000]
            messages.error(request, f"Falha no backup: {exc}")
        ex.finalizado_em = timezone.now()
        ex.save()
        auditar(request, "backup", None, "CRIAR")
        return redirect("core:backups")
    lista = []
    if pasta.exists():
        for f in sorted(pasta.glob("geop_*.gz"), reverse=True):
            lista.append({"nome": f.name, "tamanho": formatar_bytes(f.stat().st_size),
                          "data": datetime.fromtimestamp(f.stat().st_mtime, tz=timezone.get_current_timezone())})
    return render(request, "core/backups.html", {"lista": lista, "pasta": pasta})
