-- GEOP V8.0 — FALLBACK MANUAL
-- Preferencialmente use /public/atualizar_geop_v8.php, que registra migrations e cria backup.
-- MariaDB 10.11+.

CREATE TABLE IF NOT EXISTS schema_migrations (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 versao VARCHAR(40) NOT NULL UNIQUE,
 descricao VARCHAR(190) NOT NULL,
 checksum CHAR(64) NULL,
 aplicada_por INT NULL,
 aplicada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 duracao_ms INT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sistema_configuracoes (
 chave VARCHAR(120) NOT NULL PRIMARY KEY, valor LONGTEXT NULL, tipo VARCHAR(30) NOT NULL DEFAULT 'STRING',
 descricao VARCHAR(255) NULL, atualizado_por INT NULL,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sistema_backups (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, tipo VARCHAR(30) NOT NULL DEFAULT 'BANCO',
 arquivo VARCHAR(255) NOT NULL, tamanho_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0, sha256 CHAR(64) NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'CONCLUIDO', observacao TEXT NULL, criado_por INT NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sistema_execucoes (
 chave VARCHAR(120) NOT NULL PRIMARY KEY, ultima_execucao DATETIME NULL,
 ultimo_status VARCHAR(30) NULL, detalhe VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compras_solicitacoes (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NULL, solicitante_id INT NOT NULL,
 aprovador_id INT NULL, status VARCHAR(30) NOT NULL DEFAULT 'RASCUNHO', justificativa TEXT NULL,
 fornecedor VARCHAR(180) NULL, numero_documento VARCHAR(100) NULL,
 valor_previsto DECIMAL(12,2) NOT NULL DEFAULT 0, valor_final DECIMAL(12,2) NOT NULL DEFAULT 0,
 solicitado_em DATETIME NULL, decidido_em DATETIME NULL, comprado_em DATETIME NULL, recebido_em DATETIME NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_compra_status(status,criado_em), KEY idx_compra_os(ordem_servico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compras_itens (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, compra_id BIGINT UNSIGNED NOT NULL, material_id INT NULL,
 descricao VARCHAR(190) NOT NULL, quantidade DECIMAL(12,2) NOT NULL DEFAULT 1, unidade VARCHAR(30) NULL,
 custo_unitario_previsto DECIMAL(12,2) NOT NULL DEFAULT 0, custo_unitario_final DECIMAL(12,2) NULL,
 KEY idx_compra_item_compra(compra_id), KEY idx_compra_item_material(material_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_custos_documentos (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL,
 tipo VARCHAR(40) NOT NULL DEFAULT 'OUTRO', fornecedor VARCHAR(180) NULL, documento VARCHAR(100) NULL,
 descricao VARCHAR(255) NULL, valor DECIMAL(12,2) NOT NULL DEFAULT 0, arquivo_url VARCHAR(255) NULL,
 registrado_por INT NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_os_custos_os(ordem_servico_id,criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checklist_modelos (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, categoria_id INT NOT NULL, nome VARCHAR(160) NOT NULL,
 ativo TINYINT(1) NOT NULL DEFAULT 1, criado_por INT NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_checklist_modelo_categoria(categoria_id,ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checklist_modelo_itens (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, modelo_id BIGINT UNSIGNED NOT NULL,
 descricao VARCHAR(255) NOT NULL, obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
 exige_foto TINYINT(1) NOT NULL DEFAULT 0, ordem SMALLINT NOT NULL DEFAULT 0,
 KEY idx_checklist_modelo_itens(modelo_id,ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_checklist_itens (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, origem_item_id BIGINT UNSIGNED NULL,
 descricao VARCHAR(255) NOT NULL, obrigatorio TINYINT(1) NOT NULL DEFAULT 0, exige_foto TINYINT(1) NOT NULL DEFAULT 0,
 concluido TINYINT(1) NOT NULL DEFAULT 0, observacao VARCHAR(500) NULL, concluido_por INT NULL, concluido_em DATETIME NULL,
 ordem SMALLINT NOT NULL DEFAULT 0, KEY idx_os_checklist_os(ordem_servico_id,ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_reabertura_solicitacoes (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, solicitado_por INT NOT NULL,
 motivo TEXT NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE', analisado_por INT NULL, analisado_em DATETIME NULL,
 parecer TEXT NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_reabertura_status(status,criado_em), KEY idx_reabertura_os(ordem_servico_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seguranca_login_tentativas (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, email VARCHAR(190) NULL, usuario_id INT NULL, ip VARCHAR(45) NULL,
 sucesso TINYINT(1) NOT NULL DEFAULT 0, motivo VARCHAR(100) NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_login_tentativa_email(email,criado_em), KEY idx_login_tentativa_ip(ip,criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seguranca_eventos (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, usuario_id INT NULL, tipo VARCHAR(80) NOT NULL,
 detalhe TEXT NULL, ip VARCHAR(45) NULL, user_agent VARCHAR(500) NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_seg_evento_usuario(usuario_id,criado_em), KEY idx_seg_evento_tipo(tipo,criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ia_analises (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NULL, usuario_id INT NULL,
 tipo VARCHAR(60) NOT NULL, entrada_hash CHAR(64) NULL, resultado_json LONGTEXT NULL,
 origem VARCHAR(40) NOT NULL DEFAULT 'HEURISTICA_LOCAL', criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_ia_analise_os(ordem_servico_id,criado_em), KEY idx_ia_analise_tipo(tipo,criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS arquivo_integridade (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, tabela_origem VARCHAR(80) NOT NULL,
 registro_id BIGINT UNSIGNED NOT NULL, arquivo_url VARCHAR(255) NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
 tamanho_bytes BIGINT UNSIGNED NULL, sha256 CHAR(64) NULL, verificado_em DATETIME NULL,
 UNIQUE KEY uq_arquivo_integridade(tabela_origem,registro_id), KEY idx_arquivo_status(status,verificado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE ordens_servico ADD COLUMN IF NOT EXISTS qualidade_percentual TINYINT UNSIGNED NULL;
ALTER TABLE ordens_servico ADD COLUMN IF NOT EXISTS qualidade_atualizada_em DATETIME NULL;
ALTER TABLE ordens_servico ADD COLUMN IF NOT EXISTS custo_mao_obra DECIMAL(12,2) NOT NULL DEFAULT 0.00;
ALTER TABLE ordens_servico ADD COLUMN IF NOT EXISTS custo_terceiros DECIMAL(12,2) NOT NULL DEFAULT 0.00;
ALTER TABLE ordens_servico ADD COLUMN IF NOT EXISTS custo_total_final DECIMAL(12,2) NOT NULL DEFAULT 0.00;
ALTER TABLE ordens_servico ADD COLUMN IF NOT EXISTS duplicidade_ignorada TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE ordens_servico ADD COLUMN IF NOT EXISTS duplicidade_referencia_id INT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS totp_secret_enc TEXT NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS totp_enabled TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS totp_confirmed_em DATETIME NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ultimo_login_em DATETIME NULL;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS ultimo_login_ip VARCHAR(45) NULL;
