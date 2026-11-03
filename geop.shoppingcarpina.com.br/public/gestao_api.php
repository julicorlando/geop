<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

if (($_GET['action'] ?? '') !== 'recomendar_agentes') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Ação inválida']);
    exit;
}

$categoriaId = (int)($_GET['categoria_id'] ?? 0);
$localId = (int)($_GET['local_id'] ?? 0);
$agentes = gestao_recomendar_agentes($pdo, $categoriaId, $localId, 8);
echo json_encode(['ok' => true, 'agentes' => $agentes], JSON_UNESCAPED_UNICODE);
