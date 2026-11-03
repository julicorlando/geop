-- GEOP 003: vinculos locais e permissoes para patrimonio externo (somente leitura externa).
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS ordem_patrimonios (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 ordem_id INT NOT NULL,
 patrimonio_externo_id VARCHAR(190) NULL,
 codigo_barras VARCHAR(190) NULL,
 numero_patrimonio VARCHAR(190) NULL,
 descricao_snapshot VARCHAR(255) NULL,
 marca_snapshot VARCHAR(120) NULL,
 modelo_snapshot VARCHAR(120) NULL,
 serie_snapshot VARCHAR(190) NULL,
 setor_snapshot VARCHAR(190) NULL,
 localizacao_snapshot VARCHAR(255) NULL,
 origem VARCHAR(50) NOT NULL DEFAULT 'BANCO_EXTERNO',
 consultado_por INT NOT NULL,
 consultado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 atualizado_por INT NULL,
 atualizado_em DATETIME NULL,
 desvinculado_por INT NULL,
 desvinculado_em DATETIME NULL,
 ativo TINYINT(1) NOT NULL DEFAULT 1,
 INDEX idx_op_ordem_ativo(ordem_id,ativo),
 INDEX idx_op_externo(patrimonio_externo_id),
 INDEX idx_op_codigo(codigo_barras),
 INDEX idx_op_numero(numero_patrimonio),
 CONSTRAINT fk_op_ordem FOREIGN KEY(ordem_id) REFERENCES ordens_servico(id),
 CONSTRAINT fk_op_consultado FOREIGN KEY(consultado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patrimonio_consultas_rate (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 consultado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_pcr_usuario_data(usuario_id,consultado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO permissoes(codigo,nome) VALUES
('patrimonio.consultar','Consultar patrimonio externo'),('patrimonio.vincular','Vincular patrimonio a OS'),
('patrimonio.desvincular','Desvincular patrimonio'),('patrimonio.atualizar_snapshot','Atualizar snapshot'),
('patrimonio.visualizar_historico','Visualizar historico do patrimonio'),('patrimonio.testar_conexao','Testar conexao externa'),
('patrimonio.configurar','Configurar integracao de patrimonio');
INSERT IGNORE INTO perfil_permissoes SELECT p.id,pm.id FROM perfis p CROSS JOIN permissoes pm WHERE p.codigo='TI' AND pm.codigo LIKE 'patrimonio.%';
INSERT IGNORE INTO perfil_permissoes SELECT p.id,pm.id FROM perfis p JOIN permissoes pm ON pm.codigo IN('patrimonio.consultar','patrimonio.vincular','patrimonio.desvincular','patrimonio.atualizar_snapshot','patrimonio.visualizar_historico') WHERE p.codigo IN('GESTOR','COORDENADOR_OPERACOES_OBRAS');
INSERT IGNORE INTO perfil_permissoes SELECT p.id,pm.id FROM perfis p JOIN permissoes pm ON pm.codigo IN('patrimonio.consultar','patrimonio.vincular','patrimonio.visualizar_historico') WHERE p.codigo='AGENTE';
INSERT IGNORE INTO schema_migrations(version) VALUES('003_external_assets');
