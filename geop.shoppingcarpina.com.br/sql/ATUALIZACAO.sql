-- GEOP ATUALIZACAO - execute após backup completo

-- GEOP 001: estabilização e segurança (MySQL 5.7+/MariaDB 10.3+)
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(64) NOT NULL PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS os_numero_sequences (
  ano SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  ultimo_numero BIGINT UNSIGNED NOT NULL DEFAULT 0,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO os_numero_sequences (ano, ultimo_numero)
SELECT CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(numero_os, '-', 2), '-', -1) AS UNSIGNED),
       MAX(CAST(SUBSTRING_INDEX(numero_os, '-', -1) AS UNSIGNED))
FROM ordens_servico
WHERE numero_os REGEXP '^OS-[0-9]{4}-[0-9]+$'
GROUP BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(numero_os, '-', 2), '-', -1) AS UNSIGNED)
ON DUPLICATE KEY UPDATE ultimo_numero = GREATEST(ultimo_numero, VALUES(ultimo_numero));

UPDATE ordens_servico SET prioridade = 'CRITICA' WHERE prioridade = 'URGENTE';

CREATE TABLE IF NOT EXISTS perfis (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(60) NOT NULL,
  nome VARCHAR(100) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_perfis_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(100) NOT NULL,
  nome VARCHAR(120) NOT NULL,
  descricao VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_permissoes_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfil_permissoes (
  perfil_id INT UNSIGNED NOT NULL,
  permissao_id INT UNSIGNED NOT NULL,
  concedida_por INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (perfil_id, permissao_id),
  KEY idx_pp_permissao (permissao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO perfis (codigo,nome) VALUES
('TI','TI'),('DIRETORIA','Diretoria'),('GESTOR','Gestor'),('GESTOR_SINDICO','Gestor síndico'),
('CFTV','CFTV'),('AGENTE','Agente'),('COORDENADOR_OPERACOES_OBRAS','Coordenador de operações e obras')
ON DUPLICATE KEY UPDATE nome=VALUES(nome), ativo=1;

INSERT INTO permissoes (codigo,nome,descricao) VALUES
('os.ver','Visualizar ordens','Visualizar ordens'),
('os.criar','Criar ordens','Criar ordens'),
('os.editar','Editar ordens','Editar ordens'),
('os.executar','Executar ordem atribuída','Executar ordem atribuída'),
('os.reabrir','Reabrir ordens','Reabrir ordens'),
('os.aprovar','Aprovar conclusão','Aprovar conclusão'),
('relatorios.ver','Visualizar relatórios','Visualizar relatórios'),
('relatorios.exportar','Exportar relatórios','Exportar relatórios'),
('usuarios.gerenciar','Gerenciar usuários','Gerenciar usuários'),
('rbac.gerenciar','Gerenciar permissões','Gerenciar permissões'),
('catalogos.gerenciar','Gerenciar catálogos','Gerenciar catálogos'),
('estoque.ver','Visualizar estoque','Visualizar estoque'),
('estoque.movimentar','Movimentar estoque','Movimentar estoque'),
('estoque.negativo','Autorizar estoque negativo','Autorizar estoque negativo'),
('preventivas.gerenciar','Gerenciar preventivas','Gerenciar preventivas'),
('cftv.operar','Operar CFTV','Operar CFTV'),
('auditoria.ver','Visualizar auditoria','Visualizar auditoria'),
('aprovacoes.decidir','Decidir aprovações','Decidir aprovações')
ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao);

INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id,p.id FROM perfis pf CROSS JOIN permissoes p WHERE pf.codigo='TI';
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id,p.id FROM perfis pf JOIN permissoes p ON p.codigo IN
('os.ver','os.criar','os.editar','os.reabrir','os.aprovar','relatorios.ver','relatorios.exportar',
 'catalogos.gerenciar','estoque.ver','estoque.movimentar','preventivas.gerenciar','auditoria.ver','aprovacoes.decidir')
WHERE pf.codigo IN ('GESTOR','GESTOR_SINDICO','DIRETORIA');
INSERT IGNORE INTO perfil_permissoes (perfil_id,permissao_id)
SELECT pf.id,p.id FROM perfis pf JOIN permissoes p ON p.codigo IN ('os.ver','os.executar')
WHERE pf.codigo='AGENTE';

DROP PROCEDURE IF EXISTS geop_add_column;
DELIMITER $$
CREATE PROCEDURE geop_add_column(IN p_table VARCHAR(64), IN p_column VARCHAR(64), IN p_definition TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=p_table AND COLUMN_NAME=p_column
  ) THEN
    SET @ddl=CONCAT('ALTER TABLE `',p_table,'` ADD COLUMN `',p_column,'` ',p_definition);
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
END$$
DELIMITER ;

CALL geop_add_column('anexos_os','origem',"VARCHAR(30) NOT NULL DEFAULT 'AUTENTICADO'");
CALL geop_add_column('anexos_os','mime_type','VARCHAR(100) NULL');
CALL geop_add_column('anexos_os','tamanho_bytes','BIGINT UNSIGNED NULL');
CALL geop_add_column('anexos_os','sha256','CHAR(64) NULL');
CALL geop_add_column('anexos_os','ip_origem','VARCHAR(45) NULL');
CALL geop_add_column('anexos_os','fase',"VARCHAR(20) NOT NULL DEFAULT 'GERAL'");
CALL geop_add_column('chat_mensagens','lida_em','DATETIME NULL');
CALL geop_add_column('usuarios','senha_alterada_em','DATETIME NULL');
CALL geop_add_column('usuarios','forcar_troca_senha','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('usuarios','tentativas_login','SMALLINT UNSIGNED NOT NULL DEFAULT 0');
CALL geop_add_column('usuarios','bloqueado_ate','DATETIME NULL');
CALL geop_add_column('gestor_tokens','token_hash','CHAR(64) NULL');
CALL geop_add_column('usuario_convites','token_selector','CHAR(64) NULL');
CALL geop_add_column('auditoria_eventos','valores_anteriores','JSON NULL');
CALL geop_add_column('auditoria_eventos','valores_posteriores','JSON NULL');
CALL geop_add_column('auditoria_eventos','origem',"VARCHAR(30) NOT NULL DEFAULT 'WEB'");
CALL geop_add_column('auditoria_eventos','request_id','VARCHAR(64) NULL');

ALTER TABLE anexos_os MODIFY usuario_id INT NULL;

CREATE TABLE IF NOT EXISTS auditoria_eventos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  acao VARCHAR(80) NOT NULL,
  entidade VARCHAR(80) NOT NULL,
  entidade_id BIGINT NULL,
  valores_anteriores JSON NULL,
  valores_posteriores JSON NULL,
  ip VARCHAR(45) NULL,
  origem VARCHAR(30) NOT NULL DEFAULT 'WEB',
  request_id VARCHAR(64) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_auditoria_entidade (entidade,entidade_id),
  KEY idx_auditoria_usuario (usuario_id),
  KEY idx_auditoria_data (criado_em),
  KEY idx_auditoria_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tokens_seguros (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo VARCHAR(40) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  entidade_id BIGINT NULL,
  usuario_id INT NULL,
  payload JSON NULL,
  expira_em DATETIME NOT NULL,
  usado_em DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_token_hash (token_hash),
  KEY idx_token_tipo_entidade (tipo,entidade_id),
  KEY idx_token_expira (expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS geop_convert_utf8mb4;
DELIMITER $$
CREATE PROCEDURE geop_convert_utf8mb4()
BEGIN
  DECLARE done INT DEFAULT 0;
  DECLARE table_name_value VARCHAR(64);
  DECLARE tables_cursor CURSOR FOR
    SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE';
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done=1;
  OPEN tables_cursor;
  conversion_loop: LOOP
    FETCH tables_cursor INTO table_name_value;
    IF done=1 THEN LEAVE conversion_loop; END IF;
    SET @ddl=CONCAT('ALTER TABLE `',table_name_value,'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END LOOP;
  CLOSE tables_cursor;
END$$
DELIMITER ;
-- Execute CALL geop_convert_utf8mb4() somente depois do backup e da auditoria de mojibake.

INSERT INTO schema_migrations (version,applied_at) VALUES ('001_core_security',NOW())
ON DUPLICATE KEY UPDATE applied_at=applied_at;

-- GEOP 002: estruturas operacionais e 18 módulos
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, codigo_patrimonial VARCHAR(80) NOT NULL,
 qr_token_hash CHAR(64) NOT NULL, categoria_id INT NULL, marca VARCHAR(100), modelo VARCHAR(100),
 numero_serie VARCHAR(120), local_id INT NULL, unidade VARCHAR(100), fornecedor_id BIGINT NULL,
 garantia_ate DATE NULL, adquirido_em DATE NULL, estado_conservacao VARCHAR(40), latitude DECIMAL(10,7),
 longitude DECIMAL(10,7), dados_externos JSON NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_ativo_codigo(codigo_patrimonial), UNIQUE KEY uq_ativo_qr(qr_token_hash), KEY idx_ativo_local(local_id), KEY idx_ativo_categoria(categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativo_arquivos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ativo_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(20) NOT NULL,
 caminho VARCHAR(255) NOT NULL, mime_type VARCHAR(100), sha256 CHAR(64), criado_por INT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_arquivo_ativo(ativo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS checklist_modelos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(160) NOT NULL, tipo_servico VARCHAR(60),
 categoria_id INT NULL, local_id INT NULL, equipe VARCHAR(100), tipo_manutencao VARCHAR(60),
 ativo TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS checklist_itens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, modelo_id BIGINT UNSIGNED NOT NULL, rotulo VARCHAR(255) NOT NULL,
 tipo ENUM('SIM_NAO','TEXTO','NUMERO','FOTO','ASSINATURA','SELECAO','DATA','MEDIDOR') NOT NULL,
 opcoes JSON NULL, obrigatorio TINYINT(1) NOT NULL DEFAULT 0, ordem SMALLINT NOT NULL DEFAULT 0, KEY idx_item_modelo(modelo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS checklist_respostas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, item_id BIGINT UNSIGNED NOT NULL,
 usuario_id INT NULL, valor_texto TEXT NULL, valor_numero DECIMAL(18,4) NULL, arquivo_id BIGINT NULL,
 respondido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_check_resposta(ordem_servico_id,item_id), KEY idx_check_usuario(usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS aprovacoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo VARCHAR(40) NOT NULL, entidade VARCHAR(60) NOT NULL,
 entidade_id BIGINT NOT NULL, solicitante_id INT NOT NULL, aprovador_id INT NULL,
 status ENUM('PENDENTE','APROVADA','REPROVADA','CANCELADA') NOT NULL DEFAULT 'PENDENTE',
 observacao TEXT NULL, decidido_em DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_aprovacao_fila(status,tipo,created_at), KEY idx_aprovacao_entidade(entidade,entidade_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS aprovacao_historico (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, aprovacao_id BIGINT UNSIGNED NOT NULL, usuario_id INT NULL,
 status_anterior VARCHAR(20), status_novo VARCHAR(20) NOT NULL, observacao TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_aprov_hist(aprovacao_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conhecimento_artigos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, titulo VARCHAR(200) NOT NULL, categoria_id INT NULL, conteudo MEDIUMTEXT NOT NULL,
 links JSON NULL, versao VARCHAR(30) NOT NULL DEFAULT '1.0', responsavel_id INT NULL, revisar_em DATE NULL,
 status ENUM('RASCUNHO','PUBLICADO','INATIVO') NOT NULL DEFAULT 'RASCUNHO',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FULLTEXT KEY ft_artigo(titulo,conteudo), KEY idx_artigo_categoria(categoria_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS equipe_disponibilidade (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL,
 tipo ENUM('JORNADA','ESCALA','FERIAS','FOLGA','AUSENCIA','AFASTAMENTO','PLANTAO') NOT NULL,
 inicio DATETIME NOT NULL, fim DATETIME NOT NULL, observacao VARCHAR(255), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_disp_usuario(usuario_id,inicio,fim), CHECK(fim>inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS usuario_especialidades (
 usuario_id INT NOT NULL, especialidade VARCHAR(100) NOT NULL, nivel TINYINT UNSIGNED DEFAULT 1,
 PRIMARY KEY(usuario_id,especialidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS passagens_turno (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, entrega_id INT NOT NULL, recebe_id INT NOT NULL,
 pendencias TEXT, ocorrencias_criticas TEXT, equipamentos_indisponiveis TEXT, servicos_andamento TEXT,
 itens_entregues TEXT, observacoes TEXT, lida_em DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_turno_recebe(recebe_id,lida_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS terceiros (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo_pessoa ENUM('PF','PJ') NOT NULL, documento VARCHAR(20) NOT NULL,
 nome VARCHAR(180) NOT NULL, contatos JSON, contrato VARCHAR(120), vigencia_inicio DATE, vigencia_fim DATE,
 especialidade VARCHAR(160), situacao VARCHAR(30) NOT NULL DEFAULT 'ATIVO', avaliacao DECIMAL(3,2),
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_terceiro_documento(documento), KEY idx_terceiro_vigencia(vigencia_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS terceiro_documentos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, terceiro_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(80), caminho VARCHAR(255),
 vence_em DATE, sha256 CHAR(64), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_doc_vence(vence_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS conclusao_assinaturas (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, nome VARCHAR(180) NOT NULL,
 papel VARCHAR(60) NOT NULL, arquivo VARCHAR(255), aceite TINYINT(1) NOT NULL, justificativa TEXT,
 ip VARCHAR(45), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_assinatura_os(ordem_servico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recorrencias_os (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL, semelhante_id INT NOT NULL,
 score DECIMAL(5,4) NOT NULL, criterios JSON NOT NULL, detectada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_recorrencia(ordem_servico_id,semelhante_id), KEY idx_rec_score(score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dispositivos_iot (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identificador VARCHAR(100) NOT NULL, tipo VARCHAR(80) NOT NULL,
 local_id INT NULL, status VARCHAR(30) NOT NULL DEFAULT 'NOVO', ultima_comunicacao DATETIME NULL,
 bateria DECIMAL(5,2), limite_alerta JSON, api_key_hash CHAR(64) NOT NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_iot_identificador(identificador), UNIQUE KEY uq_iot_api(api_key_hash), KEY idx_iot_status(status,ultima_comunicacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS iot_leituras (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, dispositivo_id BIGINT UNSIGNED NOT NULL, tipo VARCHAR(80) NOT NULL,
 valor_numero DECIMAL(20,6), valor_texto VARCHAR(255), payload JSON, recebida_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_leitura_disp(dispositivo_id,recebida_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ativos_rede (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tipo ENUM('CAMERA','NVR','DVR','SWITCH','ACCESS_POINT','LINK','SERVIDOR') NOT NULL,
 nome VARCHAR(160) NOT NULL, endereco_ip VARCHAR(45), local_id INT NULL, criticidade VARCHAR(20) NOT NULL DEFAULT 'MEDIA',
 falhas_para_alerta SMALLINT UNSIGNED NOT NULL DEFAULT 3, falhas_consecutivas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 status VARCHAR(30) NOT NULL DEFAULT 'DESCONHECIDO', ultima_verificacao DATETIME NULL, ativo TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_rede_ip(endereco_ip), KEY idx_rede_monitor(status,ultima_verificacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ativo_rede_checks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ativo_rede_id BIGINT UNSIGNED NOT NULL, sucesso TINYINT(1) NOT NULL,
 latencia_ms INT NULL, detalhe VARCHAR(255), verificado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_check_ativo(ativo_rede_id,verificado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL, tipo VARCHAR(60) NOT NULL,
 titulo VARCHAR(180) NOT NULL, mensagem TEXT, url VARCHAR(255), lida_em DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_notificacao_usuario(usuario_id,lida_em,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cron_execucoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, comando VARCHAR(80) NOT NULL, status VARCHAR(20) NOT NULL,
 iniciado_em DATETIME NOT NULL, finalizado_em DATETIME NULL, resumo JSON NULL, erro TEXT NULL,
 KEY idx_cron_comando(comando,iniciado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL geop_add_column('ordens_servico','ativo_id','BIGINT UNSIGNED NULL');
CALL geop_add_column('ordens_servico','token_publico_hash','CHAR(64) NULL');
CALL geop_add_column('ordens_servico','latitude','DECIMAL(10,7) NULL');
CALL geop_add_column('ordens_servico','longitude','DECIMAL(10,7) NULL');
CALL geop_add_column('categorias','exige_foto_antes','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('categorias','exige_foto_durante','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('categorias','exige_foto_depois','TINYINT(1) NOT NULL DEFAULT 0');
CALL geop_add_column('materiais_os','produto_id','INT NULL');
CALL geop_add_column('movimentacoes_estoque','justificativa','TEXT NULL');
CALL geop_add_column('movimentacoes_estoque','aprovacao_id','BIGINT UNSIGNED NULL');

INSERT INTO schema_migrations (version,applied_at) VALUES ('002_operational_features',NOW())
ON DUPLICATE KEY UPDATE applied_at=applied_at;

