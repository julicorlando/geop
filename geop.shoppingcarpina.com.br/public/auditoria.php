<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';

require_role(['TI', 'GESTOR']);
ensure_operational_schema($pdo);
$user = current_user();

if ($user['perfil'] === 'GESTOR') {
    $tokenErro = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $token = trim((string)($_POST['token'] ?? ''));
        if ($token !== '' && validate_gestor_token($token, 'GESTOR_REPORTS')) {
            mark_gestor_reports_access();
        } else {
            $tokenErro = 'Token inválido ou expirado. Solicite um token novo ao TI.';
        }
    }

    if (!gestor_reports_access_allowed()) {
        render_header('Acesso restrito');
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">Acesso restrito</h1>
                <small class="text-muted">Informe o token gerado pelo TI para acessar Auditoria.</small>
            </div>
            <a class="btn btn-outline-secondary" href="<?= e(app_path('index')) ?>">Voltar</a>
        </div>
        <div class="card p-3" style="max-width: 520px;">
            <?php if ($tokenErro): ?><div class="alert alert-danger"><?= e($tokenErro) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="mb-3">
                    <label class="form-label">Token TI</label>
                    <input class="form-control" type="text" name="token" autocomplete="off" required>
                </div>
                <button class="btn btn-primary w-100">Validar token</button>
            </form>
        </div>
        <?php
        render_footer();
        exit;
    }
}

$entidade = trim($_GET['entidade'] ?? '');
$usuarioId = (int)($_GET['usuario_id'] ?? 0);
$inicio = trim($_GET['inicio'] ?? '');
$fim = trim($_GET['fim'] ?? '');

$where = [];
$params = [];

if ($entidade !== '') {
    $where[] = 'ae.entidade = ?';
    $params[] = $entidade;
}

if ($usuarioId > 0) {
    $where[] = 'ae.usuario_id = ?';
    $params[] = $usuarioId;
}

if ($inicio !== '') {
    $where[] = 'DATE(ae.criado_em) >= ?';
    $params[] = $inicio;
}

if ($fim !== '') {
    $where[] = 'DATE(ae.criado_em) <= ?';
    $params[] = $fim;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("
    SELECT ae.*, u.nome AS usuario_nome
    FROM auditoria_eventos ae
    LEFT JOIN usuarios u ON u.id = ae.usuario_id
    $whereSql
    ORDER BY ae.criado_em DESC
    LIMIT 300
");
$st->execute($params);
$eventos = $st->fetchAll();

$usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome")->fetchAll();
$entidades = $pdo->query("SELECT DISTINCT entidade FROM auditoria_eventos ORDER BY entidade")->fetchAll(PDO::FETCH_COLUMN);

render_header('Auditoria');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Auditoria</h1>
    <a class="btn btn-outline-secondary" href="<?= e(app_path('relatorios')) ?>">Relatorios</a>
</div>

<div class="card p-3 mb-3">
    <form method="get" class="row g-2">
        <div class="col-md-3">
            <label class="form-label">Entidade</label>
            <select class="form-select" name="entidade">
                <option value="">Todas</option>
                <?php foreach ($entidades as $eEntidade): ?>
                    <option value="<?= e($eEntidade) ?>" <?= $entidade === $eEntidade ? 'selected' : '' ?>><?= e($eEntidade) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Usuario</label>
            <select class="form-select" name="usuario_id">
                <option value="">Todos</option>
                <?php foreach ($usuarios as $u): ?>
                    <option value="<?= e($u['id']) ?>" <?= $usuarioId === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Inicio</label>
            <input class="form-control" type="date" name="inicio" value="<?= e($inicio) ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label">Fim</label>
            <input class="form-control" type="date" name="fim" value="<?= e($fim) ?>">
        </div>

        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Usuario</th>
                    <th>Entidade</th>
                    <th>Acao</th>
                    <th>Campo</th>
                    <th>Antes</th>
                    <th>Depois</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $ev): ?>
                    <tr>
                        <td><?= e($ev['criado_em']) ?></td>
                        <td><?= e($ev['usuario_nome'] ?: 'Sistema') ?></td>
                        <td><?= e($ev['entidade']) ?> #<?= e($ev['entidade_id'] ?: '-') ?></td>
                        <td><?= e($ev['acao']) ?></td>
                        <td><?= e($ev['campo'] ?: '-') ?></td>
                        <td style="max-width:220px; white-space:normal;"><?= e($ev['valor_anterior'] ?: '-') ?></td>
                        <td style="max-width:220px; white-space:normal;"><?= e($ev['valor_novo'] ?: '-') ?></td>
                        <td><?= e($ev['ip'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$eventos): ?>
                    <tr><td colspan="8" class="text-center text-muted">Nenhum evento encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php render_footer(); ?>
