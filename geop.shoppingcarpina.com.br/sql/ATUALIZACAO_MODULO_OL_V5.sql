-- GEOP Shopping Carpina - Módulo OL (Ordem de Limpeza) V5
-- Aplicar somente após o módulo Limpeza V3.
-- Compatível com MySQL 8 / MariaDB 10.4+.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS limpeza_ordens_sequences (
    ano SMALLINT UNSIGNED NOT NULL,
    ultimo_numero INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (ano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_ordens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero_ol VARCHAR(24) NOT NULL,
    titulo VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    tipo ENUM('ROTINA','EMERGENCIAL','EVENTO','POS_OBRA','REFORCO','OUTRA') NOT NULL DEFAULT 'ROTINA',
    prioridade ENUM('BAIXA','MEDIA','ALTA','CRITICA') NOT NULL DEFAULT 'MEDIA',
    status ENUM('ABERTA','ATRIBUIDA','EM_ANDAMENTO','AGUARDANDO_VALIDACAO','CORRECAO_SOLICITADA','ENCERRADA','CANCELADA') NOT NULL DEFAULT 'ABERTA',
    ambiente_id BIGINT UNSIGNED NULL,
    local_id INT NULL,
    supervisor_membro_id BIGINT UNSIGNED NULL,
    solicitante_id INT NULL,
    criado_por INT NOT NULL,
    validado_por INT NULL,
    data_programada DATE NULL,
    hora_inicio_prevista TIME NULL,
    prazo_em DATETIME NULL,
    iniciado_em DATETIME NULL,
    concluido_em DATETIME NULL,
    validado_em DATETIME NULL,
    encerrado_em DATETIME NULL,
    cancelado_em DATETIME NULL,
    observacao_execucao TEXT NULL,
    problema_encontrado TEXT NULL,
    insumos_utilizados TEXT NULL,
    parecer_validacao TEXT NULL,
    motivo_cancelamento TEXT NULL,
    ambiente_registro_id BIGINT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_limpeza_ordem_numero (numero_ol),
    KEY idx_ol_status_prazo (status,prazo_em),
    KEY idx_ol_ambiente (ambiente_id,status),
    KEY idx_ol_local (local_id,status),
    KEY idx_ol_supervisor (supervisor_membro_id,status),
    KEY idx_ol_criador (criado_por,criado_em),
    KEY idx_ol_registro_ambiente (ambiente_registro_id),
    CONSTRAINT fk_ol_ambiente FOREIGN KEY (ambiente_id) REFERENCES ambientes_qr(id) ON DELETE SET NULL,
    CONSTRAINT fk_ol_local FOREIGN KEY (local_id) REFERENCES locais(id) ON DELETE SET NULL,
    CONSTRAINT fk_ol_supervisor_membro FOREIGN KEY (supervisor_membro_id) REFERENCES limpeza_organograma_nos(id) ON DELETE SET NULL,
    CONSTRAINT fk_ol_solicitante FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_ol_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id),
    CONSTRAINT fk_ol_validador FOREIGN KEY (validado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_ordem_participantes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ordem_limpeza_id BIGINT UNSIGNED NOT NULL,
    membro_id BIGINT UNSIGNED NOT NULL,
    planejado TINYINT(1) NOT NULL DEFAULT 1,
    executou TINYINT(1) NOT NULL DEFAULT 0,
    responsavel_principal TINYINT(1) NOT NULL DEFAULT 0,
    papel ENUM('RESPONSAVEL','EXECUTOR','APOIO') NOT NULL DEFAULT 'EXECUTOR',
    observacao VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ol_participante (ordem_limpeza_id,membro_id),
    KEY idx_ol_participante_membro (membro_id,executou,planejado),
    KEY idx_ol_participante_principal (ordem_limpeza_id,responsavel_principal),
    CONSTRAINT fk_ol_participante_ordem FOREIGN KEY (ordem_limpeza_id) REFERENCES limpeza_ordens(id) ON DELETE CASCADE,
    CONSTRAINT fk_ol_participante_membro FOREIGN KEY (membro_id) REFERENCES limpeza_organograma_nos(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_ordem_itens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ordem_limpeza_id BIGINT UNSIGNED NOT NULL,
    tarefa_plano_id BIGINT UNSIGNED NULL,
    descricao VARCHAR(255) NOT NULL,
    obrigatorio TINYINT(1) NOT NULL DEFAULT 1,
    status_item ENUM('PENDENTE','CONCLUIDO','NAO_REALIZADO') NOT NULL DEFAULT 'PENDENTE',
    observacao_execucao VARCHAR(500) NULL,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ol_item_ordem (ordem_limpeza_id,ordem),
    KEY idx_ol_item_tarefa (tarefa_plano_id),
    CONSTRAINT fk_ol_item_ordem FOREIGN KEY (ordem_limpeza_id) REFERENCES limpeza_ordens(id) ON DELETE CASCADE,
    CONSTRAINT fk_ol_item_tarefa FOREIGN KEY (tarefa_plano_id) REFERENCES limpeza_plano_tarefas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_ordem_fotos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ordem_limpeza_id BIGINT UNSIGNED NOT NULL,
    usuario_id INT NULL,
    origem ENUM('EXECUCAO','SUPERVISAO','GESTAO') NOT NULL DEFAULT 'EXECUCAO',
    tipo ENUM('ANTES','DURANTE','DEPOIS','INSPECAO','PROBLEMA') NOT NULL DEFAULT 'DEPOIS',
    arquivo_url VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) NULL,
    publica TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ol_foto_ordem (ordem_limpeza_id,origem,tipo),
    CONSTRAINT fk_ol_foto_ordem FOREIGN KEY (ordem_limpeza_id) REFERENCES limpeza_ordens(id) ON DELETE CASCADE,
    CONSTRAINT fk_ol_foto_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_ordem_historico (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ordem_limpeza_id BIGINT UNSIGNED NOT NULL,
    usuario_id INT NULL,
    status_anterior VARCHAR(40) NULL,
    status_novo VARCHAR(40) NOT NULL,
    acao VARCHAR(80) NOT NULL,
    descricao TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ol_historico_ordem (ordem_limpeza_id,criado_em),
    CONSTRAINT fk_ol_historico_ordem FOREIGN KEY (ordem_limpeza_id) REFERENCES limpeza_ordens(id) ON DELETE CASCADE,
    CONSTRAINT fk_ol_historico_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS geop_ol_add_column_if_missing;
DELIMITER $$
CREATE PROCEDURE geop_ol_add_column_if_missing(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=p_table AND COLUMN_NAME=p_column
    ) THEN
        SET @ol_sql=CONCAT('ALTER TABLE `',p_table,'` ADD COLUMN `',p_column,'` ',p_definition);
        PREPARE ol_stmt FROM @ol_sql;
        EXECUTE ol_stmt;
        DEALLOCATE PREPARE ol_stmt;
    END IF;
END$$
DELIMITER ;

CALL geop_ol_add_column_if_missing('ambiente_registros','ol_id','BIGINT UNSIGNED NULL AFTER ordem_servico_id');
DROP PROCEDURE IF EXISTS geop_ol_add_column_if_missing;

-- As Ordens de Limpeza validadas podem alimentar automaticamente o histórico público do ambiente.
ALTER TABLE ambiente_registros
    MODIFY COLUMN origem_lancamento ENUM('QR','GESTOR','SUPERVISOR','CRONOGRAMA','OL') NOT NULL DEFAULT 'GESTOR';
ALTER TABLE ambiente_registro_fotos
    MODIFY COLUMN origem ENUM('ASG','SUPERVISOR','GESTOR','OL') NOT NULL;

DROP PROCEDURE IF EXISTS geop_ol_add_index_if_missing;
DELIMITER $$
CREATE PROCEDURE geop_ol_add_index_if_missing(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_sql TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=p_table AND INDEX_NAME=p_index
    ) THEN
        SET @ol_idx_sql=p_sql;
        PREPARE ol_idx_stmt FROM @ol_idx_sql;
        EXECUTE ol_idx_stmt;
        DEALLOCATE PREPARE ol_idx_stmt;
    END IF;
END$$
DELIMITER ;
CALL geop_ol_add_index_if_missing('ambiente_registros','uq_ambiente_registro_ol','CREATE UNIQUE INDEX uq_ambiente_registro_ol ON ambiente_registros(ol_id)');
DROP PROCEDURE IF EXISTS geop_ol_add_index_if_missing;

INSERT IGNORE INTO permissoes(codigo,nome,descricao) VALUES
('ol.acessar','Acessar Ordens de Limpeza','Visualizar o módulo OL'),
('ol.criar','Criar Ordem de Limpeza','Abrir e editar Ordens de Limpeza'),
('ol.atribuir','Atribuir equipe da OL','Selecionar funcionários, responsável principal e supervisor'),
('ol.executar','Registrar execução da OL','Iniciar, preencher checklist, equipe real e evidências'),
('ol.validar','Validar Ordem de Limpeza','Aprovar ou solicitar correção'),
('ol.cancelar','Cancelar Ordem de Limpeza','Cancelar uma OL com justificativa'),
('ol.relatorios','Relatórios de Ordem de Limpeza','Consultar e exportar relatórios OL');

-- Remove permissões OL de perfis não autorizados.
DELETE pp FROM perfil_permissoes pp
JOIN perfis pf ON pf.id=pp.perfil_id
JOIN permissoes pm ON pm.id=pp.permissao_id
WHERE pm.codigo LIKE 'ol.%'
  AND pf.codigo NOT IN ('TI','GESTOR','GESTOR_SINDICO','SUPERVISOR_ASG');

-- TI e Gestores recebem o fluxo completo.
INSERT IGNORE INTO perfil_permissoes(perfil_id,permissao_id)
SELECT pf.id,pm.id FROM perfis pf CROSS JOIN permissoes pm
WHERE pf.codigo IN ('TI','GESTOR','GESTOR_SINDICO') AND pm.codigo LIKE 'ol.%';

-- O Supervisor da Limpeza também abre, atribui, executa e valida, respeitando o bloqueio de autovalidação.
INSERT IGNORE INTO perfil_permissoes(perfil_id,permissao_id)
SELECT pf.id,pm.id FROM perfis pf CROSS JOIN permissoes pm
WHERE pf.codigo='SUPERVISOR_ASG' AND pm.codigo LIKE 'ol.%';

INSERT IGNORE INTO schema_migrations(version,applied_at) VALUES('007_ordem_limpeza_ol_v5',NOW());

SET FOREIGN_KEY_CHECKS=1;
