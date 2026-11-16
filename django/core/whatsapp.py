"""Links de WhatsApp (wa.me) com mensagem pronta — mesmo recurso do PHP (fila de avisos)."""
from urllib.parse import quote


def telefone_whatsapp(telefone) -> str:
    numero = "".join(c for c in str(telefone or "") if c.isdigit())
    if not numero:
        return ""
    if len(numero) in (10, 11):  # DDD + número, sem DDI
        numero = "55" + numero
    return numero


def link(telefone, mensagem: str) -> str:
    numero = telefone_whatsapp(telefone)
    return f"https://wa.me/{numero}?text={quote(mensagem)}" if numero else ""


def mensagem_ordem(evento: str, ordem, url_publica: str = "") -> str:
    linhas = [f"*GEOP — {evento}*", f"{ordem.numero}: {ordem.titulo}", f"Local: {ordem.local}",
              f"Prioridade: {ordem.get_prioridade_display()} · Status: {ordem.get_status_display()}"]
    if ordem.prazo:
        linhas.append(f"Prazo: {ordem.prazo:%d/%m/%Y %H:%M}")
    if url_publica:
        linhas.append(f"Fotos e encerramento: {url_publica}")
    return "\n".join(linhas)
