<?php
require_once __DIR__ . '/../includes/usuario_convites.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/cftv.php';

require_role('TI');
check_csrf();
ensure_password_policy_schema();
ensure_cftv_user_profile($pdo);
ensure_usuario_convites_schema($pdo);

$perfis = app_user_profiles();
$setores = $pdo->query("SELECT * FROM setores WHERE ativo = 1 ORDER BY nome")->fetchAll();

$mensagem = $_SESSION['convite_usuario_msg'] ?? '';
$erro = $_SESSION['convite_usuario_erro'] ?? '';
$linkGerado = $_SESSION['convite_usuario_link'] ?? '';
$emailGerado = $_SESSION['convite_usuario_email'] ?? '';
$mailOk = $_SESSION['convite_usuario_mail_ok'] ?? null;
unset(
    $_SESSION['convite_usuario_msg'],
    $_SESSION['convite_usuario_erro'],
    $_SESSION['convite_usuario_link'],
    $_SESSION['convite_usuario_email'],
    $_SESSION['convite_usuario_mail_ok']
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string)($_POST['nome'] ?? ''));
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')), 'UTF-8');
    $cargo = trim((string)($_POST['cargo'] ?? ''));
    $perfil = array_key_exists($_POST['perfil'] ?? '', $perfis) ? $_POST['perfil'] : 'AGENTE';
    $setorId = ($_POST['setor_id'] ?? '') !== '' ? (int)$_POST['setor_id'] : null;
    $status = in_array($_POST['status'] ?? '', ['ATIVO', 'INATIVO'], true) ? $_POST['status'] : 'ATIVO';

    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['convite_usuario_erro'] = 'Informe nome e e-mail valido.';
        header('Location: ' . app_path('usuario_convite'));
        exit;
    }

    try {
        $hashTemporario = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE LOWER(email) = ? LIMIT 1");
        $st->execute([$email]);
        $usuarioId = (int)$st->fetchColumn();

        if ($usuarioId > 0) {
            $st = $pdo->prepare("
                UPDATE usuarios
                SET nome = ?,
                    email = ?,
                    cargo = ?,
                    perfil = ?,
                    setor_id = ?,
                    status = ?,
                    senha_hash = ?,
                    senha_alterada_em = NULL,
                    forcar_troca_senha = 1
                WHERE id = ?
            ");
            $st->execute([$nome, $email, $cargo, $perfil, $setorId, $status, $hashTemporario, $usuarioId]);
        } else {
            $st = $pdo->prepare("
                INSERT INTO usuarios
                (nome, email, telefone, cargo, perfil, setor_id, senha_hash, status, criado_por, senha_alterada_em, forcar_troca_senha)
                VALUES (?, ?, '', ?, ?, ?, ?, ?, ?, NULL, 1)
            ");
            $st->execute([$nome, $email, $cargo, $perfil, $setorId, $hashTemporario, $status, current_user()['id']]);
            $usuarioId = (int)$pdo->lastInsertId();
        }

        $convite = usuario_convite_criar($pdo, $usuarioId, (int)current_user()['id']);
        $enviado = usuario_convite_enviar_email($email, $nome, $convite['url']);

        $_SESSION['convite_usuario_msg'] = $enviado
            ? 'Convite criado e envio por e-mail solicitado ao servidor.'
            : 'Convite criado. Se o e-mail nao sair pelo servidor, copie o link abaixo e envie ao usuario.';
        $_SESSION['convite_usuario_link'] = $convite['url'];
        $_SESSION['convite_usuario_email'] = $email;
        $_SESSION['convite_usuario_mail_ok'] = $enviado ? 1 : 0;
    } catch (Throwable $e) {
        $_SESSION['convite_usuario_erro'] = 'Nao foi possivel gerar o convite. Verifique o cadastro e tente novamente.';
    }

    header('Location: ' . app_path('usuario_convite'));
    exit;
}

render_header('Convite de usuario');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Convite de usuario</h1>
        <small class="text-muted">O TI cadastra os dados e gera um link para o usuario criar senha e telefone.</small>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(app_path('usuarios')) ?>">Usuarios</a>
</div>

<?php if ($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<?php if ($linkGerado): ?>
    <div class="card p-3 mb-3">
        <h2 class="h5">Link do convite</h2>
        <div class="input-group mb-2">
            <input class="form-control" id="link_convite" value="<?= e($linkGerado) ?>" readonly>
            <button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('link_convite').value)">Copiar</button>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-dark" href="mailto:<?= e($emailGerado) ?>?subject=Crie%20sua%20senha%20-%20Sistema%20PDU%20OS&body=Use%20este%20link%20para%20criar%20sua%20senha%20e%20informar%20seu%20telefone:%0A<?= e(rawurlencode($linkGerado)) ?>">Abrir e-mail</a>
            <span class="small text-muted align-self-center">Validade: 72 horas. Apos o uso, o link fica invalido.</span>
        </div>
    </div>
<?php endif; ?>

<div class="card p-3">
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="col-md-6">
            <label class="form-label">Nome completo</label>
            <input class="form-control" name="nome" value="<?= e($_POST['nome'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">E-mail</label>
            <input class="form-control" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Cargo</label>
            <input class="form-control" name="cargo" value="<?= e($_POST['cargo'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Perfil</label>
            <select class="form-select" name="perfil">
                <?php foreach ($perfis as $perfil => $label): ?>
                    <option value="<?= e($perfil) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option>ATIVO</option>
                <option>INATIVO</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Setor</label>
            <select class="form-select" name="setor_id">
                <option value="">Sem setor</option>
                <?php foreach ($setores as $setor): ?>
                    <option value="<?= e($setor['id']) ?>"><?= e($setor['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <button class="btn btn-primary w-100">Gerar convite e link</button>
        </div>
    </form>
</div>

<?php render_footer(); ?>
