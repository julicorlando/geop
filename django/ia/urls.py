from django.urls import path

from . import views

app_name = "ia"
urlpatterns = [
    path("", views.chat, name="chat"),
    path("<int:pk>/", views.chat, name="conversa"),
    path("assistente/", views.assistente, name="assistente"),
    path("configuracao/", views.configuracao, name="configuracao"),
]
