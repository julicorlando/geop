# Integração com o GEOP atual

## 1. Pré-requisitos
O manual do GEOP da edição 10/09/2026 indica:
- PHP 8.1+;
- PDO MySQL;
- MySQL/MariaDB;
- RBAC;
- auditoria;
- atualização com backup e homologação.

Este patch segue essas premissas.

## 2. Banco
Execute `sql/001_exclusao_ordens.sql`.

Depois confirme o nome da tabela principal de ordens.
Se for `ordens`, adicione:
- deleted_at
- deleted_by
- delete_request_id

Antes dos ALTERs, confira se as colunas já existem.

## 3. Filtro obrigatório nas consultas de ordens
Em TODAS as listagens normais, adicione:

    AND deleted_at IS NULL

Exemplo:

    SELECT * FROM ordens
    WHERE deleted_at IS NULL
    ORDER BY id DESC

Isso faz a "ordem excluída" sumir do sistema sem destruir o histórico.

## 4. Botão no detalhe da OS/OL

### Para Gestor
Mostrar:
    Solicitar exclusão

Form POST para:
    /exclusoes/solicitar.php

Campos:
    csrf_token
    ordem_tipo = OS ou OL
    ordem_id
    motivo

Exemplo:

<form method="post" action="/exclusoes/solicitar.php">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(geopCsrfToken()) ?>">
  <input type="hidden" name="ordem_tipo" value="OS">
  <input type="hidden" name="ordem_id" value="<?= (int)$ordem['id'] ?>">
  <textarea name="motivo" required minlength="5"></textarea>
  <button type="submit">Solicitar exclusão</button>
</form>

### Para TI
Pode usar o mesmo formulário adicionando:
    excluir_direto = 1

Isso envia diretamente para lixeira.

## 5. Menu
Adicionar para Gestor/Gestor Síndico/TI:

    Gestão > Solicitações de exclusão

Apontando para:
    /exclusoes/listar.php

## 6. Regra anti-autoaprovação
Já implementada no endpoint:
    analisar.php

O usuário cujo ID é `solicitado_por` não pode analisar a própria solicitação.

## 7. Lixeira
O patch inclui restauração por endpoint `restaurar.php`.

Crie uma tela TI consultando:

    SELECT * FROM ordens
    WHERE deleted_at IS NOT NULL
    ORDER BY deleted_at DESC

e POST para `restaurar.php`.

## 8. Exclusão física
Não está habilitada neste patch.
Recomendação: manter a ordem em soft delete para preservar histórico,
anexos, materiais, relatórios e auditoria.

Se futuramente desejar exclusão permanente, implemente-a como operação
separada, apenas TI, com confirmação reforçada e verificação de dependências.

## 9. Auditoria nativa
Este patch grava sua própria tabela `ordem_exclusao_auditoria`.
Se o GEOP tiver helper/função nativa de auditoria, chame-o também dentro
de `geopAudit()` para que as ações apareçam no painel já existente.
