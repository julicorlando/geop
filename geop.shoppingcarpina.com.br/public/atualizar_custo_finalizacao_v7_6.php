<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/custo_finalizacao.php';

require_role('TI');
check_csrf();

$mensagens = [];
$erro = '';
$pronto = false;

function v76_pronto(PDO $pdo): bool {
    return custo_finalizacao_table_exists($pdo, 'os_finalizacao_custos')
        && custo_finalizacao_table_exists($pdo, 'os_finalizacao_custo_itens')
        && custo_finalizacao_column_exists($pdo, 'ordens_servico', 'custo_materiais');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mensagens = custo_finalizacao_ensure_schema($pdo);
        $pronto = v76_pronto($pdo);
        if (function_exists('auditar')) {
            auditar($pdo, 'ATUALIZAR_GEOP_V7_6', 'sistema', null, [], ['modulo'=>'custo_finalizacao_os']);
        }
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
} else {
    $pronto = v76_pronto($pdo);
}

$dbAtual = '';
try { $dbAtual = (string)$pdo->query('SELECT DATABASE()')->fetchColumn(); } catch (Throwable $e) {}

render_header('Atualização V7.6 — custo na finalização');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">GEOP V7.6 — Custo de material na finalização da OS</h1>
        <p class="text-muted mb-0">Instala a conferência obrigatória de custo antes de encerrar uma OS.</p>
    </div>
    <span class="badge <?= $pronto ? 'text-bg-success' : 'text-bg-warning' ?> fs-6"><?= $pronto ? 'Pronto' : 'Pendente' ?></span>
</div>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>

<?php if ($mensagens): ?>
<div class="alert alert-success">
    <strong>Atualização executada.</strong>
    <ul class="mb-0 mt-2"><?php foreach ($mensagens as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card shadow-sm"><div class="card-body p-4">
    <p><strong>Banco:</strong> <?= e($dbAtual ?: 'não identificado') ?></p>
    <ul>
        <li><code>ordens_servico.custo_materiais</code>: <?= custo_finalizacao_column_exists($pdo,'ordens_servico','custo_materiais') ? 'OK' : 'PENDENTE' ?></li>
        <li><code>os_finalizacao_custos</code>: <?= custo_finalizacao_table_exists($pdo,'os_finalizacao_custos') ? 'OK' : 'PENDENTE' ?></li>
        <li><code>os_finalizacao_custo_itens</code>: <?= custo_finalizacao_table_exists($pdo,'os_finalizacao_custo_itens') ? 'OK' : 'PENDENTE' ?></li>
    </ul>

    <?php if (!$pronto): ?>
    <form method="post" onsubmit="return confirm('Aplicar a atualização V7.6? Confirme que existe backup recente do banco.');">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <button class="btn btn-primary">Aplicar atualização V7.6</button>
    </form>
    <?php else: ?>
        <div class="alert alert-success mb-0">Banco pronto. Abra uma OS de teste e clique em “Finalizar OS”.</div>
    <?php endif; ?>
</div></div>
<?php render_footer(); ?>
