"""Validação e compressão de uploads.

Toda foto enviada ao GEOP passa por aqui antes de ser gravada:

* o tipo é detectado pelo conteúdo (assinatura do arquivo), não pela extensão;
* a orientação EXIF é aplicada (fotos de celular não ficam "deitadas");
* a imagem é reduzida para no máximo ``GEOP_IMAGEM_LADO_MAX`` px (padrão 1600) no maior lado;
* é regravada em WEBP (ou JPEG) com a qualidade configurada, sem metadados
  EXIF — o que remove, por exemplo, a localização GPS da foto;
* uma miniatura leve é gerada para listas e galerias.

A imagem continua sendo guardada (nada é descartado), apenas ocupa bem menos
espaço: fotos de 3–8 MB direto da câmera do celular ficam em ~150–400 KB; fotos que
já chegam reduzidas (como as do PHP, ~1600 px) encolhem uns 25%, e nunca ficam maiores.
"""
from __future__ import annotations

import hashlib
import io
import uuid
from dataclasses import dataclass

from django.conf import settings
from django.core.exceptions import ValidationError
from django.core.files.base import ContentFile
from PIL import Image, ImageOps, UnidentifiedImageError, features

# Protege contra "bombas de descompressão" (imagens minúsculas que explodem em memória).
Image.MAX_IMAGE_PIXELS = 80_000_000

TIPOS_IMAGEM = {"image/jpeg", "image/png", "image/webp", "image/gif"}
TIPO_PDF = "application/pdf"


@dataclass
class ArquivoProcessado:
    conteudo: ContentFile
    nome: str
    mime: str
    tamanho: int
    tamanho_original: int
    sha256: str
    eh_imagem: bool
    largura: int | None = None
    altura: int | None = None
    miniatura: ContentFile | None = None
    nome_miniatura: str = ""

    @property
    def economia_percentual(self) -> int:
        if not self.tamanho_original:
            return 0
        return max(0, round(100 - (self.tamanho * 100 / self.tamanho_original)))


def detectar_mime(cabecalho: bytes) -> str | None:
    if cabecalho.startswith(b"\xff\xd8\xff"):
        return "image/jpeg"
    if cabecalho.startswith(b"\x89PNG\r\n\x1a\n"):
        return "image/png"
    if cabecalho[:4] == b"RIFF" and cabecalho[8:12] == b"WEBP":
        return "image/webp"
    if cabecalho[:6] in (b"GIF87a", b"GIF89a"):
        return "image/gif"
    if cabecalho.startswith(b"%PDF-"):
        return TIPO_PDF
    if cabecalho[4:12] in (b"ftypheic", b"ftypheix", b"ftypmif1", b"ftyphevc"):
        return "image/heic"
    return None


def _formato_saida() -> tuple[str, str, str]:
    formato = getattr(settings, "GEOP_IMAGEM_FORMATO", "WEBP")
    if formato == "WEBP" and features.check("webp"):
        return "WEBP", "webp", "image/webp"
    return "JPEG", "jpg", "image/jpeg"


def _preparar_modo(img: Image.Image, formato: str) -> Image.Image:
    tem_alfa = img.mode in ("RGBA", "LA") or (img.mode == "P" and "transparency" in img.info)
    if tem_alfa and formato == "WEBP":
        return img.convert("RGBA")
    if tem_alfa:  # JPEG não tem transparência: aplica fundo branco
        fundo = Image.new("RGB", img.size, (255, 255, 255))
        fundo.paste(img.convert("RGBA"), mask=img.convert("RGBA").split()[-1])
        return fundo
    return img.convert("RGB") if img.mode != "RGB" else img


def _codificar(img: Image.Image, formato: str, qualidade: int, icc: bytes | None) -> bytes:
    buf = io.BytesIO()
    opcoes = {"quality": qualidade}
    if icc:
        opcoes["icc_profile"] = icc
    if formato == "WEBP":
        opcoes["method"] = 5
    else:
        opcoes.update(optimize=True, progressive=True)
    img.save(buf, format=formato, **opcoes)
    return buf.getvalue()


def comprimir_imagem(dados: bytes, *, lado_max: int | None = None, qualidade: int | None = None,
                     miniatura: bool = True) -> dict:
    """Recebe os bytes de uma imagem e devolve a versão reduzida (+ miniatura)."""
    lado_max = lado_max or settings.GEOP_IMAGEM_LADO_MAX
    qualidade = max(30, min(95, qualidade or settings.GEOP_IMAGEM_QUALIDADE))
    formato, extensao, mime = _formato_saida()
    try:
        with Image.open(io.BytesIO(dados)) as origem:
            origem.load()
            icc = origem.info.get("icc_profile")
            formato_original = origem.format
            tem_metadados = bool(origem.getexif()) or "xmp" in origem.info
            img = ImageOps.exif_transpose(origem)  # aplica a rotação e descarta o EXIF
            if getattr(img, "is_animated", False):
                img.seek(0)
            img = _preparar_modo(img, formato)
    except Image.DecompressionBombError as exc:
        raise ValidationError("A imagem tem resolução grande demais.") from exc
    except (UnidentifiedImageError, OSError, SyntaxError, ValueError) as exc:
        raise ValidationError("A imagem está corrompida ou em formato não suportado.") from exc

    redimensionar = max(img.size) > lado_max
    if redimensionar:
        img.thumbnail((lado_max, lado_max), Image.Resampling.LANCZOS)
    saida = _codificar(img, formato, qualidade, icc)

    # Nunca deixar a foto maior: se ela já estava pequena, sem metadados (EXIF/GPS) e no tamanho certo,
    # e a recodificação não ajudou, o arquivo original é mantido.
    if (len(saida) >= len(dados) and not redimensionar and not tem_metadados
            and formato_original in ("JPEG", "WEBP")):
        saida = dados
        extensao, mime = ("jpg", "image/jpeg") if formato_original == "JPEG" else ("webp", "image/webp")

    resultado = {"dados": saida, "extensao": extensao, "mime": mime, "largura": img.width, "altura": img.height}
    if miniatura:
        thumb = img.copy()
        lado = settings.GEOP_MINIATURA_LADO
        thumb.thumbnail((lado, lado), Image.Resampling.LANCZOS)
        resultado["miniatura"] = _codificar(thumb, formato, min(qualidade, 70), None)
    return resultado


def processar_upload(arquivo, *, permitir_pdf: bool = True, prefixo: str = "") -> ArquivoProcessado:
    """Valida um `UploadedFile` e devolve o conteúdo pronto para gravar."""
    limite = settings.GEOP_UPLOAD_MAX_MB * 1024 * 1024
    tamanho_original = getattr(arquivo, "size", 0) or 0
    if tamanho_original <= 0:
        raise ValidationError("O arquivo enviado está vazio.")
    if tamanho_original > limite:
        raise ValidationError(f"O arquivo excede o limite de {settings.GEOP_UPLOAD_MAX_MB} MB.")

    arquivo.seek(0)
    dados = arquivo.read()
    mime = detectar_mime(dados[:16])
    base = f"{prefixo}{uuid.uuid4().hex}"
    sha = hashlib.sha256(dados).hexdigest()

    if mime == "image/heic":
        raise ValidationError("Fotos HEIC não são suportadas. No iPhone, use Ajustes › Câmera › Formatos › Mais Compatível.")
    if mime in TIPOS_IMAGEM:
        r = comprimir_imagem(dados)
        return ArquivoProcessado(
            conteudo=ContentFile(r["dados"]),
            nome=f"{base}.{r['extensao']}",
            mime=r["mime"],
            tamanho=len(r["dados"]),
            tamanho_original=tamanho_original,
            sha256=sha,
            eh_imagem=True,
            largura=r["largura"],
            altura=r["altura"],
            miniatura=ContentFile(r["miniatura"]),
            nome_miniatura=f"{base}_mini.{r['extensao']}",
        )
    if mime == TIPO_PDF and permitir_pdf:
        return ArquivoProcessado(
            conteudo=ContentFile(dados), nome=f"{base}.pdf", mime=TIPO_PDF, tamanho=len(dados),
            tamanho_original=tamanho_original, sha256=sha, eh_imagem=False,
        )
    raise ValidationError(
        "Tipo de arquivo não permitido. Envie JPG, PNG, WEBP" + (" ou PDF." if permitir_pdf else ".")
    )


def formatar_bytes(n: int | None) -> str:
    n = n or 0
    for unidade in ("B", "KB", "MB", "GB"):
        if n < 1024 or unidade == "GB":
            return f"{n:.0f} {unidade}" if unidade == "B" else f"{n:.1f} {unidade}".replace(".", ",")
        n /= 1024
    return ""
