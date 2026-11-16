from django import forms

from .models import Artigo


class ArtigoForm(forms.ModelForm):
    class Meta:
        model = Artigo
        fields = ["titulo", "categoria", "problema", "conteudo", "palavras_chave", "links", "versao", "revisar_em",
                  "status"]
        widgets = {"problema": forms.Textarea(attrs={"rows": 3}), "conteudo": forms.Textarea(attrs={"rows": 8}),
                   "links": forms.Textarea(attrs={"rows": 2}), "revisar_em": forms.DateInput(attrs={"type": "date"})}
