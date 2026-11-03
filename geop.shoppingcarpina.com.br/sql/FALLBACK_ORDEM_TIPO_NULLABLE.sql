-- GEOP V7.5.3 - CORREÇÃO OPCIONAL DE COMPATIBILIDADE
-- O hotfix PHP já grava ordem_tipo e entidade simultaneamente.
-- Este SQL é apenas uma proteção adicional para o campo legado.

ALTER TABLE `ordem_solicitacoes_exclusao`
  MODIFY COLUMN `ordem_tipo` VARCHAR(2) NULL DEFAULT NULL;
