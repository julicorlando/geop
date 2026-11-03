<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

geopRequireLogin();
geopCheckCsrf();

if (!geopIsTI()) {
    http_response_code(403);
    exit('Apenas TI pode restaurar ordens.');
}

$uid = geopCurrentUserId();
$ordemId = (int)($_POST['ordem_id'] ?? 0);
$tipo = geopValidateOrderType((string)($_POST['ordem_tipo'] ?? ''));

if ($ordemId <= 0) {
    http_response_code(422);
    exit('Ordem inválida.');
}

try {
    $pdo->beginTransaction();
    geopRestoreOrder($pdo, $ordemId);
    geopAudit($pdo, null, $tipo, $ordemId, $uid, 'ORDEM_RESTAURADA', 'Ordem restaurada da lixeira.');
    $pdo->commit();
    header('Location: listar.php?ok=restaurada');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    exit($e->getMessage());
}
