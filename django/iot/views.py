import json

from django.contrib import messages
from django.core.paginator import Paginator
from django.http import JsonResponse
from django.shortcuts import get_object_or_404, redirect, render
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_POST

from core.models import Categoria, Local
from core.permissions import permissao_requerida
from core.services import auditar

from . import services
from .models import AtivoRede, Dispositivo, hash_chave


@csrf_exempt
@require_POST
def api_leituras(request):
    """POST JSON {"identificador", "tipo", "valor", "bateria"} com cabeçalho X-API-Key (ou Authorization: Bearer)."""
    chave = request.headers.get("X-API-Key") or request.headers.get("Authorization", "").removeprefix("Bearer ").strip()
    if not chave:
        return JsonResponse({"ok": False, "erro": "Chave ausente."}, status=401)
    dispositivo = Dispositivo.objects.filter(api_key_hash=hash_chave(chave), ativo=True).first()
    if not dispositivo:
        return JsonResponse({"ok": False, "erro": "Chave inválida."}, status=403)
    try:
        dados = json.loads(request.body or b"{}")
    except json.JSONDecodeError:
        return JsonResponse({"ok": False, "erro": "JSON inválido."}, status=400)
    leituras = dados if isinstance(dados, list) else [dados]
    if len(leituras) > 100:
        return JsonResponse({"ok": False, "erro": "Máximo de 100 leituras por envio."}, status=400)
    if any(str(l.get("identificador", dispositivo.identificador)) != dispositivo.identificador for l in leituras):
        return JsonResponse({"ok": False, "erro": "Identificador não corresponde à chave."}, status=403)
    resultado = [services.receber_leitura(dispositivo, l) for l in leituras]
    return JsonResponse({"ok": True, "recebidas": len(resultado), "alertas": sum(1 for l in resultado if l.alerta)})


@permissao_requerida("iot.gerenciar")
def painel(request):
    chave_nova = None
    if request.method == "POST":
        acao = request.POST.get("acao")
        local = Local.objects.filter(pk=request.POST.get("local") or None).first()
        if acao == "dispositivo":
            ident = request.POST.get("identificador", "").strip()[:100]
            if ident and not Dispositivo.objects.filter(identificador=ident).exists():
                d = Dispositivo(identificador=ident, tipo=request.POST.get("tipo", "")[:80] or "sensor", local=local,
                                categoria_os=Categoria.objects.filter(pk=request.POST.get("categoria") or None).first(),
                                limite_min=request.POST.get("limite_min") or None,
                                limite_max=request.POST.get("limite_max") or None,
                                offline_minutos=int(request.POST.get("offline_minutos") or 60))
                chave_nova = (ident, d.nova_chave())
                d.save()
                auditar(request, "iot", d.pk, "CRIAR")
            else:
                messages.error(request, "Identificador vazio ou já cadastrado.")
        elif acao == "nova_chave":
            d = get_object_or_404(Dispositivo, pk=request.POST.get("id"))
            chave_nova = (d.identificador, d.nova_chave())
            d.save(update_fields=["api_key_hash"])
            auditar(request, "iot", d.pk, "NOVA_CHAVE")
        elif acao == "alternar":
            d = get_object_or_404(Dispositivo, pk=request.POST.get("id"))
            d.ativo = not d.ativo
            d.save(update_fields=["ativo"])
        elif acao == "ativo_rede":
            ip = request.POST.get("endereco_ip", "").strip()[:255]
            if ip and request.POST.get("tipo") in dict(AtivoRede.TIPOS):
                AtivoRede.objects.update_or_create(endereco_ip=ip, defaults={
                    "nome": request.POST.get("nome", ip)[:160], "tipo": request.POST["tipo"],
                    "porta": int(request.POST.get("porta") or 80), "local": local,
                    "criticidade": request.POST.get("criticidade", "MEDIA")})
        elif acao == "verificar_rede":
            n = services.verificar_rede()
            messages.success(request, f"Verificação concluída. {n} ativo(s) em falha.")
        if not chave_nova:
            return redirect("iot:painel")
    return render(request, "iot/painel.html", {
        "dispositivos": Dispositivo.objects.select_related("local", "ordem_alerta"),
        "ativos": AtivoRede.objects.select_related("local", "ordem_alerta"), "chave_nova": chave_nova,
        "locais": Local.objects.filter(ativo=True), "categorias": Categoria.objects.filter(ativo=True),
        "tipos_rede": AtivoRede.TIPOS, "api_url": request.build_absolute_uri("/iot/api/leituras/")})


@permissao_requerida("iot.gerenciar")
def dispositivo(request, pk):
    d = get_object_or_404(Dispositivo, pk=pk)
    return render(request, "iot/dispositivo.html", {"d": d, "leituras": Paginator(d.leituras.all(), 50).get_page(
        request.GET.get("pagina"))})
