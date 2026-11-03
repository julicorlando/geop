<?php
declare(strict_types=1);

/** Carrega um .env privado sem sobrescrever variáveis definidas pelo servidor. */
function load_private_env(?string $file = null): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;
    $file ??= dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($file) || !is_readable($file)) return;

    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        // Remove BOM UTF-8 comum em arquivos criados pelo Bloco de Notas/Windows.
        $line = trim(preg_replace('/^\xEF\xBB\xBF/', '', $line));
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || getenv($key) !== false) continue;
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if (str_contains($value, "\0") || str_contains($value, "\n") || str_contains($value, "\r")) continue;
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

load_private_env();
