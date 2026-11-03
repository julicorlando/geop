-- GEOP V7.5.2 - CORREÇÃO MANUAL BASEADA NO BANCO REAL DE 22/09/2026
-- Use somente como fallback se a página public/corrigir_exclusao_ordens_v7_5_2.php não puder ser executada.
-- Faça backup antes.

-- Tabelas reais confirmadas:
-- ordens_servico
-- limpeza_ordens
-- ordem_solicitacoes_exclusao

ALTER TABLE `ordem_solicitacoes_exclusao`
  ADD COLUMN IF NOT EXISTS `entidade` ENUM('ORDEM_SERVICO','LIMPEZA_ORDEM') NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `numero_ordem` VARCHAR(40) NULL AFTER `ordem_id`,
  ADD COLUMN IF NOT EXISTS `titulo_ordem` VARCHAR(190) NULL AFTER `numero_ordem`,
  ADD COLUMN IF NOT EXISTS `origem` ENUM('SOLICITACAO_GESTOR','DIRETA_TI') NOT NULL DEFAULT 'SOLICITACAO_GESTOR' AFTER `motivo`,
  ADD COLUMN IF NOT EXISTS `parecer` TEXT NULL AFTER `analisado_em`,
  ADD COLUMN IF NOT EXISTS `executado_por` INT NULL AFTER `parecer`,
  ADD COLUMN IF NOT EXISTS `executado_em` DATETIME NULL AFTER `executado_por`,
  ADD COLUMN IF NOT EXISTS `restaurado_por` INT NULL AFTER `executado_em`,
  ADD COLUMN IF NOT EXISTS `restaurado_em` DATETIME NULL AFTER `restaurado_por`;

UPDATE `ordem_solicitacoes_exclusao`
SET `entidade` = CASE UPPER(TRIM(`ordem_tipo`))
  WHEN 'OS' THEN 'ORDEM_SERVICO'
  WHEN 'OL' THEN 'LIMPEZA_ORDEM'
  ELSE `entidade`
END
WHERE `entidade` IS NULL;

UPDATE `ordem_solicitacoes_exclusao`
SET `parecer` = COALESCE(`parecer`,`observacao_analise`)
WHERE `observacao_analise` IS NOT NULL;

UPDATE `ordem_solicitacoes_exclusao`
SET `executado_por` = COALESCE(`executado_por`,`excluido_por`)
WHERE `excluido_por` IS NOT NULL;

UPDATE `ordem_solicitacoes_exclusao`
SET `executado_em` = COALESCE(`executado_em`,`excluido_em`)
WHERE `excluido_em` IS NOT NULL;

ALTER TABLE `ordens_servico`
  ADD COLUMN IF NOT EXISTS `excluida_em` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `excluida_por` INT NULL,
  ADD COLUMN IF NOT EXISTS `exclusao_solicitacao_id` BIGINT UNSIGNED NULL;

ALTER TABLE `limpeza_ordens`
  ADD COLUMN IF NOT EXISTS `excluida_em` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `excluida_por` INT NULL,
  ADD COLUMN IF NOT EXISTS `exclusao_solicitacao_id` BIGINT UNSIGNED NULL;

-- Índices: se algum CREATE INDEX abaixo disser "Duplicate key name",
-- ele já existe e pode ser ignorado.
CREATE INDEX `idx_ose_entidade_ordem` ON `ordem_solicitacoes_exclusao` (`entidade`,`ordem_id`);
CREATE INDEX `idx_ose_status_data` ON `ordem_solicitacoes_exclusao` (`status`,`criado_em`);
CREATE INDEX `idx_ose_solicitante` ON `ordem_solicitacoes_exclusao` (`solicitado_por`,`criado_em`);
CREATE INDEX `idx_ose_executado_por` ON `ordem_solicitacoes_exclusao` (`executado_por`);
CREATE INDEX `idx_ordens_excluida_em` ON `ordens_servico` (`excluida_em`);
CREATE INDEX `idx_limpeza_ordens_excluida_em` ON `limpeza_ordens` (`excluida_em`);
