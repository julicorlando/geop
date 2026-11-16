from django import forms

from .models import OrganogramaNo, PlanoGrupo, PlanoTarefa


class OrganogramaForm(forms.ModelForm):
    class Meta:
        model = OrganogramaNo
        fields = ["nome_colaborador", "cargo", "parent", "usuario", "setor", "turno", "contato", "observacao",
                  "posicao", "ativo"]
        widgets = {"observacao": forms.Textarea(attrs={"rows": 2})}

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        qs = OrganogramaNo.objects.filter(ativo=True)
        if self.instance.pk:
            qs = qs.exclude(pk=self.instance.pk)
        self.fields["parent"].queryset = qs

    def clean_parent(self):
        parent = self.cleaned_data.get("parent")
        no = parent
        while no is not None:  # impede ciclos no organograma
            if self.instance.pk and no.pk == self.instance.pk:
                raise forms.ValidationError("O superior não pode ser um subordinado deste colaborador.")
            no = no.parent
        return parent


class GrupoForm(forms.ModelForm):
    class Meta:
        model = PlanoGrupo
        fields = ["nome", "chave", "descricao", "posicao", "ativo"]
        widgets = {"descricao": forms.Textarea(attrs={"rows": 2})}


class TarefaForm(forms.ModelForm):
    class Meta:
        model = PlanoTarefa
        fields = ["grupo", "descricao", "chave", "periodicidade", "exige_foto", "exige_observacao", "observacao",
                  "posicao", "ativo"]
        widgets = {"observacao": forms.Textarea(attrs={"rows": 2})}
