from django.contrib import messages
from django.contrib.auth.decorators import login_required
from django.core.exceptions import PermissionDenied
from django.db import transaction
from django.db.models import Q, Count
from django.shortcuts import get_object_or_404, redirect, render
from django.utils import timezone
from .models import ServiceOrder, Location, Category, OrderComment, AuditEvent, DeletionRequest

def can_manage(user): return user.is_ti or user.role in {"GESTOR", "DIRETORIA", "COORDENADOR"}

@login_required
def dashboard(request):
    qs = ServiceOrder.objects.filter(deleted_at__isnull=True)
    if not can_manage(request.user): qs = qs.filter(Q(agent=request.user) | Q(requester=request.user))
    data = {"orders": qs.select_related("location", "category", "agent")[:12], "open_count": qs.exclude(status__in=["ENCERRADA", "CANCELADA", "REPROVADA"]).count(), "overdue_count": qs.filter(due_at__lt=timezone.now()).exclude(status__in=["ENCERRADA", "CANCELADA"]).count(), "counts": qs.values("status").annotate(total=Count("id"))}
    return render(request, "orders/dashboard.html", data)

@login_required
def order_list(request):
    qs = ServiceOrder.objects.filter(deleted_at__isnull=True).select_related("location", "category", "requester", "agent")
    if not can_manage(request.user): qs = qs.filter(Q(agent=request.user) | Q(requester=request.user))
    if request.GET.get("status"): qs = qs.filter(status=request.GET["status"])
    return render(request, "orders/list.html", {"orders": qs[:200], "statuses": ServiceOrder.STATUSES})

@login_required
def order_create(request):
    error = ""
    if request.method == "POST":
        try:
            order = ServiceOrder.objects.create(title=request.POST["title"].strip(), description=request.POST["description"].strip(), location_id=int(request.POST["location"]), category_id=int(request.POST["category"]), priority=request.POST.get("priority", "NORMAL"), requester=request.user)
            AuditEvent.objects.create(actor=request.user, action="CRIAR_OS", entity="ServiceOrder", entity_id=order.pk, details={"number": order.number}, ip=request.META.get("REMOTE_ADDR"))
            return redirect("order_detail", pk=order.pk)
        except (KeyError, ValueError, Location.DoesNotExist, Category.DoesNotExist): error = "Preencha os campos obrigatórios com valores válidos."
    return render(request, "orders/form.html", {"locations": Location.objects.filter(active=True), "categories": Category.objects.filter(active=True), "priorities": ServiceOrder.PRIORITIES, "error": error})

@login_required
def order_detail(request, pk):
    order = get_object_or_404(ServiceOrder.objects.select_related("requester", "agent", "location", "category"), pk=pk, deleted_at__isnull=True)
    if not can_manage(request.user) and request.user not in (order.requester, order.agent): raise PermissionDenied
    if request.method == "POST" and "comment" in request.POST:
        body = request.POST.get("body", "").strip()
        if body:
            OrderComment.objects.create(order=order, author=request.user, body=body)
            AuditEvent.objects.create(actor=request.user, action="COMENTAR_OS", entity="ServiceOrder", entity_id=order.pk, ip=request.META.get("REMOTE_ADDR"))
        return redirect("order_detail", pk=pk)
    if request.method == "POST" and "status" in request.POST:
        if request.user != order.agent and not can_manage(request.user): raise PermissionDenied
        new = request.POST["status"]
        if new not in dict(ServiceOrder.STATUSES): raise PermissionDenied
        agent_transitions = {"EM_ANDAMENTO", "PAUSADA", "AGUARDANDO_MATERIAL", "CONCLUIDA_PELO_AGENTE"}
        if request.user == order.agent and not can_manage(request.user) and new not in agent_transitions: raise PermissionDenied
        old = order.status; order.status = new
        if new == "EM_ANDAMENTO" and not order.started_at: order.started_at = timezone.now()
        if new == "CONCLUIDA_PELO_AGENTE": order.completed_at = timezone.now()
        if new == "ENCERRADA": order.closed_at = timezone.now()
        order.save()
        AuditEvent.objects.create(actor=request.user, action="ALTERAR_STATUS", entity="ServiceOrder", entity_id=order.pk, details={"before": old, "after": new}, ip=request.META.get("REMOTE_ADDR"))
        return redirect("order_detail", pk=pk)
    is_manager = can_manage(request.user)
    agent_transitions = {"EM_ANDAMENTO", "PAUSADA", "AGUARDANDO_MATERIAL", "CONCLUIDA_PELO_AGENTE"}
    statuses = ServiceOrder.STATUSES if is_manager else [(key, label) for key, label in ServiceOrder.STATUSES if key in agent_transitions]
    return render(request, "orders/detail.html", {"order": order, "manager": is_manager, "statuses": statuses})

@login_required
def delete_request(request, pk):
    if request.method != "POST": raise PermissionDenied
    order = get_object_or_404(ServiceOrder, pk=pk, deleted_at__isnull=True)
    reason = request.POST.get("reason", "").strip()
    if not reason: messages.error(request, "Informe o motivo da solicitação."); return redirect("order_detail", pk=pk)
    if request.user.is_ti:
        order.deleted_at = timezone.now(); order.save(update_fields=["deleted_at"])
        AuditEvent.objects.create(actor=request.user, action="EXCLUIR_OS", entity="ServiceOrder", entity_id=pk, details={"reason": reason})
    elif request.user.role in {"GESTOR", "DIRETORIA"}:
        DeletionRequest.objects.create(order=order, requested_by=request.user, reason=reason)
        AuditEvent.objects.create(actor=request.user, action="SOLICITAR_EXCLUSAO", entity="ServiceOrder", entity_id=pk, details={"reason": reason})
    else: raise PermissionDenied
    return redirect("order_list")

@login_required
def delete_decide(request, pk):
    if request.method != "POST" or not can_manage(request.user): raise PermissionDenied
    with transaction.atomic():
        item = get_object_or_404(DeletionRequest.objects.select_for_update(), pk=pk, state="PENDENTE")
        if item.requested_by_id == request.user.id: raise PermissionDenied("O solicitante não pode decidir a própria solicitação.")
        state = "APROVADA" if request.POST.get("decision") == "approve" else "REPROVADA"
        item.state = state; item.decided_by = request.user; item.decided_at = timezone.now(); item.note = request.POST.get("note", "")[:2000]; item.save()
        AuditEvent.objects.create(actor=request.user, action="DECIDIR_EXCLUSAO", entity="DeletionRequest", entity_id=pk, details={"state": state})
    return redirect("order_detail", pk=item.order_id)

@login_required
def delete_execute(request, pk):
    if request.method != "POST" or not request.user.is_ti: raise PermissionDenied
    with transaction.atomic():
        item = get_object_or_404(DeletionRequest.objects.select_for_update(), pk=pk, state="APROVADA")
        item.state = "EXECUTADA"; item.decided_by = request.user; item.decided_at = timezone.now(); item.save()
        order = item.order; order.deleted_at = timezone.now(); order.save(update_fields=["deleted_at"])
        AuditEvent.objects.create(actor=request.user, action="EXECUTAR_EXCLUSAO", entity="ServiceOrder", entity_id=order.pk, details={"request": pk})
    return redirect("order_list")
