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


-- Reforço V6.2: migração exclusiva do perfil TI e registro da correção do botão.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

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
VALUES('010_corrigir_botao_migrar_os_ol_v6_2',NOW());

SET FOREIGN_KEY_CHECKS=1;
