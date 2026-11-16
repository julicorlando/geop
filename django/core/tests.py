import io

from django.core.exceptions import ValidationError
from django.core.files.uploadedfile import SimpleUploadedFile
from django.test import SimpleTestCase, override_settings
from PIL import Image

from core.imaging import comprimir_imagem, processar_upload


def foto_jpeg(largura=4000, altura=3000, orientacao=None, gps=False, qualidade=95):
    img = Image.effect_noise((largura, altura), 60).convert("RGB")  # ruído = foto "difícil" de comprimir
    exif = Image.Exif()
    if orientacao:
        exif[0x0112] = orientacao
    if gps:
        exif[0x8825] = {1: "S", 2: (8.0, 0.0, 0.0)}
    buf = io.BytesIO()
    img.save(buf, "JPEG", quality=qualidade, exif=exif.tobytes())
    return buf.getvalue()


def upload(nome, dados, tipo="image/jpeg"):
    return SimpleUploadedFile(nome, dados, content_type=tipo)


@override_settings(GEOP_IMAGEM_LADO_MAX=1920, GEOP_IMAGEM_QUALIDADE=80, GEOP_IMAGEM_FORMATO="WEBP",
                   GEOP_UPLOAD_MAX_MB=15, GEOP_MINIATURA_LADO=480)
class CompressaoTests(SimpleTestCase):
    def test_reduz_dimensao_e_tamanho(self):
        original = foto_jpeg()
        p = processar_upload(upload("foto.jpg", original))
        self.assertTrue(p.eh_imagem)
        self.assertEqual((p.largura, p.altura), (1920, 1440))
        self.assertLess(p.tamanho, len(original) / 3)
        self.assertEqual(p.mime, "image/webp")
        self.assertTrue(p.nome.endswith(".webp"))
        with Image.open(io.BytesIO(p.miniatura.read())) as mini:
            self.assertLessEqual(max(mini.size), 480)

    def test_aplica_orientacao_exif_e_remove_metadados(self):
        dados = foto_jpeg(1200, 800, orientacao=6, gps=True)  # 6 = girar 90°
        r = comprimir_imagem(dados)
        self.assertEqual((r["largura"], r["altura"]), (800, 1200))
        with Image.open(io.BytesIO(r["dados"])) as img:
            self.assertFalse(img.getexif())  # sem EXIF/GPS

    def test_imagem_pequena_nao_e_ampliada(self):
        r = comprimir_imagem(foto_jpeg(640, 480))
        self.assertEqual((r["largura"], r["altura"]), (640, 480))

    def test_nunca_aumenta_foto_ja_otimizada(self):
        buf = io.BytesIO()
        Image.effect_noise((800, 600), 60).convert("RGB").save(buf, "JPEG", quality=40)
        dados = buf.getvalue()
        r = comprimir_imagem(dados, qualidade=95)
        self.assertLessEqual(len(r["dados"]), len(dados))

    def test_png_com_transparencia(self):
        buf = io.BytesIO()
        Image.new("RGBA", (300, 200), (255, 0, 0, 120)).save(buf, "PNG")
        r = comprimir_imagem(buf.getvalue())
        with Image.open(io.BytesIO(r["dados"])) as img:
            self.assertIn(img.mode, ("RGBA", "RGB"))

    @override_settings(GEOP_IMAGEM_FORMATO="JPEG")
    def test_formato_jpeg_configuravel(self):
        p = processar_upload(upload("a.png", foto_jpeg(500, 500)))
        self.assertEqual(p.mime, "image/jpeg")

    def test_rejeita_arquivo_disfarcado(self):
        with self.assertRaises(ValidationError):
            processar_upload(upload("virus.jpg", b"<?php system($_GET['c']); ?>"))

    def test_rejeita_imagem_corrompida(self):
        with self.assertRaises(ValidationError):
            processar_upload(upload("x.jpg", b"\xff\xd8\xff\xe0" + b"lixo" * 50))

    def test_rejeita_heic_com_mensagem_clara(self):
        with self.assertRaisesMessage(ValidationError, "HEIC"):
            processar_upload(upload("a.heic", b"\x00\x00\x00\x18ftypheic" + b"\x00" * 40, "image/heic"))

    def test_pdf_mantido(self):
        pdf = b"%PDF-1.4\n" + b"0" * 1000
        p = processar_upload(upload("nota.pdf", pdf, "application/pdf"))
        self.assertFalse(p.eh_imagem)
        self.assertEqual(p.tamanho, len(pdf))

    def test_pdf_bloqueado_quando_somente_imagens(self):
        with self.assertRaises(ValidationError):
            processar_upload(upload("nota.pdf", b"%PDF-1.4\n" + b"0" * 100, "application/pdf"), permitir_pdf=False)

    @override_settings(GEOP_UPLOAD_MAX_MB=1)
    def test_limite_de_tamanho(self):
        with self.assertRaisesMessage(ValidationError, "limite"):
            processar_upload(upload("grande.jpg", b"\xff\xd8\xff" + b"0" * (2 * 1024 * 1024)))
