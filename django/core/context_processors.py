from django.conf import settings


def geop(request):
    dados = {"GEOP_NOME_UNIDADE": settings.GEOP_NOME_UNIDADE, "GEOP_LADO_MAX": settings.GEOP_IMAGEM_LADO_MAX}
    user = getattr(request, "user", None)
    if user is not None and user.is_authenticated:
        dados["notificacoes_nao_lidas"] = user.notificacoes.filter(lida_em__isnull=True).count()
    return dados
