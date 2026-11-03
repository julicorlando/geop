-- GEOP V7.6 — CUSTO DE MATERIAL NA FINALIZAÇÃO DA OS
-- Fallback manual. Preferencialmente use public/atualizar_custo_finalizacao_v7_6.php

ALTER TABLE `ordens_servico`
  ADD COLUMN IF NOT EXISTS `custo_materiais` DECIMAL(12,2) NOT NULL DEFAULT 0.00;

CREATE TABLE IF NOT EXISTS `os_finalizacao_custos` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ordem_servico_id` INT NOT NULL,
  `houve_custo` TINYINT(1) NOT NULL DEFAULT 0,
  `custo_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `score_indicios` SMALLINT NOT NULL DEFAULT 0,
  `nivel_indicios` VARCHAR(20) NOT NULL DEFAULT 'BAIXO',
  `evidencias_json` LONGTEXT NULL,
  `justificativa_sem_custo` TEXT NULL,
  `confirmado_por` INT NOT NULL,
  `confirmado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_os_finalizacao_custos_os` (`ordem_servico_id`),
  KEY `idx_os_finalizacao_custos_usuario` (`confirmado_por`),
  KEY `idx_os_finalizacao_custos_data` (`confirmado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `os_finalizacao_custo_itens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `finalizacao_id` BIGINT UNSIGNED NOT NULL,
  `material_os_id` INT NULL,
  `estoque_material_id` INT NULL,
  `nome_material` VARCHAR(180) NOT NULL,
  `quantidade` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `unidade` VARCHAR(30) NULL,
  `custo_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `custo_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `baixa_estoque` TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ofci_finalizacao` (`finalizacao_id`),
  KEY `idx_ofci_material_os` (`material_os_id`),
  KEY `idx_ofci_estoque_material` (`estoque_material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
