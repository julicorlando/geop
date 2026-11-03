from django.contrib import admin
from django.contrib.auth.admin import UserAdmin
from .models import User, Location, Category, ServiceOrder, OrderComment, AuditEvent, DeletionRequest
@admin.register(User)
class GEOPUserAdmin(UserAdmin):
    fieldsets = UserAdmin.fieldsets + (("GEOP", {"fields": ("role", "phone")}),)
    list_display = UserAdmin.list_display + ("role",)
@admin.register(ServiceOrder)
class ServiceOrderAdmin(admin.ModelAdmin):
    list_display = ("number", "title", "status", "priority", "location", "agent", "due_at")
    list_filter = ("status", "priority", "location", "category")
    search_fields = ("number", "title", "description")
admin.site.register([Location, Category, OrderComment, AuditEvent, DeletionRequest])
