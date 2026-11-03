<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$layout = file_get_contents($root . '/includes/layout.php');
$cftv = file_get_contents($root . '/public/cftv.php');

$requiredMenuLabels = [
    "menuDropdown('Ordens'",
    "menuDropdown('Limpeza'",
    "menuDropdown('CFTV'",
    "menuDropdown('Operações'",
    "menuDropdown('Admin'",
    "navLink('Relatório geral'",
    "'Visualizar ordens'",
    "'Nova OS'",
    "'Serviço essencial'",
    "'Câmeras OFF'",
    "'Ocorrências'",
    "'Perfis e permissões'",
];

foreach ($requiredMenuLabels as $needle) {
    if (strpos($layout, $needle) === false) {
        fwrite(STDERR, "Menu ausente: {$needle}\n");
        exit(1);
    }
}

foreach ([
    "\$filtroDestaque === 'cameras_off'",
    "r.cameras_offline > 0",
    "\$filtroDestaque === 'ocorrencias'",
    "EXISTS (SELECT 1 FROM cftv_ocorrencias",
] as $needle) {
    if (strpos($cftv, $needle) === false) {
        fwrite(STDERR, "Filtro CFTV ausente: {$needle}\n");
        exit(1);
    }
}

echo "Menu Modular V4 static test: OK\n";
