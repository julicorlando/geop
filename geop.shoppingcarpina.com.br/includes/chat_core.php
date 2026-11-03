<?php
// includes/chat_core.php
// Funções do chat interno GEOP. Pode ser incluído pelo chat_api.php.

function chat_table_exists(PDO $pdo, string $table): bool {
    try {
        $st = $pdo->prepare("SHOW TABLES LIKE ?");
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function chat_column_exists(PDO $pdo, string $table, string $column): bool {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $st->execute([$column]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function chat_ensure_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        $pdo->query("SELECT id,usuario_1_id,usuario_2_id,tipo,nome,criador_id,atualizado_em FROM chat_conversas LIMIT 1");
        $pdo->query("SELECT id,conversa_id,remetente_id,destinatario_id,mensagem,lida_em,editada_em,excluida_em,anexo_url,anexo_nome,anexo_mime,anexo_tamanho,visualizacao_unica,resposta_a_id,encaminhada_de_id,criado_em FROM chat_mensagens LIMIT 1");
        $pdo->query("SELECT conversa_id,usuario_id FROM chat_grupo_participantes LIMIT 1");
        $pdo->query("SELECT mensagem_id,usuario_id,lida_em FROM chat_mensagem_leituras LIMIT 1");
        $pdo->query("SELECT mensagem_id,usuario_id,visualizada_em FROM chat_mensagem_visualizacoes LIMIT 1");
    } catch (Throwable $e) {
        throw new RuntimeException('As tabelas do chat ainda não foram instaladas.', 0, $e);
    }
}

function chat_is_enabled(PDO $pdo): bool {
    try {
        $st = $pdo->prepare("SELECT valor FROM configuracoes_sistema WHERE chave='chat_habilitado' LIMIT 1");
        $st->execute();
        $value = $st->fetchColumn();
        return $value === false || in_array(strtolower(trim((string)$value)), ['1','true','sim','on'], true);
    } catch (Throwable $e) {
        error_log('[GEOP chat config] ' . $e->getMessage());
        return true;
    }
}

function chat_pair_ids(int $a, int $b): array {
    return $a < $b ? [$a, $b] : [$b, $a];
}

function chat_find_conversa(PDO $pdo, int $userA, int $userB): int {
    [$u1, $u2] = chat_pair_ids($userA, $userB);
    $st = $pdo->prepare("SELECT id FROM chat_conversas WHERE usuario_1_id=? AND usuario_2_id=? AND tipo='DIRETA' LIMIT 1");
    $st->execute([$u1,$u2]);
    return (int)$st->fetchColumn();
}

function chat_get_or_create_conversa(PDO $pdo, int $userA, int $userB): int {
    [$u1, $u2] = chat_pair_ids($userA, $userB);

    $id = chat_find_conversa($pdo, $userA, $userB);
    if ($id > 0) return $id;

    try {
        $st = $pdo->prepare("INSERT INTO chat_conversas (usuario_1_id, usuario_2_id) VALUES (?, ?)");
        $st->execute([$u1, $u2]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? null) !== 1062) throw $e;
        $st = $pdo->prepare("SELECT id FROM chat_conversas WHERE usuario_1_id=? AND usuario_2_id=? AND tipo='DIRETA' LIMIT 1");
        $st->execute([$u1,$u2]);
        return (int)$st->fetchColumn();
    }
}
