"""Ferramentas que o modelo pode chamar. Todas respeitam as permissões e a visibilidade do usuário."""
from datetime import timedelta

from django.db.models import Count, F, Q, Sum
from django.utils import timezone

from ordens.models import Ordem, Status


def _lim(v, padrao=10, maximo=30):
    try:
        return max(1, min(maximo, int(v or padrao)))
    except (TypeError, ValueError):
        return padrao


def _ordem_dict(o):
    return {"numero": o.numero, "titulo": o.titulo, "status": o.get_status_display(), "prioridade": o.get_prioridade_display(),
            "local": o.local.nome, "categoria": o.categoria.nome, "responsavel": o.agente.nome if o.agente else None,
            "aberta_em": timezone.localtime(o.criado_em).strftime("%d/%m/%Y %H:%M"),
            "prazo": timezone.localtime(o.prazo).strftime("%d/%m/%Y %H:%M") if o.prazo else None, "atrasada": o.atrasada}


def buscar_ordens(user, texto="", status="", local="", dias=90, somente_atrasadas=False, limite=10):
    qs = Ordem.objects.visiveis_para(user).select_related("local", "categoria", "agente").filter(
        criado_em__gte=timezone.now() - timedelta(days=_lim(dias, 90, 3650)))
    if texto:
        for t in texto.split()[:5]:
            qs = qs.filter(Q(titulo__icontains=t) | Q(descricao__icontains=t) | Q(numero__icontains=t))
    if status:
        mapa = {s.label.lower(): s.value for s in Status}
        qs = qs.filter(status=mapa.get(status.lower(), status.upper()))
    if local:
        qs = qs.filter(local__nome__icontains=local)
    if somente_atrasadas:
        qs = qs.atrasadas()
    total = qs.count()
    return {"total": total, "ordens": [_ordem_dict(o) for o in qs.order_by("-criado_em")[:_lim(limite)]]}


def resumir_ordem(user, numero):
    o = Ordem.objects.visiveis_para(user).select_related("local", "categoria", "agente", "gestor").filter(
        numero__iexact=(numero or "").strip()).first()
    if not o:
        return {"erro": "Ordem não encontrada ou sem permissão."}
    d = _ordem_dict(o)
    d.update({"descricao": o.descricao[:1500], "gestor": o.gestor.nome,
              "historico": [f"{timezone.localtime(h.criado_em):%d/%m %H:%M} — {h.descricao[:200]}" for h in o.historico.all()[:15]],
              "materiais": [f"{m.nome_material} ({m.quantidade} {m.unidade})" for m in o.materiais.all()[:20]],
              "fotos": o.anexos.filter(mime__startswith="image/").count(),
              "checklist_pendente": list(o.checklist.filter(concluido=False).values_list("descricao", flat=True)[:20])})
    if user.pode("ia.financeiro"):
        d["custo_total"] = float(o.custo_total_final)
    return d


def consultar_estoque(user, texto="", abaixo_minimo=False):
    if not user.pode_algum("estoque.gerenciar", "compras.solicitar", "os.executar"):
        return {"erro": "Sem permissão para consultar o estoque."}
    from estoque.models import MaterialEstoque

    qs = MaterialEstoque.objects.filter(ativo=True)
    if texto:
        qs = qs.filter(nome__icontains=texto)
    if abaixo_minimo:
        qs = qs.filter(quantidade_atual__lte=F("estoque_minimo"))
    return {"materiais": [{"nome": m.nome, "saldo": float(m.quantidade_atual), "minimo": float(m.estoque_minimo),
                           "unidade": m.unidade} for m in qs[:30]]}


def consultar_preventivas(user, dias=30):
    from operacao.models import Preventiva

    ate = timezone.localdate() + timedelta(days=_lim(dias, 30, 365))
    return {"preventivas": [{"titulo": p.titulo, "proxima": p.proxima_execucao.strftime("%d/%m/%Y"), "local": p.local.nome,
                             "intervalo_dias": p.intervalo_dias}
                            for p in Preventiva.objects.filter(ativo=True, proxima_execucao__lte=ate).select_related("local")[:30]]}


def consultar_indicadores(user, dias=30):
    from relatorios.services import indicadores

    qs = Ordem.objects.visiveis_para(user).filter(criado_em__gte=timezone.now() - timedelta(days=_lim(dias, 30, 3650)))
    k = indicadores(qs)
    k.pop("agora", None)
    if not user.pode("ia.financeiro"):
        for chave in ("custo_materiais", "custo_total", "orcamento_previsto"):
            k.pop(chave, None)
    k = {c: (float(v) if hasattr(v, "quantize") else v) for c, v in k.items()}
    k["por_categoria"] = list(qs.values("categoria__nome").annotate(n=Count("id")).order_by("-n")[:8])
    return k


def consultar_custos(user, dias=30):
    if not user.pode("ia.financeiro"):
        return {"erro": "Seu perfil não pode consultar custos pelo GEOP IA."}
    qs = Ordem.objects.visiveis_para(user).filter(criado_em__gte=timezone.now() - timedelta(days=_lim(dias, 30, 3650)))
    tot = qs.aggregate(materiais=Sum("custo_materiais"), mao_obra=Sum("custo_mao_obra"), terceiros=Sum("custo_terceiros"),
                       total=Sum("custo_total_final"), previsto=Sum("valor_previsto_orcamento"))
    return {"totais": {k: float(v or 0) for k, v in tot.items()},
            "maiores": [{"numero": o.numero, "titulo": o.titulo, "custo": float(o.custo_total_final)}
                        for o in qs.order_by("-custo_total_final")[:10]]}


def consultar_conhecimento(user, texto=""):
    if not user.pode_algum("conhecimento.visualizar", "conhecimento.gerenciar"):
        return {"erro": "Sem acesso à base de conhecimento."}
    from conhecimento.services import buscar

    return {"artigos": [{"titulo": a.titulo, "categoria": a.categoria.nome if a.categoria else None,
                         "solucao": a.conteudo[:800]} for a in buscar(texto)[:5]]}


def consultar_aprovacoes(user):
    from operacao.services import pendencias_aprovacao

    return {"pendencias": [{"grupo": t, "quantidade": len(itens)} for _, t, itens in pendencias_aprovacao(user)]}


def consultar_reincidencias(user, dias=90):
    from relatorios.services import reincidencias

    locais, patrimonios = reincidencias(user, _lim(dias, 90, 3650))
    return {"local_categoria": [{"local": r["local__nome"], "categoria": r["categoria__nome"], "ocorrencias": r["ocorrencias"]}
                                for r in locais[:15]],
            "patrimonios": [{"patrimonio": p["numero_patrimonio"], "ocorrencias": p["ocorrencias"]} for p in patrimonios[:15]]}


def consultar_liberacoes(user, dias=0):
    if not user.pode_algum("cftv.liberacoes", "cftv.historico", "controle_acesso.registrar"):
        return {"erro": "Sem acesso às liberações."}
    from cftv.services import liberacoes_do_dia

    dia = timezone.localdate() + timedelta(days=int(dias or 0))
    return {"dia": dia.strftime("%d/%m/%Y"), "liberacoes": [
        {"nome": l.nome_completo, "destino": l.destino_nome, "inicio": l.hora_inicio.strftime("%H:%M") if l.hora_inicio else None}
        for l in liberacoes_do_dia(dia)[:40]]}


FERRAMENTAS = {
    "buscar_ordens": (buscar_ordens, "Busca ordens de serviço/limpeza por texto, status, local, período ou atraso.", {
        "texto": {"type": "string"}, "status": {"type": "string"}, "local": {"type": "string"},
        "dias": {"type": "integer", "description": "Janela de dias (padrão 90)"},
        "somente_atrasadas": {"type": "boolean"}, "limite": {"type": "integer"}}),
    "resumir_ordem": (resumir_ordem, "Detalhes de uma ordem pelo número (ex.: OS-2026-0000123).",
                      {"numero": {"type": "string"}}),
    "consultar_estoque": (consultar_estoque, "Saldo de materiais do estoque.",
                          {"texto": {"type": "string"}, "abaixo_minimo": {"type": "boolean"}}),
    "consultar_preventivas": (consultar_preventivas, "Manutenções preventivas previstas.", {"dias": {"type": "integer"}}),
    "consultar_indicadores": (consultar_indicadores, "Indicadores operacionais (SLA, atrasos, conclusão).",
                              {"dias": {"type": "integer"}}),
    "consultar_custos": (consultar_custos, "Custos das ordens no período.", {"dias": {"type": "integer"}}),
    "consultar_conhecimento": (consultar_conhecimento, "Procedimentos e soluções da base de conhecimento.",
                               {"texto": {"type": "string"}}),
    "consultar_aprovacoes": (consultar_aprovacoes, "Pendências de aprovação do usuário.", {}),
    "consultar_reincidencias": (consultar_reincidencias, "Locais/patrimônios com problemas repetidos.",
                                {"dias": {"type": "integer"}}),
    "consultar_liberacoes": (consultar_liberacoes, "Liberações de acesso de um dia (0 = hoje, 1 = amanhã).",
                             {"dias": {"type": "integer"}}),
}


def definicoes():
    return [{"type": "function", "function": {"name": nome, "description": desc,
                                              "parameters": {"type": "object", "properties": props}}}
            for nome, (_, desc, props) in FERRAMENTAS.items()]


def executar(user, nome, argumentos):
    if nome not in FERRAMENTAS:
        return {"erro": f"Ferramenta desconhecida: {nome}"}
    func, _, props = FERRAMENTAS[nome]
    args = {k: v for k, v in (argumentos or {}).items() if k in props}
    try:
        return func(user, **args)
    except Exception as exc:  # o modelo recebe o erro e pode tentar de outra forma
        return {"erro": f"Falha ao consultar: {exc.__class__.__name__}"}
