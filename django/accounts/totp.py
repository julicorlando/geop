"""2FA por aplicativo autenticador (TOTP, RFC 6238) — mesmo esquema do PHP (SHA-1, 6 dígitos, 30 s)."""
import base64
import hashlib
import hmac
import os
import secrets
import struct
import time
from urllib.parse import quote

from django.conf import settings


def _fernet():
    from cryptography.fernet import Fernet

    base = os.getenv("GEOP_CHAVE_CRIPTOGRAFIA") or settings.SECRET_KEY
    return Fernet(base64.urlsafe_b64encode(hashlib.sha256(f"geop-2fa|{base}".encode()).digest()))


def cifrar(texto: str) -> str:
    return _fernet().encrypt(texto.encode()).decode()


def decifrar(token: str) -> str:
    try:
        return _fernet().decrypt(token.encode()).decode()
    except Exception:
        return ""


def novo_segredo() -> str:
    return base64.b32encode(secrets.token_bytes(20)).decode().rstrip("=")


def codigo(segredo: str, instante=None) -> str:
    chave = base64.b32decode(segredo + "=" * (-len(segredo) % 8), casefold=True)
    contador = int((instante or time.time()) // 30)
    h = hmac.new(chave, struct.pack(">Q", contador), hashlib.sha1).digest()
    o = h[-1] & 0x0F
    valor = (struct.unpack(">I", h[o:o + 4])[0] & 0x7FFFFFFF) % 1_000_000
    return f"{valor:06d}"


def verificar(segredo: str, informado: str, janela: int = 1) -> bool:
    informado = "".join(c for c in (informado or "") if c.isdigit())
    if len(informado) != 6 or not segredo:
        return False
    agora = time.time()
    return any(hmac.compare_digest(codigo(segredo, agora + i * 30), informado) for i in range(-janela, janela + 1))


def uri(email: str, segredo: str) -> str:
    emissor = f"GEOP {settings.GEOP_NOME_UNIDADE}"
    return (f"otpauth://totp/{quote(emissor)}:{quote(email)}?secret={segredo}&issuer={quote(emissor)}"
            f"&digits=6&period=30")
