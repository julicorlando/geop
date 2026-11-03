-- GEOP 002: RBAC dinamico e ciclo de vida das solicitacoes de token.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '-03:00';

CREATE TABLE IF NOT EXISTS permissoes (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 codigo VARCHAR(80) NOT NULL,
 nome VARCHAR(120) NOT NULL,
 descricao VARCHAR(255) NULL,
 UNIQUE KEY uq_permissoes_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfis (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 codigo VARCHAR(80) NOT NULL,
 nome VARCHAR(120) NOT NULL,
 descricao VARCHAR(255) NULL,
 ativo TINYINT(1) NOT NULL DEFAULT 1,
 administrativo TINYINT(1) NOT NULL DEFAULT 0,
 sistema TINYINT(1) NOT NULL DEFAULT 0,
 criado_por INT NULL,
 criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 atualizado_em DATETIME NULL,
 UNIQUE KEY uq_perfis_codigo (codigo),
 UNIQUE KEY uq_perfis_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perfil_permissoes (
 perfil_id INT UNSIGNED NOT NULL,
 permissao_id INT UNSIGNED NOT NULL,
 PRIMARY KEY (perfil_id, permissao_id),
 CONSTRAINT fk_pp_perfil FOREIGN KEY (perfil_id) REFERENCES perfis(id) ON DELETE CASCADE,
 CONSTRAINT fk_pp_permissao FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissoes (codigo,nome) VALUES
('dashboard.visualizar','Visualizar dashboard'),('os.criar','Criar OS'),('os.visualizar_todas','Visualizar todas as OS'),
('os.visualizar_vinculadas','Visualizar OS vinculadas'),('os.editar','Editar OS'),('os.atribuir','Atribuir agentes'),
('os.alterar_status','Alterar status'),('os.concluir','Concluir OS'),('os.encerrar','Encerrar OS'),('os.reabrir','Reabrir OS'),
('token.gerar','Gerar token'),('relatorios.visualizar','Visualizar relatorios'),('relatorios.exportar','Exportar relatorios'),
('usuarios.gerenciar','Gerenciar usuarios'),('perfis.gerenciar','Gerenciar perfis'),('estoque.gerenciar','Gerenciar estoque'),
('preventivas.gerenciar','Gerenciar preventivas'),('cronograma.gerenciar','Gerenciar cronograma'),('cftv.acessar','Acessar CFTV'),
('chat.acessar','Acessar chat'),('auditoria.visualizar','Visualizar auditoria'),('configuracoes.administrar','Administrar configuracoes');

INSERT IGNORE INTO perfis (codigo,nome,descricao,administrativo,sistema) VALUES
('TI','TI','Administracao integral',1,1),('GESTOR','Gestor','Gestao operacional',0,1),
('AGENTE','Agente','Execucao de ordens vinculadas',0,1),('CFTV','CFTV','Operacao CFTV',0,1),
('DIRETOR_EXECUTIVO','Diretor executivo','Visao executiva',0,1),
('COORDENADOR_OPERACOES_OBRAS','Coordenador de operacoes e obras','Coordenacao operacional',0,1);

INSERT IGNORE INTO perfil_permissoes SELECT pf.id,pm.id FROM perfis pf CROSS JOIN permissoes pm WHERE pf.codigo='TI';
INSERT IGNORE INTO perfil_permissoes SELECT pf.id,pm.id FROM perfis pf JOIN permissoes pm ON pm.codigo IN
('dashboard.visualizar','os.criar','os.visualizar_todas','os.editar','os.atribuir','os.alterar_status','os.encerrar','os.reabrir','token.gerar','relatorios.visualizar','relatorios.exportar','estoque.gerenciar','preventivas.gerenciar','cronograma.gerenciar','chat.acessar','auditoria.visualizar') WHERE pf.codigo='GESTOR';
INSERT IGNORE INTO perfil_permissoes SELECT pf.id,pm.id FROM perfis pf JOIN permissoes pm ON pm.codigo IN
('dashboard.visualizar','os.visualizar_vinculadas','os.alterar_status','os.concluir','chat.acessar') WHERE pf.codigo='AGENTE';
INSERT IGNORE INTO perfil_permissoes SELECT pf.id,pm.id FROM perfis pf JOIN permissoes pm ON pm.codigo IN
('dashboard.visualizar','cftv.acessar','chat.acessar') WHERE pf.codigo='CFTV';
INSERT IGNORE INTO perfil_permissoes SELECT pf.id,pm.id FROM perfis pf JOIN permissoes pm ON pm.codigo IN
('dashboard.visualizar','os.visualizar_todas','relatorios.visualizar','relatorios.exportar','chat.acessar') WHERE pf.codigo='DIRETOR_EXECUTIVO';
INSERT IGNORE INTO perfil_permissoes SELECT pf.id,pm.id FROM perfis pf JOIN permissoes pm ON pm.codigo IN
('dashboard.visualizar','os.criar','os.visualizar_todas','os.editar','os.atribuir','os.alterar_status','os.concluir','relatorios.visualizar','estoque.gerenciar','preventivas.gerenciar','cronograma.gerenciar','chat.acessar') WHERE pf.codigo='COORDENADOR_OPERACOES_OBRAS';

DROP PROCEDURE IF EXISTS geop_migration_002;
DELIMITER $$
CREATE PROCEDURE geop_migration_002()
BEGIN
 IF EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='perfil' AND data_type='enum') THEN
   ALTER TABLE usuarios MODIFY perfil VARCHAR(80) NOT NULL;
 END IF;
 IF NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='perfil_id') THEN
   ALTER TABLE usuarios ADD COLUMN perfil_id INT UNSIGNED NULL AFTER perfil, ADD INDEX idx_usuarios_perfil_id(perfil_id);
 END IF;
 IF NOT EXISTS(SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='usuarios' AND constraint_name='fk_usuarios_perfil') THEN
   ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_perfil FOREIGN KEY(perfil_id) REFERENCES perfis(id);
 END IF;
 IF NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='token_reabertura_solicitacoes' AND column_name='resolvida_em') THEN
   ALTER TABLE token_reabertura_solicitacoes ADD COLUMN resolvida_em DATETIME NULL, ADD COLUMN resolvida_por INT NULL, ADD COLUMN token_id INT NULL, ADD INDEX idx_trs_status(status,criado_em);
 END IF;
 IF NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='tokens_reabertura_os' AND column_name='solicitacao_id') THEN
   ALTER TABLE tokens_reabertura_os ADD COLUMN solicitacao_id INT NULL, ADD UNIQUE KEY uq_token_solicitacao(solicitacao_id);
 END IF;
END$$
DELIMITER ;
CALL geop_migration_002();
DROP PROCEDURE geop_migration_002;

UPDATE usuarios u JOIN perfis p ON p.codigo=u.perfil SET u.perfil_id=p.id WHERE u.perfil_id IS NULL;
INSERT IGNORE INTO schema_migrations(version) VALUES('002_dynamic_rbac_notifications');
