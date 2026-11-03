<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/layout.php';require_once __DIR__.'/../includes/geop_v8.php';
require_role('TI');check_csrf();geop_v8_boot($pdo);
if (!geop_v8_feature_ready($pdo)) {
    if ((current_user()['perfil'] ?? '') === 'TI') redirect('atualizar_geop_v8');
    http_response_code(503); die('O módulo GEOP V8 aguarda a atualização do banco pelo TI.');
}$s=geop_v8_attachment_stats($pdo);render_header('Integridade de arquivos');
?>
<h1 class="h3">Integridade de arquivos</h1><div class="alert alert-info">Esta tela é somente diagnóstica. A V8 não apaga arquivos órfãos automaticamente.</div>
<div class="row g-3 mb-3"><div class="col-md-4"><div class="card"><div class="card-body"><div class="display-6"><?=e((string)$s['referenciados'])?></div>Referenciados</div></div></div><div class="col-md-4"><div class="card"><div class="card-body"><div class="display-6"><?=e((string)$s['ausentes'])?></div>Ausentes</div></div></div><div class="col-md-4"><div class="card"><div class="card-body"><div class="display-6"><?=e((string)$s['orfaos'])?></div>Órfãos</div></div></div></div>
<div class="card mb-3"><div class="card-body"><h2 class="h5">Referências sem arquivo</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>ID anexo</th><th>Arquivo</th></tr></thead><tbody><?php foreach(array_slice($s['missing_rows'],0,100) as $r):?><tr><td><?=e((string)$r['id'])?></td><td class="text-break"><?=e($r['arquivo_url'])?></td></tr><?php endforeach?><?php if(!$s['missing_rows']):?><tr><td colspan="2" class="text-muted">Nenhuma referência quebrada.</td></tr><?php endif?></tbody></table></div></div></div>
<div class="card"><div class="card-body"><h2 class="h5">Arquivos órfãos</h2><div class="small text-muted mb-2">Mostrando no máximo 100.</div><ul><?php foreach(array_slice($s['orphan_files'],0,100) as $f):?><li class="text-break"><?=e(str_replace(dirname(__DIR__).'/public/','',$f))?></li><?php endforeach?><?php if(!$s['orphan_files']):?><li class="text-muted">Nenhum arquivo órfão encontrado.</li><?php endif?></ul></div></div>
<?php render_footer(); ?>
