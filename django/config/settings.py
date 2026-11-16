"""Configuração do GEOP (Django).

Todos os segredos e parâmetros de ambiente vêm de variáveis de ambiente (ou do
arquivo `.env` em desenvolvimento). Veja `.env.example`.
"""
import os
from pathlib import Path

import dj_database_url
from django.core.exceptions import ImproperlyConfigured

try:
    from dotenv import load_dotenv
except ImportError:  # pragma: no cover
    load_dotenv = None

BASE_DIR = Path(__file__).resolve().parent.parent
if load_dotenv:
    load_dotenv(BASE_DIR / ".env")


def env(name, default=None):
    return os.getenv(name, default)


def env_bool(name, default=False):
    return str(os.getenv(name, str(default))).strip().lower() in {"1", "true", "yes", "on", "sim"}


def env_int(name, default):
    try:
        return int(os.getenv(name, default))
    except (TypeError, ValueError):
        return default


def env_list(name, default=""):
    return [item.strip() for item in os.getenv(name, default).split(",") if item.strip()]


DEBUG = env_bool("DJANGO_DEBUG", False)
SECRET_KEY = env("DJANGO_SECRET_KEY") or ("dev-only-insecure-key" if DEBUG else None)
if not SECRET_KEY:
    raise ImproperlyConfigured("Defina DJANGO_SECRET_KEY (ou DJANGO_DEBUG=true em desenvolvimento).")

ALLOWED_HOSTS = env_list("DJANGO_ALLOWED_HOSTS", "localhost,127.0.0.1")
CSRF_TRUSTED_ORIGINS = env_list("CSRF_TRUSTED_ORIGINS")

INSTALLED_APPS = [
    "django.contrib.admin",
    "django.contrib.auth",
    "django.contrib.contenttypes",
    "django.contrib.sessions",
    "django.contrib.messages",
    "django.contrib.staticfiles",
    "django.contrib.humanize",
    "accounts",
    "core",
    "limpeza",
    "ordens",
    "ambientes",
    "patrimonio",
    "estoque",
    "operacao",
    "conhecimento",
    "relatorios",
    "cftv",
    "chat",
    "ia",
    "iot",
]

MIDDLEWARE = [
    "django.middleware.security.SecurityMiddleware",
    "whitenoise.middleware.WhiteNoiseMiddleware",
    "django.contrib.sessions.middleware.SessionMiddleware",
    "django.middleware.common.CommonMiddleware",
    "django.middleware.csrf.CsrfViewMiddleware",
    "django.contrib.auth.middleware.AuthenticationMiddleware",
    "django.contrib.messages.middleware.MessageMiddleware",
    "django.middleware.clickjacking.XFrameOptionsMiddleware",
    "accounts.middleware.TrocaSenhaObrigatoriaMiddleware",
]

ROOT_URLCONF = "config.urls"
TEMPLATES = [
    {
        "BACKEND": "django.template.backends.django.DjangoTemplates",
        "DIRS": [BASE_DIR / "templates"],
        "APP_DIRS": True,
        "OPTIONS": {
            "context_processors": [
                "django.template.context_processors.request",
                "django.contrib.auth.context_processors.auth",
                "django.contrib.messages.context_processors.messages",
                "core.context_processors.geop",
            ],
        },
    }
]
WSGI_APPLICATION = "config.wsgi.application"
ASGI_APPLICATION = "config.asgi.application"

# --- Banco de dados -------------------------------------------------------
DATABASE_URL = env("DATABASE_URL", f"sqlite:///{BASE_DIR / 'db.sqlite3'}")
DATABASES = {
    "default": dj_database_url.parse(
        DATABASE_URL,
        conn_max_age=env_int("DB_CONN_MAX_AGE", 60),
        conn_health_checks=True,
        ssl_require=env_bool("DB_SSL_REQUIRE", False),
    )
}
# Banco MySQL/MariaDB do PHP, usado SOMENTE em leitura pelo comando `importar_legado`.
if env("LEGACY_DATABASE_URL"):
    DATABASES["legacy"] = dj_database_url.parse(env("LEGACY_DATABASE_URL"))
    if "mysql" in DATABASES["legacy"]["ENGINE"]:
        DATABASES["legacy"].setdefault("OPTIONS", {})["charset"] = "utf8mb4"
# Banco externo de patrimônio (somente leitura), equivalente às variáveis PATRIMONIO_DB_* do PHP.
if env("PATRIMONIO_DATABASE_URL"):
    DATABASES["patrimonio"] = dj_database_url.parse(env("PATRIMONIO_DATABASE_URL"))
DATABASE_ROUTERS = ["core.routers.SomenteLeituraExternaRouter"]

DEFAULT_AUTO_FIELD = "django.db.models.BigAutoField"

# --- Autenticação -----------------------------------------------------------
AUTH_USER_MODEL = "accounts.Usuario"
PASSWORD_HASHERS = [
    "django.contrib.auth.hashers.PBKDF2PasswordHasher",
    "django.contrib.auth.hashers.PBKDF2SHA1PasswordHasher",
    "django.contrib.auth.hashers.ScryptPasswordHasher",
    # Senhas importadas do PHP; regravadas em PBKDF2 automaticamente no primeiro login.
    "accounts.hashers.PHPBcryptPasswordHasher",
]
AUTH_PASSWORD_VALIDATORS = [
    {"NAME": "django.contrib.auth.password_validation.UserAttributeSimilarityValidator",
     "OPTIONS": {"user_attributes": ("nome", "email")}},
    {"NAME": "django.contrib.auth.password_validation.MinimumLengthValidator", "OPTIONS": {"min_length": 10}},
    {"NAME": "django.contrib.auth.password_validation.CommonPasswordValidator"},
    {"NAME": "django.contrib.auth.password_validation.NumericPasswordValidator"},
]
LOGIN_URL = "accounts:login"
LOGIN_REDIRECT_URL = "core:dashboard"
LOGOUT_REDIRECT_URL = "accounts:login"
LOGIN_MAX_TENTATIVAS = env_int("LOGIN_MAX_TENTATIVAS", 5)
LOGIN_BLOQUEIO_MINUTOS = env_int("LOGIN_BLOQUEIO_MINUTOS", 15)

# Sessão expira após inatividade (padrão: 60 min), como o "session guard" do PHP.
SESSION_COOKIE_AGE = env_int("SESSION_IDLE_MINUTES", 60) * 60
SESSION_SAVE_EVERY_REQUEST = True
SESSION_EXPIRE_AT_BROWSER_CLOSE = True
SESSION_COOKIE_HTTPONLY = True
SESSION_COOKIE_SAMESITE = "Lax"
CSRF_COOKIE_HTTPONLY = True

CACHES = {
    "default": (
        {"BACKEND": "django.core.cache.backends.redis.RedisCache", "LOCATION": env("REDIS_URL")}
        if env("REDIS_URL")
        else {"BACKEND": "django.core.cache.backends.locmem.LocMemCache", "LOCATION": "geop"}
    )
}

# --- Internacionalização ----------------------------------------------------
LANGUAGE_CODE = "pt-br"
TIME_ZONE = env("TIME_ZONE", "America/Recife")
USE_I18N = True
USE_TZ = True
USE_THOUSAND_SEPARATOR = True

# --- Arquivos estáticos e mídia --------------------------------------------
STATIC_URL = "static/"
STATIC_ROOT = BASE_DIR / "staticfiles"
STATICFILES_DIRS = [BASE_DIR / "static"]
STORAGES = {
    "default": {"BACKEND": "django.core.files.storage.FileSystemStorage"},
    "staticfiles": {
        "BACKEND": (
            "django.contrib.staticfiles.storage.StaticFilesStorage"
            if DEBUG
            else "whitenoise.storage.CompressedManifestStaticFilesStorage"
        )
    },
}
# A mídia é PRIVADA: nunca é servida diretamente pelo servidor web. Os arquivos
# passam por views que conferem permissão (ou o token público da OS/ambiente).
MEDIA_ROOT = Path(env("MEDIA_ROOT", str(BASE_DIR / "media")))
MEDIA_URL = "/arquivos/"
# Com nginx, defina p.ex. "/protected-media/" e um `location internal` apontando para MEDIA_ROOT.
GEOP_X_ACCEL_REDIRECT_PREFIX = env("GEOP_X_ACCEL_REDIRECT_PREFIX", "")

FILE_UPLOAD_MAX_MEMORY_SIZE = 5 * 1024 * 1024
DATA_UPLOAD_MAX_MEMORY_SIZE = 5 * 1024 * 1024
DATA_UPLOAD_MAX_NUMBER_FILES = 20

# --- Imagens ----------------------------------------------------------------
GEOP_UPLOAD_MAX_MB = env_int("GEOP_UPLOAD_MAX_MB", 15)          # limite do arquivo recebido
GEOP_IMAGEM_LADO_MAX = env_int("GEOP_IMAGEM_LADO_MAX", 1600)    # maior lado após redução (px)
GEOP_IMAGEM_QUALIDADE = env_int("GEOP_IMAGEM_QUALIDADE", 75)    # 1–95
GEOP_IMAGEM_FORMATO = env("GEOP_IMAGEM_FORMATO", "WEBP").upper()  # WEBP ou JPEG
GEOP_MINIATURA_LADO = env_int("GEOP_MINIATURA_LADO", 480)
GEOP_FOTOS_POR_ENVIO = env_int("GEOP_FOTOS_POR_ENVIO", 10)

# --- Regras de negócio ------------------------------------------------------
GEOP_NOME_UNIDADE = env("GEOP_NOME_UNIDADE", "Shopping Carpina")
# Mesmo valor de APP_PUBLIC_SHARE_SECRET do PHP mantém válidos os links públicos já enviados.
GEOP_PUBLIC_SHARE_SECRET = env("GEOP_PUBLIC_SHARE_SECRET") or SECRET_KEY
# Assinatura das credenciais QR de acesso. Use o valor de `qr_secret` (tabela cftv_acesso_config do PHP)
# para que QR Codes já emitidos continuem válidos.
GEOP_QR_SECRET = env("GEOP_QR_SECRET") or SECRET_KEY
GEOP_SLA_HORAS = {"BAIXA": 72, "MEDIA": 48, "ALTA": 24, "CRITICA": 4}
GEOP_TOKEN_REABERTURA_HORAS = 24
# Perfis com franquia de emissões de relatório (renovada com token do TI), como o GESTOR no PHP.
GEOP_RELATORIOS_COTA_PERFIS = env("GEOP_RELATORIOS_COTA_PERFIS", "GESTOR")
GEOP_TOKEN_REABERTURA_TENTATIVAS = 5

# --- E-mail (convites, recuperação de senha e respostas de solicitações de acesso) ---
EMAIL_BACKEND = env("EMAIL_BACKEND", "django.core.mail.backends.smtp.EmailBackend" if env("EMAIL_HOST")
                    else "django.core.mail.backends.console.EmailBackend")
EMAIL_HOST = env("EMAIL_HOST", "")
EMAIL_PORT = env_int("EMAIL_PORT", 587)
EMAIL_HOST_USER = env("EMAIL_HOST_USER", "")
EMAIL_HOST_PASSWORD = env("EMAIL_HOST_PASSWORD", "")
EMAIL_USE_TLS = env_bool("EMAIL_USE_TLS", True)
DEFAULT_FROM_EMAIL = env("DEFAULT_FROM_EMAIL", "GEOP <nao-responda@localhost>")
PASSWORD_RESET_TIMEOUT = 60 * 60 * 2

# Backups (comando `backup_banco`): pasta fora da mídia pública.
GEOP_BACKUP_DIR = Path(env("GEOP_BACKUP_DIR", str(BASE_DIR / "backups")))
GEOP_BACKUP_MANTER = env_int("GEOP_BACKUP_MANTER", 14)

# --- Segurança em produção --------------------------------------------------
SECURE_CONTENT_TYPE_NOSNIFF = True
SECURE_REFERRER_POLICY = "same-origin"
X_FRAME_OPTIONS = "DENY"
if not DEBUG:
    SECURE_PROXY_SSL_HEADER = ("HTTP_X_FORWARDED_PROTO", "https")
    SECURE_SSL_REDIRECT = env_bool("SECURE_SSL_REDIRECT", True)
    SESSION_COOKIE_SECURE = True
    CSRF_COOKIE_SECURE = True
    SECURE_HSTS_SECONDS = env_int("SECURE_HSTS_SECONDS", 31536000)
    SECURE_HSTS_INCLUDE_SUBDOMAINS = True

LOGGING = {
    "version": 1,
    "disable_existing_loggers": False,
    "handlers": {"console": {"class": "logging.StreamHandler"}},
    "root": {"handlers": ["console"], "level": env("LOG_LEVEL", "INFO")},
}
