"""Cliente para APIs compatíveis com OpenAI (OpenAI, Groq e Ollama /v1) com chamada de ferramentas."""
import json
import os
import time
import urllib.error
import urllib.request

from django.conf import settings
from django.utils import timezone

from core.models import Configuracao

from . import ferramentas
from .models import Execucao

PADROES = {
    "groq": ("https://api.groq.com/openai/v1", "llama-3.3-70b-versatile"),
    "openai": ("https://api.openai.com/v1", "gpt-4o-mini"),
    "ollama": ("http://localhost:11434/v1", "llama3.1"),
}
MAX_RODADAS = 4

SISTEMA = (
    "Você é o GEOP IA, assistente de operações e manutenção do {unidade}. Responda em português do Brasil, de forma "
    "objetiva. Use SEMPRE as ferramentas para consultar dados reais do GEOP antes de afirmar números, ordens, "
    "estoque ou prazos; não invente. Se a ferramenta retornar erro de permissão, explique que o perfil do usuário "
    "não tem acesso. Cite os números das ordens quando relevante. Hoje é {hoje}. Usuário: {usuario} ({perfil})."
)


class ErroIA(Exception):
    pass


def configuracao():
    provedor = (Configuracao.obter("ia_provedor") or os.getenv("GEOP_IA_PROVEDOR", "groq")).lower()
    base, modelo = PADROES.get(provedor, PADROES["groq"])
    return {
        "habilitada": Configuracao.obter_bool("ia_habilitada"),
        "provedor": provedor,
        "base_url": (Configuracao.obter("ia_base_url") or os.getenv("GEOP_IA_BASE_URL") or base).rstrip("/"),
        "modelo": Configuracao.obter("ia_modelo") or os.getenv("GEOP_IA_MODELO") or modelo,
        "chave": os.getenv("GEOP_IA_API_KEY", ""),  # a chave fica só no ambiente, nunca no banco
        "limite_diario": Configuracao.obter_int("ia_limite_diario", 40),
    }


def pronto():
    cfg = configuracao()
    if not cfg["habilitada"]:
        return False, "O GEOP IA está desativado. O TI pode ativá-lo em Administração › GEOP IA."
    if cfg["provedor"] != "ollama" and not cfg["chave"]:
        return False, "Falta a variável de ambiente GEOP_IA_API_KEY no servidor."
    return True, ""


def _post(cfg, corpo, timeout=60):
    req = urllib.request.Request(
        f"{cfg['base_url']}/chat/completions", data=json.dumps(corpo).encode(), method="POST",
        headers={"Content-Type": "application/json", **({"Authorization": f"Bearer {cfg['chave']}"} if cfg["chave"] else {})})
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            return json.loads(resp.read().decode())
    except urllib.error.HTTPError as exc:
        detalhe = exc.read().decode(errors="ignore")[:300]
        raise ErroIA(f"O provedor de IA respondeu {exc.code}: {detalhe}") from exc
    except (urllib.error.URLError, TimeoutError) as exc:
        raise ErroIA(f"Não foi possível falar com o provedor de IA ({exc}).") from exc


def usos_hoje(user):
    return Execucao.objects.filter(usuario=user, tipo="CHAT", criado_em__date=timezone.localdate()).count()


def responder(user, conversa, pergunta, historico):
    ok, motivo = pronto()
    if not ok:
        raise ErroIA(motivo)
    cfg = configuracao()
    if not user.is_ti and usos_hoje(user) >= cfg["limite_diario"]:
        raise ErroIA(f"Limite diário de {cfg['limite_diario']} perguntas atingido. Tente amanhã.")
    inicio = time.monotonic()
    usadas = []
    mensagens = [{"role": "system", "content": SISTEMA.format(
        unidade=settings.GEOP_NOME_UNIDADE, hoje=timezone.localdate().strftime("%d/%m/%Y"), usuario=user.nome,
        perfil=user.perfil.nome if user.perfil_id else "")}]
    mensagens += [{"role": m.papel, "content": m.conteudo} for m in historico][-12:]
    mensagens.append({"role": "user", "content": pergunta})
    execucao = Execucao(usuario=user, conversa=conversa, provedor=cfg["provedor"], modelo=cfg["modelo"])
    try:
        for _ in range(MAX_RODADAS):
            resp = _post(cfg, {"model": cfg["modelo"], "messages": mensagens, "tools": ferramentas.definicoes(),
                               "tool_choice": "auto", "temperature": 0.2})
            msg = resp["choices"][0]["message"]
            chamadas = msg.get("tool_calls") or []
            if not chamadas:
                texto = (msg.get("content") or "").strip() or "Não consegui gerar uma resposta."
                return texto, usadas
            mensagens.append({"role": "assistant", "content": msg.get("content") or "", "tool_calls": chamadas})
            for ch in chamadas:
                nome = ch["function"]["name"]
                try:
                    args = json.loads(ch["function"].get("arguments") or "{}")
                except json.JSONDecodeError:
                    args = {}
                resultado = ferramentas.executar(user, nome, args)
                usadas.append(nome)
                mensagens.append({"role": "tool", "tool_call_id": ch.get("id", nome), "name": nome,
                                  "content": json.dumps(resultado, ensure_ascii=False, default=str)[:12000]})
        return "A pergunta exigiu consultas demais. Tente ser mais específico.", usadas
    except Exception as exc:
        execucao.sucesso, execucao.erro = False, str(exc)[:2000]
        raise
    finally:
        execucao.ferramentas = usadas
        execucao.duracao_ms = int((time.monotonic() - inicio) * 1000)
        execucao.save()


def testar():
    cfg = configuracao()
    resp = _post(cfg, {"model": cfg["modelo"], "messages": [{"role": "user", "content": "Responda apenas: OK"}],
                       "max_tokens": 5}, timeout=20)
    return resp["choices"][0]["message"].get("content", "")
