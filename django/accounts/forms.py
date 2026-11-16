from django import forms
from django.contrib.auth import password_validation
from django.contrib.auth.forms import AuthenticationForm

from .models import Perfil, Permissao, Setor, Usuario


class LoginForm(AuthenticationForm):
    username = forms.EmailField(label="E-mail", widget=forms.EmailInput(attrs={"autofocus": True, "autocomplete": "email"}))
    password = forms.CharField(label="Senha", strip=False,
                               widget=forms.PasswordInput(attrs={"autocomplete": "current-password"}))

    error_messages = {
        "invalid_login": "E-mail ou senha inválidos.",
        "inactive": "Este usuário está inativo.",
    }

    def clean_username(self):
        return self.cleaned_data["username"].strip().lower()


class UsuarioForm(forms.ModelForm):
    senha = forms.CharField(label="Senha", required=False, strip=False, widget=forms.PasswordInput(render_value=False),
                            help_text="Obrigatória para novos usuários. Em branco mantém a atual.")
    forcar_troca_senha = forms.BooleanField(label="Exigir troca de senha no próximo login", required=False, initial=True)

    class Meta:
        model = Usuario
        fields = ["nome", "email", "telefone", "cargo", "perfil", "setor", "is_active", "forcar_troca_senha"]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["perfil"].queryset = Perfil.objects.filter(ativo=True)
        self.fields["perfil"].required = True
        self.fields["setor"].queryset = Setor.objects.filter(ativo=True)

    def clean_email(self):
        email = self.cleaned_data["email"].strip().lower()
        if Usuario.objects.filter(email=email).exclude(pk=self.instance.pk).exists():
            raise forms.ValidationError("Já existe um usuário com este e-mail.")
        return email

    def clean(self):
        dados = super().clean()
        senha = dados.get("senha")
        if not self.instance.pk and not senha:
            self.add_error("senha", "Informe uma senha inicial.")
        if senha:
            tmp = self.instance if self.instance.pk else Usuario(nome=dados.get("nome", ""), email=dados.get("email", ""))
            try:
                password_validation.validate_password(senha, tmp)
            except forms.ValidationError as exc:
                self.add_error("senha", exc)
        return dados

    def save(self, commit=True):
        user = super().save(commit=False)
        if self.cleaned_data.get("senha"):
            user.set_password(self.cleaned_data["senha"])
        if commit:
            user.save()
        return user


class PerfilForm(forms.ModelForm):
    permissoes = forms.ModelMultipleChoiceField(label="Permissões", required=False, queryset=Permissao.objects.all(),
                                                widget=forms.CheckboxSelectMultiple)

    class Meta:
        model = Perfil
        fields = ["nome", "codigo", "descricao", "ativo", "administrativo", "permissoes"]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        if self.instance.pk and self.instance.sistema:
            self.fields["codigo"].disabled = True

    def clean_codigo(self):
        return self.cleaned_data["codigo"].strip().upper().replace(" ", "_")
