<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
ensure_password_policy_schema();

$user = current_user();
$senhaPolicyDisponivel = auth_column_exists($pdo, 'usuarios', 'senha_alterada_em') && auth_column_exists($pdo, 'usuarios', 'forcar_troca_senha');
$erro = '';
$sucesso = false;
$obrigatoria = password_change_required($user);
$pedirSenhaAtual = !$obrigatoria;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $senhaAtual = trim((string)($_POST['senha_atual'] ?? ''));
    $senhaNova = trim((string)($_POST['senha_nova'] ?? ''));
    $senhaConfirma = trim((string)($_POST['senha_confirma'] ?? ''));

    if (($pedirSenhaAtual && $senhaAtual === '') || $senhaNova === '' || $senhaConfirma === '') {
        $erro = 'Preencha todos os campos.';
    } elseif ($senhaNova !== $senhaConfirma) {
        $erro = 'A nova senha e a confirmacao nao coincidem.';
    } else {
        $senhaAtualOk = true;

        if ($pedirSenhaAtual) {
            $st = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = ? LIMIT 1');
            $st->execute([$user['id']]);
            $dados = $st->fetch();
            $senhaAtualOk = $dados && password_verify($senhaAtual, $dados['senha_hash']);
        }

        if (!$senhaAtualOk) {
            $erro = 'Senha atual incorreta.';
        } else {
            $novoHash = password_hash($senhaNova, PASSWORD_DEFAULT);
            if ($senhaPolicyDisponivel) {
                $up = $pdo->prepare('UPDATE usuarios SET senha_hash = ?, senha_alterada_em = NOW(), forcar_troca_senha = 0 WHERE id = ?');
                $up->execute([$novoHash, $user['id']]);

                $_SESSION['user']['senha_alterada_em'] = date('Y-m-d H:i:s');
                $_SESSION['user']['forcar_troca_senha'] = 0;
                $_SESSION['senha_troca_obrigatoria'] = 0;
            } else {
                $up = $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
                $up->execute([$novoHash, $user['id']]);
                $_SESSION['senha_troca_obrigatoria'] = 0;
            }

            unset($_SESSION['user']['senha_hash']);

            $obrigatoria = false;
            $pedirSenhaAtual = true;
            $sucesso = true;
        }
    }
}

render_header('Alterar senha');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Alterar senha</h1>
        <small class="text-muted"><?= $obrigatoria ? 'Troca obrigatoria no primeiro acesso ou a cada 90 dias.' : 'Clique no seu nome para acessar esta pagina a qualquer momento.' ?></small>
    </div>
    <?php if (!$obrigatoria): ?>
        <a class="btn btn-outline-secondary" href="<?= e(app_path('index')) ?>">Voltar</a>
    <?php endif; ?>
</div>

<div class="card p-3 mx-auto" style="max-width: 520px;">
    <?php if ($obrigatoria && !$sucesso): ?>
        <div class="alert alert-warning">Para continuar usando o sistema, cadastre uma nova senha. Nao precisa informar a senha provisoria novamente.</div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?= e($erro) ?></div>
    <?php elseif ($sucesso): ?>
        <div class="alert alert-success">
            Senha alterada com sucesso.
            <div class="mt-2"><a class="btn btn-success btn-sm" href="<?= e(app_path('index')) ?>">Continuar</a></div>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <?php if ($pedirSenhaAtual): ?>
            <div class="mb-3">
                <label class="form-label">Senha atual</label>
                <input class="form-control" type="password" name="senha_atual" required>
            </div>
        <?php endif; ?>
        <div class="mb-3">
            <label class="form-label">Nova senha</label>
            <input class="form-control" type="password" name="senha_nova" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirmar nova senha</label>
            <input class="form-control" type="password" name="senha_confirma" required>
        </div>
        <button class="btn btn-primary w-100">Salvar</button>
    </form>
</div>

<?php render_footer(); ?>
