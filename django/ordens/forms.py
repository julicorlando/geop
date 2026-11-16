from django import forms
from django.contrib.auth import get_user_model

from core.forms import DateTimeLocalInput, MultiplosArquivosField
from core.models import Categoria, Local
from limpeza.models import OrganogramaNo, PlanoTarefa

from .models import Anexo, Ordem, Prioridade, Status

Usuario = get_user_model()


def executores():
    return Usuario.objects.filter(is_active=True, perfil__permissoes__codigo="os.executar").distinct().order_by("nome")


def gestores():
    return Usuario.objects.filter(is_active=True, perfil__permissoes__codigo="os.gerenciar").distinct().order_by("nome")


class OrdemForm(forms.Form):
    titulo = forms.CharField(label="Título", max_length=150)
    descricao = forms.CharField(label="Descrição", widget=forms.Textarea(attrs={"rows": 4}))
    local = forms.ModelChoiceField(label="Local", queryset=Local.objects.filter(ativo=True))
    categoria = forms.ModelChoiceField(label="Categoria", queryset=Categoria.objects.filter(ativo=True))
    prioridade = forms.ChoiceField(label="Prioridade", choices=Prioridade.choices, initial=Prioridade.MEDIA)
    prazo = forms.DateTimeField(label="Prazo", required=False, widget=DateTimeLocalInput(),
                                help_text="Em branco: calculado pelo SLA da prioridade.")
    valor_previsto_orcamento = forms.DecimalField(label="Orçamento previsto (R$)", required=False, max_digits=12,
                                                  decimal_places=2, min_value=0)
    agente = forms.ModelChoiceField(label="Agente responsável", required=False, queryset=Usuario.objects.none())
    auxiliares = forms.ModelMultipleChoiceField(label="Agentes auxiliares", required=False,
                                                queryset=Usuario.objects.none(),
                                                widget=forms.SelectMultiple(attrs={"size": 4}))
    gestor = forms.ModelChoiceField(label="Gestor responsável", required=False, queryset=Usuario.objects.none(),
                                    help_text="Somente TI: abrir a ordem em nome de um gestor.")
    fotos = MultiplosArquivosField(label="Fotos da abertura")
    # Ordem de limpeza (OL)
    tarefas = forms.ModelMultipleChoiceField(label="Checklist do plano de limpeza", required=False,
                                             queryset=PlanoTarefa.objects.filter(ativo=True, grupo__ativo=True)
                                             .select_related("grupo"), widget=forms.CheckboxSelectMultiple)
    itens_livres = forms.CharField(label="Itens extras do checklist (um por linha)", required=False,
                                   widget=forms.Textarea(attrs={"rows": 3}))
    equipe = forms.ModelMultipleChoiceField(label="Equipe ASG", required=False,
                                            queryset=OrganogramaNo.objects.filter(ativo=True),
                                            widget=forms.CheckboxSelectMultiple)
    asg_principal = forms.ModelChoiceField(label="ASG principal", required=False,
                                           queryset=OrganogramaNo.objects.filter(ativo=True))
    supervisor = forms.ModelChoiceField(label="Supervisor", required=False,
                                        queryset=OrganogramaNo.objects.filter(ativo=True))

    def __init__(self, *args, user=None, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["agente"].queryset = executores()
        self.fields["auxiliares"].queryset = executores()
        if user is not None and user.is_ti:
            self.fields["gestor"].queryset = gestores()
        else:
            del self.fields["gestor"]

    def itens_livres_lista(self):
        return [linha.strip() for linha in self.cleaned_data.get("itens_livres", "").splitlines() if linha.strip()]


class EditarOrdemForm(forms.ModelForm):
    class Meta:
        model = Ordem
        fields = ["titulo", "descricao", "local", "categoria", "prioridade", "prazo"]
        widgets = {"descricao": forms.Textarea(attrs={"rows": 4}), "prazo": DateTimeLocalInput()}

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["local"].queryset = Local.objects.filter(ativo=True) | Local.objects.filter(pk=self.instance.local_id)
        self.fields["categoria"].queryset = (Categoria.objects.filter(ativo=True)
                                             | Categoria.objects.filter(pk=self.instance.categoria_id))
        self.fields["prazo"].required = False


class AtribuirForm(forms.Form):
    agente = forms.ModelChoiceField(label="Responsável", required=False, queryset=Usuario.objects.none())
    auxiliares = forms.ModelMultipleChoiceField(label="Auxiliares", required=False, queryset=Usuario.objects.none(),
                                                widget=forms.SelectMultiple(attrs={"size": 4}))

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["agente"].queryset = executores()
        self.fields["auxiliares"].queryset = executores()


class StatusForm(forms.Form):
    status = forms.ChoiceField(label="Novo status", choices=Status.choices)
    obs = forms.CharField(label="Observação", required=False, widget=forms.Textarea(attrs={"rows": 2}))
    custo_materiais = forms.DecimalField(label="Custo de materiais (R$)", required=False, max_digits=12,
                                         decimal_places=2, min_value=0,
                                         help_text="Ao encerrar. Em branco: soma dos materiais lançados.")

    def __init__(self, *args, permitidos=(), **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["status"].choices = [("", "Selecione…")] + [(s, Status(s).label) for s in permitidos]


class AnexoForm(forms.Form):
    tipo = forms.ChoiceField(label="Tipo", choices=Anexo.Tipo.choices, initial=Anexo.Tipo.FOTO_DEPOIS)
    descricao = forms.CharField(label="Descrição", required=False, max_length=500)
    arquivos = MultiplosArquivosField(label="Arquivos", imagens=False)


class MaterialForm(forms.Form):
    nome_material = forms.CharField(label="Material", max_length=150)
    quantidade = forms.DecimalField(label="Quantidade", max_digits=10, decimal_places=2, min_value=0.01)
    unidade = forms.CharField(label="Unidade", max_length=30, required=False)
    custo_unitario = forms.DecimalField(label="Custo unitário (R$)", required=False, max_digits=12, decimal_places=2,
                                        min_value=0)
    observacao = forms.CharField(label="Observação", required=False)


class FiltroOrdensForm(forms.Form):
    q = forms.CharField(label="Buscar", required=False,
                        widget=forms.TextInput(attrs={"placeholder": "Número, título ou descrição"}))
    tipo = forms.ChoiceField(label="Tipo", required=False, choices=[("", "Todos")] + Ordem.Tipo.choices)
    status = forms.ChoiceField(label="Status", required=False,
                               choices=[("", "Todos"), ("abertas", "Em aberto"), ("atrasadas", "Atrasadas")]
                               + Status.choices)
    prioridade = forms.ChoiceField(label="Prioridade", required=False, choices=[("", "Todas")] + Prioridade.choices)
    local = forms.ModelChoiceField(label="Local", required=False, queryset=Local.objects.all())
    categoria = forms.ModelChoiceField(label="Categoria", required=False, queryset=Categoria.objects.all())
    agente = forms.ModelChoiceField(label="Agente", required=False, queryset=Usuario.objects.none())

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["agente"].queryset = executores()
