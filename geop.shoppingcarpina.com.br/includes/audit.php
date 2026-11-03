<?php
declare(strict_types=1);

function audit_sanitize(array $values): array
{
    $blocked = ['senha', 'password', 'token', 'cookie', 'secret', 'smtp', 'authorization'];
    foreach ($values as $key => $value) {
        foreach ($blocked as $needle) {
            if (stripos((string) $key, $needle) !== false) {
                $values[$key] = '[REDACTED]';
                continue 2;
            }
        }
    }
    return $values;
}

function auditar(PDO $pdo, string $action, string $entity, ?int $entityId = null, array $before = [], array $after = [], string $origin = 'WEB'): void
{
    $sql = 'INSERT INTO auditoria_eventos
        (usuario_id, acao, entidade, entidade_id, valores_anteriores, valores_posteriores, ip, origem, request_id, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())';
    try {
        $pdo->prepare($sql)->execute([
            (int) ($_SESSION['user']['id'] ?? 0) ?: null,
            substr($action, 0, 80),
            substr($entity, 0, 80),
            $entityId,
            json_encode(audit_sanitize($before), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode(audit_sanitize($after), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            substr($origin, 0, 30),
            defined('GEOP_REQUEST_ID') ? GEOP_REQUEST_ID : null,
        ]);
    } catch (Throwable $exception) {
        error_log('[GEOP audit] ' . $exception->getMessage());
    }
}
