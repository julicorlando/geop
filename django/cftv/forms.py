from django import forms

from core.forms import DateTimeLocalInput

from .models import Destino, Liberacao, Observacao, Ocorrencia, RelatorioPlantao, cpf_valido, normalizar_cpf

DATA = {"type": "date"}
HORA = {"type": "time"}


class PlantaoForm(forms.ModelForm):
    class Meta:
        model = RelatorioPlantao
        fields = ["inicio_turno", "fim_turno", "cameras_funcionando", "nvrs_gravando", "horario_sincronizado",
                  "radios_ok", "cameras_offline", "rondas_virtuais", "eventos_preventivos", "fluxo_clientes",
                  "tempo_medio_resposta_min", "falhas_livro", "areas_prioritarias", "situacoes_suspeitas",
                  "ocorrencias_resumo", "passagem_servico", "observacoes_gerais", "plano_melhoria"]
        widgets = {"inicio_turno": forms.TimeInput(attrs=HORA), "fim_turno": forms.TimeInput(attrs=HORA),
                   **{f: forms.Textarea(attrs={"rows": 3}) for f in
                      ["falhas_livro", "areas_prioritarias", "situacoes_suspeitas", "ocorrencias_resumo",
                       "passagem_servico", "observacoes_gerais", "plano_melhoria"]}}


class OcorrenciaForm(forms.ModelForm):
    class Meta:
        model = Ocorrencia
        fields = ["horario", "nivel", "tipo", "local", "descricao", "envolvidos", "imagens", "tempo_resposta_min",
                  "equipes_acionadas", "encerramento", "preservar_imagens"]
        widgets = {"horario": DateTimeLocalInput(), **{f: forms.Textarea(attrs={"rows": 2}) for f in
                                                       ["descricao", "envolvidos", "equipes_acionadas", "encerramento"]}}


class ObservacaoForm(forms.ModelForm):
    equipe = forms.ChoiceField(choices=[(e, e) for e in Observacao.EQUIPES])
    item = forms.ChoiceField(choices=[(i, i) for i in Observacao.ITENS], required=False)

    class Meta:
        model = Observacao
        fields = ["equipe", "horario", "local", "item", "classificacao", "descricao", "tempo_resposta_min", "providencia"]
        widgets = {"horario": DateTimeLocalInput(), "descricao": forms.Textarea(attrs={"rows": 2}),
                   "providencia": forms.Textarea(attrs={"rows": 2})}


class LiberacaoForm(forms.ModelForm):
    class Meta:
        model = Liberacao
        fields = ["nome_completo", "cpf", "email", "data_acesso", "data_fim", "hora_inicio", "hora_fim", "destino_tipo",
                  "destino_nome", "motivo", "observacoes"]
        widgets = {"data_acesso": forms.DateInput(attrs=DATA), "data_fim": forms.DateInput(attrs=DATA),
                   "hora_inicio": forms.TimeInput(attrs=HORA), "hora_fim": forms.TimeInput(attrs=HORA),
                   "motivo": forms.Textarea(attrs={"rows": 2}), "observacoes": forms.Textarea(attrs={"rows": 2})}

    def clean_cpf(self):
        cpf = normalizar_cpf(self.cleaned_data["cpf"])
        if not cpf_valido(cpf):
            raise forms.ValidationError("CPF inválido.")
        return cpf


class SolicitacaoPublicaForm(forms.Form):
    nome_completo = forms.CharField(label="Nome completo", max_length=180)
    cpf = forms.CharField(label="CPF", max_length=14, widget=forms.TextInput(attrs={"inputmode": "numeric"}))
    telefone = forms.CharField(label="Telefone / WhatsApp", max_length=30, required=False)
    email = forms.EmailField(label="E-mail (para receber a resposta)", required=False)
    empresa = forms.CharField(label="Empresa", max_length=180, required=False)
    destino_tipo = forms.ChoiceField(label="Destino", choices=Destino.TIPOS)
    destino_nome = forms.CharField(label="Loja / setor / empresa de destino", max_length=180)
    responsavel_interno = forms.CharField(label="Quem autorizou / responsável no local", max_length=180, required=False)
    data_acesso = forms.DateField(label="Data", widget=forms.DateInput(attrs=DATA))
    data_fim = forms.DateField(label="Até (se for mais de um dia)", required=False, widget=forms.DateInput(attrs=DATA))
    hora_inicio = forms.TimeField(label="Das", required=False, widget=forms.TimeInput(attrs=HORA))
    hora_fim = forms.TimeField(label="Às", required=False, widget=forms.TimeInput(attrs=HORA))
    motivo = forms.CharField(label="Motivo do acesso", widget=forms.Textarea(attrs={"rows": 2}))
    veiculo_placa = forms.CharField(label="Placa do veículo", max_length=20, required=False)
    observacoes = forms.CharField(label="Observações", required=False, widget=forms.Textarea(attrs={"rows": 2}))
    acompanhantes = forms.CharField(label="Acompanhantes (um por linha: Nome; CPF)", required=False,
                                    widget=forms.Textarea(attrs={"rows": 3}))
    aceite = forms.BooleanField(label="Autorizo o uso destes dados exclusivamente para o controle de acesso (LGPD).")

    def __init__(self, *args, destinos=None, destino_padrao="", **kwargs):
        super().__init__(*args, **kwargs)
        if destino_padrao:
            self.fields["destino_nome"].initial = destino_padrao
        self.destinos = destinos or []

    def clean_cpf(self):
        if not cpf_valido(self.cleaned_data["cpf"]):
            raise forms.ValidationError("CPF inválido.")
        return normalizar_cpf(self.cleaned_data["cpf"])

    def pessoas(self):
        out = []
        for linha in (self.cleaned_data.get("acompanhantes") or "").splitlines():
            if ";" in linha:
                nome, cpf = linha.rsplit(";", 1)
                out.append({"nome": nome.strip(), "cpf": cpf.strip()})
        return out

    def dados(self):
        d = {k: v for k, v in self.cleaned_data.items() if k not in ("acompanhantes", "aceite")}
        return {k: ("" if v is None and k not in ("data_fim", "hora_inicio", "hora_fim") else v) for k, v in d.items()}
