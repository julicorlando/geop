from django.db.models import Count, Q

from ia.heuristica import tokens

from .models import Artigo


def gerar_artigo_da_ordem(ordem, responsavel):
    """Ao encerrar uma OS com solução descrita, publica um artigo de 'solução histórica' (como no PHP)."""
    solucao = (ordem.observacao_aprovacao or ordem.solicitacao_encerramento_obs or "").strip()
    if len(solucao) < 10 or Artigo.objects.filter(origem_ordem=ordem).exists():
        return None
    return Artigo.objects.create(
        titulo=f"Solução histórica — {ordem.titulo}"[:200], categoria=ordem.categoria, problema=ordem.descricao,
        conteudo=solucao, palavras_chave=", ".join(filter(None, [ordem.categoria.nome, ordem.titulo, ordem.numero]))[:255],
        origem_ordem=ordem, responsavel=responsavel if getattr(responsavel, "is_authenticated", False) else None,
    )


def buscar(termo="", categoria=None, incluir_inativos=False):
    qs = Artigo.objects.select_related("categoria", "responsavel").annotate(
        uteis=Count("avaliacoes", filter=Q(avaliacoes__util=True)),
        nao_uteis=Count("avaliacoes", filter=Q(avaliacoes__util=False))).order_by("-atualizado_em")
    if not incluir_inativos:
        qs = qs.filter(status=Artigo.Status.PUBLICADO)
    if categoria:
        qs = qs.filter(categoria=categoria)
    for t in tokens(termo)[:6]:
        qs = qs.filter(Q(titulo__icontains=t) | Q(conteudo__icontains=t) | Q(problema__icontains=t)
                       | Q(palavras_chave__icontains=t))
    return qs


def sugestoes(ordem, limite=5):
    """Artigos publicados da mesma categoria que compartilham palavras com a ordem."""
    termos = set(tokens(f"{ordem.titulo} {ordem.descricao}"))
    candidatos = Artigo.objects.filter(status=Artigo.Status.PUBLICADO).filter(
        Q(categoria=ordem.categoria) | Q(categoria__isnull=True)).exclude(origem_ordem=ordem)[:200]
    pontuados = []
    for a in candidatos:
        comum = termos & set(tokens(f"{a.titulo} {a.problema} {a.palavras_chave}"))
        if comum:
            pontuados.append((len(comum), a))
    pontuados.sort(key=lambda x: -x[0])
    return [a for _, a in pontuados[:limite]]
