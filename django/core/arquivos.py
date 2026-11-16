"""Entrega de arquivos privados (fotos e documentos) após checar permissão."""
import mimetypes
from urllib.parse import quote

from django.conf import settings
from django.http import FileResponse, Http404, HttpResponse


def responder_arquivo(campo, *, nome_download: str = "", inline: bool = True):
    if not campo or not campo.name:
        raise Http404
    storage = campo.storage
    if not storage.exists(campo.name):
        raise Http404
    tipo = mimetypes.guess_type(campo.name)[0] or "application/octet-stream"
    disposicao = "inline" if inline else "attachment"
    nome = quote(nome_download or campo.name.rsplit("/", 1)[-1])
    prefixo = settings.GEOP_X_ACCEL_REDIRECT_PREFIX
    if prefixo:
        resposta = HttpResponse(content_type=tipo)
        resposta["X-Accel-Redirect"] = prefixo.rstrip("/") + "/" + quote(campo.name)
    else:
        resposta = FileResponse(storage.open(campo.name, "rb"), content_type=tipo)
    resposta["Content-Disposition"] = f"{disposicao}; filename*=UTF-8''{nome}"
    resposta["Cache-Control"] = "private, max-age=86400"
    resposta["X-Content-Type-Options"] = "nosniff"
    # PDFs/imagens não podem executar scripts mesmo se abertos diretamente.
    resposta["Content-Security-Policy"] = "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox"
    return resposta
