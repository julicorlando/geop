from functools import wraps

from django.contrib.auth.decorators import login_required
from django.core.exceptions import PermissionDenied


def permissao_requerida(*codigos):
    """Exige login e ao menos uma das permissões informadas."""

    def decorator(view):
        @login_required
        @wraps(view)
        def wrapper(request, *args, **kwargs):
            if not request.user.pode_algum(*codigos):
                raise PermissionDenied("Você não tem permissão para acessar esta página.")
            return view(request, *args, **kwargs)

        return wrapper

    return decorator


def exigir(user, codigo):
    if not user.pode(codigo):
        raise PermissionDenied("Você não tem permissão para realizar esta operação.")


def somente_post(view):
    @wraps(view)
    def wrapper(request, *args, **kwargs):
        if request.method != "POST":
            raise PermissionDenied("Método não permitido.")
        return view(request, *args, **kwargs)

    return wrapper
