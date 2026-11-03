# GEOP — Upgrade V5: OL (Ordem de Limpeza)

## Objetivo

Adicionar ao módulo Limpeza um fluxo próprio de Ordens de Limpeza, separado das OS técnicas, utilizando todos os colaboradores ativos do organograma — inclusive supervisores.

## Fluxo

1. Abertura da OL.
2. Definição do ambiente/local, prioridade, data e prazo.
3. Seleção de vários funcionários e de um responsável principal.
4. Definição opcional de supervisor responsável.
5. Início e execução com equipe real, checklist, horários, observações, insumos e fotos.
6. Envio para validação.
7. Aprovação e encerramento ou solicitação de correção.
8. Quando houver ambiente QR vinculado, criação automática do registro validado no histórico público do ambiente.

## Acesso

- TI
- Gestor
- Gestor/Síndico
- Supervisor de Limpeza

Os funcionários do organograma podem ser atribuídos à OL mesmo sem usuário de acesso ao GEOP.

## Arquivos principais

- `includes/ordens_limpeza.php`
- `public/ol.php`
- `public/ol_form.php`
- `public/ol_detalhe.php`
- `public/ol_relatorios.php`
- `public/ol_imprimir.php`
- `sql/ATUALIZACAO_MODULO_OL_V5.sql`

## Instalação sobre a V3/V4

1. Faça backup dos arquivos e do banco.
2. Copie o conteúdo do ZIP sobre o GEOP, preservando o `.env`.
3. Importe `sql/ATUALIZACAO_MODULO_OL_V5.sql`.
4. Crie o diretório `public/uploads/ol` e conceda permissão de escrita ao PHP.
5. Saia e entre novamente no sistema.
6. Acesse `Limpeza > OL — Ordens de Limpeza`.

## Instalação do zero do módulo Limpeza

Use `sql/INSTALACAO_COMPLETA_MODULO_LIMPEZA_V5.sql`.

## Segurança e auditoria

- CSRF em todas as operações de escrita.
- Permissões RBAC específicas para OL.
- Bloqueio de autovalidação quando o usuário participou da execução, com exceção de override do TI.
- Histórico de mudança de status.
- Registro na auditoria geral do GEOP.
- Validação de arquivos e limite de fotos.
