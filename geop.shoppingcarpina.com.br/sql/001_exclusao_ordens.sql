-- GEOP PATCH EXCLUSAO DE ORDENS V1
-- Compatível com MySQL/MariaDB.
-- Execute em HOMOLOGAÇÃO primeiro.

CREATE TABLE IF NOT EXISTS ordem_solicitacoes_exclusao (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ordem_tipo VARCHAR(2) NOT NULL,
    ordem_id BIGINT UNSIGNED NOT NULL,
    solicitado_por BIGINT UNSIGNED NOT NULL,
    motivo TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDENTE',
    analisado_por BIGINT UNSIGNED NULL,
    analisado_em DATETIME NULL,
    observacao_analise TEXT NULL,
    excluido_por BIGINT UNSIGNED NULL,
    excluido_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ose_ordem (ordem_tipo, ordem_id),
    KEY idx_ose_status (status),
    KEY idx_ose_solicitado_por (solicitado_por),
    KEY idx_ose_analisado_por (analisado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ordem_exclusao_auditoria (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    solicitacao_id BIGINT UNSIGNED NULL,
    ordem_tipo VARCHAR(2) NOT NULL,
    ordem_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    acao VARCHAR(50) NOT NULL,
    detalhe TEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_oea_solicitacao (solicitacao_id),
    KEY idx_oea_ordem (ordem_tipo, ordem_id),
    KEY idx_oea_usuario (usuario_id),
    KEY idx_oea_data (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IMPORTANTE:
-- O soft delete exige três colunas na tabela real de ordens.
-- Se a tabela principal do seu GEOP for `ordens`, execute o bloco abaixo.
-- Se tiver outro nome, ajuste antes de executar.

-- ALTER TABLE ordens ADD COLUMN deleted_at DATETIME NULL;
-- ALTER TABLE ordens ADD COLUMN deleted_by BIGINT UNSIGNED NULL;
-- ALTER TABLE ordens ADD COLUMN delete_request_id BIGINT UNSIGNED NULL;
-- CREATE INDEX idx_ordens_deleted_at ON ordens (deleted_at);

-- Caso as colunas já existam, NÃO execute novamente os ALTER TABLE.
