import unittest

from django.contrib.auth import authenticate
from django.test import TestCase, override_settings
from django.urls import reverse

from .models import Perfil, Usuario

try:
    import bcrypt
except ImportError:  # pragma: no cover
    bcrypt = None


class PermissoesTests(TestCase):
    def test_perfis_padrao_criados(self):
        self.assertTrue(Perfil.objects.filter(codigo="TI", administrativo=True).exists())
        gestor = Usuario.objects.create_user("g@x.com", "G", "x", perfil=Perfil.objects.get(codigo="GESTOR"))
        agente = Usuario.objects.create_user("a@x.com", "A", "x", perfil=Perfil.objects.get(codigo="AGENTE"))
        ti = Usuario.objects.create_user("t@x.com", "T", "x", perfil=Perfil.objects.get(codigo="TI"))
        self.assertTrue(gestor.pode("os.gerenciar"))
        self.assertFalse(agente.pode("os.criar"))
        self.assertTrue(agente.pode("os.executar"))
        self.assertTrue(ti.pode("qualquer.coisa"))
        agente.is_active = False
        self.assertFalse(agente.pode("os.executar"))


@override_settings(CACHES={"default": {"BACKEND": "django.core.cache.backends.locmem.LocMemCache", "LOCATION": "t"}},
                   LOGIN_MAX_TENTATIVAS=3)
class LoginTests(TestCase):
    def setUp(self):
        self.user = Usuario.objects.create_user("Joao@X.com", "João", "Senha-forte-123",
                                                perfil=Perfil.objects.get(codigo="GESTOR"))

    def test_login_case_insensitive_e_bloqueio(self):
        url = reverse("accounts:login")
        for _ in range(3):
            self.client.post(url, {"username": "joao@x.com", "password": "errada"})
        r = self.client.post(url, {"username": "joao@x.com", "password": "Senha-forte-123"})
        self.assertContains(r, "Muitas tentativas")
        from django.core.cache import cache

        cache.clear()
        r = self.client.post(url, {"username": "JOAO@x.com", "password": "Senha-forte-123"})
        self.assertRedirects(r, reverse("core:dashboard"))

    def test_troca_de_senha_obrigatoria(self):
        self.user.forcar_troca_senha = True
        self.user.save()
        self.client.force_login(self.user)
        r = self.client.get(reverse("ordens:lista"))
        self.assertRedirects(r, reverse("accounts:mudar_senha") + "?obrigatoria=1")

    @unittest.skipIf(bcrypt is None, "biblioteca bcrypt não instalada")
    def test_senha_importada_do_php(self):
        php_hash = bcrypt.hashpw(b"senha-antiga", bcrypt.gensalt(4)).decode().replace("$2b$", "$2y$", 1)
        u = Usuario.objects.create(email="php@x.com", nome="PHP", password=f"php_bcrypt${php_hash}")
        self.assertIsNotNone(authenticate(username="php@x.com", password="senha-antiga"))
        u.refresh_from_db()
        self.assertTrue(u.password.startswith("pbkdf2_sha256$"))  # regravada no padrão do Django
