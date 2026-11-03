<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

geopRequireLogin();
geopCheckCsrf();

if (!geopIsTI()) {
    http_response_code(403);
    exit('Apenas TI pode executar a exclusão.');
}

$uid = geopCurrentUserId();
$id = (int)($_POST['solicitacao_id'] ?? 0);

try {
    $pdo->beginTransaction();

    $req = geopFindDeleteRequest($pdo, $id);

    if ($req['status'] !== 'APROVADA') {
        throw new RuntimeException('A solicitação precisa estar APROVADA.');
    }

    geopSoftDeleteOrder($pdo, (int)$req['ordem_id'], $uid, $id);

    $st = $pdo->prepare(
        "UPDATE ordem_solicitacoes_exclusao
         SET status = 'EXECUTADA',
             excluido_por = :uid,
             excluido_em = NOW()
         WHERE id = :id AND status = 'APROVADA'"
    );
    $st->execute([':uid' => $uid, ':id' => $id]);

    geopAudit(
        $pdo,
        $id,
        (string)$req['ordem_tipo'],
        (int)$req['ordem_id'],
        $uid,
        'EXCLUSAO_EXECUTADA',
        'Ordem enviada para a lixeira (soft delete).'
    );

    $pdo->commit();
    header('Location: listar.php?ok=executada');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    exit($e->getMessage());
}
