<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ambientes_qr.php';

limpeza_require_access();
ambiente_require_schema($pdo);
limpeza_require_schema($pdo);
exigir_permissao('ambientes.relatorios');

$start = preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['inicio']??'')) ? (string)$_GET['inicio'] : date('Y-m-01');
$end = preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['fim']??'')) ? (string)$_GET['fim'] : date('Y-m-d');
$environmentId = (int)($_GET['ambiente_id']??0);
$memberId = (int)($_GET['membro_id']??0);
$status = strtoupper(trim((string)($_GET['status']??'')));
$validStatuses=['AGUARDANDO_VALIDACAO','VALIDADA','REPROVADA','CORRECAO_SOLICITADA','SOLICITADO_FECHAMENTO','CANCELADA'];
if(!in_array($status,$validStatuses,true)) $status='';
$params=[$start.' 00:00:00',$end.' 23:59:59'];
$where="ar.finalizado_em BETWEEN ? AND ?";
if($environmentId>0){$where.=" AND ar.ambiente_id=?";$params[]=$environmentId;}
if($memberId>0){$where.=" AND EXISTS (SELECT 1 FROM ambiente_registro_responsaveis rf WHERE rf.registro_id=ar.id AND rf.membro_id=?)";$params[]=$memberId;}
if($status!==''){$where.=" AND ar.status=?";$params[]=$status;}

$kpi=$pdo->prepare("SELECT COUNT(*) total,
SUM(ar.status IN ('VALIDADA','SOLICITADO_FECHAMENTO')) validados,
SUM(ar.status='AGUARDANDO_VALIDACAO') pendentes,
SUM(ar.status IN ('REPROVADA','CORRECAO_SOLICITADA')) nao_conformes,
SUM(ar.ordem_servico_id IS NOT NULL) com_os,
AVG(CASE WHEN ar.validado_em IS NOT NULL THEN TIMESTAMPDIFF(MINUTE,ar.criado_em,ar.validado_em) END) media_validacao
FROM ambiente_registros ar WHERE $where");
$kpi->execute($params);$metrics=$kpi->fetch(PDO::FETCH_ASSOC)?:[];

$sql="SELECT ar.id,ar.finalizado_em,ar.status,ar.executor_nome,ar.origem_lancamento,aq.nome_publico,
(SELECT GROUP_CONCAT(rr.nome_responsavel ORDER BY rr.responsavel_principal DESC,rr.nome_responsavel SEPARATOR ', ') FROM ambiente_registro_responsaveis rr WHERE rr.registro_id=ar.id) AS equipe_nomes,
legacy.nome AS asg_nome,eu.nome AS executor_usuario_nome,m.nome_colaborador AS membro_nome,
registrador.nome AS registrador_nome,s.nome AS supervisor_nome,os.numero_os,
(SELECT GROUP_CONCAT(DISTINCT aa.nome ORDER BY aa.nome SEPARATOR ' • ') FROM ambiente_registro_itens ri JOIN ambiente_acoes aa ON aa.id=ri.acao_id WHERE ri.registro_id=ar.id) AS acoes_genericas,
(SELECT GROUP_CONCAT(DISTINCT t.descricao ORDER BY t.ordem,t.descricao SEPARATOR ' • ') FROM ambiente_registro_plano_itens rpi JOIN limpeza_plano_tarefas t ON t.id=rpi.tarefa_id WHERE rpi.registro_id=ar.id) AS tarefas_plano,
TIMESTAMPDIFF(MINUTE,ar.criado_em,ar.validado_em) minutos_validacao
FROM ambiente_registros ar
JOIN ambientes_qr aq ON aq.id=ar.ambiente_id
JOIN usuarios legacy ON legacy.id=ar.asg_id
LEFT JOIN usuarios eu ON eu.id=ar.executor_usuario_id
LEFT JOIN limpeza_organograma_nos m ON m.id=ar.executor_membro_id
LEFT JOIN usuarios registrador ON registrador.id=ar.registrado_por
LEFT JOIN usuarios s ON s.id=ar.supervisor_id
LEFT JOIN ordens_servico os ON os.id=ar.ordem_servico_id
WHERE $where ORDER BY ar.finalizado_em DESC";
$st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC)?:[];
foreach($rows as &$r){
    $r['responsavel']=limpeza_record_executor($r);
    $r['acoes']=limpeza_merge_action_names($r['acoes_genericas']??'', $r['tarefas_plano']??'');
}
unset($r);

if(($_GET['export']??'')==='csv'){
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="limpezas_'.$start.'_a_'.$end.'.csv"');
    echo "\xEF\xBB\xBF";
    $out=fopen('php://output','w');
    fputcsv($out,['Data','Ambiente','Equipe responsável','Registrado por','Atividades','Status','Supervisor','OS','Origem','Minutos para validar'],';');
    foreach($rows as $r)fputcsv($out,[date('d/m/Y H:i',strtotime($r['finalizado_em'])),$r['nome_publico'],$r['responsavel'],$r['registrador_nome'],$r['acoes'],ambiente_status_label($r['status']),$r['supervisor_nome'],$r['numero_os'],$r['origem_lancamento'],$r['minutos_validacao']],';');
    fclose($out);exit;
}

$environments=$pdo->query("SELECT id,nome_publico FROM ambientes_qr ORDER BY nome_publico")->fetchAll(PDO::FETCH_ASSOC)?:[];
$members=limpeza_members($pdo,false);
$exportQuery=$_GET;$exportQuery['export']='csv';
render_header('Relatórios de limpeza');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 mb-1">Relatórios de limpeza</h1><p class="text-muted mb-0">Execuções por ambiente, equipe responsável, validação, não conformidades e vínculo com OS.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza'))?>">Painel</a><a class="btn btn-outline-secondary" href="<?=e(app_path('ambientes'))?>">Ambientes</a></div></div>
<form class="card card-body shadow-sm mb-4" method="get"><div class="row g-3 align-items-end"><div class="col-md-2"><label class="form-label">Início</label><input class="form-control" type="date" name="inicio" value="<?=e($start)?>"></div><div class="col-md-2"><label class="form-label">Fim</label><input class="form-control" type="date" name="fim" value="<?=e($end)?>"></div><div class="col-md-3"><label class="form-label">Ambiente</label><select class="form-select" name="ambiente_id"><option value="0">Todos</option><?php foreach($environments as $env): ?><option value="<?=e($env['id'])?>" <?=((int)$env['id']===$environmentId)?'selected':''?>><?=e($env['nome_publico'])?></option><?php endforeach ?></select></div><div class="col-md-3"><label class="form-label">Responsável</label><select class="form-select" name="membro_id"><option value="0">Todos</option><?php foreach($members as $member): ?><option value="<?=e($member['id'])?>" <?=((int)$member['id']===$memberId)?'selected':''?>><?=e($member['nome_colaborador'].' — '.$member['cargo'])?></option><?php endforeach ?></select></div><div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Todos</option><?php foreach($validStatuses as $s): ?><option value="<?=e($s)?>" <?=$status===$s?'selected':''?>><?=e(ambiente_status_label($s))?></option><?php endforeach ?></select></div><div class="col-12 d-grid d-md-flex justify-content-md-end"><button class="btn btn-primary px-4">Filtrar</button></div></div></form>
<div class="row g-3 mb-4"><?php $cards=[['Total',$metrics['total']??0,'primary'],['Validados',$metrics['validados']??0,'success'],['Pendentes',$metrics['pendentes']??0,'warning'],['Não conformes',$metrics['nao_conformes']??0,'danger'],['Com OS',$metrics['com_os']??0,'info'],['Média validação',isset($metrics['media_validacao'])&&$metrics['media_validacao']!==null?round((float)$metrics['media_validacao']).' min':'—','secondary']]; foreach($cards as $c): ?><div class="col-6 col-lg-2"><div class="card h-100 border-<?=$c[2]?>"><div class="card-body"><small class="text-muted"><?=e($c[0])?></small><strong class="d-block h3 mb-0"><?=e($c[1])?></strong></div></div></div><?php endforeach ?></div>
<div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Registros</h2><a class="btn btn-sm btn-success" href="<?=e(app_path('ambiente_relatorios',$exportQuery))?>">Exportar CSV</a></div>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Data</th><th>Ambiente</th><th>Equipe responsável</th><th>Atividades</th><th>Status</th><th>Registrado por</th><th>Supervisor</th><th>OS</th><th>Validação</th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td class="text-nowrap"><?=e(date('d/m/Y H:i',strtotime($r['finalizado_em'])))?></td><td><?=e($r['nome_publico'])?></td><td><strong><?=e($r['responsavel'])?></strong></td><td><small><?=e($r['acoes']?:'—')?></small></td><td><span class="badge text-bg-<?=e(ambiente_status_class($r['status']))?>"><?=e(ambiente_status_label($r['status']))?></span></td><td><?=e($r['registrador_nome']?:'—')?></td><td><?=e($r['supervisor_nome']?:'—')?></td><td><?=e($r['numero_os']?:'—')?></td><td><?= $r['minutos_validacao']!==null?e($r['minutos_validacao'].' min'):'—' ?></td></tr><?php endforeach ?><?php if(!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Nenhum registro no período.</td></tr><?php endif ?></tbody></table></div></div>
<?php render_footer(); ?>
