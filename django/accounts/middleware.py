from django.shortcuts import redirect
from django.urls import reverse

ISENTOS = ("/conta/entrar", "/conta/sair", "/conta/senha", "/static/")


class TrocaSenhaObrigatoriaMiddleware:
    """Redireciona para a troca de senha quando o usuário está marcado para isso."""

    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        user = getattr(request, "user", None)
        if (
            user is not None
            and user.is_authenticated
            and getattr(user, "forcar_troca_senha", False)
            and not request.path.startswith(ISENTOS)
        ):
            return redirect(f"{reverse('accounts:mudar_senha')}?obrigatoria=1")
        return self.get_response(request)
