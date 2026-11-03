<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/exclusao_ordens.php';

require_role('TI');
check_csrf();

$resultados = [];
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $resultados = exclusao_instalar_schema($pdo);
        auditar($pdo,'ATUALIZAR_GEOP_V7_5','sistema',null,[],['modulo'=>'exclusao_ordens']);
    } catch (Throwable $e) {
        $erro = $e->getMessage();
    }
}

$ready = exclusao_schema_ready($pdo);
render_header('Atualização V7.5 — Exclusão de ordens');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 mb-1">GEOP V7.5 — Exclusão de ordens</h1>
    <p class="text-muted mb-0">Ativação do fluxo de solicitação, aprovação, lixeira e restauração.</p>
  </div>
  <?php if ($ready): ?><span class="badge text-bg-success fs-6">Atualização ativa</span><?php else: ?><span class="badge text-bg-warning fs-6">Pendente</span><?php endif ?>
</div>

<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif ?>
<?php if ($resultados): ?><div class="alert alert-success"><strong>Atualização concluída.</strong><ul class="mb-0 mt-2"><?php foreach($resultados as $r): ?><li><?= e($r) ?></li><?php endforeach ?></ul></div><?php endif ?>

<div class="card shadow-sm">
  <div class="card-body p-4">
    <h2 class="h5">O que será instalado</h2>
    <ul>
      <li>Tabela de solicitações de exclusão.</li>
      <li>Campos de lixeira nas OS e OL legadas.</li>
      <li>Índices para manter as consultas rápidas.</li>
      <li>Fluxo: Gestor solicita → outro Gestor/TI aprova → TI exclui.</li>
      <li>Exclusão direta e restauração exclusiva do TI.</li>
    </ul>
    <?php if (!$ready): ?>
    <form method="post" onsubmit="return confirm('Confirma a atualização do banco para o GEOP V7.5? Faça backup antes de continuar.');">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <button class="btn btn-primary">Aplicar atualização V7.5</button>
    </form>
    <?php else: ?>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="<?= e(app_path('exclusoes_ordens')) ?>">Abrir solicitações</a>
        <a class="btn btn-outline-danger" href="<?= e(app_path('lixeira_ordens')) ?>">Abrir lixeira</a>
      </div>
    <?php endif ?>
  </div>
</div>
<?php render_footer(); ?>
