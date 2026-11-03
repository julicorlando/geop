<?php
require_once __DIR__ . '/auth.php';

function ensure_usuario_convites_schema(PDO $pdo): void {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS usuario_convites (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                token_selector CHAR(64) NULL,
                gerado_por INT NOT NULL,
                usado_em DATETIME NULL,
                invalidado_em DATETIME NULL,
                expira_em DATETIME NOT NULL,
                criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_uso VARCHAR(64) NULL,
                user_agent_uso VARCHAR(255) NULL,
                INDEX idx_usuario (usuario_id),
                INDEX idx_expira (expira_em),
                INDEX idx_usado (usado_em),
                INDEX idx_invalidado (invalidado_em)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
        // A tela exibira erro se a hospedagem negar CREATE TABLE.
    }
}

function usuario_convite_token_limpo($token): string {
    return strtolower(preg_replace('/[^a-f0-9]/i', '', (string)$token));
}

function usuario_convite_buscar(PDO $pdo, string $token): ?array {
    $token = usuario_convite_token_limpo($token);
    if ($token === '') {
        return null;
    }

    $selector = hash('sha256', $token);
    $st = $pdo->prepare("
        SELECT c.*, u.nome, u.email, u.telefone, u.status
        FROM usuario_convites c
        JOIN usuarios u ON u.id = c.usuario_id
        WHERE c.usado_em IS NULL
        AND c.invalidado_em IS NULL
        AND c.expira_em >= NOW()
        AND (c.token_selector = ? OR c.token_selector IS NULL)
        ORDER BY c.criado_em DESC
    ");
    $st->execute([$selector]);

    foreach ($st->fetchAll() as $convite) {
        if (password_verify($token, $convite['token_hash'])) {
            return $convite;
        }
    }

    return null;
}

function usuario_convite_criar(PDO $pdo, int $usuarioId, int $geradoPor, int $horas = 72): array {
    $pdo->prepare("
        UPDATE usuario_convites
        SET invalidado_em = NOW()
        WHERE usuario_id = ?
        AND usado_em IS NULL
        AND invalidado_em IS NULL
    ")->execute([$usuarioId]);

    $token = bin2hex(random_bytes(32));
    $hash = password_hash($token, PASSWORD_DEFAULT);
    $selector = hash('sha256', $token);

    $st = $pdo->prepare("
        INSERT INTO usuario_convites (usuario_id, token_hash, token_selector, gerado_por, expira_em)
        VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))
    ");
    $st->execute([$usuarioId, $hash, $selector, $geradoPor, $horas]);

    return [
        'id' => (int)$pdo->lastInsertId(),
        'token' => $token,
        'url' => app_url('ativar_usuario', ['token' => $token]),
    ];
}

function usuario_convite_enviar_email(string $email, string $nome, string $link): bool {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $assunto = 'Crie sua senha - Sistema PDU OS';
    $mensagem = "Ola, {$nome}.\n\n";
    $mensagem .= "Use o link abaixo para criar sua senha e informar seu telefone:\n{$link}\n\n";
    $mensagem .= "Este link e pessoal, temporario e sera invalidado apos o uso.\n";
    $mensagem .= "Se voce nao solicitou este acesso, ignore esta mensagem.\n";
    $headers = "From: no-reply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return @mail($email, $assunto, $mensagem, $headers);
}
