from django.urls import path
from . import views
urlpatterns = [path("", views.dashboard, name="dashboard"), path("ordens/", views.order_list, name="order_list"), path("ordens/nova/", views.order_create, name="order_create"), path("ordens/<int:pk>/", views.order_detail, name="order_detail"), path("ordens/<int:pk>/excluir/", views.delete_request, name="delete_request"), path("exclusoes/<int:pk>/decidir/", views.delete_decide, name="delete_decide"), path("exclusoes/<int:pk>/executar/", views.delete_execute, name="delete_execute")]
