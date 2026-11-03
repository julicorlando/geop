CREATE TABLE IF NOT EXISTS auditoria_eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidade VARCHAR(80) NOT NULL,
    entidade_id INT NULL,
    acao VARCHAR(80) NOT NULL,
    campo VARCHAR(120) NULL,
    valor_anterior TEXT NULL,
    valor_novo TEXT NULL,
    usuario_id INT NULL,
    ip VARCHAR(64) NULL,
    user_agent VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entidade (entidade, entidade_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tokens_reabertura_os (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ordem_servico_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    gerado_por INT NOT NULL,
    usado_por INT NULL,
    usado_em DATETIME NULL,
    expira_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ordem_servico_id (ordem_servico_id),
    INDEX idx_expira_em (expira_em),
    INDEX idx_usado_em (usado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS estoque_materiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(160) NOT NULL,
    unidade VARCHAR(30) NULL,
    quantidade_atual DECIMAL(12,2) NOT NULL DEFAULT 0,
    estoque_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
    custo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL,
    UNIQUE KEY uq_estoque_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    ordem_servico_id INT NULL,
    usuario_id INT NULL,
    tipo VARCHAR(30) NOT NULL,
    quantidade DECIMAL(12,2) NOT NULL,
    custo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
    observacao TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_material (material_id),
    INDEX idx_os (ordem_servico_id),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS manutencoes_preventivas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    local_id INT NOT NULL,
    categoria_id INT NOT NULL,
    agente_id INT NULL,
    prioridade VARCHAR(20) NOT NULL DEFAULT 'MEDIA',
    intervalo_dias INT NOT NULL DEFAULT 30,
    proxima_execucao DATE NOT NULL,
    ultimo_gerado_em DATETIME NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_por INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NULL,
    INDEX idx_proxima_execucao (proxima_execucao),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE ordens_servico ADD COLUMN sla_horas INT NULL;
ALTER TABLE ordens_servico ADD COLUMN sla_vencimento DATETIME NULL;
ALTER TABLE ordens_servico ADD COLUMN origem VARCHAR(30) NOT NULL DEFAULT 'MANUAL';
ALTER TABLE ordens_servico ADD COLUMN preventiva_id INT NULL;
ALTER TABLE ordens_servico ADD COLUMN custo_materiais DECIMAL(12,2) NOT NULL DEFAULT 0;
