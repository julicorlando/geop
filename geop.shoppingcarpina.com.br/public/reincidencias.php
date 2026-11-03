<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/layout.php';require_once __DIR__.'/../includes/geop_v8.php';
require_role(['TI','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS']);check_csrf();geop_v8_boot($pdo);
if (!geop_v8_feature_ready($pdo)) {
    if ((current_user()['perfil'] ?? '') === 'TI') redirect('atualizar_geop_v8');
    http_response_code(503); die('O módulo GEOP V8 aguarda a atualização do banco pelo TI.');
}$days=max(7,min(3650,(int)($_GET['dias']??90)));$rows=geop_v8_recurrences($pdo,$days);render_header('Reincidências');
?>
<div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3"><div><h1 class="h3 mb-1">Reincidências e custo acumulado</h1><p class="text-muted mb-0">Patrimônios, locais e categorias que concentram chamados repetidos.</p></div><form method="get" class="d-flex gap-2"><select class="form-select" name="dias"><?php foreach([30,60,90,180,365,730] as $d):?><option value="<?=$d?>" <?= $days===$d?'selected':''?>><?=$d?> dias</option><?php endforeach?></select><button class="btn btn-primary">Aplicar</button></form></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Tipo</th><th>Referência</th><th>Ocorrências</th><th>Custo acumulado</th><th>Última</th><th>Indicador</th></tr></thead><tbody><?php foreach($rows as $r):$occ=(int)$r['ocorrencias'];$level=$occ>=6?'danger':($occ>=4?'warning':'secondary');?><tr><td><?=e($r['tipo'])?></td><td><?php if($r['tipo']==='PATRIMONIO'):?><?=e($r['codigo_patrimonial']??('#'.$r['ativo_id']))?><?php else:?><?=e(($r['local_nome']??'-').' / '.($r['categoria_nome']??'-'))?><?php endif?></td><td><?=e((string)$occ)?></td><td>R$ <?=e(number_format((float)$r['custo'],2,',','.'))?></td><td><?=e($r['ultima'])?></td><td><span class="badge text-bg-<?=$level?>"><?= $occ>=6?'Alta reincidência':($occ>=4?'Atenção':'Monitorar')?></span></td></tr><?php endforeach?><?php if(!$rows):?><tr><td colspan="6" class="text-muted text-center py-4">Nenhuma reincidência relevante no período.</td></tr><?php endif?></tbody></table></div></div>
<?php render_footer(); ?>
