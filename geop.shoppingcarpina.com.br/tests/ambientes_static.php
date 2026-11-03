<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$required = [
    'includes/ambientes_qr.php',
    'public/ambientes.php',
    'public/ambiente_publico.php',
    'public/ambiente_registro.php',
    'public/ambiente_validar.php',
    'public/ambiente_relatorios.php',
    'public/ambiente_qr_imprimir.php',
    'sql/migrations/005_ambientes_qr_limpeza.sql',
];
$errors = [];
foreach ($required as $file) {
    if (!is_file($root . '/' . $file)) $errors[] = 'Arquivo ausente: ' . $file;
}
$sql = file_get_contents($root . '/sql/migrations/005_ambientes_qr_limpeza.sql') ?: '';
foreach (['ambientes_qr','ambiente_acoes','ambiente_registros','ambiente_registro_fotos','ASG','SUPERVISOR_ASG'] as $needle) {
    if (!str_contains($sql, $needle)) $errors[] = 'Migração não contém: ' . $needle;
}
$htaccess = file_get_contents($root . '/public/.htaccess') ?: '';
if (!str_contains($htaccess, 'ambiente_publico.php?t=$1')) $errors[] = 'Rota pública do QR não configurada.';
if ($errors) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}
echo "Ambientes static test: OK\n";
