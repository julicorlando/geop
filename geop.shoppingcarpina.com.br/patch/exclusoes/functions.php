<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function geopAudit(PDO $pdo, ?int $solicitacaoId, string $tipo, int $ordemId, int $usuarioId, string $acao, ?string $detalhe = null): void
{
    $sql = "INSERT INTO ordem_exclusao_auditoria
            (solicitacao_id, ordem_tipo, ordem_id, usuario_id, acao, detalhe, ip, user_agent)
            VALUES (:sid, :tipo, :oid, :uid, :acao, :detalhe, :ip, :ua)";
    $st = $pdo->prepare($sql);
    $st->execute([
        ':sid' => $solicitacaoId,
        ':tipo' => $tipo,
        ':oid' => $ordemId,
        ':uid' => $usuarioId,
        ':acao' => $acao,
        ':detalhe' => $detalhe,
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ':ua' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
    ]);
}

function geopValidateOrderType(string $tipo): string
{
    $tipo = strtoupper(trim($tipo));
    if (!in_array($tipo, ['OS', 'OL'], true)) {
        throw new InvalidArgumentException('Tipo de ordem inválido.');
    }
    return $tipo;
}

function geopFindDeleteRequest(PDO $pdo, int $id): array
{
    $st = $pdo->prepare("SELECT * FROM ordem_solicitacoes_exclusao WHERE id = :id LIMIT 1");
    $st->execute([':id' => $id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('Solicitação não encontrada.');
    }
    return $row;
}

function geopHasPendingDeleteRequest(PDO $pdo, string $tipo, int $ordemId): bool
{
    $st = $pdo->prepare(
        "SELECT 1 FROM ordem_solicitacoes_exclusao
         WHERE ordem_tipo = :tipo
           AND ordem_id = :oid
           AND status IN ('PENDENTE','APROVADA')
         LIMIT 1"
    );
    $st->execute([':tipo' => $tipo, ':oid' => $ordemId]);
    return (bool)$st->fetchColumn();
}

function geopSoftDeleteOrder(PDO $pdo, int $ordemId, int $userId, ?int $requestId): void
{
    $table = GEOP_ORDENS_TABLE;
    $pk = GEOP_ORDENS_PK;

    // Identificadores vêm de constantes locais e não de input do usuário.
    $sql = "UPDATE `{$table}`
            SET deleted_at = NOW(),
                deleted_by = :uid,
                delete_request_id = :rid
            WHERE `{$pk}` = :oid
              AND deleted_at IS NULL";

    $st = $pdo->prepare($sql);
    $st->execute([
        ':uid' => $userId,
        ':rid' => $requestId,
        ':oid' => $ordemId,
    ]);

    if ($st->rowCount() < 1) {
        throw new RuntimeException('A ordem não foi localizada ou já está na lixeira.');
    }
}

function geopRestoreOrder(PDO $pdo, int $ordemId): void
{
    $table = GEOP_ORDENS_TABLE;
    $pk = GEOP_ORDENS_PK;

    $sql = "UPDATE `{$table}`
            SET deleted_at = NULL,
                deleted_by = NULL,
                delete_request_id = NULL
            WHERE `{$pk}` = :oid
              AND deleted_at IS NOT NULL";

    $st = $pdo->prepare($sql);
    $st->execute([':oid' => $ordemId]);

    if ($st->rowCount() < 1) {
        throw new RuntimeException('A ordem não está na lixeira ou não foi localizada.');
    }
}
