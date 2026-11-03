-- GEOP - Módulo Limpeza V3
-- Múltiplos responsáveis no cronograma e na execução das limpezas.
-- Compatível com instalações que já aplicaram a V2.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS limpeza_cronograma_responsaveis (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cronograma_item_id BIGINT UNSIGNED NOT NULL,
    membro_id BIGINT UNSIGNED NOT NULL,
    responsavel_principal TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_limpeza_crono_responsavel (cronograma_item_id,membro_id),
    KEY idx_limpeza_crono_resp_membro (membro_id),
    KEY idx_limpeza_crono_resp_principal (cronograma_item_id,responsavel_principal),
    CONSTRAINT fk_limpeza_crono_resp_item FOREIGN KEY (cronograma_item_id) REFERENCES limpeza_cronograma_itens(id) ON DELETE CASCADE,
    CONSTRAINT fk_limpeza_crono_resp_membro FOREIGN KEY (membro_id) REFERENCES limpeza_organograma_nos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_registro_responsaveis (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registro_id BIGINT UNSIGNED NOT NULL,
    membro_id BIGINT UNSIGNED NULL,
    usuario_id INT NULL,
    nome_responsavel VARCHAR(160) NOT NULL,
    responsavel_principal TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_amb_reg_resp_membro (registro_id,membro_id),
    KEY idx_amb_reg_resp_registro (registro_id,responsavel_principal),
    KEY idx_amb_reg_resp_membro (membro_id),
    KEY idx_amb_reg_resp_usuario (usuario_id),
    CONSTRAINT fk_amb_reg_resp_registro FOREIGN KEY (registro_id) REFERENCES ambiente_registros(id) ON DELETE CASCADE,
    CONSTRAINT fk_amb_reg_resp_membro FOREIGN KEY (membro_id) REFERENCES limpeza_organograma_nos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_amb_reg_resp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migra o responsável único dos itens do cronograma como responsável principal.
INSERT IGNORE INTO limpeza_cronograma_responsaveis(cronograma_item_id,membro_id,responsavel_principal)
SELECT ci.id,ci.responsavel_membro_id,1
FROM limpeza_cronograma_itens ci
WHERE ci.responsavel_membro_id IS NOT NULL;

-- Migra registros antigos do ambiente para a nova tabela de equipe.
INSERT INTO ambiente_registro_responsaveis(registro_id,membro_id,usuario_id,nome_responsavel,responsavel_principal)
SELECT ar.id,ar.executor_membro_id,ar.executor_usuario_id,
       COALESCE(NULLIF(ar.executor_nome,''),n.nome_colaborador,u.nome,legacy.nome,'Responsável não informado'),1
FROM ambiente_registros ar
LEFT JOIN limpeza_organograma_nos n ON n.id=ar.executor_membro_id
LEFT JOIN usuarios u ON u.id=ar.executor_usuario_id
LEFT JOIN usuarios legacy ON legacy.id=ar.asg_id
WHERE NOT EXISTS (
    SELECT 1 FROM ambiente_registro_responsaveis rr WHERE rr.registro_id=ar.id
);

SET FOREIGN_KEY_CHECKS=1;

INSERT IGNORE INTO schema_migrations(version) VALUES('006_limpeza_multiplos_responsaveis');
