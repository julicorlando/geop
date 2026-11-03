<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/geop_v8.php';
require_role('TI'); check_csrf(); geop_v8_boot($pdo);
$migrations=[];
try {
    $migrations = geop_v8_list_migrations($pdo);
    if ($migrations) {
        $usuarios = [];
        try {
            $rows = $pdo->query("SELECT id,nome FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) $usuarios[(int)$row['id']] = $row['nome'];
        } catch (Throwable $e) {}

        foreach ($migrations as &$migration) {
            $uid = (int)($migration['aplicada_por'] ?? 0);
            $migration['aplicada_por_nome'] = $uid > 0 ? ($usuarios[$uid] ?? ('#' . $uid)) : '-';
            if (empty($migration['descricao'])) {
                $migration['descricao'] = str_starts_with((string)$migration['versao'], '8.')
                    ? 'Migration GEOP V8'
                    : 'Migration legada do GEOP';
            }
        }
        unset($migration);
    }
} catch (Throwable $e) {}
$pre=geop_v8_preflight($pdo);$post=geop_v8_postflight($pdo);
render_header('Atualizações do GEOP');
?>
<div class="d-flex justify-content-between flex-wrap gap-2 align-items-center mb-3"><div><h1 class="h3 mb-1">Atualizações e migrations</h1><p class="text-muted mb-0">Versão do núcleo: <?=e(GEOP_V8_VERSION)?>. Cada migration é registrada e executada uma única vez.</p></div><a class="btn btn-primary" href="<?=e(app_path('atualizar_geop_v8'))?>">Instalador V8</a></div>
<div class="card mb-3"><div class="card-body"><h2 class="h5">Histórico</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Versão</th><th>Descrição</th><th>Aplicada em</th><th>Por</th><th>Duração</th></tr></thead><tbody><?php foreach($migrations as $m):?><tr><td><code><?=e($m['versao'])?></code></td><td><?=e($m['descricao'])?></td><td><?=e($m['aplicada_em'])?></td><td><?=e($m['aplicada_por_nome']??'-')?></td><td><?=e((string)($m['duracao_ms']??'-'))?> ms</td></tr><?php endforeach?><?php if(!$migrations):?><tr><td colspan="5" class="text-muted">Nenhuma migration V8 registrada.</td></tr><?php endif?></tbody></table></div></div></div>
<div class="row g-3"><div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2 class="h5">Preflight atual</h2><?php foreach($pre as $c):?><div class="d-flex justify-content-between border-bottom py-2"><span><?=e($c['nome'])?></span><span class="badge <?= $c['ok']?'text-bg-success':'text-bg-warning'?>"><?= $c['ok']?'OK':'ATENÇÃO'?></span></div><?php endforeach?></div></div></div><div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2 class="h5">Estrutura V8</h2><?php foreach($post as $c):?><div class="d-flex justify-content-between border-bottom py-2"><span><?=e($c['nome'])?></span><span class="badge <?= $c['ok']?'text-bg-success':'text-bg-warning'?>"><?= $c['ok']?'OK':'PENDENTE'?></span></div><?php endforeach?></div></div></div></div>
<?php render_footer(); ?>
