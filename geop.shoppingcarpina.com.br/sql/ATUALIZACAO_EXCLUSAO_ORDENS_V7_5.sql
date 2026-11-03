-- GEOP V7.5 - Fluxo de exclusão de ordens
-- Preferência: use public/atualizar_exclusao_ordens_v7_5.php.
-- Este arquivo é uma referência/fallback para administração manual.

CREATE TABLE IF NOT EXISTS ordem_solicitacoes_exclusao (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    entidade ENUM('ORDEM_SERVICO','LIMPEZA_ORDEM') NOT NULL,
    ordem_id BIGINT UNSIGNED NOT NULL,
    numero_ordem VARCHAR(40) NULL,
    titulo_ordem VARCHAR(190) NULL,
    solicitado_por INT NOT NULL,
    motivo TEXT NOT NULL,
    origem ENUM('SOLICITACAO_GESTOR','DIRETA_TI') NOT NULL DEFAULT 'SOLICITACAO_GESTOR',
    status ENUM('PENDENTE','APROVADA','REPROVADA','EXECUTADA','CANCELADA') NOT NULL DEFAULT 'PENDENTE',
    analisado_por INT NULL,
    analisado_em DATETIME NULL,
    parecer TEXT NULL,
    executado_por INT NULL,
    executado_em DATETIME NULL,
    restaurado_por INT NULL,
    restaurado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ose_entidade_ordem (entidade,ordem_id),
    KEY idx_ose_status_data (status,criado_em),
    KEY idx_ose_solicitante (solicitado_por,criado_em),
    KEY idx_ose_analisado_por (analisado_por),
    KEY idx_ose_executado_por (executado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Execute os ALTER apenas se as colunas ainda não existirem:
-- ALTER TABLE ordens_servico ADD COLUMN excluida_em DATETIME NULL;
-- ALTER TABLE ordens_servico ADD COLUMN excluida_por INT NULL;
-- ALTER TABLE ordens_servico ADD COLUMN exclusao_solicitacao_id BIGINT UNSIGNED NULL;
-- CREATE INDEX idx_ordens_excluida_em ON ordens_servico(excluida_em);
--
-- ALTER TABLE limpeza_ordens ADD COLUMN excluida_em DATETIME NULL;
-- ALTER TABLE limpeza_ordens ADD COLUMN excluida_por INT NULL;
-- ALTER TABLE limpeza_ordens ADD COLUMN exclusao_solicitacao_id BIGINT UNSIGNED NULL;
-- CREATE INDEX idx_limpeza_ordens_excluida_em ON limpeza_ordens(excluida_em);
