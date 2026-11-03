<?php
declare(strict_types=1);

function env_load(string $file): void
{
    if (!is_file($file) || !is_readable($file)) {
        return;
    }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key) || getenv($key) !== false) {
            continue;
        }
        if (strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"') {
            $value = stripcslashes(substr($value, 1, -1));
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

env_load(dirname(__DIR__) . '/.env');

function env(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    return $value === false ? $default : $value;
}

function env_required(string $name): string
{
    $value = env($name);
    if ($value === null || $value === '') {
        throw new RuntimeException("Variável de ambiente obrigatória ausente: {$name}");
    }
    return $value;
}

function env_bool(string $name, bool $default = false): bool
{
    $value = env($name);
    return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
}

function env_int(string $name, int $default): int
{
    $value = env($name);
    return $value === null || !is_numeric($value) ? $default : (int) $value;
}
