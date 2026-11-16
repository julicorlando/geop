from django.urls import path

from . import views

app_name = "conhecimento"
urlpatterns = [
    path("", views.lista, name="lista"),
    path("novo/", views.form, name="novo"),
    path("<int:pk>/", views.detalhe, name="detalhe"),
    path("<int:pk>/editar/", views.form, name="editar"),
]
