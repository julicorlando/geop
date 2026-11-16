"""Gestão integrada: recomendação de agentes, preventivas e rotinas automáticas (gestao_integrada.php, cron.php)."""
from datetime import timedelta

from django.contrib.auth import get_user_model
from django.db import transaction
from django.db.models import Count, F, Q
from django.urls import reverse
from django.utils import timezone

from core.models import Configuracao, normalizar
from core.services import notificar, notificar_permissao
from ordens.models import Ordem, Status

from .models import EquipeEstado, Especialidade, Feriado, Preventiva, SlaEscalonamento

STATUS_ATIVOS_EXCLUIDOS = [Status.ENCERRADA, Status.CONCLUIDA_PELO_AGENTE, Status.REPROVADA]


def executores():
    Usuario = get_user_model()
    return Usuario.objects.filter(is_active=True, perfil__permissoes__codigo="os.executar").distinct()


def recomendar_agentes(categoria=None, local=None, limite=5):
    """Ordena os agentes por especialidade na categoria, disponibilidade e carga atual."""
    estados = {e.usuario_id: e for e in EquipeEstado.objects.all()}
    especialidades = {}
    if categoria:
        nome = normalizar(categoria.nome)
        for esp in Especialidade.objects.all():
            if normalizar(esp.especialidade) == nome:
                especialidades[esp.usuario_id] = esp.nivel
    cargas = dict(Ordem.objects.ativas().exclude(status__in=STATUS_ATIVOS_EXCLUIDOS).filter(agente__isnull=False)
                  .values_list("agente").annotate(n=Count("id")))
    ordem_estado = {"DISPONIVEL": 0, "EM_ATENDIMENTO": 1}
    resultado = []
    for u in executores():
        est = estados.get(u.pk)
        estado = est.estado if est else "DISPONIVEL"
        capacidade = est.capacidade_maxima if est else 5
        carga = cargas.get(u.pk, 0)
        if estado in EquipeEstado.INDISPONIVEIS or carga >= capacidade:
            continue
        resultado.append({"id": u.pk, "nome": u.nome, "estado": estado, "carga": carga, "capacidade": capacidade,
                          "especialidade": especialidades.get(u.pk, 0)})
    resultado.sort(key=lambda r: (-r["especialidade"], ordem_estado.get(r["estado"], 2), r["carga"], r["nome"]))
    return resultado[:limite]


def proximo_dia_util(data, local=None):
    feriados = set(Feriado.objects.filter(ativo=True).filter(Q(local__isnull=True) | Q(local=local))
                   .values_list("data", flat=True))
    while data.weekday() == 6 or data in feriados:  # domingos e feriados
        data += timedelta(days=1)
    return data


def gerar_preventivas(simular=False, hoje=None):
    """Gera as ordens das preventivas vencidas (equivalente a cron_preventivas)."""
    from ordens.services import criar_ordem_sistema

    hoje = hoje or timezone.localdate()
    resultado = {"elegiveis": 0, "criadas": 0, "ordens": []}
    for prev in Preventiva.objects.filter(ativo=True, proxima_execucao__lte=hoje).select_related("local", "categoria",
                                                                                                    "agente"):
        resultado["elegiveis"] += 1
        if simular:
            continue
        with transaction.atomic():
            prev = Preventiva.objects.select_for_update().get(pk=prev.pk)
            if prev.proxima_execucao > hoje:
                continue
            ciclo = prev.proxima_execucao
            if Ordem.objects.ativas().filter(preventiva=prev, criado_em__date__gte=ciclo).exists():
                pass  # já gerada neste ciclo; apenas avança a data
            else:
                agente = prev.agente
                estado = EquipeEstado.objects.filter(usuario=agente).first() if agente else None
                if agente and estado and estado.estado in EquipeEstado.INDISPONIVEIS:
                    agente = None
                if not agente:
                    rec = recomendar_agentes(prev.categoria, prev.local, 1)
                    agente = get_user_model().objects.filter(pk=rec[0]["id"]).first() if rec else None
                ordem = criar_ordem_sistema({
                    "titulo": prev.titulo, "descricao": prev.descricao or f"Manutenção preventiva: {prev.titulo}",
                    "local": prev.local, "categoria": prev.categoria, "prioridade": prev.prioridade,
                    "agente": agente, "origem": "PREVENTIVA", "preventiva": prev,
                    "gestor": prev.criado_por,
                })
                resultado["criadas"] += 1
                resultado["ordens"].append(ordem.numero)
            prev.ultimo_gerado_em = timezone.now()
            prev.proxima_execucao = proximo_dia_util(ciclo + timedelta(days=max(1, prev.intervalo_dias)), prev.local)
            prev.save(update_fields=["ultimo_gerado_em", "proxima_execucao"])
    return resultado


def alertas_sla():
    """Notifica SLA próximo (≤ N h) e vencido; escala para a gestão quando vencido, crítico ou sem responsável."""
    horas = Configuracao.obter_int("sla_alerta_horas", 6)
    limite = timezone.now() + timedelta(hours=horas)
    n = 0
    gestores = [u for u in get_user_model().objects.filter(is_active=True) if u.pode("os.gerenciar") and not u.is_ti]
    for ordem in (Ordem.objects.ativas().exclude(status__in=STATUS_ATIVOS_EXCLUIDOS)
                  .filter(prazo__isnull=False, prazo__lte=limite).select_related("gestor", "agente")):
        vencida = ordem.prazo < timezone.now()
        nivel = "VENCIDA" if vencida else "PROXIMA"
        destinos = {u for u in (ordem.gestor, ordem.agente) if u}
        if vencida or not ordem.agente_id or ordem.prioridade == "CRITICA":
            destinos |= set(gestores)
        for u in destinos:
            _, criado = SlaEscalonamento.objects.get_or_create(ordem=ordem, nivel=nivel, destinatario=u)
            if criado:
                notificar(u, f"SLA_{nivel}", "SLA vencido" if vencida else "SLA próximo do vencimento",
                          f"{ordem.numero} — {ordem.titulo}", ordem.get_absolute_url(), f"sla-{nivel}-{ordem.pk}-{u.pk}")
                n += 1
    return n


def alertas_ordens_paradas():
    horas = Configuracao.obter_int("os_sem_atualizacao_horas", 48)
    corte = timezone.now() - timedelta(hours=horas)
    n = 0
    for ordem in (Ordem.objects.ativas().exclude(status__in=STATUS_ATIVOS_EXCLUIDOS)
                  .exclude(status=Status.PAUSADA).filter(atualizado_em__lt=corte)[:200]):
        for u in {ordem.gestor, ordem.agente} - {None}:
            if notificar(u, "OS_SEM_ATUALIZACAO", "Ordem sem atualização",
                         f"{ordem.numero} — {ordem.titulo} está sem atualização há mais de {horas} h.",
                         ordem.get_absolute_url(), f"parada-{ordem.pk}-{u.pk}-{ordem.atualizado_em:%Y%m%d}"):
                n += 1
    return n


def alertas_estoque():
    from estoque.models import MaterialEstoque

    n = 0
    for m in MaterialEstoque.objects.filter(ativo=True, quantidade_atual__lte=F("estoque_minimo")):
        notificar_permissao("estoque.gerenciar", "ESTOQUE_MINIMO", "Estoque mínimo",
                            f"{m.nome} está com {m.quantidade_atual} (mínimo {m.estoque_minimo}).",
                            reverse("estoque:material", args=[m.pk]),
                            f"estoque-min-{m.pk}-{timezone.localdate():%Y%m%d}")
        n += 1
    return n


def alertas_preventivas_proximas():
    hoje = timezone.localdate()
    n = 0
    for p in Preventiva.objects.filter(ativo=True, proxima_execucao__range=(hoje, hoje + timedelta(days=7))):
        for u in {p.criado_por, p.agente} - {None}:
            if notificar(u, "PREVENTIVA_PROXIMA", "Preventiva próxima", f"{p.titulo} em {p.proxima_execucao:%d/%m}",
                         reverse("operacao:preventivas"), f"prev-{p.pk}-{p.proxima_execucao}-{u.pk}"):
                n += 1
    return n


def pendencias_aprovacao(user):
    """Tudo que espera decisão de alguém, filtrado pelo que o usuário pode decidir (central de aprovações)."""
    from ambientes.models import Registro
    from cftv.models import SolicitacaoAcesso
    from estoque.models import Compra, Reserva
    from ordens.models import SolicitacaoExclusao, SolicitacaoReabertura

    grupos = []
    if user.pode("estoque.gerenciar"):
        grupos.append(("estoque", "Consumo com saldo insuficiente",
                       Reserva.objects.filter(status=Reserva.Status.APROVACAO_NEGATIVO).select_related(
                           "ordem", "material", "usuario")))
    if user.pode("compras.gerenciar"):
        grupos.append(("compras", "Compras aguardando aprovação",
                       Compra.objects.filter(status=Compra.Status.SOLICITADA).exclude(
                           solicitante=user if not user.is_ti else None).select_related("ordem", "solicitante")))
    if user.pode("os.gerenciar"):
        grupos.append(("conclusoes", "Conclusões aguardando aprovação",
                       Ordem.objects.visiveis_para(user).filter(status=Status.CONCLUIDA_PELO_AGENTE)
                       .select_related("agente", "local")))
        grupos.append(("encerramentos", "Encerramento solicitado (link público / QR)",
                       Ordem.objects.visiveis_para(user).filter(solicitacao_encerramento=True)
                       .exclude(status=Status.ENCERRADA).select_related("local")))
    if user.pode("os.excluir_analisar"):
        grupos.append(("exclusoes", "Exclusões de ordens",
                       SolicitacaoExclusao.objects.filter(estado="PENDENTE").exclude(solicitado_por=user)
                       .select_related("ordem", "solicitado_por")))
    if user.is_ti:
        grupos.append(("reaberturas", "Tokens de reabertura solicitados",
                       SolicitacaoReabertura.objects.filter(estado="PENDENTE").select_related("ordem", "solicitado_por")))
        grupos.append(("exclusoes_exec", "Exclusões aprovadas (executar)",
                       SolicitacaoExclusao.objects.filter(estado="APROVADA").select_related("ordem")))
    if user.pode("cftv.liberacoes"):
        grupos.append(("acessos", "Solicitações de acesso",
                       SolicitacaoAcesso.objects.filter(status="PENDENTE")))
    if user.pode("ambientes.validar"):
        grupos.append(("limpezas", "Limpezas para validar",
                       Registro.objects.filter(status=Registro.Status.AGUARDANDO_VALIDACAO).select_related("ambiente", "asg")))
    return [(chave, titulo, list(qs[:50])) for chave, titulo, qs in grupos]
