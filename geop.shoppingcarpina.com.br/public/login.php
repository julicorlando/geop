<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

function login_safe_next(string $next): string {
    $next = trim(str_replace('\\', '/', $next));
    if ($next === '' || str_contains($next, '://') || str_starts_with($next, '//') || str_contains($next, '..')) return '';
    $next = ltrim($next, '/');
    return preg_match('/^[A-Za-z0-9_\-\.]+(?:\?[A-Za-z0-9_\-\.%=&]+)?$/', $next) ? $next : '';
}

$next = login_safe_next((string)($_GET['next'] ?? $_POST['next'] ?? ''));

if (is_logged_in()) {
    if (!empty($_SESSION['senha_troca_obrigatoria']) || password_change_required(current_user())) {
        redirect('mudar_senha?obrigatoria=1');
    }

    if ($next !== '') redirect($next);
    if(($_GET['app']??'')==='controle_acesso') redirect('app_controle_acesso');
    redirect('index');
}

$erro = !empty($_GET['expirada']) ? 'Sua sessão expirou por inatividade. Entre novamente.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo = '';
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (login_user($email, $senha, $motivo)) {
        if (!empty($_SESSION['senha_troca_obrigatoria']) || password_change_required(current_user())) {
            redirect('mudar_senha?obrigatoria=1');
        }

        if ($next !== '') redirect($next);
        if(($_POST['app']??'')==='controle_acesso') redirect('app_controle_acesso');
        redirect('index');
    }

    if ($motivo === '2FA_REQUIRED' && !empty($_SESSION['2fa_pending_user'])) {
        redirect('2fa');
    }

    $erro = $motivo ?: 'E-mail, senha ou status do usuário inválido.';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Shopping Carpina OS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(app_path('assets/style.css')) ?>">

    <style>
        /* Visual principal definido em assets/style.css */
    </style>
</head>

<body class="login-page">
    <div class="login-card card p-4">
        <div class="login-logo">
            PDU
        </div>

        <h1 class="h4 mb-1 text-center">
            Seja bem-vindo(a)
        </h1>

        <p class="text-muted text-center mb-4">
            ao GEOP — Ordens de Serviço
        </p>

        <?php if ($erro): ?>
            <div class="alert alert-danger">
                <?= e($erro) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(app_path('login')) ?>" autocomplete="on">
            <?php if(($_GET['app']??'')==='controle_acesso'): ?><input type="hidden" name="app" value="controle_acesso"><?php endif ?>
            <?php if($next !== ''): ?><input type="hidden" name="next" value="<?= e($next) ?>"><?php endif ?>
            <div class="mb-3">
                <label class="form-label">E-mail</label>
                <input 
                    class="form-control" 
                    type="email" 
                    name="email" 
                    value="<?= e($_POST['email'] ?? '') ?>"
                    placeholder="seuemail@exemplo.com"
                    required 
                    autofocus
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Senha</label>
                <input 
                    class="form-control" 
                    type="password" 
                    name="senha" 
                    placeholder="Digite sua senha"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100">
                Entrar
            </button>

            <hr>

            <div class="support-box text-muted">
                Para acessar, digite seu e-mail e senha.
                Em caso de dúvidas ou problemas de acesso,
                <a href="https://tickets.shoppingcarpina.com.br" target="_blank" rel="noopener">
                    abra um chamado clicando aqui
                </a>.
            </div>
        </form>
    </div>
</body>
</html>
