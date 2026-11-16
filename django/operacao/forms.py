from django import forms

from core.forms import DateTimeLocalInput  # noqa: F401  (reexportado para templates)
from ordens.forms import executores

from .models import CronogramaAgente, Escala, Feriado, PassagemTurno, Preventiva


class PreventivaForm(forms.ModelForm):
    class Meta:
        model = Preventiva
        fields = ["titulo", "descricao", "local", "categoria", "agente", "prioridade", "intervalo_dias",
                  "proxima_execucao", "ativo"]
        widgets = {"descricao": forms.Textarea(attrs={"rows": 3}), "proxima_execucao": forms.DateInput(attrs={"type": "date"})}

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["agente"].queryset = executores()


class FeriadoForm(forms.ModelForm):
    class Meta:
        model = Feriado
        fields = ["data", "nome", "local", "ativo"]
        widgets = {"data": forms.DateInput(attrs={"type": "date"})}


class EscalaForm(forms.ModelForm):
    class Meta:
        model = Escala
        fields = ["usuario", "data_servico", "tipo", "funcao", "posto", "jornada_horas", "hora_inicio", "hora_fim"]
        widgets = {"data_servico": forms.DateInput(attrs={"type": "date"}),
                   "hora_inicio": forms.TimeInput(attrs={"type": "time"}), "hora_fim": forms.TimeInput(attrs={"type": "time"})}

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["usuario"].queryset = executores()


class PassagemForm(forms.ModelForm):
    class Meta:
        model = PassagemTurno
        fields = ["recebe", "pendencias", "servicos_andamento", "ocorrencias_criticas", "equipamentos_indisponiveis",
                  "observacoes"]
        widgets = {f: forms.Textarea(attrs={"rows": 2}) for f in
                   ["pendencias", "servicos_andamento", "ocorrencias_criticas", "equipamentos_indisponiveis", "observacoes"]}
        labels = {"recebe": "Quem recebe o turno"}

    def __init__(self, *args, user=None, **kwargs):
        super().__init__(*args, **kwargs)
        from django.contrib.auth import get_user_model

        qs = get_user_model().objects.filter(is_active=True)
        self.fields["recebe"].queryset = qs.exclude(pk=user.pk) if user else qs


class CronogramaForm(forms.ModelForm):
    class Meta:
        model = CronogramaAgente
        fields = ["agente", "titulo", "descricao", "local", "dia_semana", "hora_inicio", "hora_fim"]
        widgets = {"descricao": forms.Textarea(attrs={"rows": 2}), "hora_inicio": forms.TimeInput(attrs={"type": "time"}),
                   "hora_fim": forms.TimeInput(attrs={"type": "time"})}

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["agente"].queryset = executores()

    def clean(self):
        d = super().clean()
        if d.get("hora_inicio") and d.get("hora_fim") and d["hora_fim"] <= d["hora_inicio"]:
            raise forms.ValidationError("O horário final deve ser depois do inicial.")
        return d


class ServicoEssencialForm(forms.Form):
    titulo = forms.CharField(label="O que precisa ser feito?", max_length=130)
    descricao = forms.CharField(label="Detalhes", widget=forms.Textarea(attrs={"rows": 3}))
    local = forms.ModelChoiceField(label="Local", queryset=None)
    categoria = forms.ModelChoiceField(label="Categoria", queryset=None)
    agente = forms.ModelChoiceField(label="Agente (em branco: recomendado automaticamente)", required=False,
                                    queryset=None)

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        from core.models import Categoria, Local

        self.fields["local"].queryset = Local.objects.filter(ativo=True)
        self.fields["categoria"].queryset = Categoria.objects.filter(ativo=True)
        self.fields["agente"].queryset = executores()
