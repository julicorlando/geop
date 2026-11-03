# Upgrade V6 — Migração de OS para OL

## Objetivo

Converter uma demanda de limpeza cadastrada por engano como Ordem de Serviço em Ordem de Limpeza, sem perda de rastreabilidade.

## Fluxo

1. O Gestor ou TI abre a OS.
2. Clica em **Migrar para OL**.
3. O formulário recebe automaticamente título, descrição, prioridade, local, solicitante e prazo.
4. O usuário define ambiente, equipe, responsável principal, supervisor e checklist.
5. O sistema cria a OL e registra o vínculo único entre OS e OL.
6. Opcionalmente, a OS original é encerrada para evitar execução duplicada.

## Dados preservados

- número e identidade da OS de origem;
- solicitante original;
- local;
- prioridade;
- descrição e prazo;
- imagens anexadas;
- histórico nos dois módulos;
- responsável pela migração e status anterior da OS.

## Segurança

- uma OS só pode originar uma OL;
- uma OL só pode ter uma OS de origem;
- a migração exige `ol.migrar_os`, `ol.criar` e permissão de gerenciamento da OS;
- a operação usa transação e restrições únicas no banco;
- a OS e a OL mantêm links recíprocos.
