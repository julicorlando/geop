<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/exclusao_ordens.php';

require_role('TI');
check_csrf();

$resultados = [];
$erro = '';
$dbAtual = '';
try {
    $dbAtual = (string)($pdo->query('SELECT DATABASE()')->fetchColumn() ?: 'não identificado');
} catch (Throwable $e) {
    $dbAtual = 'não identificado';
}

function v752_diag(PDO $pdo): array {
    return [
        'ordem_solicitacoes_exclusao' => exclusao_table_exists($pdo, 'ordem_solicitacoes_exclusao'),
        'ordens_servico' => exclusao_table_exists($pdo, 'ordens_servico'),
        'limpeza_ordens' => exclusao_table_exists($pdo, 'limpeza_ordens'),
        'schema_v75_pronto' => exclusao_schema_ready($pdo),
    ];
}

$diagnostico = v752_diag($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $resultados = exclusao_instalar_schema($pdo);
        if (function_exists('auditar')) {
            auditar($pdo,'CORRIGIR_GEOP_V7_5_2','sistema',null,[],[
                'modulo'=>'exclusao_ordens',
                'banco'=>$dbAtual,
                'diagnostico_antes'=>$diagnostico
            ]);
        }
        $diagnostico = v752_diag($pdo);
    } catch (Throwable $e) {
        $erro = $e->getMessage();
        $diagnostico = v752_diag($pdo);
    }
}

$ready = exclusao_schema_ready($pdo);
render_header('Correção V7.5.2 — Exclusão de ordens');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div>
    <h1 class="h3 mb-1">GEOP V7.5.2 — Correção do banco real</h1>
    <p class="text-muted mb-0">Corrige a detecção das tabelas e converte com segurança o esquema antigo de solicitações para o formato usado pela V7.5.</p>
  </div>
  <?php if ($ready): ?><span class="badge text-bg-success fs-6">Pronto</span><?php else: ?><span class="badge text-bg-warning fs-6">Correção pendente</span><?php endif ?>
</div>

<?php if ($erro): ?>
<div class="alert alert-danger"><strong>Erro:</strong> <?= e($erro) ?></div>
<?php endif ?>

<?php if ($resultados): ?>
<div class="alert alert-success">
  <strong>Correção executada.</strong>
  <ul class="mb-0 mt-2">
    <?php foreach($resultados as $r): ?><li><?= e($r) ?></li><?php endforeach ?>
  </ul>
</div>
<?php endif ?>

<div class="card shadow-sm mb-3"><div class="card-body p-4">
  <h2 class="h5">Diagnóstico</h2>
  <p><strong>Banco conectado:</strong> <?= e($dbAtual) ?></p>
  <ul class="mb-0">
    <li><code>ordem_solicitacoes_exclusao</code>: <strong><?= $diagnostico['ordem_solicitacoes_exclusao']?'ENCONTRADA':'NÃO ENCONTRADA' ?></strong></li>
    <li><code>ordens_servico</code>: <strong><?= $diagnostico['ordens_servico']?'ENCONTRADA':'NÃO ENCONTRADA' ?></strong></li>
    <li><code>limpeza_ordens</code>: <strong><?= $diagnostico['limpeza_ordens']?'ENCONTRADA':'NÃO ENCONTRADA' ?></strong></li>
    <li>Schema completo V7.5: <strong><?= $diagnostico['schema_v75_pronto']?'OK':'PENDENTE' ?></strong></li>
  </ul>
</div></div>

<div class="card shadow-sm"><div class="card-body p-4">
<?php if (!$ready): ?>
  <p>O hotfix preserva a tabela existente e adiciona/migra apenas o que falta. Também cria os campos de lixeira em <code>ordens_servico</code> e <code>limpeza_ordens</code>.</p>
  <form method="post" onsubmit="return confirm('Aplicar a correção V7.5.2? Confirme que o backup do banco está atualizado.');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <button class="btn btn-primary">Aplicar correção V7.5.2</button>
  </form>
<?php else: ?>
  <div class="alert alert-success">Banco pronto para o fluxo completo de exclusão de ordens.</div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="<?= e(app_path('exclusoes_ordens')) ?>">Solicitações de exclusão</a>
    <a class="btn btn-outline-danger" href="<?= e(app_path('lixeira_ordens')) ?>">Lixeira</a>
  </div>
<?php endif ?>
</div></div>
<?php render_footer(); ?>
