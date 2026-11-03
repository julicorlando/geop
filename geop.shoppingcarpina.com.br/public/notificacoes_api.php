<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';

require_login();
$user = current_user();
if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('X-Content-Type-Options: nosniff');
}

$after = max(0, (int)($_GET['after'] ?? 0));
$limit = min(30, max(1, (int)($_GET['limit'] ?? 20)));
$userId = (int)($user['id'] ?? 0);

// Libera o bloqueio da sessão para não atrasar outras páginas durante o polling.
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();

gestao_ensure_schema($pdo);
try {
    $perfil = strtoupper((string)($user['perfil'] ?? gestao_usuario_perfil($pdo, $userId)));
    $fetchLimit = min(300, max($limit * 8, 80));

    $st = $pdo->prepare("SELECT id,tipo,titulo,mensagem,url,created_at,lida_em
        FROM notificacoes
        WHERE usuario_id=? AND id>? AND lida_em IS NULL
        ORDER BY id ASC LIMIT ?");
    $st->bindValue(1, $userId, PDO::PARAM_INT);
    $st->bindValue(2, $after, PDO::PARAM_INT);
    $st->bindValue(3, $fetchLimit, PDO::PARAM_INT);
    $st->execute();

    $items = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $item) {
        if (!gestao_notificacao_pertinente_perfil((string)($item['tipo'] ?? ''), $perfil)) continue;
        $item['id'] = (int)$item['id'];
        $item['url'] = trim((string)($item['url'] ?? ''));
        unset($item['lida_em']);
        $items[] = $item;
        if (count($items) >= $limit) break;
    }

    $summary = gestao_notificacoes_resumo_usuario($pdo, $userId);
    $maxId = max($after, (int)($summary['max_id'] ?? 0));
    $unread = (int)($summary['unread'] ?? 0);

    echo json_encode([
        'ok' => true,
        'items' => $items,
        'max_id' => $maxId,
        'unread' => $unread,
        'server_time' => date(DATE_ATOM),
        'perfil' => $perfil,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[GEOP notificações API] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'Não foi possível consultar as notificações.'], JSON_UNESCAPED_UNICODE);
}
