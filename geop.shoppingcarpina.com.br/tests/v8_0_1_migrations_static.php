<?php
$root = dirname(__DIR__);
$g = file_get_contents($root . '/includes/geop_v8.php');
$u = file_get_contents($root . '/public/atualizacoes.php');
$need = [
    'function geop_v8_migration_schema',
    'function geop_v8_list_migrations',
    "'version'",
    "'versao'",
    "'applied_at'",
    "'aplicada_em'",
];
$fail = [];
foreach ($need as $n) if (strpos($g, $n) === false) $fail[] = $n;
if (strpos($u, 'geop_v8_list_migrations') === false) $fail[] = 'atualizacoes usa helper';
if ($fail) {
    fwrite(STDERR, "Faltando: " . implode(', ', $fail) . PHP_EOL);
    exit(1);
}
echo "GEOP V8.0.1 migration compatibility static: OK\n";
