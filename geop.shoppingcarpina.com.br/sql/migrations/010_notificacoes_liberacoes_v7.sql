-- GEOP V7.1 - SQL simplificado para cPanel/MariaDB
-- Não consulta information_schema.
-- Recomendado: use public/atualizar_notificacoes_v7.php, que verifica antes de alterar.

ALTER TABLE cftv_solicitacoes_acesso
    ADD COLUMN IF NOT EXISTS email VARCHAR(190) NULL AFTER telefone;

ALTER TABLE cftv_liberacoes
    ADD COLUMN IF NOT EXISTS email VARCHAR(190) NULL AFTER cpf;

ALTER TABLE cftv_solicitacoes_acesso
    ADD INDEX IF NOT EXISTS idx_solicitacao_email (email);

ALTER TABLE cftv_liberacoes
    ADD INDEX IF NOT EXISTS idx_email (email);

SELECT DATABASE() AS banco_atual, 'GEOP V7.1 instalado' AS resultado;
