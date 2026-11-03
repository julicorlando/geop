<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

geopRequireLogin();
geopCheckCsrf();

if (!geopIsGestor() && !geopIsTI()) {
    http_response_code(403);
    exit('Sem permissão.');
}

$uid = geopCurrentUserId();
$tipo = geopValidateOrderType((string)($_POST['ordem_tipo'] ?? ''));
$ordemId = (int)($_POST['ordem_id'] ?? 0);
$motivo = trim((string)($_POST['motivo'] ?? ''));

if ($ordemId <= 0 || mb_strlen($motivo) < 5) {
    http_response_code(422);
    exit('Informe a ordem e um motivo válido.');
}

try {
    $pdo->beginTransaction();

    if (geopIsTI() && !empty($_POST['excluir_direto'])) {
        geopSoftDeleteOrder($pdo, $ordemId, $uid, null);
        geopAudit($pdo, null, $tipo, $ordemId, $uid, 'EXCLUSAO_DIRETA_TI', $motivo);
        $pdo->commit();
        header('Location: listar.php?ok=excluida');
        exit;
    }

    if (geopHasPendingDeleteRequest($pdo, $tipo, $ordemId)) {
        throw new RuntimeException('Já existe solicitação de exclusão pendente/aprovada para esta ordem.');
    }

    $st = $pdo->prepare(
        "INSERT INTO ordem_solicitacoes_exclusao
         (ordem_tipo, ordem_id, solicitado_por, motivo, status)
         VALUES (:tipo, :oid, :uid, :motivo, 'PENDENTE')"
    );
    $st->execute([
        ':tipo' => $tipo,
        ':oid' => $ordemId,
        ':uid' => $uid,
        ':motivo' => $motivo,
    ]);
    $id = (int)$pdo->lastInsertId();

    geopAudit($pdo, $id, $tipo, $ordemId, $uid, 'SOLICITACAO_CRIADA', $motivo);

    $pdo->commit();
    header('Location: listar.php?ok=solicitada');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    exit($e->getMessage());
}
