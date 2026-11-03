<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/env.php';

$host = env('DB_HOST', '127.0.0.1');
$port = env_int('DB_PORT', 3306);
$name = env_required('DB_NAME');
$user = env_required('DB_USER');
$pass = env_required('DB_PASS');
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_STRINGIFY_FETCHES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("SET time_zone = '-03:00'");
} catch (Throwable $exception) {
    error_log('[GEOP] database connection failed: ' . $exception->getMessage());
    throw new RuntimeException('Não foi possível conectar ao banco de dados.');
}
