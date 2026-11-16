from django.contrib.auth.models import AbstractBaseUser, BaseUserManager, PermissionsMixin
from django.db import models
from django.utils import timezone
from django.utils.functional import cached_property

PERFIL_TI = "TI"


class Setor(models.Model):
    nome = models.CharField(max_length=100, unique=True)
    descricao = models.TextField(blank=True)
    ativo = models.BooleanField(default=True)
    criado_em = models.DateTimeField(default=timezone.now)

    class Meta:
        ordering = ["nome"]
        verbose_name_plural = "setores"

    def __str__(self):
        return self.nome


class Permissao(models.Model):
    codigo = models.CharField(max_length=80, unique=True)
    nome = models.CharField(max_length=120)
    modulo = models.CharField(max_length=60, blank=True)
    descricao = models.CharField(max_length=255, blank=True)

    class Meta:
        ordering = ["modulo", "codigo"]
        verbose_name = "permissão"
        verbose_name_plural = "permissões"

    def __str__(self):
        return f"{self.nome} ({self.codigo})"


class Perfil(models.Model):
    codigo = models.CharField(max_length=80, unique=True)
    nome = models.CharField(max_length=120)
    descricao = models.CharField(max_length=255, blank=True)
    ativo = models.BooleanField(default=True)
    administrativo = models.BooleanField(default=False, help_text="Acesso integral, como o TI.")
    sistema = models.BooleanField(default=False, help_text="Perfil padrão do GEOP (não pode ser excluído).")
    permissoes = models.ManyToManyField(Permissao, blank=True, related_name="perfis")
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(auto_now=True)

    class Meta:
        ordering = ["nome"]
        verbose_name_plural = "perfis"

    def __str__(self):
        return self.nome


class UsuarioManager(BaseUserManager):
    use_in_migrations = True

    def _create(self, email, nome, password, **extra):
        if not email:
            raise ValueError("O e-mail é obrigatório.")
        user = self.model(email=self.normalize_email(email).lower(), nome=nome, **extra)
        user.set_password(password)
        user.save(using=self._db)
        return user

    def create_user(self, email, nome="", password=None, **extra):
        extra.setdefault("is_superuser", False)
        extra.setdefault("is_staff", False)
        return self._create(email, nome, password, **extra)

    def create_superuser(self, email, nome="Administrador", password=None, **extra):
        extra.update(is_superuser=True, is_staff=True)
        extra.setdefault("perfil", Perfil.objects.filter(codigo=PERFIL_TI).first())
        return self._create(email, nome, password, **extra)


class Usuario(AbstractBaseUser, PermissionsMixin):
    nome = models.CharField(max_length=150)
    email = models.EmailField(max_length=150, unique=True)
    telefone = models.CharField(max_length=30, blank=True)
    cargo = models.CharField(max_length=100, blank=True)
    perfil = models.ForeignKey(Perfil, null=True, blank=True, on_delete=models.PROTECT, related_name="usuarios")
    setor = models.ForeignKey(Setor, null=True, blank=True, on_delete=models.SET_NULL, related_name="usuarios")
    is_active = models.BooleanField("ativo", default=True)
    is_staff = models.BooleanField("acesso ao admin", default=False)
    forcar_troca_senha = models.BooleanField(default=False)
    totp_segredo = models.CharField(max_length=255, blank=True, help_text="Segredo 2FA cifrado.")
    totp_ativo = models.BooleanField("2FA ativo", default=False)
    ultimo_login_ip = models.GenericIPAddressField(null=True, blank=True)
    senha_alterada_em = models.DateTimeField(null=True, blank=True)
    criado_por = models.ForeignKey("self", null=True, blank=True, on_delete=models.SET_NULL, related_name="+")
    criado_em = models.DateTimeField(default=timezone.now)
    atualizado_em = models.DateTimeField(auto_now=True)

    objects = UsuarioManager()
    USERNAME_FIELD = "email"
    EMAIL_FIELD = "email"
    REQUIRED_FIELDS = ["nome"]

    class Meta:
        ordering = ["nome"]
        verbose_name = "usuário"

    def __str__(self):
        return self.nome or self.email

    def save(self, *args, **kwargs):
        self.email = (self.email or "").strip().lower()
        super().save(*args, **kwargs)

    def get_full_name(self):
        return self.nome

    def get_short_name(self):
        return (self.nome or self.email).split(" ")[0]

    @property
    def perfil_codigo(self):
        return self.perfil.codigo if self.perfil_id else ""

    @property
    def is_ti(self):
        return bool(self.is_superuser or self.perfil_codigo == PERFIL_TI or (self.perfil_id and self.perfil.administrativo))

    @cached_property
    def codigos_permissao(self):
        if not self.perfil_id or not self.perfil.ativo:
            return frozenset()
        return frozenset(self.perfil.permissoes.values_list("codigo", flat=True))

    def pode(self, codigo: str) -> bool:
        """Equivalente a `usuario_pode()` do PHP: TI/administrativo pode tudo."""
        if not self.is_active:
            return False
        return self.is_ti or codigo in self.codigos_permissao

    def pode_algum(self, *codigos) -> bool:
        return any(self.pode(c) for c in codigos)


class Convite(models.Model):
    """Link de ativação: o TI cria o usuário e o próprio usuário define a senha."""

    usuario = models.ForeignKey(Usuario, on_delete=models.CASCADE, related_name="convites")
    token_hash = models.CharField(max_length=64, unique=True)
    gerado_por = models.ForeignKey(Usuario, on_delete=models.PROTECT, related_name="+")
    expira_em = models.DateTimeField()
    usado_em = models.DateTimeField(null=True, blank=True)
    invalidado_em = models.DateTimeField(null=True, blank=True)
    criado_em = models.DateTimeField(default=timezone.now)

    @property
    def valido(self):
        return not self.usado_em and not self.invalidado_em and self.expira_em > timezone.now()
