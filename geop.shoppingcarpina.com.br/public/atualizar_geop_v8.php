<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/geop_v8.php';

require_role('TI');
check_csrf();
geop_v8_boot($pdo);
$user=current_user();
$pre=geop_v8_preflight($pdo);
$criticalFail=array_filter($pre,fn($c)=>!$c['ok']&&!empty($c['critico']));
$result=[];$backup=null;$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        if($criticalFail) throw new RuntimeException('Há falhas críticas no preflight. Corrija antes de aplicar a V8.');
        if(!empty($_POST['criar_backup'])) $backup=geop_v8_create_database_backup($pdo,(int)$user['id']);
        $result=geop_v8_apply_migrations($pdo,(int)$user['id']);
        if(function_exists('auditar')) auditar($pdo,'ATUALIZAR_GEOP_V8','sistema',null,[],['versao'=>GEOP_V8_VERSION]);
    }catch(Throwable $e){$error=$e->getMessage();}
}
$post=geop_v8_postflight($pdo);
$allPost=!array_filter($post,fn($c)=>!$c['ok']);
render_header('Atualização GEOP V8');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
  <div><h1 class="h3 mb-1">GEOP V8.0 — Atualização estrutural</h1><p class="text-muted mb-0">Migrations, backup, segurança, aprovações, custos, compras, qualidade, IA assistiva e saúde do sistema.</p></div>
  <span class="badge <?= $allPost?'text-bg-success':'text-bg-warning' ?> fs-6"><?= $allPost?'V8 pronta':'Instalação pendente' ?></span>
</div>
<?php if($error):?><div class="alert alert-danger"><strong>Erro:</strong> <?=e($error)?></div><?php endif?>
<?php if($backup):?><div class="alert alert-success">Backup criado antes da atualização: <strong><?=e($backup['arquivo'])?></strong> — SHA-256 <?=e($backup['sha256'])?></div><?php endif?>
<?php if($result):?><div class="alert alert-success"><strong>Migrations processadas:</strong><ul class="mb-0 mt-2"><?php foreach($result as $r):?><li><?=e($r['versao'])?> — <?=e($r['status'])?> — <?=e($r['descricao'])?></li><?php endforeach?></ul></div><?php endif?>
<div class="row g-3">
 <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2 class="h5">Preflight</h2><table class="table table-sm"><tbody><?php foreach($pre as $c):?><tr><td><?=e($c['nome'])?></td><td><span class="badge <?= $c['ok']?'text-bg-success':(!empty($c['critico'])?'text-bg-danger':'text-bg-warning')?>"><?= $c['ok']?'OK':'ATENÇÃO'?></span></td><td class="small text-muted"><?=e($c['detalhe'])?></td></tr><?php endforeach?></tbody></table></div></div></div>
 <div class="col-lg-6"><div class="card h-100"><div class="card-body"><h2 class="h5">Postflight V8</h2><table class="table table-sm"><tbody><?php foreach($post as $c):?><tr><td><?=e($c['nome'])?></td><td><span class="badge <?= $c['ok']?'text-bg-success':'text-bg-warning'?>"><?= $c['ok']?'OK':'PENDENTE'?></span></td></tr><?php endforeach?></tbody></table></div></div></div>
</div>
<?php if(!$allPost):?><form method="post" class="card mt-3"><div class="card-body"><div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="criar_backup" value="1" id="criarBackup" checked><label class="form-check-label" for="criarBackup">Criar backup completo do banco antes de aplicar</label></div><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="btn btn-primary" <?= $criticalFail?'disabled':''?>>Criar backup e aplicar GEOP V8</button><?php if($criticalFail):?><div class="text-danger small mt-2">A instalação está bloqueada por falha crítica no preflight.</div><?php endif?></div></form><?php endif?>
<?php render_footer(); ?>
