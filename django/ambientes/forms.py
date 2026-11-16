from django import forms

from core.forms import DateTimeLocalInput, MultiplosArquivosField
from core.models import Local
from limpeza.models import OrganogramaNo, PlanoTarefa
from ordens.models import Ordem

from .models import Ambiente, AmbienteAcao


class AmbienteForm(forms.ModelForm):
    acoes_selecionadas = forms.ModelMultipleChoiceField(
        label="Ações disponíveis neste ambiente", required=False,
        queryset=AmbienteAcao.objects.filter(ativo=True), widget=forms.CheckboxSelectMultiple)
    acoes_obrigatorias = forms.ModelMultipleChoiceField(
        label="Ações obrigatórias", required=False,
        queryset=AmbienteAcao.objects.filter(ativo=True), widget=forms.CheckboxSelectMultiple)

    class Meta:
        model = Ambiente
        fields = ["local", "nome_publico", "descricao_publica", "frequencia_minutos", "exibir_historico",
                  "historico_limite", "exibir_fotos", "exige_foto_asg", "exige_foto_supervisor", "ativo"]
        widgets = {"descricao_publica": forms.Textarea(attrs={"rows": 2})}
        help_texts = {"frequencia_minutos": "Após esse intervalo sem limpeza validada, o QR mostra “atrasado”."}

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["local"].queryset = Local.objects.filter(ativo=True)
        if self.instance.pk:
            vinc = self.instance.acoes_ativas()
            self.initial["acoes_selecionadas"] = [v.acao_id for v in vinc]
            self.initial["acoes_obrigatorias"] = [v.acao_id for v in vinc if v.obrigatoria]


class AcaoForm(forms.ModelForm):
    class Meta:
        model = AmbienteAcao
        fields = ["nome", "descricao", "categoria", "exige_foto", "exige_observacao", "ativo"]


class RegistroForm(forms.Form):
    acoes = forms.ModelMultipleChoiceField(label="Ações realizadas", required=False, queryset=AmbienteAcao.objects.none(),
                                           widget=forms.CheckboxSelectMultiple)
    tarefas = forms.ModelMultipleChoiceField(label="Tarefas do plano", required=False,
                                             queryset=PlanoTarefa.objects.filter(ativo=True, grupo__ativo=True),
                                             widget=forms.CheckboxSelectMultiple)
    equipe = forms.ModelMultipleChoiceField(label="Equipe que executou", required=False,
                                            queryset=OrganogramaNo.objects.filter(ativo=True),
                                            widget=forms.CheckboxSelectMultiple)
    principal = forms.ModelChoiceField(label="Responsável principal", required=False,
                                       queryset=OrganogramaNo.objects.filter(ativo=True))
    executor_nome = forms.CharField(label="Outro executor (nome)", required=False, max_length=160)
    iniciado_em = forms.DateTimeField(label="Início", required=False, widget=DateTimeLocalInput())
    finalizado_em = forms.DateTimeField(label="Término", required=False, widget=DateTimeLocalInput(),
                                        help_text="Em branco: agora.")
    observacao_asg = forms.CharField(label="Observações", required=False, widget=forms.Textarea(attrs={"rows": 2}))
    problema_encontrado = forms.CharField(label="Problema encontrado", required=False,
                                          widget=forms.Textarea(attrs={"rows": 2}))
    insumos_repostos = forms.CharField(label="Insumos repostos", required=False, widget=forms.Textarea(attrs={"rows": 2}))
    ordem = forms.ModelChoiceField(label="Vincular a uma ordem aberta (opcional)", required=False,
                                   queryset=Ordem.objects.none(), empty_label="Rotina sem ordem")
    fotos = MultiplosArquivosField(label="Fotos")

    def __init__(self, *args, ambiente=None, ordens=None, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["acoes"].queryset = AmbienteAcao.objects.filter(
            ambienteacaovinculo__ambiente=ambiente, ambienteacaovinculo__ativo=True, ativo=True)
        if ordens is not None:
            self.fields["ordem"].queryset = ordens

    def clean(self):
        dados = super().clean()
        if dados.get("iniciado_em") and dados.get("finalizado_em") and dados["iniciado_em"] > dados["finalizado_em"]:
            raise forms.ValidationError("O início não pode ser depois do término.")
        return dados


class ValidacaoForm(forms.Form):
    resultado = forms.ChoiceField(label="Resultado", choices=[
        ("APROVADA", "Aprovar"), ("CORRECAO_SOLICITADA", "Solicitar correção"), ("REPROVADA", "Reprovar")])
    observacao = forms.CharField(label="Observação", required=False, widget=forms.Textarea(attrs={"rows": 2}))
    fotos = MultiplosArquivosField(label="Fotos da inspeção")
    publicar_fotos = forms.BooleanField(label="Publicar as fotos no QR público", required=False)
