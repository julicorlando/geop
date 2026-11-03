<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [
    'includes/cftv.php' => [
        'cftv_solicitacoes_acesso',
        'email VARCHAR(190)',
        'cftv_email_status_solicitacao',
    ],
    'public/solicitar_acesso.php' => [
        "name=\"email\"",
        'FILTER_VALIDATE_EMAIL',
        "'ACESSO_PENDENTE'",
        'Nova solicitação de liberação',
    ],
    'public/cftv.php' => [
        "'ACESSO_APROVADO'",
        'Liberação aprovada — comunicar CFTV',
        'liberacoes_aprovadas',
    ],
    'public/notificacoes_api.php' => [
        'usuario_id=? AND id>? AND lida_em IS NULL',
        "'unread'",
    ],
    'public/assets/notificacoes-realtime.js' => [
        'Notification.requestPermission',
        'navigator.vibrate',
        'showNotification',
        'ACESSO_PENDENTE',
        'ACESSO_APROVADO',
    ],
    'public/sw.js' => [
        'notificationclick',
        'clients.openWindow',
    ],
];

$errors = [];
foreach ($checks as $relative => $needles) {
    $path = $root . '/' . $relative;
    if (!is_file($path)) {
        $errors[] = "Arquivo ausente: {$relative}";
        continue;
    }
    $content = (string)file_get_contents($path);
    foreach ($needles as $needle) {
        if (!str_contains($content, $needle)) {
            $errors[] = "Texto ausente em {$relative}: {$needle}";
        }
    }
}

if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo "OK: estrutura de notificações e e-mail validada.\n";
