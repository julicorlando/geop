<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$directory = dirname(__DIR__) . '/sql/migrations';
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
 version VARCHAR(64) PRIMARY KEY, applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$lock = $pdo->query("SELECT GET_LOCK('geop_migrations', 10)")->fetchColumn();
if ((int) $lock !== 1) throw new RuntimeException('Outra migration está em execução.');
try {
    foreach (glob($directory . '/*.sql') ?: [] as $file) {
        $version = pathinfo($file, PATHINFO_FILENAME);
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version=?');
        $check->execute([$version]);
        if ($check->fetchColumn()) { echo "skip {$version}\n"; continue; }
        $sql = file_get_contents($file);
        if ($sql === false) throw new RuntimeException("Falha ao ler {$file}");
        echo "apply {$version} (importe este SQL pelo cliente MySQL, pois contém DELIMITER)\n";
        if (str_contains($sql, 'DELIMITER')) continue;
        $pdo->exec($sql);
    }
} finally {
    $pdo->query("SELECT RELEASE_LOCK('geop_migrations')");
}
