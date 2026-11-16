import unicodedata

from django.conf import settings
from django.db import models
from django.utils import timezone


def normalizar(texto: str) -> str:
    texto = unicodedata.normalize("NFKD", (texto or "").strip().lower())
    return "".join(c for c in texto if not unicodedata.combining(c))


class Local(models.Model):
    nome = models.CharField(max_length=150, unique=True)
    descricao = models.TextField(blank=True)
    ativo = models.BooleanField(default=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["nome"]
        verbose_name_plural = "locais"

    def __str__(self):
        return self.nome


class Categoria(models.Model):
    nome = models.CharField(max_length=100, unique=True)
    descricao = models.TextField(blank=True)
    ativo = models.BooleanField(default=True)
    exige_foto_antes = models.BooleanField(default=False)
    exige_foto_durante = models.BooleanField(default=False)
    exige_foto_depois = models.BooleanField(default=False)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["nome"]

    def __str__(self):
        return self.nome

    @property
    def eh_limpeza(self) -> bool:
        """Ordens de categorias 'Limpeza' são OL (regra da V10 do PHP)."""
        return normalizar(self.nome) == "limpeza"


class Notificacao(models.Model):
    usuario = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name="notificacoes")
    tipo = models.CharField(max_length=60)
    titulo = models.CharField(max_length=180)
    mensagem = models.TextField(blank=True)
    url = models.CharField(max_length=255, blank=True)
    chave = models.CharField(max_length=190, null=True, blank=True, unique=True,
                             help_text="Evita notificações duplicadas para o mesmo evento.")
    lida_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-criado_em"]
        indexes = [models.Index(fields=["usuario", "lida_em", "criado_em"])]
        verbose_name = "notificação"
        verbose_name_plural = "notificações"

    def __str__(self):
        return self.titulo


class AuditoriaEvento(models.Model):
    entidade = models.CharField(max_length=80)
    entidade_id = models.BigIntegerField(null=True, blank=True)
    acao = models.CharField(max_length=80)
    campo = models.CharField(max_length=120, blank=True)
    valor_anterior = models.TextField(blank=True)
    valor_novo = models.TextField(blank=True)
    usuario = models.ForeignKey(settings.AUTH_USER_MODEL, null=True, blank=True, on_delete=models.SET_NULL)
    ip = models.GenericIPAddressField(null=True, blank=True)
    user_agent = models.CharField(max_length=255, blank=True)
    criado_em = models.DateTimeField(default=timezone.now, db_index=True)

    class Meta:
        ordering = ["-criado_em"]
        indexes = [models.Index(fields=["entidade", "entidade_id"])]
        verbose_name = "evento de auditoria"
        verbose_name_plural = "auditoria"

    def __str__(self):
        return f"{self.acao} {self.entidade}#{self.entidade_id}"


class ArquivoBase(models.Model):
    """Campos comuns a anexos/fotos. Os arquivos ficam em MEDIA_ROOT (privado)."""

    arquivo = models.FileField(max_length=255)
    miniatura = models.FileField(max_length=255, blank=True)
    nome_original = models.CharField(max_length=255, blank=True)
    mime = models.CharField(max_length=80, blank=True)
    tamanho = models.PositiveIntegerField(default=0)
    tamanho_original = models.PositiveIntegerField(default=0)
    largura = models.PositiveIntegerField(null=True, blank=True)
    altura = models.PositiveIntegerField(null=True, blank=True)
    sha256 = models.CharField(max_length=64, blank=True, db_index=True)

    PASTA = "arquivos"

    class Meta:
        abstract = True

    @property
    def eh_imagem(self) -> bool:
        if self.mime:
            return self.mime.startswith("image/")
        return str(self.arquivo.name).lower().rsplit(".", 1)[-1] in {"jpg", "jpeg", "png", "webp", "gif"}

    def aplicar(self, processado, nome_original: str = ""):
        """Preenche os campos a partir de `core.imaging.ArquivoProcessado` (sem salvar o model)."""
        pasta = f"{self.PASTA}/{timezone.localdate():%Y/%m}"
        self.arquivo.save(f"{pasta}/{processado.nome}", processado.conteudo, save=False)
        if processado.miniatura is not None:
            self.miniatura.save(f"{pasta}/{processado.nome_miniatura}", processado.miniatura, save=False)
        self.nome_original = (nome_original or "")[:255]
        self.mime = processado.mime
        self.tamanho = processado.tamanho
        self.tamanho_original = processado.tamanho_original
        self.largura = processado.largura
        self.altura = processado.altura
        self.sha256 = processado.sha256

    def remover_arquivos(self):
        """Apaga os arquivos do disco se nenhum outro registro (de qualquer tipo) os referencia."""
        for campo in (self.arquivo, self.miniatura):
            if not campo or not campo.name:
                continue
            em_uso = any(
                modelo.objects.filter(models.Q(arquivo=campo.name) | models.Q(miniatura=campo.name))
                .exclude(pk=self.pk if modelo is type(self) else None).exists()
                for modelo in ArquivoBase.__subclasses__() if not modelo._meta.abstract
            )
            if not em_uso:
                campo.storage.delete(campo.name)
