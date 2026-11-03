# GEOP — Patch de Aprovação de Exclusão de Ordens
Versão do patch: 1.0 — 22/09/2026
Baseline funcional: GEOP edição 10/09/2026

## Objetivo
Adicionar fluxo seguro para exclusão de OS/OL:

- TI pode excluir diretamente (soft delete / lixeira).
- Gestor pode SOLICITAR exclusão.
- O mesmo Gestor que solicitou NÃO pode aprovar a própria solicitação.
- Outro Gestor ou TI pode aprovar/reprovar.
- Após aprovação de Gestor, a exclusão fica aguardando execução pelo TI.
- TI pode aprovar e executar a exclusão.
- Toda ação fica registrada na tabela própria de histórico.
- A exclusão padrão é lógica (lixeira), permitindo restauração pelo TI.
- Exclusão física definitiva deve ser feita apenas pelo TI e somente depois da lixeira.

## Importante
Este pacote foi preparado de forma isolada porque o ZIP do código-fonte da edição
10/09/2026 não está disponível no contexto atual. Portanto, ele NÃO sobrescreve
arquivos do GEOP automaticamente.

Faça backup completo antes de aplicar:
1. Banco de dados.
2. public_html/geop.shoppingcarpina.com.br (ou raiz equivalente).
3. .env/configuração.
4. uploads/anexos.

## Estrutura
- sql/001_exclusao_ordens.sql
- patch/exclusoes/config.php
- patch/exclusoes/functions.php
- patch/exclusoes/solicitar.php
- patch/exclusoes/analisar.php
- patch/exclusoes/executar.php
- patch/exclusoes/restaurar.php
- patch/exclusoes/listar.php
- docs/INTEGRACAO.md
- docs/TESTES.md

## Instalação resumida
1. Execute `sql/001_exclusao_ordens.sql`.
2. Copie a pasta `patch/exclusoes` para uma área protegida do GEOP, por exemplo:
   `public/exclusoes/` ou equivalente.
3. Edite `patch/exclusoes/config.php` para apontar para o bootstrap/autenticação
   real do GEOP e definir os nomes das tabelas/colunas.
4. Integre os botões conforme `docs/INTEGRACAO.md`.
5. Teste em homologação antes de publicar em produção.

## Regra de estados
PENDENTE -> APROVADA -> EXECUTADA
PENDENTE -> REPROVADA
PENDENTE -> CANCELADA

A aprovação nunca remove a ordem automaticamente quando feita por Gestor.
Apenas TI executa a exclusão.
