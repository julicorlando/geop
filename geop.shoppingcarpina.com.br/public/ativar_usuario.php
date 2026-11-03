<?php
require_once __DIR__ . '/../includes/usuario_convites.php';
require_once __DIR__ . '/../includes/helpers.php';

ensure_password_policy_schema();
ensure_usuario_convites_schema($pdo);

$token = usuario_convite_token_limpo($_GET['token'] ?? ($_POST['token'] ?? ''));
$convite = usuario_convite_buscar($pdo, $token);
$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $convite = usuario_convite_buscar($pdo, $token);
    $telefone = trim((string)($_POST['telefone'] ?? ''));
    $senha = trim((string)($_POST['senha'] ?? ''));
    $confirmar = trim((string)($_POST['confirmar_senha'] ?? ''));

    if (!$convite) {
        $erro = 'Link invalido, expirado ou ja utilizado.';
    } elseif ($telefone === '' || $senha === '' || $confirmar === '') {
        $erro = 'Informe telefone, senha e confirmacao.';
    } elseif (mb_strlen($senha, 'UTF-8') < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar) {
        $erro = 'A senha e a confirmacao nao coincidem.';
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $pdo->prepare("
            UPDATE usuarios
            SET telefone = ?,
                senha_hash = ?,
                senha_alterada_em = NOW(),
                forcar_troca_senha = 0,
                status = 'ATIVO'
            WHERE id = ?
        ")->execute([$telefone, $hash, $convite['usuario_id']]);

        $pdo->prepare("
            UPDATE usuario_convites
            SET usado_em = NOW(),
                ip_uso = ?,
                user_agent_uso = ?
            WHERE id = ?
        ")->execute([
            substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            $convite['id'],
        ]);

        if (is_logged_in() && (int)(current_user()['id'] ?? 0) === (int)$convite['usuario_id']) {
            $_SESSION['user']['telefone'] = $telefone;
            $_SESSION['user']['senha_alterada_em'] = date('Y-m-d H:i:s');
            $_SESSION['user']['forcar_troca_senha'] = 0;
            $_SESSION['senha_troca_obrigatoria'] = 0;
        }

        $sucesso = true;
        $convite = null;
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar senha - Shopping Carpina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_path('assets/style.css')) ?>">
    <style>
        body {
            min-height: 100vh;
            background: #f4f6f8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .activation-card {
            width: 100%;
            max-width: 520px;
        }
    </style>
</head>
<body>
    <div class="activation-card card p-4">
        <h1 class="h4 mb-1">Criar senha</h1>
        <p class="text-muted mb-4">Cadastre sua senha de acesso e informe seu telefone.</p>

        <?php if ($erro): ?>
            <div class="alert alert-danger"><?= e($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="alert alert-success">
                Acesso configurado com sucesso.
                <div class="mt-2"><a class="btn btn-success btn-sm" href="<?= e(app_path('login')) ?>">Ir para login</a></div>
            </div>
        <?php elseif (!$convite): ?>
            <div class="alert alert-warning mb-0">Link invalido, expirado ou ja utilizado.</div>
        <?php else: ?>
            <div class="mb-3">
                <div class="fw-semibold"><?= e($convite['nome']) ?></div>
                <div class="text-muted small"><?= e($convite['email']) ?></div>
            </div>

            <form method="post" autocomplete="off">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="mb-3">
                    <label class="form-label">Telefone</label>
                    <input class="form-control" name="telefone" value="<?= e($_POST['telefone'] ?? $convite['telefone'] ?? '') ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Senha</label>
                    <input class="form-control" type="password" name="senha" minlength="6" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar senha</label>
                    <input class="form-control" type="password" name="confirmar_senha" minlength="6" required>
                </div>
                <button class="btn btn-primary w-100">Salvar acesso</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
