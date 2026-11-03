<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/layout.php';require_once __DIR__.'/../includes/geop_v8.php';
require_role('TI');check_csrf();geop_v8_boot($pdo);$health=geop_v8_health($pdo);$files=geop_v8_attachment_stats($pdo);
render_header('Saúde do GEOP');
?>
<h1 class="h3 mb-1">Saúde do GEOP</h1><p class="text-muted mb-4">Diagnóstico de banco, disco, uploads, migrations e automações.</p>
<div class="row g-3 mb-3"><?php foreach($health as $c):$cls=$c['status']==='OK'?'success':($c['status']==='ERRO'?'danger':'warning');?><div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body"><div class="d-flex justify-content-between gap-2"><strong><?=e($c['nome'])?></strong><span class="badge text-bg-<?=$cls?>"><?=e($c['status'])?></span></div><div class="small text-muted mt-2 text-break"><?=e($c['detalhe'])?></div></div></div></div><?php endforeach?></div>
<div class="card"><div class="card-body"><h2 class="h5">Integridade de anexos</h2><div class="row"><div class="col-md-3"><strong><?=e((string)$files['referenciados'])?></strong><div class="small text-muted">arquivos referenciados</div></div><div class="col-md-3"><strong><?=e((string)$files['ausentes'])?></strong><div class="small text-muted">referências sem arquivo</div></div><div class="col-md-3"><strong><?=e((string)$files['orfaos'])?></strong><div class="small text-muted">arquivos órfãos</div></div><div class="col-md-3"><strong><?=e(number_format($files['bytes']/1024/1024,1,',','.'))?> MB</strong><div class="small text-muted">volume referenciado</div></div></div><div class="mt-3"><a class="btn btn-outline-primary" href="<?=e(app_path('integridade_arquivos'))?>">Abrir análise de arquivos</a></div></div></div>
<?php render_footer(); ?>
