<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"ok":false}'; exit; }
$authorization = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
if (!preg_match('/^Bearer\s+([A-Za-z0-9_-]{32,})$/', $authorization, $match)) { http_response_code(401); echo '{"ok":false}'; exit; }
$hash = hash('sha256', $match[1]);
$st = $pdo->prepare("SELECT id FROM dispositivos_iot WHERE api_key_hash=? AND ativo=1 LIMIT 1");
$st->execute([$hash]);
$deviceId = (int)$st->fetchColumn();
if (!$deviceId) { http_response_code(401); echo '{"ok":false}'; exit; }
$payload = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($payload) || empty($payload['tipo'])) { http_response_code(422); echo '{"ok":false}'; exit; }
$pdo->beginTransaction();
try {
    $pdo->prepare("INSERT INTO iot_leituras(dispositivo_id,tipo,valor_numero,valor_texto,payload)
        VALUES(?,?,?,?,?)")->execute([$deviceId,substr((string)$payload['tipo'],0,80),$payload['valor']??null,$payload['texto']??null,json_encode($payload,JSON_UNESCAPED_UNICODE)]);
    $pdo->prepare("UPDATE dispositivos_iot SET ultima_comunicacao=NOW(),status='ONLINE' WHERE id=?")->execute([$deviceId]);
    $pdo->commit();
    echo '{"ok":true}';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[GEOP IoT] '.$e->getMessage()); http_response_code(500); echo '{"ok":false}';
}
