from django.db import migrations


def sincronizar(apps, schema_editor):
    from accounts.catalogo import sincronizar as sinc

    sinc(apps.get_model("accounts", "Permissao"), apps.get_model("accounts", "Perfil"))


class Migration(migrations.Migration):
    dependencies = [("accounts", "0003_usuario_totp_ativo_usuario_totp_segredo_and_more")]
    operations = [migrations.RunPython(sincronizar, migrations.RunPython.noop)]
