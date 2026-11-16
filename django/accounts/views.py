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
        user = form.get_user()
        if user.totp_ativo:
            # Segunda etapa: a sessão só é aberta depois do código do autenticador.
            self.request.session["2fa_usuario"] = user.pk
            self.request.session["2fa_backend"] = user.backend
            self.request.session["2fa_next"] = self.get_success_url()
            return redirect("accounts:dois_fatores")
        resposta = super().form_valid(form)
        _registrar_login(self.request)
        return resposta


def _registrar_login(request):
    Usuario.objects.filter(pk=request.user.pk).update(ultimo_login_ip=request.META.get("REMOTE_ADDR") or None)
    auditar(request, "usuario", request.user.pk, "LOGIN")


def dois_fatores(request):
    from django.contrib.auth import login
    from django.utils.http import url_has_allowed_host_and_scheme

    from . import totp

    uid = request.session.get("2fa_usuario")
    if not uid:
        return redirect("accounts:login")
    user = Usuario.objects.filter(pk=uid, is_active=True).first()
    if not user:
        return redirect("accounts:login")
    erro = ""
    if request.method == "POST":
        chave = f"2fa:{uid}"
        tentativas = cache.get(chave, 0)
        if tentativas >= settings.LOGIN_MAX_TENTATIVAS:
            erro = "Muitas tentativas. Entre novamente mais tarde."
        elif totp.verificar(totp.decifrar(user.totp_segredo), request.POST.get("codigo", "")):
            cache.delete(chave)
            destino = request.session.pop("2fa_next", "/")
            backend = request.session.pop("2fa_backend", None)
            request.session.pop("2fa_usuario", None)
            login(request, user, backend=backend)
            _registrar_login(request)
            if not url_has_allowed_host_and_scheme(destino, allowed_hosts={request.get_host()}):
                destino = "/"
            return redirect(destino)
        else:
            cache.set(chave, tentativas + 1, settings.LOGIN_BLOQUEIO_MINUTOS * 60)
            auditar(request, "usuario", user.pk, "2FA_FALHA", usuario=user)
            erro = "Código inválido."
    return render(request, "accounts/dois_fatores.html", {"erro": erro})


@login_required
def seguranca(request):
    from . import totp

    user = request.user
    segredo_pendente = request.session.get("2fa_novo_segredo")
    if request.method == "POST":
        acao = request.POST.get("acao")
        if acao == "iniciar":
            request.session["2fa_novo_segredo"] = totp.novo_segredo()
        elif acao == "confirmar" and segredo_pendente:
            if totp.verificar(segredo_pendente, request.POST.get("codigo", "")):
                user.totp_segredo, user.totp_ativo = totp.cifrar(segredo_pendente), True
                user.save(update_fields=["totp_segredo", "totp_ativo"])
                request.session.pop("2fa_novo_segredo", None)
                auditar(request, "usuario", user.pk, "2FA_ATIVADO")
                messages.success(request, "Verificação em duas etapas ativada.")
            else:
                messages.error(request, "Código incorreto. Confira o horário do celular e tente de novo.")
        elif acao == "desativar":
            if user.check_password(request.POST.get("senha", "")) and totp.verificar(
                    totp.decifrar(user.totp_segredo), request.POST.get("codigo", "")):
                user.totp_segredo, user.totp_ativo = "", False
                user.save(update_fields=["totp_segredo", "totp_ativo"])
                auditar(request, "usuario", user.pk, "2FA_DESATIVADO")
                messages.success(request, "Verificação em duas etapas desativada.")
            else:
                messages.error(request, "Senha ou código incorretos.")
        return redirect("accounts:seguranca")
    from core.models import AuditoriaEvento

    return render(request, "accounts/seguranca.html", {
        "segredo": segredo_pendente, "uri": totp.uri(user.email, segredo_pendente) if segredo_pendente else "",
        "acessos": AuditoriaEvento.objects.filter(usuario=user, acao__in=["LOGIN", "2FA_FALHA", "ALTERAR_SENHA",
                                                                         "2FA_ATIVADO", "2FA_DESATIVADO"])[:15]})


def ativar(request, token):
    """Link de convite: o próprio usuário define a senha."""
    import hashlib

    from django.contrib.auth.forms import SetPasswordForm

    from .models import Convite

    conv = Convite.objects.select_related("usuario").filter(
        token_hash=hashlib.sha256(token.encode()).hexdigest()).first()
    if not conv or not conv.valido or not conv.usuario.is_active:
        return render(request, "accounts/convite_invalido.html", status=404)
    form = SetPasswordForm(conv.usuario, request.POST or None)
    if request.method == "POST" and form.is_valid():
        user = form.save()
        user.forcar_troca_senha = False
        user.senha_alterada_em = timezone.now()
        user.save(update_fields=["forcar_troca_senha", "senha_alterada_em"])
        conv.usado_em = timezone.now()
        conv.save(update_fields=["usado_em"])
        auditar(request, "usuario", user.pk, "CONVITE_USADO", usuario=user)
        messages.success(request, "Senha criada. Entre com seu e-mail e a nova senha.")
        return redirect("accounts:login")
    return render(request, "accounts/ativar.html", {"form": form, "usuario": conv.usuario})


def criar_convite(usuario, gerado_por, horas=72):
    import hashlib
    import secrets
    from datetime import timedelta

    from .models import Convite

    usuario.convites.filter(usado_em__isnull=True, invalidado_em__isnull=True).update(invalidado_em=timezone.now())
    token = secrets.token_urlsafe(32)
    Convite.objects.create(usuario=usuario, token_hash=hashlib.sha256(token.encode()).hexdigest(), gerado_por=gerado_por,
                           expira_em=timezone.now() + timedelta(hours=horas))
    return token


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
    if request.method == "POST" and obj and request.POST.get("acao") in ("convite", "reset_2fa"):
        if request.POST["acao"] == "reset_2fa":
            obj.totp_segredo, obj.totp_ativo = "", False
            obj.save(update_fields=["totp_segredo", "totp_ativo"])
            auditar(request, "usuario", obj.pk, "2FA_REDEFINIDO")
            messages.success(request, f"2FA de {obj.nome} desativado.")
            return redirect("accounts:usuario_editar", pk=obj.pk)
        return _mostrar_convite(request, obj)
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
            if novo and form.cleaned_data.get("enviar_convite"):
                return _mostrar_convite(request, user)
            return redirect("accounts:usuarios")
    return render(request, "accounts/usuario_form.html", {"form": form, "obj": obj})


def _mostrar_convite(request, usuario):
    from django.core.mail import send_mail
    from django.urls import reverse

    token = criar_convite(usuario, request.user)
    link = request.build_absolute_uri(reverse("accounts:ativar", args=[token]))
    enviado = False
    try:
        enviado = bool(send_mail(f"Seu acesso ao GEOP — {settings.GEOP_NOME_UNIDADE}",
                                 f"Olá, {usuario.nome}.\n\nCrie sua senha em até 72 horas:\n{link}\n", None,
                                 [usuario.email], fail_silently=True))
    except Exception:
        enviado = False
    auditar(request, "usuario", usuario.pk, "CONVITE_GERADO")
    return render(request, "accounts/convite.html", {"usuario": usuario, "link": link, "enviado": enviado})


@permissao_requerida("perfis.gerenciar")
def perfis(request):
    lista = Perfil.objects.annotate(n_usuarios=Count("usuarios"), n_permissoes=Count("permissoes", distinct=True)).order_by("nome")
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
