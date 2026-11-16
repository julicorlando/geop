"""Assistente local (sem serviço externo): prioridade sugerida, categorias, materiais e duplicidades.

Mesmas regras de geop_v8_ai_assist / geop_v8_find_duplicates do PHP.
"""
import re

from core.models import normalizar

STOP = {"de", "da", "do", "das", "dos", "e", "a", "o", "as", "os", "em", "no", "na", "nos", "nas", "para", "por",
        "com", "um", "uma", "ao", "the"}
SINONIMOS = {
    "eletrica": ["lampada", "luminaria", "energia", "fio", "cabo", "disjuntor", "tomada", "curto"],
    "hidraulica": ["vazamento", "torneira", "tubo", "registro", "bomba", "agua", "hidrante"],
    "climatizacao": ["ar condicionado", "climatizacao", "evaporadora", "condensadora", "filtro"],
    "ti": ["rede", "internet", "wifi", "computador", "impressora", "switch", "roteador", "camera"],
    "limpeza": ["limpeza", "higienizacao", "lavagem", "lixeira", "banheiro", "piso"],
    "civil": ["pintura", "parede", "piso", "telhado", "massa", "porta", "forro"],
}


def texto_normalizado(texto: str) -> str:
    t = re.sub(r"[^a-z0-9\s-]+", " ", normalizar(texto))
    return re.sub(r"\s+", " ", t).strip()


def tokens(texto: str) -> list[str]:
    vistos = []
    for t in texto_normalizado(texto).split():
        if len(t) >= 3 and t not in STOP and t not in vistos:
            vistos.append(t)
    return vistos


def similaridade(a: str, b: str) -> float:
    ta, tb = set(tokens(a)), set(tokens(b))
    if not ta or not tb:
        return 0.0
    return len(ta & tb) / len(ta | tb)


def duplicidades(titulo, descricao, local=None, categoria=None, excluir=None, limite=5, minimo=0.25):
    from ordens.models import Ordem, Status

    qs = Ordem.objects.ativas().exclude(status__in=[Status.ENCERRADA, Status.REPROVADA])
    if local:
        qs = qs.filter(local=local)
    if categoria:
        qs = qs.filter(categoria=categoria)
    if excluir:
        qs = qs.exclude(pk=excluir.pk)
    base = f"{titulo} {descricao}"
    achados = []
    for o in qs.order_by("-criado_em")[:100]:
        s = similaridade(base, f"{o.titulo} {o.descricao}")
        if s >= minimo:
            achados.append({"id": o.pk, "numero": o.numero, "titulo": o.titulo, "status": o.get_status_display(),
                            "similaridade": round(s, 2), "url": o.get_absolute_url()})
    achados.sort(key=lambda d: -d["similaridade"])
    return achados[:limite]


def prioridade_sugerida(texto: str) -> str:
    t = texto_normalizado(texto)
    if re.search(r"incend|choque|curto|vazamento grande|alagamento|risco|pane total|sem energia|urgente|emergenc", t):
        return "CRITICA"
    if re.search(r"parado|quebrado|vazamento|falha|nao funciona|inoperante", t):
        return "ALTA"
    if re.search(r"preventiv|limpeza|ajuste|inspec|verificar", t):
        return "BAIXA"
    return "MEDIA"


def assistir(titulo, descricao, local=None):
    from core.models import Categoria
    from estoque.models import MaterialEstoque

    texto = texto_normalizado(f"{titulo} {descricao}")
    categorias = []
    for c in Categoria.objects.filter(ativo=True):
        nome = texto_normalizado(c.nome)
        score = sum(3 for t in tokens(nome) if t in texto)
        for chave, termos in SINONIMOS.items():
            if chave in nome:
                score += sum(2 for termo in termos if termo in texto)
        if score:
            categorias.append({"id": c.pk, "nome": c.nome, "score": score})
    categorias.sort(key=lambda d: -d["score"])
    materiais = []
    for m in MaterialEstoque.objects.filter(ativo=True):
        mt = tokens(m.nome)
        hits = sum(1 for t in mt if t in texto)
        if (hits >= 1 and len(mt) <= 5) or hits >= 2:
            materiais.append({"id": m.pk, "nome": m.nome, "disponivel": float(m.quantidade_atual), "score": hits})
    materiais.sort(key=lambda d: -d["score"])
    return {
        "prioridade_sugerida": prioridade_sugerida(f"{titulo} {descricao}"),
        "categorias_sugeridas": categorias[:3],
        "materiais_sugeridos": materiais[:5],
        "duplicidades": duplicidades(titulo, descricao, local=local),
        "observacao": "Sugestões automáticas. A decisão final é sua.",
    }
