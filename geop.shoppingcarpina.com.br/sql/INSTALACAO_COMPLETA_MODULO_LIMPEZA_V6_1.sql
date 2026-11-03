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


-- ============================================================
-- ETAPA 2: LIMPEZA V2
-- ============================================================

-- GEOP 006: Módulo Limpeza V2
-- Cronograma editável baseado na planilha "Plano de Limpeza - Teste Júlio.xlsx",
-- organograma/equipe editável e lançamento de limpeza por Gestor/Supervisor.
-- Executar APÓS sql/ATUALIZACAO_MODULO_AMBIENTES_QR.sql quando o módulo V1 já estiver instalado.
-- Compatível com MySQL 5.7+/MariaDB 10.3+.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '-03:00';

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(64) NOT NULL PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_organograma_nos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id BIGINT UNSIGNED NULL,
    usuario_id INT NULL,
    nome_colaborador VARCHAR(160) NOT NULL,
    cargo VARCHAR(120) NOT NULL,
    setor VARCHAR(120) NULL,
    turno VARCHAR(80) NULL,
    contato VARCHAR(120) NULL,
    observacao TEXT NULL,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_por INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_limpeza_org_parent (parent_id, ativo, ordem),
    KEY idx_limpeza_org_usuario (usuario_id),
    CONSTRAINT fk_limpeza_org_parent FOREIGN KEY (parent_id) REFERENCES limpeza_organograma_nos(id) ON DELETE SET NULL,
    CONSTRAINT fk_limpeza_org_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_limpeza_org_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_plano_grupos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    chave VARCHAR(80) NOT NULL,
    nome VARCHAR(220) NOT NULL,
    descricao TEXT NULL,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_por INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_limpeza_grupo_chave (chave),
    KEY idx_limpeza_grupo_ativo (ativo, ordem),
    CONSTRAINT fk_limpeza_grupo_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_plano_tarefas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    grupo_id BIGINT UNSIGNED NOT NULL,
    chave VARCHAR(80) NOT NULL,
    descricao VARCHAR(500) NOT NULL,
    observacao TEXT NULL,
    periodicidade VARCHAR(40) NOT NULL DEFAULT 'Diária',
    exige_foto TINYINT(1) NOT NULL DEFAULT 0,
    exige_observacao TINYINT(1) NOT NULL DEFAULT 0,
    ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_por INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_limpeza_tarefa_chave (chave),
    KEY idx_limpeza_tarefa_grupo (grupo_id, ativo, ordem),
    CONSTRAINT fk_limpeza_tarefa_grupo FOREIGN KEY (grupo_id) REFERENCES limpeza_plano_grupos(id) ON DELETE CASCADE,
    CONSTRAINT fk_limpeza_tarefa_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_cronogramas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ano SMALLINT UNSIGNED NOT NULL,
    mes TINYINT UNSIGNED NOT NULL,
    nome VARCHAR(160) NOT NULL,
    status ENUM('RASCUNHO','PUBLICADO','ARQUIVADO') NOT NULL DEFAULT 'PUBLICADO',
    criado_por INT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_limpeza_cronograma_mes (ano, mes),
    CONSTRAINT fk_limpeza_cronograma_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS limpeza_cronograma_itens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cronograma_id BIGINT UNSIGNED NOT NULL,
    tarefa_id BIGINT UNSIGNED NOT NULL,
    dia TINYINT UNSIGNED NOT NULL,
    status ENUM('PENDENTE','CONCLUIDO','NAO_REALIZADO','REPROGRAMADO') NOT NULL DEFAULT 'PENDENTE',
    responsavel_membro_id BIGINT UNSIGNED NULL,
    registro_id BIGINT UNSIGNED NULL,
    observacao VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_limpeza_cronograma_item (cronograma_id, tarefa_id, dia),
    KEY idx_limpeza_cronograma_status (cronograma_id, dia, status),
    KEY idx_limpeza_cronograma_resp (responsavel_membro_id),
    CONSTRAINT fk_limpeza_item_cronograma FOREIGN KEY (cronograma_id) REFERENCES limpeza_cronogramas(id) ON DELETE CASCADE,
    CONSTRAINT fk_limpeza_item_tarefa FOREIGN KEY (tarefa_id) REFERENCES limpeza_plano_tarefas(id) ON DELETE CASCADE,
    CONSTRAINT fk_limpeza_item_responsavel FOREIGN KEY (responsavel_membro_id) REFERENCES limpeza_organograma_nos(id) ON DELETE SET NULL,
    CONSTRAINT fk_limpeza_item_registro FOREIGN KEY (registro_id) REFERENCES ambiente_registros(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ambiente_registro_plano_itens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    registro_id BIGINT UNSIGNED NOT NULL,
    tarefa_id BIGINT UNSIGNED NOT NULL,
    cronograma_item_id BIGINT UNSIGNED NULL,
    concluida TINYINT(1) NOT NULL DEFAULT 1,
    observacao VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ambiente_registro_tarefa (registro_id, tarefa_id),
    KEY idx_amb_reg_plano_cronograma (cronograma_item_id),
    CONSTRAINT fk_amb_reg_plano_registro FOREIGN KEY (registro_id) REFERENCES ambiente_registros(id) ON DELETE CASCADE,
    CONSTRAINT fk_amb_reg_plano_tarefa FOREIGN KEY (tarefa_id) REFERENCES limpeza_plano_tarefas(id),
    CONSTRAINT fk_amb_reg_plano_cronograma FOREIGN KEY (cronograma_item_id) REFERENCES limpeza_cronograma_itens(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adição idempotente de colunas em tabelas do módulo V1.
DROP PROCEDURE IF EXISTS geop_add_column_if_missing;
DELIMITER $$
CREATE PROCEDURE geop_add_column_if_missing(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL geop_add_column_if_missing('ambientes_qr','plano_grupo_id','BIGINT UNSIGNED NULL AFTER local_id');
CALL geop_add_column_if_missing('ambiente_registros','registrado_por','INT NULL AFTER asg_id');
CALL geop_add_column_if_missing('ambiente_registros','executor_membro_id','BIGINT UNSIGNED NULL AFTER registrado_por');
CALL geop_add_column_if_missing('ambiente_registros','executor_usuario_id','INT NULL AFTER executor_membro_id');
CALL geop_add_column_if_missing('ambiente_registros','executor_nome','VARCHAR(160) NULL AFTER executor_usuario_id');
CALL geop_add_column_if_missing('ambiente_registros','origem_lancamento','ENUM(''QR'',''GESTOR'',''SUPERVISOR'',''CRONOGRAMA'') NOT NULL DEFAULT ''GESTOR'' AFTER executor_nome');
DROP PROCEDURE IF EXISTS geop_add_column_if_missing;

INSERT IGNORE INTO permissoes (codigo,nome,descricao) VALUES
('limpeza.acessar','Acessar módulo Limpeza','Acessar o painel de limpeza'),
('limpeza.cronograma','Editar cronograma de limpeza','Criar, editar e publicar cronogramas mensais'),
('limpeza.organograma','Editar organograma de limpeza','Cadastrar equipe, cargos e hierarquia da limpeza');

INSERT IGNORE INTO perfis (codigo,nome,descricao,administrativo,sistema) VALUES
('SUPERVISOR_ASG','Supervisor de Limpeza','Supervisão dos serviços gerais e validação das limpezas',0,1);

-- Remove o módulo de perfis não autorizados. O acesso fica restrito a TI, Gestor e Supervisor de Limpeza.
DELETE pp FROM perfil_permissoes pp
JOIN perfis pf ON pf.id=pp.perfil_id
JOIN permissoes pm ON pm.id=pp.permissao_id
WHERE (pm.codigo LIKE 'ambientes.%' OR pm.codigo LIKE 'limpeza.%')
  AND pf.codigo NOT IN ('TI','GESTOR','GESTOR_SINDICO','SUPERVISOR_ASG');

-- TI e Gestores recebem controle completo.
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id,pm.id FROM perfis pf CROSS JOIN permissoes pm
WHERE pf.codigo IN ('TI','GESTOR','GESTOR_SINDICO')
  AND (pm.codigo LIKE 'ambientes.%' OR pm.codigo LIKE 'limpeza.%');

-- Supervisor registra, valida, acompanha o cronograma e mantém a equipe, sem alterar QR/configuração estrutural do ambiente.
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id,pm.id FROM perfis pf JOIN permissoes pm ON pm.codigo IN (
'limpeza.acessar','limpeza.cronograma','limpeza.organograma',
'ambientes.visualizar','ambientes.registrar','ambientes.visualizar_proprios','ambientes.visualizar_todos',
'ambientes.validar','ambientes.reprovar','ambientes.solicitar_fechamento','ambientes.relatorios','ambientes.publicar_fotos')
WHERE pf.codigo='SUPERVISOR_ASG';

INSERT INTO limpeza_plano_grupos(chave,nome,ordem,ativo) VALUES('planilha_agosto_2026_grupo_01','DOCAS',1,1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_grupos(chave,nome,ordem,ativo) VALUES('planilha_agosto_2026_grupo_02','Corredores de serviços',2,1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_grupos(chave,nome,ordem,ativo) VALUES('planilha_agosto_2026_grupo_03','Praça de alimentação',3,1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_grupos(chave,nome,ordem,ativo) VALUES('planilha_agosto_2026_grupo_04','Administrativo - Recepção, Sala de Reunião, PDU, Adm Shopping e Sala de operações e CFTV.',4,1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_grupos(chave,nome,ordem,ativo) VALUES('planilha_agosto_2026_grupo_05','Mall',5,1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_grupos(chave,nome,ordem,ativo) VALUES('planilha_agosto_2026_grupo_06','Refeitório',6,1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_grupos(chave,nome,ordem,ativo) VALUES('planilha_agosto_2026_grupo_07','Vestiários e Banheiros e Fraudários',7,1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_grupos(chave,nome,ordem,ativo) VALUES('planilha_agosto_2026_grupo_08','Estacionamento, Jardim dos Cisnes e Área externa DOCAS',8,1) ON DUPLICATE KEY UPDATE nome=VALUES(nome),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_06','Inspeção visual','','Diária',1,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_01' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_07','Espanar paredes e áreas','','Semanal',2,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_01' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_08','Limpeza das calhas: lixos em gerais e acumulos de terra.','','Quinzenal',3,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_01' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_10','Limpeza das portas de acesso as DOCAS, praça de alimentação e corredores de serviços.','','Diária',4,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_02' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_11','Limpeza das caixas dos registros de gás e água.','','Diária',5,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_02' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_12','Varrer os corredores da laje técnica - Ralos da laje','','Semanal',6,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_02' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_13','Lavagem do teto de vidro','','Semanal',7,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_02' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_14','Lavagem do piso.','O serviço deverá ser realizado por etapas, na segunda, terça e quarta.','Quinzenal',8,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_02' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_15','Limpeza das paredes.','','Mensal',9,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_02' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_17','Limpeza das mesas e cadeiras.','','Diária',10,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_03' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_18','Limpeza das porta de acesso a praça (Portas de vidro).','','Diária',11,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_03' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_19','Lavagem do piso.','O serviço deverá ser realizado sempre aos domingos.','Semanal',12,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_03' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_20','Limpeza do teto de vidro ( Parte externa).','','Semanal',13,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_03' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_21','Higêinização dos lixeiros.','O serviço deverá ser realizado sempre aos domingos.','Semanal',14,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_03' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_23','Limpeza das salas.','O serviço deverá ser realizado antes do expediente do shopping. Pegar as chaves na central de monitoramento.','Diária',15,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_04' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_24','Limpeza das mesas e cadeiras.','','Diária',16,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_04' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_25','Recolhimento do lixo de ambas as salas.','Após a realização do serviço, as portas deverão ser fechadas e as chaves entregues na central de monitoramento.','Diária',17,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_04' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_26','Banheiro CFTV','','Diária',18,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_04' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_27','Limpeza do filtro de água','','Semanal',19,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_04' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_29','Limpeza dos refletores de piso: Arvores da praça de eventos.','','Diária',20,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_05' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_30','Espanar paredes e áreas','','Diária',21,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_05' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_31','Limpeza das canaletas: lixos em gerais e acumulos de terra.','','Semanal',22,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_05' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_32','Limpeza do mall.','Verificar pontos manchados e lavar.','Semanal',23,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_05' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_34','Higêinização dos lixeiros.','','Semanal',24,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_06' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_35','Limpeza dos ventiladores do refeitório','Solicitar apoio ao Agente de Manutenção','Semanal',25,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_06' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_36','Lavagem do piso.','O serviço deverá ser realizado sempre aos Sábados.','Quinzenal',26,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_06' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_38','Inspenção dos Odorizadores','','Diária',27,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_07' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_39','Lavagem do piso.','O serviço deverá ser realizado sempre aos domingos.','Semanal',28,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_07' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_40','Higêinização dos lixeiros.','','Semanal',29,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_07' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_41','Limpeza geral: Boxer, cubas, paredes e piso.','O serviço deverá ser realizado sempre aos domingos.','Quinzenal',30,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_07' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_43','Inspeção visual: Área da parada de ônibus, cancelas de entrada, saída e pátio.','Colocar um peso dentro da lixeira, para evitar que o saco de lixo saia.','Diária',31,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_08' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_44','Limpeza dos refletores de piso: entrada retangular e triangular.','','Diária',32,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_08' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_45','Limpeza da central de gás: Varrer e espanar paredes, teto e grade.','','Semanal',33,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_08' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;
INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,ordem,ativo) SELECT id,'planilha_agosto_2026_linha_46','Higêinização dos lixeiros.','','Semanal',34,1 FROM limpeza_plano_grupos WHERE chave='planilha_agosto_2026_grupo_08' ON DUPLICATE KEY UPDATE grupo_id=VALUES(grupo_id),descricao=VALUES(descricao),observacao=VALUES(observacao),periodicidade=VALUES(periodicidade),ordem=VALUES(ordem),ativo=1;

INSERT INTO limpeza_cronogramas(ano,mes,nome,status) VALUES(2026,8,'Plano de Limpeza - Agosto/2026','PUBLICADO') ON DUPLICATE KEY UPDATE nome=VALUES(nome);
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_06' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_07' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_07' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_07' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_07' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_07' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_08' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_08' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_08' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_08' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_10' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_11' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_12' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_12' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_12' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_12' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_13' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_13' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_13' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_13' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_14' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_15' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_15' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_15' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_15' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_15' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_17' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_18' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_19' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_19' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_19' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_19' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_19' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_20' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_20' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_20' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_20' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_21' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_21' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_21' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_21' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_21' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_23' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_24' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_25' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_26' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_27' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_27' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_27' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_27' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_27' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_29' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_30' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_31' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_31' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_31' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_31' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_32' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_32' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_32' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_32' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_34' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_34' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_34' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_34' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_34' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_35' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_35' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_35' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_35' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_35' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_36' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_36' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_36' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_36' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_38' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_39' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_39' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_39' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_39' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_39' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_40' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_40' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_40' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_40' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_40' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_41' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_41' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_41' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_41' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_41' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_43' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,1,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,2,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,3,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,5,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,6,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,7,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,8,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,9,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,10,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,12,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,13,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,14,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,15,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,16,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,17,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,19,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,20,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,21,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,22,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,23,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,24,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,26,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,27,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,28,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,29,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,30,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,31,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_44' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_45' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_45' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_45' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_45' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,4,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_46' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,11,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_46' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,18,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_46' WHERE c.ano=2026 AND c.mes=8;
INSERT IGNORE INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status) SELECT c.id,t.id,25,'PENDENTE' FROM limpeza_cronogramas c JOIN limpeza_plano_tarefas t ON t.chave='planilha_agosto_2026_linha_46' WHERE c.ano=2026 AND c.mes=8;

INSERT INTO schema_migrations(version,applied_at) VALUES('006_limpeza_cronograma_organograma',NOW())
ON DUPLICATE KEY UPDATE applied_at=applied_at;
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


-- ============================================================
-- COMPLEMENTO V5: MÓDULO OL - ORDEM DE LIMPEZA
-- ============================================================

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


-- ============================================================
-- EXTENSÃO V6: MIGRAÇÃO DE OS PARA OL
-- ============================================================

-- GEOP Shopping Carpina - Migração de OS para OL V6
-- Aplicar após o Módulo OL V5.
-- Compatível com MySQL 8 / MariaDB 10.4+.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS limpeza_ordem_migracoes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ordem_servico_id INT NOT NULL,
    ordem_limpeza_id BIGINT UNSIGNED NOT NULL,
    migrado_por INT NOT NULL,
    status_os_anterior VARCHAR(40) NOT NULL,
    os_encerrada TINYINT(1) NOT NULL DEFAULT 1,
    observacao TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_migracao_os (ordem_servico_id),
    UNIQUE KEY uq_migracao_ol (ordem_limpeza_id),
    KEY idx_migracao_usuario_data (migrado_por,criado_em),
    CONSTRAINT fk_migracao_os FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico(id) ON DELETE RESTRICT,
    CONSTRAINT fk_migracao_ol FOREIGN KEY (ordem_limpeza_id) REFERENCES limpeza_ordens(id) ON DELETE CASCADE,
    CONSTRAINT fk_migracao_usuario FOREIGN KEY (migrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissoes(codigo,nome,descricao) VALUES
('ol.migrar_os','Migrar OS para OL','Converter uma Ordem de Serviço aberta no módulo errado em Ordem de Limpeza');

-- A migração OS -> OL é exclusiva do perfil TI.
DELETE pp FROM perfil_permissoes pp
JOIN perfis pf ON pf.id=pp.perfil_id
JOIN permissoes pm ON pm.id=pp.permissao_id
WHERE pm.codigo='ol.migrar_os'
  AND pf.codigo <> 'TI';

INSERT IGNORE INTO perfil_permissoes(perfil_id,permissao_id)
SELECT pf.id,pm.id
FROM perfis pf CROSS JOIN permissoes pm
WHERE pf.codigo='TI'
  AND pm.codigo='ol.migrar_os';

INSERT IGNORE INTO schema_migrations(version,applied_at)
VALUES('008_migracao_os_para_ol_v6',NOW());

SET FOREIGN_KEY_CHECKS=1;

-- ============================================================
-- MARCADOR V6.1: MIGRAÇÃO OS -> OL EXCLUSIVA DO TI
-- ============================================================
INSERT IGNORE INTO schema_migrations(version,applied_at)
VALUES('009_restringir_migracao_os_ol_ao_ti_v6_1',NOW());
