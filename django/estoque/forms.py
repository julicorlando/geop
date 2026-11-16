from django import forms

from .models import CustoDocumento, MaterialEstoque, Movimentacao


class MaterialForm(forms.ModelForm):
    class Meta:
        model = MaterialEstoque
        fields = ["nome", "unidade", "estoque_minimo", "custo_unitario", "ativo"]
        help_texts = {"custo_unitario": "Atualizado automaticamente pelo custo médio das entradas."}


class MovimentacaoForm(forms.Form):
    tipo = forms.ChoiceField(label="Tipo", choices=[(Movimentacao.Tipo.ENTRADA, "Entrada"),
                                                   (Movimentacao.Tipo.SAIDA, "Saída manual"),
                                                   (Movimentacao.Tipo.AJUSTE, "Ajuste (saldo contado)")])
    quantidade = forms.DecimalField(label="Quantidade", max_digits=12, decimal_places=2, min_value=0)
    custo_unitario = forms.DecimalField(label="Custo unitário (entradas)", required=False, max_digits=12,
                                        decimal_places=2, min_value=0)
    observacao = forms.CharField(label="Observação", required=False)


class CustoForm(forms.Form):
    tipo = forms.ChoiceField(label="Tipo", choices=CustoDocumento.Tipo.choices)
    valor = forms.DecimalField(label="Valor (R$)", max_digits=12, decimal_places=2, min_value=0)
    fornecedor = forms.CharField(label="Fornecedor", required=False, max_length=180)
    documento = forms.CharField(label="NF / documento", required=False, max_length=100)
    descricao = forms.CharField(label="Descrição", required=False, max_length=255)
    comprovante = forms.FileField(label="Comprovante (foto ou PDF)", required=False,
                                  widget=forms.ClearableFileInput(attrs={"accept": "image/*,application/pdf"}))
