import hashlib

from django.conf import settings
from django.contrib import messages
from django.contrib.auth import update_session_auth_hash
from django.contrib.auth import views as auth_views
from django.contrib.auth.decorators import login_required
from django.contrib.auth.forms import PasswordChangeForm
from django.core.cache import cache
from django.core.paginator import Paginator
from django.db.models import Count, Q
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone

from core.permissions import permissao_requerida
from core.services import auditar

from .forms import LoginForm, PerfilForm, UsuarioForm
from .models import Perfil, Permissao, Usuario


def _chave_bloqueio(request):
    email = (request.POST.get("username") or "").strip().lower()
    ip = request.META.get("REMOTE_ADDR", "")
    return "login:" + hashlib.sha256(f"{ip}|{email}".encode()).hexdigest()


class LoginView(auth_views.LoginView):
    template_name = "accounts/login.html"
    authentication_form = LoginForm
    redirect_authenticated_user = True

    def post(self, request, *args, **kwargs):
        chave = _chave_bloqueio(request)
        if cache.get(chave, 0) >= settings.LOGIN_MAX_TENTATIVAS:
            messages.error(request, f"Muitas tentativas. Aguarde {settings.LOGIN_BLOQUEIO_MINUTOS} minutos e tente de novo.")
            return self.render_to_response(self.get_context_data(form=self.authentication_form(request)))
        return super().post(request, *args, **kwargs)

    def form_invalid(self, form):
        chave = _chave_bloqueio(self.request)
        cache.set(chave, cache.get(chave, 0) + 1, settings.LOGIN_BLOQUEIO_MINUTOS * 60)
        auditar(self.request, "login", None, "LOGIN_FALHA", "email", None, self.request.POST.get("username", "")[:150])
        return super().form_invalid(form)

    def form_valid(self, form):
        cache.delete(_chave_bloqueio(self.request))
        resposta = super().form_valid(form)
        auditar(self.request, "usuario", self.request.user.pk, "LOGIN")
        return resposta


class LogoutView(auth_views.LogoutView):
    pass


@login_required
def mudar_senha(request):
    form = PasswordChangeForm(request.user, request.POST or None)
    if request.method == "POST" and form.is_valid():
        user = form.save()
        user.forcar_troca_senha = False
        user.senha_alterada_em = timezone.now()
        user.save(update_fields=["forcar_troca_senha", "senha_alterada_em"])
        update_session_auth_hash(request, user)
        auditar(request, "usuario", user.pk, "ALTERAR_SENHA")
        messages.success(request, "Senha alterada com sucesso.")
        return redirect("core:dashboard")
    return render(request, "accounts/mudar_senha.html", {"form": form, "obrigatoria": request.user.forcar_troca_senha})


@permissao_requerida("usuarios.gerenciar")
def usuarios(request):
    qs = Usuario.objects.select_related("perfil", "setor")
    q = request.GET.get("q", "").strip()
    if q:
        qs = qs.filter(Q(nome__icontains=q) | Q(email__icontains=q) | Q(cargo__icontains=q))
    if request.GET.get("perfil"):
        qs = qs.filter(perfil_id=request.GET["perfil"])
    if request.GET.get("ativos", "1") == "1":
        qs = qs.filter(is_active=True)
    return render(request, "accounts/usuarios.html", {
        "pagina": Paginator(qs, 40).get_page(request.GET.get("pagina")), "perfis": Perfil.objects.all(), "q": q,
    })


@permissao_requerida("usuarios.gerenciar")
def usuario_form(request, pk=None):
    obj = get_object_or_404(Usuario, pk=pk) if pk else None
    form = UsuarioForm(request.POST or None, instance=obj)
    if request.method == "POST" and form.is_valid():
        if obj and obj.pk == request.user.pk and not form.cleaned_data["is_active"]:
            form.add_error("is_active", "Você não pode desativar o próprio usuário.")
        else:
            novo = obj is None
            user = form.save(commit=False)
            if novo:
                user.criado_por = request.user
            user.save()
            auditar(request, "usuario", user.pk, "CRIAR" if novo else "EDITAR", "perfil", None, user.perfil_codigo)
            messages.success(request, f"Usuário {user.nome} salvo.")
            return redirect("accounts:usuarios")
    return render(request, "core/form_simples.html", {"form": form, "titulo": "Usuário", "voltar": "accounts:usuarios"})


@permissao_requerida("perfis.gerenciar")
def perfis(request):
    lista = Perfil.objects.annotate(n_usuarios=Count("usuarios"), n_permissoes=Count("permissoes", distinct=True))
    return render(request, "accounts/perfis.html", {"perfis": lista})


@permissao_requerida("perfis.gerenciar")
def perfil_form(request, pk=None):
    obj = get_object_or_404(Perfil, pk=pk) if pk else None
    form = PerfilForm(request.POST or None, instance=obj)
    if request.method == "POST" and form.is_valid():
        if form.cleaned_data.get("administrativo") and not request.user.is_ti:
            form.add_error("administrativo", "Somente o TI pode criar perfis administrativos.")
        else:
            perfil = form.save()
            auditar(request, "perfil", perfil.pk, "SALVAR", "permissoes", None,
                    ",".join(perfil.permissoes.values_list("codigo", flat=True)))
            messages.success(request, "Perfil salvo.")
            return redirect("accounts:perfis")
    grupos = {}
    for p in Permissao.objects.all():
        grupos.setdefault(p.modulo or "Geral", []).append(p)
    return render(request, "accounts/perfil_form.html", {"form": form, "grupos": grupos, "perfil": obj})
