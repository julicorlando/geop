from django.urls import path

from . import views

app_name = "relatorios"
urlpatterns = [
    path("", views.ordens, name="ordens"),
    path("mensal/", views.mensal, name="mensal"),
    path("financeiro/", views.financeiro, name="financeiro"),
    path("reincidencias/", views.reincidencias, name="reincidencias"),
    path("token/", views.token, name="token"),
]
