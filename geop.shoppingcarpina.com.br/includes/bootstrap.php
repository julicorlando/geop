<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

date_default_timezone_set(env('APP_TIMEZONE', 'America/Recife') ?: 'America/Recife');
ini_set('display_errors', env_bool('APP_DEBUG') ? '1' : '0');
ini_set('log_errors', '1');

$privateRoot = env('PRIVATE_PATH', dirname(__DIR__) . '/var') ?: dirname(__DIR__) . '/var';
foreach ([$privateRoot, $privateRoot . '/log', $privateRoot . '/sessions', $privateRoot . '/uploads'] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Diretório privado indisponível.');
    }
}
ini_set('error_log', $privateRoot . '/log/geop.log');
ini_set('session.save_path', $privateRoot . '/sessions');

$https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (session_status() === PHP_SESSION_NONE) {
    session_name('GEOPSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (!session_start()) {
        throw new RuntimeException('Não foi possível iniciar uma sessão segura.');
    }
}

$requestId = preg_replace('/[^a-zA-Z0-9-]/', '', (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
if ($requestId === '') {
    $requestId = bin2hex(random_bytes(12));
}
define('GEOP_REQUEST_ID', substr($requestId, 0, 64));

if (!headers_sent()) {
    header('X-Request-Id: ' . GEOP_REQUEST_ID);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(self), geolocation=(self), microphone=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; font-src 'self' data: https://cdn.jsdelivr.net; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
}

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf('[%s] %s in %s:%d', GEOP_REQUEST_ID, $exception->getMessage(), $exception->getFile(), $exception->getLine()));
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Erro</title><body><h1>Não foi possível concluir a operação</h1><p>Tente novamente. Referência: '
        . htmlspecialchars(GEOP_REQUEST_ID, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
});

require_once __DIR__ . '/../config/database.php';
