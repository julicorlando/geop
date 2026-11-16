from django import forms

from .models import Categoria, Local


class MultiplosArquivosInput(forms.ClearableFileInput):
    allow_multiple_selected = True


class MultiplosArquivosField(forms.FileField):
    """Campo que aceita vários arquivos (a validação real fica em core.imaging)."""

    def __init__(self, *args, imagens=True, **kwargs):
        attrs = {"multiple": True, "data-comprimir": "1" if imagens else "0",
                 "accept": "image/*" if imagens else "image/*,application/pdf"}
        kwargs.setdefault("widget", MultiplosArquivosInput(attrs=attrs))
        kwargs.setdefault("required", False)
        super().__init__(*args, **kwargs)

    def clean(self, data, initial=None):
        if not data:
            return []
        if not isinstance(data, (list, tuple)):
            data = [data]
        return [super(MultiplosArquivosField, self).clean(d, initial) for d in data if d]


class DateTimeLocalInput(forms.DateTimeInput):
    input_type = "datetime-local"

    def __init__(self, **kwargs):
        super().__init__(format="%Y-%m-%dT%H:%M", **kwargs)


class LocalForm(forms.ModelForm):
    class Meta:
        model = Local
        fields = ["nome", "descricao", "ativo"]
        widgets = {"descricao": forms.Textarea(attrs={"rows": 2})}


class CategoriaForm(forms.ModelForm):
    class Meta:
        model = Categoria
        fields = ["nome", "descricao", "exige_foto_antes", "exige_foto_durante", "exige_foto_depois", "ativo"]
        widgets = {"descricao": forms.Textarea(attrs={"rows": 2})}
        help_texts = {"nome": "Categorias chamadas “Limpeza” geram Ordens de Limpeza (OL)."}
