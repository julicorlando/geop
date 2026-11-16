"""Compatibilidade com as senhas do PHP (`password_hash`, bcrypt `$2y$`).

Na importação, o hash do PHP é salvo como ``php_bcrypt$<hash original>``. No
primeiro login bem-sucedido o Django regrava a senha com o hasher padrão
(PBKDF2), já que ``must_update`` sempre retorna True.
"""
from django.contrib.auth.hashers import BasePasswordHasher, mask_hash
from django.utils.crypto import constant_time_compare
from django.utils.translation import gettext_noop as _


class PHPBcryptPasswordHasher(BasePasswordHasher):
    algorithm = "php_bcrypt"
    library = "bcrypt"

    def salt(self):  # pragma: no cover - nunca usamos este hasher para criar senhas
        raise NotImplementedError("Use apenas para verificar senhas importadas do PHP.")

    def encode(self, password, salt):  # pragma: no cover
        raise NotImplementedError("Use apenas para verificar senhas importadas do PHP.")

    @staticmethod
    def _php_hash(encoded):
        algorithm, php_hash = encoded.split("$", 1)
        assert algorithm == "php_bcrypt"
        return php_hash

    def verify(self, password, encoded):
        bcrypt = self._load_library()
        php_hash = self._php_hash(encoded)
        # O PHP usa o prefixo $2y$, equivalente ao $2b$ da biblioteca bcrypt.
        normalizado = ("$2b$" + php_hash[4:]) if php_hash.startswith("$2y$") else php_hash
        try:
            calculado = bcrypt.hashpw(password.encode("utf-8"), normalizado.encode("ascii"))
        except ValueError:
            return False
        return constant_time_compare(calculado, normalizado.encode("ascii"))

    def safe_summary(self, encoded):
        return {_("algorithm"): self.algorithm, _("hash"): mask_hash(self._php_hash(encoded))}

    def must_update(self, encoded):
        return True

    def harden_runtime(self, password, encoded):
        pass
