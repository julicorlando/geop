# Roteiro de testes — Patch de exclusão

## Segurança / perfis
[ ] Usuário comum não visualiza nem acessa endpoints de exclusão.
[ ] Agente recebe HTTP 403 ao tentar chamar endpoints manualmente.
[ ] Gestor consegue criar solicitação.
[ ] Gestor não consegue aprovar a própria solicitação.
[ ] Outro Gestor consegue aprovar.
[ ] Outro Gestor consegue reprovar.
[ ] Gestor não consegue executar exclusão.
[ ] TI consegue aprovar.
[ ] TI consegue executar exclusão aprovada.
[ ] TI consegue usar exclusão direta.

## Fluxo
[ ] Solicitação nasce como PENDENTE.
[ ] PENDENTE aprovada vira APROVADA.
[ ] PENDENTE reprovada vira REPROVADA.
[ ] APROVADA executada vira EXECUTADA.
[ ] Ordem executada recebe deleted_at/deleted_by/delete_request_id.
[ ] Ordem em lixeira deixa de aparecer nas listagens normais.
[ ] TI consegue restaurar.
[ ] Ordem restaurada volta para as listagens normais.

## Duplicidade
[ ] Não é possível abrir nova solicitação se já existe PENDENTE.
[ ] Não é possível abrir nova solicitação se já existe APROVADA aguardando execução.

## Auditoria
[ ] SOLICITACAO_CRIADA registrada.
[ ] SOLICITACAO_APROVADA registrada.
[ ] SOLICITACAO_REPROVADA registrada.
[ ] EXCLUSAO_EXECUTADA registrada.
[ ] EXCLUSAO_DIRETA_TI registrada.
[ ] ORDEM_RESTAURADA registrada.
[ ] IP e User-Agent são registrados quando disponíveis.

## Regressão
[ ] Abrir OS.
[ ] Abrir OL.
[ ] Editar OS/OL.
[ ] Fotos permanecem funcionando.
[ ] Materiais permanecem funcionando.
[ ] Relatórios ignoram deleted_at IS NOT NULL.
[ ] Dashboard ignora deleted_at IS NOT NULL.
[ ] Cron/preventivas não tentam atuar em ordem excluída.
[ ] Histórico permanece disponível para TI/auditoria.
