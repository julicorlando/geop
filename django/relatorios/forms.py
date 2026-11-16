from django import forms

from core.models import Categoria, Local
from ordens.forms import executores
from ordens.models import Ordem, Prioridade, Status


class FiltroForm(forms.Form):
    inicio = forms.DateField(label="De", required=False, widget=forms.DateInput(attrs={"type": "date"}))
    fim = forms.DateField(label="Até", required=False, widget=forms.DateInput(attrs={"type": "date"}))
    status = forms.ChoiceField(label="Status", required=False, choices=[("", "Todos")] + Status.choices)
    prioridade = forms.ChoiceField(label="Prioridade", required=False, choices=[("", "Todas")] + Prioridade.choices)
    tipo = forms.ChoiceField(label="Tipo", required=False, choices=[("", "OS e OL")] + Ordem.Tipo.choices)
    local = forms.ModelChoiceField(label="Local", required=False, queryset=Local.objects.all())
    categoria = forms.ModelChoiceField(label="Categoria", required=False, queryset=Categoria.objects.all())
    agente = forms.ModelChoiceField(label="Agente", required=False, queryset=None)

    def __init__(self, *args, user=None, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["agente"].queryset = executores()
        if user is not None and not user.pode("os.visualizar_todas"):
            del self.fields["agente"]
