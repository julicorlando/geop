<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/cron.php';
cron_authorize();
$command = $argv[1] ?? 'all';
$started = date('Y-m-d H:i:s');
$summary = [];
try {
    if ($command === 'all' || $command === 'preventivas') $summary['preventivas'] = cron_preventivas($pdo);
    if ($command === 'all' || $command === 'cftv') $summary['cftv_finalizados'] = cron_cftv($pdo);
    echo json_encode(['ok'=>true,'started'=>$started,'summary'=>$summary], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Throwable $e) {
    error_log('[GEOP cron] ' . $e->getMessage());
    fwrite(STDERR, json_encode(['ok'=>false,'error'=>'Falha no cron.']) . PHP_EOL);
    exit(1);
}
