<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/custo_finalizacao.php';

require_role('TI');
check_csrf();

$erro = '';
$resultados = [];
$db = '';

try {
    $db = (string)($pdo->query('SELECT DATABASE()')->fetchColumn() ?: 'não identificado');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultados = custo_finalizacao_ensure_schema($pdo);
    }
} catch (Throwable $e) {
    $erro = $e->getMessage();
}

$coluna = custo_finalizacao_column_exists($pdo, 'ordens_servico', 'custo_materiais');
$tabela1 = custo_finalizacao_table_exists($pdo, 'os_finalizacao_custos');
$tabela2 = custo_finalizacao_table_exists($pdo, 'os_finalizacao_custo_itens');
$pronto = $coluna && $tabela1 && $tabela2;

render_header('Hotfix V7.6.1');
?>
<div class="container py-4">
  <h1 class="h3">GEOP V7.6.1 — Correção custo_materiais</h1>
  <p class="text-muted">Corrige falso negativo de coluna existente no MariaDB/cPanel.</p>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><?= e($erro) ?></div>
  <?php endif; ?>

  <?php if ($resultados): ?>
    <div class="alert alert-success">
      <strong>Correção executada.</strong>
      <ul class="mb-0 mt-2">
        <?php foreach ($resultados as $r): ?><li><?= e($r) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card"><div class="card-body">
    <p><strong>Banco:</strong> <?= e($db) ?></p>
    <ul>
      <li><code>ordens_servico.custo_materiais</code>: <strong><?= $coluna ? 'OK' : 'PENDENTE' ?></strong></li>
      <li><code>os_finalizacao_custos</code>: <strong><?= $tabela1 ? 'OK' : 'PENDENTE' ?></strong></li>
      <li><code>os_finalizacao_custo_itens</code>: <strong><?= $tabela2 ? 'OK' : 'PENDENTE' ?></strong></li>
    </ul>

    <?php if (!$pronto): ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button class="btn btn-primary">Completar atualização V7.6.1</button>
    </form>
    <?php else: ?>
      <div class="alert alert-success mb-0">V7.6 pronta. Teste agora a finalização de uma OS.</div>
    <?php endif; ?>
  </div></div>
</div>
<?php render_footer(); ?>
