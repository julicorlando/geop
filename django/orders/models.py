import secrets
from django.contrib.auth.models import AbstractUser
from django.db import models
from django.utils import timezone

class User(AbstractUser):
    ROLE_CHOICES = [(x, x.replace("_", " ").title()) for x in ("TI", "DIRETORIA", "GESTOR", "COORDENADOR", "AGENTE")]
    role = models.CharField(max_length=32, choices=ROLE_CHOICES, default="AGENTE", db_index=True)
    phone = models.CharField(max_length=30, blank=True)
    @property
    def is_ti(self): return self.is_superuser or self.role == "TI"

class Location(models.Model):
    name = models.CharField(max_length=160, unique=True)
    active = models.BooleanField(default=True)
    def __str__(self): return self.name

class Category(models.Model):
    name = models.CharField(max_length=120, unique=True)
    active = models.BooleanField(default=True)
    def __str__(self): return self.name

class ServiceOrder(models.Model):
    STATUSES = [(x, x.replace("_", " ").title()) for x in ("ABERTA", "ATRIBUIDA", "EM_ANDAMENTO", "PAUSADA", "AGUARDANDO_MATERIAL", "CONCLUIDA_PELO_AGENTE", "ENCERRADA", "REPROVADA", "CANCELADA")]
    PRIORITIES = [(x, x.title()) for x in ("BAIXA", "NORMAL", "ALTA", "CRITICA")]
    number = models.CharField(max_length=32, unique=True, blank=True)
    title = models.CharField(max_length=220)
    description = models.TextField()
    location = models.ForeignKey(Location, on_delete=models.PROTECT)
    category = models.ForeignKey(Category, on_delete=models.PROTECT)
    priority = models.CharField(max_length=16, choices=PRIORITIES, default="NORMAL", db_index=True)
    status = models.CharField(max_length=32, choices=STATUSES, default="ABERTA", db_index=True)
    requester = models.ForeignKey(User, on_delete=models.PROTECT, related_name="requested_orders")
    manager = models.ForeignKey(User, null=True, blank=True, on_delete=models.PROTECT, related_name="managed_orders")
    agent = models.ForeignKey(User, null=True, blank=True, on_delete=models.PROTECT, related_name="assigned_orders")
    due_at = models.DateTimeField(null=True, blank=True, db_index=True)
    created_at = models.DateTimeField(default=timezone.now, db_index=True)
    started_at = models.DateTimeField(null=True, blank=True)
    completed_at = models.DateTimeField(null=True, blank=True)
    closed_at = models.DateTimeField(null=True, blank=True)
    deleted_at = models.DateTimeField(null=True, blank=True, db_index=True)
    def save(self, *args, **kwargs):
        if not self.number: self.number = f"OS-{timezone.localdate():%Y}-{secrets.token_hex(4).upper()}"
        super().save(*args, **kwargs)
    def __str__(self): return f"{self.number} — {self.title}"

class OrderComment(models.Model):
    order = models.ForeignKey(ServiceOrder, on_delete=models.CASCADE, related_name="comments")
    author = models.ForeignKey(User, on_delete=models.PROTECT)
    body = models.TextField()
    created_at = models.DateTimeField(default=timezone.now)

class AuditEvent(models.Model):
    actor = models.ForeignKey(User, null=True, on_delete=models.SET_NULL)
    action = models.CharField(max_length=80)
    entity = models.CharField(max_length=80)
    entity_id = models.PositiveBigIntegerField(null=True)
    details = models.JSONField(default=dict, blank=True)
    ip = models.GenericIPAddressField(null=True, blank=True)
    created_at = models.DateTimeField(default=timezone.now, db_index=True)

class DeletionRequest(models.Model):
    STATES = [(x, x.title()) for x in ("PENDENTE", "APROVADA", "REPROVADA", "EXECUTADA", "CANCELADA")]
    order = models.ForeignKey(ServiceOrder, on_delete=models.PROTECT, related_name="deletion_requests")
    requested_by = models.ForeignKey(User, on_delete=models.PROTECT, related_name="deletion_requests")
    decided_by = models.ForeignKey(User, null=True, blank=True, on_delete=models.PROTECT, related_name="decided_deletions")
    reason = models.TextField()
    state = models.CharField(max_length=16, choices=STATES, default="PENDENTE", db_index=True)
    note = models.TextField(blank=True)
    created_at = models.DateTimeField(default=timezone.now)
    decided_at = models.DateTimeField(null=True, blank=True)
