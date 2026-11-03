<?php
$root = dirname(__DIR__);
$files = [
    $root . '/includes/custo_finalizacao.php',
    $root . '/public/ordem_detalhe.php',
    $root . '/public/atualizar_custo_finalizacao_v7_6.php',
];
$fail = [];
foreach ($files as $file) {
    if (!is_file($file)) $fail[] = 'Arquivo ausente: ' . $file;
}
$detail = file_get_contents($root . '/public/ordem_detalhe.php');
foreach ([
    'modalCustoFinalizacao',
    'custo_finalizacao_validar_payload',
    'custo_finalizacao_aplicar',
    'custo_finalizacao_confirmado',
    'CONFIRMAR_CUSTO_FINALIZACAO',
] as $needle) {
    if (strpos($detail, $needle) === false) $fail[] = 'Marcador ausente: ' . $needle;
}
if ($fail) {
    fwrite(STDERR, implode(PHP_EOL, $fail) . PHP_EOL);
    exit(1);
}
echo "GEOP V7.6 static: OK\n";
