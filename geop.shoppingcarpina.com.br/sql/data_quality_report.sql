-- Somente leitura: execute e exporte os resultados antes de corrigir manualmente.
SELECT 'OS_ENCERRADA_SEM_CONCLUSAO' tipo,id,numero_os detalhe FROM ordens_servico
 WHERE status='ENCERRADA' AND data_conclusao IS NULL;
SELECT 'PRAZO_ANTERIOR_ABERTURA' tipo,id,numero_os detalhe FROM ordens_servico
 WHERE prazo IS NOT NULL AND data_abertura IS NOT NULL AND prazo<data_abertura;
SELECT 'PREVENTIVA_COM_ORIGEM_MANUAL' tipo,id,numero_os detalhe FROM ordens_servico
 WHERE preventiva_id IS NOT NULL AND origem='MANUAL';
SELECT 'ANEXO_SEM_OS' tipo,a.id,a.arquivo_url detalhe FROM anexos_os a
 LEFT JOIN ordens_servico o ON o.id=a.ordem_servico_id WHERE o.id IS NULL;
SELECT 'AUXILIAR_DUPLICADO' tipo,MIN(id) id,CONCAT(ordem_servico_id,':',usuario_id) detalhe
 FROM ordem_agentes GROUP BY ordem_servico_id,usuario_id HAVING COUNT(*)>1;
