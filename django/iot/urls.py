from django.urls import path

from . import views

app_name = "iot"
urlpatterns = [
    path("", views.painel, name="painel"),
    path("dispositivo/<int:pk>/", views.dispositivo, name="dispositivo"),
    path("api/leituras/", views.api_leituras, name="api_leituras"),
]
