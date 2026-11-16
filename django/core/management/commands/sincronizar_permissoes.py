from django.core.management.base import BaseCommand

from accounts.catalogo import sincronizar
from accounts.models import Perfil, Permissao


class Command(BaseCommand):
    help = "Cria/atualiza o catálogo de permissões e os perfis padrão do GEOP."

    def add_arguments(self, parser):
        parser.add_argument("--redefinir", action="store_true",
                            help="Reaplica as permissões padrão nos perfis do sistema (sobrescreve ajustes).")

    def handle(self, *args, **opts):
        sincronizar(Permissao, Perfil, sobrescrever=opts["redefinir"])
        self.stdout.write(self.style.SUCCESS("Permissões sincronizadas."))
