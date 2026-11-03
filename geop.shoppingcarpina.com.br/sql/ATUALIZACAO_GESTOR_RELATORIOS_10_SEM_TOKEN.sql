-- GEOP Shopping Carpina - Gestor: 10 emissões de relatório sem token
-- Idempotente. A cada token TI validado, a franquia volta para 10 emissões.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS gestor_relatorio_controle (
    usuario_id INT NOT NULL PRIMARY KEY,
    relatorios_sem_token INT NOT NULL DEFAULT 0,
    ultimo_token_em DATETIME NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gestor_relatorio_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gestor_relatorio_emissoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(40) NOT NULL DEFAULT 'RELATORIO',
    sem_token TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_gestor_relatorio_emissoes_usuario_data (usuario_id, criado_em),
    CONSTRAINT fk_gestor_relatorio_emissao_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO schema_migrations(version,applied_at)
VALUES('012_gestor_relatorios_10_sem_token',NOW());
