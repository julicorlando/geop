-- GEOP V10.2 - Equipe ASG nas OL unificadas
-- Compatível com MariaDB/cPanel; não consulta information_schema.

CREATE TABLE IF NOT EXISTS ordem_limpeza_participantes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ordem_servico_id INT NOT NULL,
    membro_id BIGINT UNSIGNED NOT NULL,
    papel VARCHAR(20) NOT NULL DEFAULT 'ASG',
    principal TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id),
    UNIQUE KEY uq_ordem_limpeza_participante(ordem_servico_id,membro_id),
    KEY idx_ordem_limpeza_papel(ordem_servico_id,papel),
    CONSTRAINT fk_ordem_limpeza_part_os FOREIGN KEY(ordem_servico_id) REFERENCES ordens_servico(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
