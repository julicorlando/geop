-- GEOP V10 - Ordens unificadas OS/OL
-- Execute no banco do GEOP após backup.

ALTER TABLE ordens_servico ADD COLUMN tipo_ordem VARCHAR(2) NOT NULL DEFAULT 'OS' AFTER numero_os;
ALTER TABLE ordens_servico ADD INDEX idx_ordens_tipo_status (tipo_ordem,status);

CREATE TABLE IF NOT EXISTS ol_numero_sequences (
  ano INT NOT NULL PRIMARY KEY,
  ultimo_numero INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ordem_checklist_itens (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ordem_servico_id INT NOT NULL,
  tarefa_plano_id BIGINT UNSIGNED NULL,
  descricao VARCHAR(255) NOT NULL,
  obrigatorio TINYINT(1) NOT NULL DEFAULT 1,
  concluido TINYINT(1) NOT NULL DEFAULT 0,
  concluido_por INT NULL,
  concluido_em DATETIME NULL,
  observacao VARCHAR(255) NULL,
  ordem INT NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id),
  KEY idx_ordem_checklist_os(ordem_servico_id,ordem),
  CONSTRAINT fk_ordem_checklist_os FOREIGN KEY(ordem_servico_id) REFERENCES ordens_servico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ol_unificacao_migracoes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ordem_limpeza_id BIGINT UNSIGNED NOT NULL,
  ordem_servico_id INT NOT NULL,
  migrado_por INT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY(id),
  UNIQUE KEY uq_ol_unificacao_origem(ordem_limpeza_id),
  UNIQUE KEY uq_ol_unificacao_destino(ordem_servico_id),
  KEY idx_ol_unificacao_usuario(migrado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Classifica como OL as ordens já existentes cuja categoria é Limpeza, sem renumerá-las.
UPDATE ordens_servico os
JOIN categorias c ON c.id=os.categoria_id
SET os.tipo_ordem='OL'
WHERE LOWER(TRIM(c.nome))='limpeza';
