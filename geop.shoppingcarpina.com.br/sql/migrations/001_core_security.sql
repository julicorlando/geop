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
