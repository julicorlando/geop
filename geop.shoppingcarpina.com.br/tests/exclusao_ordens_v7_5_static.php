<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$fail = [];

function mustContain(string $file, array $needles, array &$fail): void {
    $body = @file_get_contents($file);
    if ($body === false) {
        $fail[] = "Arquivo ausente: $file";
        return;
    }
    foreach ($needles as $needle) {
        if (strpos($body, $needle) === false) {
            $fail[] = basename($file) . " sem: " . $needle;
        }
    }
}

mustContain($root . '/includes/exclusao_ordens.php', [
    'function exclusao_instalar_schema',
    'function exclusao_solicitar',
    'function exclusao_analisar',
    'function exclusao_executar',
    'function exclusao_excluir_direto',
    'function exclusao_restaurar',
    'function exclusao_render_card',
    "O solicitante não pode aprovar ou reprovar a própria solicitação.",
], $fail);

mustContain($root . '/public/ordem_detalhe.php', [
    'exclusao_render_card($pdo, \'ORDEM_SERVICO\'',
], $fail);

mustContain($root . '/public/ol_detalhe.php', [
    'exclusao_render_card($pdo, \'LIMPEZA_ORDEM\'',
], $fail);

mustContain($root . '/includes/layout.php', [
    "'Solicitações de exclusão'",
    "'Lixeira de ordens'",
], $fail);

mustContain($root . '/public/ordens.php', [
    'exclusao_sql_nao_excluida($pdo, \'ordens_servico\', \'os\')',
], $fail);

mustContain($root . '/public/ol.php', [
    'exclusao_sql_nao_excluida($pdo,\'limpeza_ordens\',\'ol\')',
], $fail);

mustContain($root . '/public/atualizar_exclusao_ordens_v7_5.php', [
    'exclusao_instalar_schema($pdo)',
    "require_role('TI')",
], $fail);

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    if (!in_array(strtolower($file->getExtension()), ['php','sql'], true)) continue;
    $body = file_get_contents($file->getPathname());
    if (preg_match('/DELETE\s+FROM\s+(ordens_servico|limpeza_ordens)\b/i', $body)) {
        $fail[] = 'DELETE físico encontrado em: ' . $file->getPathname();
    }
}

if ($fail) {
    fwrite(STDERR, "GEOP V7.5 static: FALHOU\n- " . implode("\n- ", $fail) . "\n");
    exit(1);
}
echo "GEOP V7.5 static: OK\n";
