GEOP Shopping Carpina - Hotfix 10/09/2026 V2

1) Correção definitiva do erro "There is no active transaction" na abertura de OS/OL:
   - geração dos números OS/OL não usa mais beginTransaction/commit;
   - incremento atômico via LAST_INSERT_ID do MySQL/MariaDB;
   - mantém segurança contra numeração duplicada em acessos concorrentes.

2) Liberações CFTV por período:
   - Data inicial + Data final;
   - Horário inicial + Horário final;
   - Exemplo: 10/09/2026 a 12/09/2026, das 09:00 às 12:00;
   - a liberação é válida em cada dia dentro do intervalo, sempre na janela de horário cadastrada;
   - registros antigos continuam válidos como liberação de um único dia.

BANCO:
Execute sql/ATUALIZACAO_LIBERACOES_PERIODO_V7_2.sql antes de publicar o código.
A função ensure_cftv_schema também tenta criar a coluna automaticamente, mas a migração SQL é recomendada em produção.

ARQUIVOS PRINCIPAIS ALTERADOS:
includes/helpers.php
includes/ordens_unificadas.php
public/ordem_form.php
includes/cftv.php
public/cftv.php
public/cftv_historico_acessos.php
sql/migrations/012_liberacoes_periodo_v7_2.sql
sql/ATUALIZACAO_LIBERACOES_PERIODO_V7_2.sql
