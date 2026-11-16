from django.db import migrations


def criar_padroes(apps, schema_editor):
    from accounts.catalogo import sincronizar

    sincronizar(apps.get_model("accounts", "Permissao"), apps.get_model("accounts", "Perfil"))


class Migration(migrations.Migration):
    dependencies = [("accounts", "0001_initial")]
    operations = [migrations.RunPython(criar_padroes, migrations.RunPython.noop)]
