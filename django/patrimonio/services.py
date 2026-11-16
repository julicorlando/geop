"""Consulta ao banco externo de patrimônio (somente leitura) e vínculo com ordens.

Configure `PATRIMONIO_DATABASE_URL` e, se necessário, `PATRIMONIO_TABELA` e
`PATRIMONIO_CAMPO_<CHAVE>` (mesmo mapeamento do config/patrimonio.php).
Sem banco configurado, o vínculo continua funcionando com dados digitados.
"""
import os
import re

from django.core.exceptions import PermissionDenied
from django.db import connections

from ordens.models import OrdemPatrimonio
from ordens.services import RegraNegocio, historico, pode_executar, pode_gerenciar
from core.services import auditar

CAMPOS_PADRAO = {
    "id": "id",
    "codigo_barras": "tomb_number",
    "numero_patrimonio": "tomb_number",
    "descricao": "notes",
    "marca": "brand",
    "modelo": "model",
    "serie": "serial_number",
    "setor": "department_id",
}
IDENT = re.compile(r"^[A-Za-z_][A-Za-z0-9_]*$")


def configurado() -> bool:
    return "patrimonio" in connections.databases


def _campos():
    campos = {}
    for chave, padrao in CAMPOS_PADRAO.items():
        coluna = os.getenv(f"PATRIMONIO_CAMPO_{chave.upper()}", padrao).strip()
        if coluna and IDENT.match(coluna):
            campos[chave] = coluna
    return campos


def normalizar_codigo(codigo: str) -> str:
    return re.sub(r"[^0-9A-Za-z\-_.]", "", (codigo or "").strip())[:190]


def buscar(codigo: str, limite: int = 10) -> list[dict]:
    codigo = normalizar_codigo(codigo)
    if not codigo or not configurado():
        return []
    tabela = os.getenv("PATRIMONIO_TABELA", "assets")
    if not IDENT.match(tabela):
        raise RegraNegocio("Tabela de patrimônio inválida na configuração.")
    campos = _campos()
    conn = connections["patrimonio"]
    q = conn.ops.quote_name
    select = ", ".join(f"{q(col)} AS {q(alias)}" for alias, col in campos.items())
    filtros = [campos[c] for c in ("codigo_barras", "numero_patrimonio", "serie") if c in campos]
    if not filtros:
        return []
    where = " OR ".join(f"{q(c)} = %s" for c in dict.fromkeys(filtros))
    sql = f"SELECT {select} FROM {q(tabela)} WHERE {where} LIMIT {int(limite)}"
    with conn.cursor() as cur:
        cur.execute(sql, [codigo] * len(dict.fromkeys(filtros)))
        nomes = [c[0] for c in cur.description]
        return [{k: ("" if v is None else str(v)) for k, v in zip(nomes, linha)} for linha in cur.fetchall()]


def vincular(ordem, user, dados, request=None):
    if not (user.pode("patrimonio.vincular") and (pode_gerenciar(user) or pode_executar(user, ordem))):
        raise PermissionDenied
    if ordem.encerrada:
        raise RegraNegocio("Não é possível vincular patrimônio a uma ordem encerrada.")
    codigo = normalizar_codigo(dados.get("codigo", ""))
    if not codigo:
        raise RegraNegocio("Informe o código/número do patrimônio.")
    encontrado = (buscar(codigo, 1) or [None])[0]
    if ordem.patrimonios.filter(ativo=True, numero_patrimonio=codigo).exists():
        raise RegraNegocio("Este patrimônio já está vinculado à ordem.")
    base = encontrado or {}
    vinculo = OrdemPatrimonio.objects.create(
        ordem=ordem,
        patrimonio_externo_id=base.get("id", ""),
        codigo_barras=base.get("codigo_barras", codigo),
        numero_patrimonio=base.get("numero_patrimonio", codigo),
        descricao=(dados.get("descricao") or base.get("descricao", ""))[:255],
        marca=base.get("marca", "")[:120],
        modelo=base.get("modelo", "")[:120],
        serie=base.get("serie", "")[:190],
        setor=base.get("setor", "")[:190],
        localizacao=(dados.get("localizacao") or ordem.local.nome)[:255],
        origem="BANCO_EXTERNO" if encontrado else "MANUAL",
        vinculado_por=user,
    )
    historico(ordem, user, f"Patrimônio {codigo} vinculado.")
    auditar(request, "ordem", ordem.pk, "VINCULAR_PATRIMONIO", "patrimonio", None, codigo)
    return vinculo


def desvincular(ordem, user, vinculo, request=None):
    if not (user.pode("patrimonio.vincular") and (pode_gerenciar(user) or pode_executar(user, ordem))):
        raise PermissionDenied
    from django.utils import timezone

    vinculo.ativo = False
    vinculo.desvinculado_por, vinculo.desvinculado_em = user, timezone.now()
    vinculo.save()
    historico(ordem, user, f"Patrimônio {vinculo.numero_patrimonio} desvinculado.")
    auditar(request, "ordem", ordem.pk, "DESVINCULAR_PATRIMONIO", "patrimonio", vinculo.numero_patrimonio, None)
