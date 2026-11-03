# Upgrade V6.1 — Migração OS → OL exclusiva do TI

## Regra aplicada

A conversão de uma Ordem de Serviço em Ordem de Limpeza passa a ser uma operação administrativa exclusiva do departamento de TI.

## Proteções

1. O botão de migração só é renderizado quando o perfil atual é `TI`.
2. A tela `ol_form.php?origem_os_id=...` valida novamente o perfil no servidor.
3. Uma requisição POST manual também é bloqueada pela mesma validação.
4. O banco mantém a permissão `ol.migrar_os` apenas para o perfil TI.
5. A regra não altera o acesso normal de Gestores e Supervisores às Ordens de Limpeza.

## Atualização de banco

Para instalações que já receberam a V6, execute:

`sql/ATUALIZACAO_RESTRICAO_MIGRACAO_TI_V6_1.sql`
