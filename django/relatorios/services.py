import csv
import hashlib
import io
import secrets
from datetime import date, datetime, time, timedelta
from decimal import Decimal

from django.conf import settings
from django.db import transaction
from django.db.models import Avg, Count, DurationField, ExpressionWrapper, F, Max, Q, Sum
from django.db.models.functions import TruncMonth
from django.utils import timezone

from core.models import Configuracao
from ordens.models import Ordem, Status

from .models import CotaRelatorio, EmissaoRelatorio, TokenRelatorio

ESSENCIAL = Q(prioridade="CRITICA") | Q(titulo__startswith="[SERVICO ESSENCIAL]")


def _inicio(d):
    return timezone.make_aware(datetime.combine(d, time.min))


def filtrar(user, f):
    qs = Ordem.objects.visiveis_para(user).select_related("local", "categoria", "agente", "gestor")
    if f.get("status") != "PAUSADA":
        qs = qs.exclude(status=Status.PAUSADA) if not f.get("status") else qs
    for campo in ("status", "prioridade", "tipo"):
        if f.get(campo):
            qs = qs.filter(**{campo: f[campo]})
    for campo in ("local", "categoria"):
        if f.get(campo):
            qs = qs.filter(**{campo: f[campo]})
    if f.get("agente"):
        qs = qs.filter(Q(agente=f["agente"]) | Q(agentes__usuario=f["agente"])).distinct()
    if f.get("inicio"):
        qs = qs.filter(criado_em__gte=_inicio(f["inicio"]))
    if f.get("fim"):
        qs = qs.filter(criado_em__lt=_inicio(f["fim"] + timedelta(days=1)))
    return qs


def indicadores(qs):
    agora = timezone.now()
    total = qs.count()
    concluidas = qs.filter(status__in=[Status.CONCLUIDA_PELO_AGENTE, Status.ENCERRADA]).count()
    encerradas = qs.filter(status=Status.ENCERRADA)
    no_prazo = encerradas.filter(prazo__isnull=False, data_encerramento__lte=F("prazo")).count()
    com_prazo = encerradas.filter(prazo__isnull=False).count()
    duracao = encerradas.filter(data_encerramento__isnull=False).annotate(
        d=ExpressionWrapper(F("data_encerramento") - F("criado_em"), output_field=DurationField())).aggregate(m=Avg("d"))["m"]
    custos = qs.aggregate(mat=Sum("custo_materiais"), tot=Sum("custo_total_final"), prev=Sum("valor_previsto_orcamento"))
    return {
        "total": total,
        "essenciais": qs.filter(ESSENCIAL).count(),
        "abertas": qs.exclude(status__in=[Status.ENCERRADA, Status.CONCLUIDA_PELO_AGENTE, Status.REPROVADA]).count(),
        "concluidas": concluidas,
        "taxa_conclusao": round(concluidas * 100 / total) if total else 0,
        "atrasadas": qs.atrasadas().count(),
        "sla_cumprido": round(no_prazo * 100 / com_prazo) if com_prazo else None,
        "tempo_medio_horas": round(duracao.total_seconds() / 3600, 1) if duracao else None,
        "custo_materiais": custos["mat"] or Decimal(0),
        "custo_total": custos["tot"] or Decimal(0),
        "orcamento_previsto": custos["prev"] or Decimal(0),
        "qualidade_media": round(qs.filter(qualidade_percentual__isnull=False).aggregate(m=Avg("qualidade_percentual"))["m"] or 0),
        "agora": agora,
    }


def _agrupar(qs, campo, rotulo=None):
    linhas = list(qs.order_by().values(campo).annotate(total=Count("id", distinct=True)).order_by("-total"))
    maximo = max([l["total"] for l in linhas] or [1])
    for l in linhas:
        valor = l[campo]
        l["rotulo"] = rotulo(valor) if rotulo else (valor or "—")
        l["pct"] = round(l["total"] * 100 / maximo)
    return linhas


def agrupamentos(qs):
    return {
        "status": _agrupar(qs, "status", lambda v: Status(v).label if v in Status.values else v),
        "categoria": _agrupar(qs, "categoria__nome"),
        "local": _agrupar(qs, "local__nome")[:15],
        "prioridade": _agrupar(qs, "prioridade"),
        "agente": _agrupar(qs.filter(agente__isnull=False), "agente__nome"),
    }


def ranking_atraso(qs):
    atrasadas = qs.atrasadas().filter(agente__isnull=False)
    linhas = list(atrasadas.order_by().values("agente__nome").annotate(total=Count("id")).order_by("-total")[:10])
    return linhas


def idade_ativas(qs):
    agora = timezone.now()
    faixas = [("Até 1 dia", 0, 1), ("1 a 3 dias", 1, 3), ("3 a 7 dias", 3, 7), ("7 a 30 dias", 7, 30), ("Mais de 30 dias", 30, 100000)]
    ativas = qs.exclude(status__in=[Status.ENCERRADA, Status.CONCLUIDA_PELO_AGENTE, Status.REPROVADA])
    return [{"rotulo": r, "total": ativas.filter(criado_em__lte=agora - timedelta(days=a),
                                                  criado_em__gt=agora - timedelta(days=b)).count()} for r, a, b in faixas]


def evolucao_mensal(qs, meses=12):
    desde = (timezone.localdate().replace(day=1) - timedelta(days=31 * (meses - 1))).replace(day=1)
    linhas = list(qs.filter(criado_em__gte=_inicio(desde)).annotate(mes=TruncMonth("criado_em")).order_by()
                  .values("mes").annotate(abertas=Count("id"), encerradas=Count("id", filter=Q(status=Status.ENCERRADA)),
                                          custo=Sum("custo_total_final")).order_by("mes"))
    maximo = max([l["abertas"] for l in linhas] or [1])
    for l in linhas:
        l["pct"] = round(l["abertas"] * 100 / maximo)
    return linhas


def tempos_por_status(qs):
    from operacao.models import StatusTempo

    linhas = list(StatusTempo.objects.filter(ordem__in=qs, segundos__isnull=False).values("status")
                  .annotate(media=Avg("segundos"), n=Count("id")).order_by("-media"))
    for l in linhas:
        l["horas"] = round((l["media"] or 0) / 3600, 1)
        l["rotulo"] = Status(l["status"]).label if l["status"] in Status.values else l["status"]
    return linhas


def reincidencias(user, dias=90):
    desde = timezone.now() - timedelta(days=dias)
    qs = Ordem.objects.visiveis_para(user).filter(criado_em__gte=desde)
    locais = list(qs.order_by().values("local__nome", "categoria__nome", "local", "categoria")
                  .annotate(ocorrencias=Count("id"), custo=Sum("custo_total_final"), ultima=Max("criado_em"))
                  .filter(ocorrencias__gte=3).order_by("-ocorrencias")[:50])
    from ordens.models import OrdemPatrimonio

    patrimonios = list(OrdemPatrimonio.objects.filter(ordem__in=qs).order_by().values("numero_patrimonio", "descricao")
                       .annotate(ocorrencias=Count("ordem", distinct=True), custo=Sum("ordem__custo_total_final"))
                       .filter(ocorrencias__gte=2).order_by("-ocorrencias")[:50])
    return locais, patrimonios


def mensal(user, ano, mes):
    inicio = date(ano, mes, 1)
    fim = (inicio + timedelta(days=32)).replace(day=1)
    qs = Ordem.objects.visiveis_para(user).filter(criado_em__gte=_inicio(inicio), criado_em__lt=_inicio(fim))
    k = indicadores(qs)
    porcat = list(qs.order_by().values("categoria__nome").annotate(qtd=Count("id"), custo=Sum("custo_total_final"))
                  .order_by("-qtd")[:12])
    porlocal = list(qs.order_by().values("local__nome").annotate(qtd=Count("id"), custo=Sum("custo_total_final"))
                    .order_by("-qtd")[:12])
    top = list(qs.order_by("-custo_total_final")[:15])
    return {"inicio": inicio, "fim": fim, "k": k, "categorias": porcat, "locais": porlocal, "top_custos": top,
            "custos": qs.aggregate(materiais=Sum("custo_materiais"), mao=Sum("custo_mao_obra"),
                                   terceiros=Sum("custo_terceiros"), total=Sum("custo_total_final"),
                                   previsto=Sum("valor_previsto_orcamento"))}


def csv_ordens(qs):
    buf = io.StringIO()
    buf.write("﻿")  # BOM: o Excel abre com acentos corretos
    w = csv.writer(buf, delimiter=";")
    w.writerow(["Número", "Tipo", "Título", "Local", "Categoria", "Prioridade", "Status", "Solicitante", "Gestor",
                "Responsável", "Aberta em", "Prazo", "Encerrada em", "Atrasada", "Custo materiais", "Custo total",
                "Qualidade (%)"])
    fmt = lambda d: timezone.localtime(d).strftime("%d/%m/%Y %H:%M") if d else ""  # noqa: E731
    for o in qs.select_related("solicitante"):
        w.writerow([o.numero, o.tipo, o.titulo, o.local.nome, o.categoria.nome, o.get_prioridade_display(),
                    o.get_status_display(), o.solicitante.nome, o.gestor.nome, o.agente.nome if o.agente else "",
                    fmt(o.criado_em), fmt(o.prazo), fmt(o.data_encerramento), "sim" if o.atrasada else "não",
                    f"{o.custo_materiais:.2f}".replace(".", ","), f"{o.custo_total_final:.2f}".replace(".", ","),
                    o.qualidade_percentual if o.qualidade_percentual is not None else ""])
    return buf.getvalue()


# --------------------------------------------------------------------- franquia do gestor

def sujeito_a_cota(user):
    perfis = {p.strip().upper() for p in getattr(settings, "GEOP_RELATORIOS_COTA_PERFIS", "GESTOR").split(",") if p.strip()}
    return not user.is_ti and user.perfil_codigo in perfis


def limite_cota():
    return Configuracao.obter_int("relatorios_limite_gestor", 10)


def status_cota(user):
    if not sujeito_a_cota(user):
        return None
    cota = CotaRelatorio.objects.filter(usuario=user).first()
    usados = cota.usados if cota else 0
    return {"limite": limite_cota(), "usados": usados, "restantes": max(0, limite_cota() - usados)}


def registrar_emissao(user, tipo, filtros=None):
    """Retorna False quando a franquia acabou (exige token do TI)."""
    with transaction.atomic():
        if sujeito_a_cota(user):
            cota, _ = CotaRelatorio.objects.select_for_update().get_or_create(usuario=user)
            if cota.usados >= limite_cota():
                return False
            cota.usados += 1
            cota.save(update_fields=["usados"])
        EmissaoRelatorio.objects.create(usuario=user, tipo=tipo, filtros={k: str(v) for k, v in (filtros or {}).items() if v})
    return True


def _hash(token):
    return hashlib.sha256(f"{settings.SECRET_KEY}|relatorio|{token.strip().upper()}".encode()).hexdigest()


def gerar_token(ti, destinatario=None, horas=24):
    token = secrets.token_hex(4).upper()
    TokenRelatorio.objects.create(token_hash=_hash(token), gerado_por=ti, destinatario=destinatario,
                                  expira_em=timezone.now() + timedelta(hours=horas))
    return token


def usar_token(user, token):
    with transaction.atomic():
        t = TokenRelatorio.objects.select_for_update().filter(
            token_hash=_hash(token or ""), usado_em__isnull=True, expira_em__gt=timezone.now()).first()
        if not t or (t.destinatario_id and t.destinatario_id != user.pk):
            return False
        t.usado_por, t.usado_em = user, timezone.now()
        t.save()
        CotaRelatorio.objects.update_or_create(usuario=user, defaults={"usados": 0, "renovada_em": timezone.now()})
    return True
