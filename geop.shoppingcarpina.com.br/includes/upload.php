<?php
declare(strict_types=1);

function upload_validate(array $file, array $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'], int $maxBytes = 10485760): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('O arquivo enviado é inválido.');
    }
    if (($file['size'] ?? 0) < 1 || (int) $file['size'] > $maxBytes) {
        throw new RuntimeException('O arquivo excede o limite permitido.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Tipo de arquivo não permitido.');
    }
    if (str_starts_with($mime, 'image/') && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('A imagem está corrompida.');
    }
    return ['mime' => $mime, 'extension' => $allowed[$mime], 'sha256' => hash_file('sha256', $file['tmp_name'])];
}

function upload_store(array $file, string $directory, string $prefix = ''): array
{
    $meta = upload_validate($file);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Armazenamento indisponível.');
    }
    $name = $prefix . bin2hex(random_bytes(24)) . '.' . $meta['extension'];
    $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Falha ao armazenar o arquivo.');
    }
    @chmod($path, 0640);
    return $meta + ['name' => $name, 'path' => $path, 'size' => (int) $file['size']];
}
