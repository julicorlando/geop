<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/geop_v8.php';

require_role('TI');
check_csrf();

$erro = '';
$resultados = [];
$schema = [];
$migrations = [];

try {
    geop_v8_core_schema($pdo);
    $schema = geop_v8_migration_schema($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $resultados = geop_v8_apply_migrations($pdo, (int)(current_user()['id'] ?? 0));
    }

    $migrations = geop_v8_list_migrations($pdo);
} catch (Throwable $e) {
    $erro = $e->getMessage();
}

render_header('Hotfix V8.0.1 — migrations');
?>
<div class="container py-4">
  <h1 class="h3">GEOP V8.0.1 — Compatibilidade de migrations</h1>
  <p class="text-muted">
    Compatibiliza <code>schema_migrations(version, applied_at)</code> do GEOP legado
    com o núcleo de atualização V8.
  </p>

  <?php if ($erro): ?>
    <div class="alert alert-danger"><strong>Erro:</strong> <?= e($erro) ?></div>
  <?php endif; ?>

  <?php if ($schema): ?>
    <div class="alert alert-success">
      <strong>Estrutura reconhecida.</strong><br>
      Coluna de versão: <code><?= e((string)$schema['version']) ?></code><br>
      Coluna de data: <code><?= e((string)($schema['date'] ?? 'não disponível')) ?></code>
    </div>
  <?php endif; ?>

  <?php if ($resultados): ?>
    <div class="alert alert-success">
      <strong>Migrations processadas:</strong>
      <ul class="mb-0 mt-2">
        <?php foreach ($resultados as $r): ?>
          <li><?= e($r['versao']) ?> — <?= e($r['status']) ?> — <?= e($r['descricao']) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card mb-3"><div class="card-body">
    <h2 class="h5">Histórico reconhecido</h2>
    <p class="mb-2">Registros encontrados: <strong><?= count($migrations) ?></strong></p>
    <?php if ($migrations): ?>
      <div class="small text-muted">
        Mais recente:
        <code><?= e((string)$migrations[0]['versao']) ?></code>
        <?= !empty($migrations[0]['aplicada_em']) ? ' — ' . e((string)$migrations[0]['aplicada_em']) : '' ?>
      </div>
    <?php endif; ?>
  </div></div>

  <form method="post" onsubmit="return confirm('Aplicar/continuar as migrations da V8? As migrations já registradas não serão repetidas.');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <button class="btn btn-primary">Corrigir e continuar atualização V8</button>
  </form>
</div>
<?php render_footer(); ?>
