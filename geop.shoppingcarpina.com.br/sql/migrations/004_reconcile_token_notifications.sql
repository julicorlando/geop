-- GEOP 004: reconcilia solicitações antigas com tokens já gerados.
-- Não apaga solicitações nem tokens; apenas resolve alertas comprovados.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '-03:00';

UPDATE token_reabertura_solicitacoes s
JOIN (
    SELECT s2.id AS solicitacao_id, MIN(t.id) AS token_id
    FROM token_reabertura_solicitacoes s2
    JOIN tokens_reabertura_os t
      ON t.ordem_servico_id = s2.ordem_servico_id
     AND t.criado_em >= s2.criado_em
    WHERE s2.status = 'PENDENTE'
    GROUP BY s2.id
) reconciliado ON reconciliado.solicitacao_id = s.id
JOIN tokens_reabertura_os token_encontrado ON token_encontrado.id = reconciliado.token_id
SET s.status = 'RESOLVIDA',
    s.resolvida_em = COALESCE(s.resolvida_em, token_encontrado.criado_em),
    s.resolvida_por = COALESCE(s.resolvida_por, token_encontrado.gerado_por),
    s.token_id = COALESCE(s.token_id, token_encontrado.id)
WHERE s.status = 'PENDENTE';

UPDATE tokens_reabertura_os t
JOIN token_reabertura_solicitacoes s ON s.token_id = t.id
SET t.solicitacao_id = s.id
WHERE t.solicitacao_id IS NULL;

INSERT IGNORE INTO schema_migrations(version) VALUES('004_reconcile_token_notifications');

SELECT COUNT(*) AS alertas_antigos_ainda_pendentes
FROM token_reabertura_solicitacoes s
WHERE s.status='PENDENTE'
  AND EXISTS (
      SELECT 1 FROM tokens_reabertura_os t
      WHERE t.ordem_servico_id=s.ordem_servico_id
        AND t.criado_em>=s.criado_em
  );
