from datetime import timedelta

from django.contrib import messages
from django.core.exceptions import PermissionDenied
from django.db.models import Avg, Count, Q
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone

from core.models import Configuracao, Local
from core.permissions import permissao_requerida

from . import provedor
from .heuristica import assistir
from .models import Conversa, Execucao, Mensagem


@permissao_requerida("ia.usar")
def chat(request, pk=None):
    user = request.user
    conversa = get_object_or_404(Conversa, pk=pk, usuario=user) if pk else None
    if request.method == "POST":
        if request.POST.get("acao") == "apagar" and conversa:
            conversa.delete()
            return redirect("ia:chat")
        pergunta = request.POST.get("pergunta", "").strip()[:2000]
        if pergunta:
            if conversa is None:
                conversa = Conversa.objects.create(usuario=user, titulo=pergunta[:80])
            historico = list(conversa.mensagens.all())
            Mensagem.objects.create(conversa=conversa, papel="user", conteudo=pergunta)
            try:
                resposta, fontes = provedor.responder(user, conversa, pergunta, historico)
            except provedor.ErroIA as exc:
                resposta, fontes = f"⚠️ {exc}", []
            Mensagem.objects.create(conversa=conversa, papel="assistant", conteudo=resposta, fontes=fontes)
            Conversa.objects.filter(pk=conversa.pk).update(atualizado_em=timezone.now())
        return redirect("ia:conversa", pk=conversa.pk) if conversa else redirect("ia:chat")
    ok, motivo = provedor.pronto()
    return render(request, "ia/chat.html", {
        "conversa": conversa, "mensagens": conversa.mensagens.all() if conversa else [],
        "conversas": Conversa.objects.filter(usuario=user)[:30], "pronto": ok, "motivo": motivo,
        "restantes": max(0, provedor.configuracao()["limite_diario"] - provedor.usos_hoje(user)),
        "sugestoes": ["Quais ordens estão atrasadas?", "Resumo da semana: indicadores dos últimos 7 dias",
                      "Que materiais estão abaixo do mínimo?", "Quais preventivas vencem nos próximos 15 dias?",
                      "Onde há mais reincidência nos últimos 90 dias?"]})


@permissao_requerida("ia.usar", "os.criar")
def assistente(request):
    """Sugestões locais (sem serviço externo) para abrir uma ordem."""
    resultado, dados = None, {}
    if request.method == "POST":
        dados = {"titulo": request.POST.get("titulo", ""), "descricao": request.POST.get("descricao", ""),
                 "local": request.POST.get("local", "")}
        resultado = assistir(dados["titulo"], dados["descricao"], Local.objects.filter(pk=dados["local"] or None).first())
        Execucao.objects.create(usuario=request.user, tipo="ASSISTENCIA_ABERTURA", provedor="HEURISTICA_LOCAL")
    return render(request, "ia/assistente.html", {"resultado": resultado, "dados": dados,
                                                  "locais": Local.objects.filter(ativo=True)})


def configuracao(request):
    if not request.user.is_authenticated or not request.user.is_ti:
        raise PermissionDenied
    if request.method == "POST":
        if request.POST.get("acao") == "testar":
            try:
                messages.success(request, f"Conexão OK. Resposta do modelo: {provedor.testar()!r}")
            except provedor.ErroIA as exc:
                messages.error(request, str(exc))
        else:
            for chave in ("ia_provedor", "ia_modelo", "ia_base_url"):
                Configuracao.definir(chave, request.POST.get(chave, "").strip(), request.user)
            Configuracao.definir("ia_habilitada", "1" if request.POST.get("ia_habilitada") else "0", request.user)
            Configuracao.definir("ia_limite_diario", max(1, int(request.POST.get("ia_limite_diario") or 40)), request.user)
            messages.success(request, "Configuração salva.")
        return redirect("ia:configuracao")
    ultimos30 = Execucao.objects.filter(criado_em__gte=timezone.now() - timedelta(days=30))
    return render(request, "ia/configuracao.html", {
        "cfg": provedor.configuracao(), "provedores": provedor.PADROES,
        "estat": ultimos30.aggregate(total=Count("id"), erros=Count("id", filter=Q(sucesso=False)), media=Avg("duracao_ms")),
        "por_usuario": ultimos30.values("usuario__nome").annotate(n=Count("id")).order_by("-n")[:10],
        "erros": ultimos30.filter(sucesso=False)[:10]})
