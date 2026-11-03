# Resultado dos testes de RBAC e notificações

- Validação sintática: todos os PHP, incluindo tela, migração e executor.
- Teste estático automatizado: camada central, middleware, matriz, bloqueio concorrente, estado resolvido, vínculo token/solicitação e Service Worker.
- Revisão de acesso direto: rotas de usuários, perfis, auditoria, estoque, preventivas, cronograma, CFTV, chat, relatórios e criação de OS usam permissões no servidor.
- Concorrência de token: `SELECT ... FOR UPDATE`, unicidade por solicitação, update condicional e rollback na falha.
- Persistência do alerta: a consulta retorna apenas `PENDENTE`, sem `resolvida_em`, sem `token_id` e sem token vinculado.

Os cenários integrados que criam usuários/perfis e simulam duas conexões simultâneas exigem MySQL restaurado. Não havia servidor MySQL/MariaDB disponível nesta estação; devem ser executados na cópia isolada antes da produção.
