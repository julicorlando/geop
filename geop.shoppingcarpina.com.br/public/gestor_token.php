<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role('TI');

$token = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $token = generate_gestor_token('GESTOR_REPORTS', current_user()['id'], 10);
}

ensure_gestor_token_table();
$st = $pdo->prepare('SELECT token, valido_ate, criado_em FROM gestor_tokens WHERE target = ? ORDER BY criado_em DESC LIMIT 5');
$st->execute(['GESTOR_REPORTS']);
$tokens = $st->fetchAll();

render_header('Gerar token TI');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Token para gestor</h1>
        <small class="text-muted">Gere um token válido para Auditoria ou para renovar a franquia de 10 relatórios do perfil GESTOR.</small>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(app_path('index')) ?>">Voltar</a>
</div>

<div class="card p-3 mb-3" style="max-width: 720px;">
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <p>Clique em gerar para criar um token temporário de 10 minutos. Nos Relatórios, o Gestor pode emitir 10 arquivos sem token; ao atingir o limite, este token renova a franquia para mais 10 emissões. Para Auditoria, o token continua obrigatório.</p>
        <button class="btn btn-primary">Gerar token</button>
    </form>

    <?php if ($token): ?>
        <div class="alert alert-success mt-3">
            <strong>Token gerado:</strong>
            <div class="mt-2"><code><?= e($token) ?></code></div>
            <div class="small text-muted">Válido por 10 minutos.</div>
        </div>
    <?php endif; ?>
</div>

<?php if ($tokens): ?>
    <div class="card p-3" style="max-width: 720px;">
        <h2 class="h5 mb-3">Tokens recentes</h2>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr><th>Token</th><th>Expira em</th><th>Criado em</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tokens as $row): ?>
                        <tr>
                            <td><code><?= e($row['token']) ?></code></td>
                            <td><?= e($row['valido_ate']) ?></td>
                            <td><?= e($row['criado_em']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php render_footer();
