from django.urls import path

from . import views

app_name = "limpeza"
urlpatterns = [
    path("organograma/", views.organograma, name="organograma"),
    path("organograma/novo/", views.organograma_form, name="organograma_novo"),
    path("organograma/<int:pk>/", views.organograma_form, name="organograma_editar"),
    path("plano/", views.plano, name="plano"),
    path("plano/grupo/novo/", views.grupo_form, name="grupo_novo"),
    path("plano/grupo/<int:pk>/", views.grupo_form, name="grupo_editar"),
    path("plano/tarefa/nova/", views.tarefa_form, name="tarefa_nova"),
    path("plano/tarefa/<int:pk>/", views.tarefa_form, name="tarefa_editar"),
]
