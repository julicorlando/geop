-- GEOP V7.2 - Liberações por período
-- Versão cPanel / MariaDB 10.11+
-- NÃO usa information_schema, PREPARE ou privilégios administrativos.
-- Pode ser executada novamente sem erro caso a coluna já exista.

SET NAMES utf8mb4;

ALTER TABLE `cftv_liberacoes`
    ADD COLUMN IF NOT EXISTS `data_fim` DATE NULL AFTER `data_acesso`;

-- Liberações antigas continuam válidas como liberação de um único dia.
UPDATE `cftv_liberacoes`
SET `data_fim` = `data_acesso`
WHERE `data_fim` IS NULL;

SELECT 'GEOP V7.2 - liberações por período instalada com sucesso' AS resultado;
