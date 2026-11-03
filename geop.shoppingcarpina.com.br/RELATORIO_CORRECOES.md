# Relatório de correções

| Tema | Correção |
|---|---|
| Upload público | MIME real, integridade, limite, nome aleatório, hash, IP/origem/fase e `usuario_id` nulo modelado. |
| Sessões/headers | Bootstrap anterior ao layout; cookies HttpOnly/SameSite/Secure; armazenamento privado. |
| Collation | Conexão e migrations em `utf8mb4_unicode_ci`; conversão explícita pós-backup. |
| Número de OS | Tabela anual, transação e `FOR UPDATE`; removido `COUNT+1`. |
| RBAC | `usuario_pode`, matriz persistida e `GESTOR_SINDICO`. |
| Prioridade | Lista oficial; migration de `URGENTE` para `CRITICA`. |
| Auxiliares | Relação única `ordem_agentes` e rota antiga compatível. |
| Reabertura | Estruturas de token/aprovação/auditoria preparadas para uso único. |
| Relatórios | Removido limite de 100 da consulta/exportação. |
| Preventivas | Cron transacional; somente vencidas; ciclo sem duplicidade; simulação com rollback. |
| Estoque | Estruturas para produto, justificativa e aprovação. |
| CFTV | Encerramento por cron e timezone `America/Recife`. |
| Chat | Mais recentes, paginação reversa, POST+CSRF, leitura e destinatário ativo. |
| Usuários | E-mail normalizável, perfil preservado e sem senha padrão. |
| PWA | Cache somente de recursos públicos explícitos. |
| Android | Sem backup/conteúdo misto; domínio e esquemas restritos. |
| Pacote | Lista de exclusão e empacotador sem sessões, logs, APKs ou keystores. |
| Dados | `sql/data_quality_report.sql` não inventa valores. |
