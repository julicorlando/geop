CREATE TABLE IF NOT EXISTS ordem_agentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ordem_servico_id INT NOT NULL,
    usuario_id INT NOT NULL,
    papel ENUM('PRINCIPAL','AUXILIAR') NOT NULL DEFAULT 'AUXILIAR',
    criado_por INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ordem_usuario (ordem_servico_id, usuario_id),
    INDEX idx_ordem (ordem_servico_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_papel (papel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO ordem_agentes (ordem_servico_id, usuario_id, papel)
SELECT id, agente_id, 'PRINCIPAL'
FROM ordens_servico
WHERE agente_id IS NOT NULL;
