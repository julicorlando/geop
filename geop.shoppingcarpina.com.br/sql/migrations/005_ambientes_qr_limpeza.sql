-- GEOP 005: Ambientes, rotinas de limpeza, QR Code e validação por supervisor.
-- Compatível com MySQL 5.7+/MariaDB 10.3+.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '-03:00';

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(64) NOT NULL PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambientes_qr (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    local_id INT NOT NULL,
    nome_publico VARCHAR(160) NOT NULL,
    descricao_publica TEXT NULL,
    token_publico CHAR(48) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    exibir_historico TINYINT(1) NOT NULL DEFAULT 1,
    exibir_fotos TINYINT(1) NOT NULL DEFAULT 0,
    historico_limite SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    frequencia_minutos INT UNSIGNED NOT NULL DEFAULT 60,
    exige_foto_asg TINYINT(1) NOT NULL DEFAULT 0,
    exige_foto_supervisor TINYINT(1) NOT NULL DEFAULT 1,
    gerado_por INT NULL,
    ultimo_acesso_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ambiente_local (local_id),
    UNIQUE KEY uq_ambiente_token (token_publico),
    KEY idx_ambiente_ativo (ativo, nome_publico),
    CONSTRAINT fk_ambiente_local FOREIGN KEY (local_id) REFERENCES locais(id),
    CONSTRAINT fk_ambiente_gerador FOREIGN KEY (gerado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_acoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(160) NOT NULL,
    descricao VARCHAR(255) NULL,
    categoria VARCHAR(80) NOT NULL DEFAULT 'LIMPEZA',
    exige_foto TINYINT(1) NOT NULL DEFAULT 0,
    exige_observacao TINYINT(1) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_por INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ambiente_acao_nome (nome),
    KEY idx_acao_ativa (ativo, categoria),
    CONSTRAINT fk_acao_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_acao_locais (
    ambiente_id BIGINT UNSIGNED NOT NULL,
    acao_id BIGINT UNSIGNED NOT NULL,
    obrigatoria TINYINT(1) NOT NULL DEFAULT 0,
    ordem_exibicao SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ambiente_id, acao_id),
    KEY idx_acao_local_ativa (ambiente_id, ativo, ordem_exibicao),
    CONSTRAINT fk_acao_local_ambiente FOREIGN KEY (ambiente_id) REFERENCES ambientes_qr(id) ON DELETE CASCADE,
    CONSTRAINT fk_acao_local_acao FOREIGN KEY (acao_id) REFERENCES ambiente_acoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_registros (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ambiente_id BIGINT UNSIGNED NOT NULL,
    ordem_servico_id INT NULL,
    asg_id INT NOT NULL,
    status ENUM('RASCUNHO','AGUARDANDO_VALIDACAO','VALIDADA','REPROVADA','CORRECAO_SOLICITADA','SOLICITADO_FECHAMENTO','CANCELADA') NOT NULL DEFAULT 'AGUARDANDO_VALIDACAO',
    iniciado_em DATETIME NULL,
    finalizado_em DATETIME NOT NULL,
    observacao_asg TEXT NULL,
    problema_encontrado TEXT NULL,
    insumos_repostos TEXT NULL,
    supervisor_id INT NULL,
    validado_em DATETIME NULL,
    observacao_supervisor TEXT NULL,
    solicitado_fechamento_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_registro_ambiente (ambiente_id, status, finalizado_em),
    KEY idx_registro_asg (asg_id, criado_em),
    KEY idx_registro_supervisor (supervisor_id, validado_em),
    KEY idx_registro_os (ordem_servico_id),
    CONSTRAINT fk_registro_ambiente FOREIGN KEY (ambiente_id) REFERENCES ambientes_qr(id),
    CONSTRAINT fk_registro_os FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico(id) ON DELETE SET NULL,
    CONSTRAINT fk_registro_asg FOREIGN KEY (asg_id) REFERENCES usuarios(id),
    CONSTRAINT fk_registro_supervisor FOREIGN KEY (supervisor_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_registro_itens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registro_id BIGINT UNSIGNED NOT NULL,
    acao_id BIGINT UNSIGNED NOT NULL,
    concluida TINYINT(1) NOT NULL DEFAULT 1,
    observacao VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_registro_acao (registro_id, acao_id),
    CONSTRAINT fk_item_registro FOREIGN KEY (registro_id) REFERENCES ambiente_registros(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_acao FOREIGN KEY (acao_id) REFERENCES ambiente_acoes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_registro_fotos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registro_id BIGINT UNSIGNED NOT NULL,
    usuario_id INT NOT NULL,
    origem ENUM('ASG','SUPERVISOR','GESTOR') NOT NULL,
    tipo ENUM('ANTES','DURANTE','DEPOIS','INSPECAO','PROBLEMA') NOT NULL DEFAULT 'DEPOIS',
    arquivo_url VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) NULL,
    publica TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_foto_registro (registro_id, origem, tipo),
    CONSTRAINT fk_foto_registro FOREIGN KEY (registro_id) REFERENCES ambiente_registros(id) ON DELETE CASCADE,
    CONSTRAINT fk_foto_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_validacoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registro_id BIGINT UNSIGNED NOT NULL,
    supervisor_id INT NOT NULL,
    resultado ENUM('APROVADA','REPROVADA','CORRECAO_SOLICITADA') NOT NULL,
    observacao TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_validacao_registro (registro_id, criado_em),
    CONSTRAINT fk_validacao_registro FOREIGN KEY (registro_id) REFERENCES ambiente_registros(id) ON DELETE CASCADE,
    CONSTRAINT fk_validacao_supervisor FOREIGN KEY (supervisor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_qr_acessos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ambiente_id BIGINT UNSIGNED NOT NULL,
    usuario_id INT NULL,
    ip_hash CHAR(64) NULL,
    user_agent_hash CHAR(64) NULL,
    acessado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_qr_acesso (ambiente_id, acessado_em),
    CONSTRAINT fk_qr_acesso_ambiente FOREIGN KEY (ambiente_id) REFERENCES ambientes_qr(id) ON DELETE CASCADE,
    CONSTRAINT fk_qr_acesso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO ambiente_acoes (nome, descricao, categoria, exige_foto, exige_observacao) VALUES
('Limpeza do piso','Varrição, lavagem ou tratamento do piso.','LIMPEZA',0,0),
('Higienização dos sanitários','Limpeza e desinfecção de vasos e mictórios.','HIGIENIZACAO',0,0),
('Limpeza de pias e bancadas','Limpeza e desinfecção das superfícies.','HIGIENIZACAO',0,0),
('Limpeza de espelhos','Limpeza sem manchas ou resíduos.','LIMPEZA',0,0),
('Retirada de resíduos','Esvaziamento e substituição dos sacos das lixeiras.','RESIDUOS',0,0),
('Reposição de papel higiênico','Reposição e conferência dos dispensadores.','INSUMOS',0,0),
('Reposição de papel-toalha','Reposição e conferência dos dispensadores.','INSUMOS',0,0),
('Reposição de sabonete','Reposição e conferência dos dispensadores.','INSUMOS',0,0),
('Limpeza completa','Execução integral da rotina configurada para o ambiente.','LIMPEZA',1,0),
('Limpeza emergencial','Atendimento extraordinário com registro da ocorrência.','EMERGENCIAL',1,1),
('Inspeção do ambiente','Conferência das condições, limpeza e abastecimento.','INSPECAO',1,1);

INSERT IGNORE INTO permissoes (codigo,nome,descricao) VALUES
('ambientes.visualizar','Visualizar ambientes','Consultar ambientes e histórico operacional'),
('ambientes.gerenciar','Gerenciar ambientes','Cadastrar e configurar ambientes'),
('ambientes.gerar_qr','Gerar QR Code de ambiente','Gerar, imprimir e rotacionar QR Codes'),
('ambientes.publicar_fotos','Publicar fotos de ambientes','Definir fotos visíveis ao público'),
('ambientes.registrar','Registrar ação em ambiente','Lançar limpeza ou ação executada'),
('ambientes.visualizar_proprios','Visualizar registros próprios','Consultar os próprios lançamentos'),
('ambientes.visualizar_todos','Visualizar todos os registros','Consultar registros de toda a equipe'),
('ambientes.validar','Validar serviço de ambiente','Aprovar serviços e registrar inspeção'),
('ambientes.reprovar','Reprovar serviço de ambiente','Reprovar ou solicitar correção'),
('ambientes.solicitar_fechamento','Solicitar fechamento da OS','Solicitar encerramento de OS vinculada'),
('ambientes.relatorios','Relatórios de ambientes','Visualizar indicadores e relatórios de ambientes');

INSERT IGNORE INTO perfis (codigo,nome,descricao,administrativo,sistema) VALUES
('ASG','ASG','Auxiliar de Serviços Gerais',0,1),
('SUPERVISOR_ASG','Supervisor ASG','Supervisão e validação dos serviços gerais',0,1);

-- TI recebe todas as permissões do módulo.
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id, pm.id FROM perfis pf CROSS JOIN permissoes pm
WHERE pf.codigo='TI' AND pm.codigo LIKE 'ambientes.%';

-- Perfis de gestão administram e validam.
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id, pm.id FROM perfis pf JOIN permissoes pm ON pm.codigo IN (
'ambientes.visualizar','ambientes.gerenciar','ambientes.gerar_qr','ambientes.publicar_fotos','ambientes.registrar',
'ambientes.visualizar_todos','ambientes.validar','ambientes.reprovar','ambientes.solicitar_fechamento','ambientes.relatorios')
WHERE pf.codigo IN ('GESTOR','GESTOR_SINDICO');

-- Supervisor registra, consulta e valida.
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id, pm.id FROM perfis pf JOIN permissoes pm ON pm.codigo IN (
'ambientes.visualizar','ambientes.registrar','ambientes.visualizar_proprios','ambientes.visualizar_todos',
'ambientes.validar','ambientes.reprovar','ambientes.solicitar_fechamento','ambientes.relatorios')
WHERE pf.codigo='SUPERVISOR_ASG';

-- O perfil ASG permanece cadastrado apenas para compatibilidade, sem acesso ao módulo.
-- Colaboradores são mantidos no organograma e o lançamento é feito por Gestor/Supervisor.

INSERT INTO schema_migrations (version,applied_at) VALUES ('005_ambientes_qr_limpeza',NOW())
ON DUPLICATE KEY UPDATE applied_at=applied_at;
