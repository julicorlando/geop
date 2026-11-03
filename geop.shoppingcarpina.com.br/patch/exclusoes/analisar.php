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
$id = (int)($_POST['solicitacao_id'] ?? 0);
$decisao = strtoupper(trim((string)($_POST['decisao'] ?? '')));
$obs = trim((string)($_POST['observacao'] ?? ''));

if (!in_array($decisao, ['APROVAR', 'REPROVAR'], true)) {
    http_response_code(422);
    exit('Decisão inválida.');
}

try {
    $pdo->beginTransaction();

    $req = geopFindDeleteRequest($pdo, $id);

    if ($req['status'] !== 'PENDENTE') {
        throw new RuntimeException('A solicitação não está pendente.');
    }

    if ((int)$req['solicitado_por'] === $uid) {
        throw new RuntimeException('O solicitante não pode aprovar/reprovar a própria solicitação.');
    }

    $status = $decisao === 'APROVAR' ? 'APROVADA' : 'REPROVADA';

    $st = $pdo->prepare(
        "UPDATE ordem_solicitacoes_exclusao
         SET status = :status,
             analisado_por = :uid,
             analisado_em = NOW(),
             observacao_analise = :obs
         WHERE id = :id AND status = 'PENDENTE'"
    );
    $st->execute([
        ':status' => $status,
        ':uid' => $uid,
        ':obs' => ($obs !== '' ? $obs : null),
        ':id' => $id,
    ]);

    geopAudit(
        $pdo,
        $id,
        (string)$req['ordem_tipo'],
        (int)$req['ordem_id'],
        $uid,
        $status === 'APROVADA' ? 'SOLICITACAO_APROVADA' : 'SOLICITACAO_REPROVADA',
        $obs !== '' ? $obs : null
    );

    /*
     * Mesmo quando TI aprova, este endpoint NÃO apaga automaticamente.
     * A execução fica explícita no botão "Executar exclusão".
     */
    $pdo->commit();
    header('Location: listar.php?ok=' . strtolower($status));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(422);
    exit($e->getMessage());
}
