-- GEOP Shopping Carpina - Restrição da migração OS -> OL ao TI (V6.1)
-- Aplicar depois da V6 caso a migração anterior já tenha sido importada.
-- Compatível com MySQL 8 / MariaDB 10.4+.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

INSERT IGNORE INTO permissoes(codigo,nome,descricao) VALUES
('ol.migrar_os','Migrar OS para OL','Converter uma Ordem de Serviço em Ordem de Limpeza — acesso exclusivo do TI');

-- Revoga a permissão de qualquer perfil que não seja TI.
DELETE pp FROM perfil_permissoes pp
JOIN perfis pf ON pf.id=pp.perfil_id
JOIN permissoes pm ON pm.id=pp.permissao_id
WHERE pm.codigo='ol.migrar_os'
  AND pf.codigo <> 'TI';

-- Garante a permissão ao perfil TI.
INSERT IGNORE INTO perfil_permissoes(perfil_id,permissao_id)
SELECT pf.id,pm.id
FROM perfis pf CROSS JOIN permissoes pm
WHERE pf.codigo='TI'
  AND pm.codigo='ol.migrar_os';

INSERT IGNORE INTO schema_migrations(version,applied_at)
VALUES('009_restringir_migracao_os_ol_ao_ti_v6_1',NOW());

SET FOREIGN_KEY_CHECKS=1;
