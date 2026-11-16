class SomenteLeituraExternaRouter:
    """Garante que os bancos `legacy` (PHP) e `patrimonio` nunca recebam escrita ou migrações."""

    EXTERNOS = {"legacy", "patrimonio"}

    def db_for_write(self, model, **hints):
        return "default"

    def allow_migrate(self, db, app_label, model_name=None, **hints):
        return db not in self.EXTERNOS
