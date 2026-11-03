<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ordens_limpeza.php';

ol_require_access();
ol_require_schema($pdo);
exigir_permissao('ol.relatorios');

$status=(string)($_GET['status']??'');
$priority=(string)($_GET['prioridade']??'');
$memberId=(int)($_GET['membro_id']??0);
$environmentId=(int)($_GET['ambiente_id']??0);
$dateFrom=trim((string)($_GET['de']??date('Y-m-01')));
$dateTo=trim((string)($_GET['ate']??date('Y-m-t')));
if($status!==''&&!array_key_exists($status,ol_statuses()))$status='';
if($priority!==''&&!array_key_exists($priority,ol_priorities()))$priority='';
$where=[exclusao_sql_nao_excluida($pdo,'limpeza_ordens','ol')];$params=[];
if($status!==''){$where[]='ol.status=?';$params[]=$status;}
if($priority!==''){$where[]='ol.prioridade=?';$params[]=$priority;}
if($memberId>0){$where[]='EXISTS(SELECT 1 FROM limpeza_ordem_participantes p2 WHERE p2.ordem_limpeza_id=ol.id AND p2.membro_id=?)';$params[]=$memberId;}
if($environmentId>0){$where[]='ol.ambiente_id=?';$params[]=$environmentId;}
if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateFrom)){$where[]='DATE(ol.criado_em)>=?';$params[]=$dateFrom;}
if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateTo)){$where[]='DATE(ol.criado_em)<=?';$params[]=$dateTo;}
$sql="SELECT ol.*,aq.nome_publico AS ambiente_nome,l.nome AS local_nome,sm.nome_colaborador AS supervisor_nome,cr.nome AS criado_por_nome,va.nome AS validado_por_nome,
 (SELECT GROUP_CONCAT(n.nome_colaborador ORDER BY p.responsavel_principal DESC,n.nome_colaborador SEPARATOR ', ') FROM limpeza_ordem_participantes p JOIN limpeza_organograma_nos n ON n.id=p.membro_id WHERE p.ordem_limpeza_id=ol.id AND p.planejado=1) AS equipe_planejada,
 (SELECT GROUP_CONCAT(n.nome_colaborador ORDER BY p.responsavel_principal DESC,n.nome_colaborador SEPARATOR ', ') FROM limpeza_ordem_participantes p JOIN limpeza_organograma_nos n ON n.id=p.membro_id WHERE p.ordem_limpeza_id=ol.id AND p.executou=1) AS equipe_executora,
 (SELECT COUNT(*) FROM limpeza_ordem_itens i WHERE i.ordem_limpeza_id=ol.id) AS total_itens,
 (SELECT COUNT(*) FROM limpeza_ordem_itens i WHERE i.ordem_limpeza_id=ol.id AND i.status_item='CONCLUIDO') AS itens_concluidos,
 TIMESTAMPDIFF(MINUTE,ol.iniciado_em,ol.concluido_em) AS duracao_minutos
 FROM limpeza_ordens ol
 LEFT JOIN ambientes_qr aq ON aq.id=ol.ambiente_id
 LEFT JOIN locais l ON l.id=ol.local_id
 LEFT JOIN limpeza_organograma_nos sm ON sm.id=ol.supervisor_membro_id
 LEFT JOIN usuarios cr ON cr.id=ol.criado_por
 LEFT JOIN usuarios va ON va.id=ol.validado_por";
if($where)$sql.=' WHERE '.implode(' AND ',$where);
$sql.=' ORDER BY ol.criado_em DESC LIMIT 2000';
$st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC)?:[];

if(($_GET['exportar']??'')==='csv'){
    header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="relatorio_ol_'.date('Ymd_His').'.csv"');
    echo "\xEF\xBB\xBF";$out=fopen('php://output','w');fputcsv($out,['OL','Título','Tipo','Prioridade','Status','Ambiente/Local','Supervisor','Equipe planejada','Equipe executora','Itens concluídos','Data programada','Início','Conclusão','Duração (min)','Criada por','Validada por','Criada em'],';');
    foreach($rows as $row)fputcsv($out,[$row['numero_ol'],$row['titulo'],ol_types()[$row['tipo']]??$row['tipo'],ol_priorities()[$row['prioridade']]??$row['prioridade'],ol_status_label($row['status']),$row['ambiente_nome']?:$row['local_nome'],$row['supervisor_nome'],$row['equipe_planejada'],$row['equipe_executora'],$row['itens_concluidos'].'/'.$row['total_itens'],$row['data_programada'],$row['iniciado_em'],$row['concluido_em'],$row['duracao_minutos'],$row['criado_por_nome'],$row['validado_por_nome'],$row['criado_em']],';');
    fclose($out);exit;
}
$metrics=['total'=>count($rows),'encerradas'=>0,'canceladas'=>0,'atrasadas'=>0,'duracao_media'=>0];$durations=[];
foreach($rows as $row){if($row['status']==='ENCERRADA')$metrics['encerradas']++;if($row['status']==='CANCELADA')$metrics['canceladas']++;if(!in_array($row['status'],['ENCERRADA','CANCELADA'],true)&&$row['prazo_em']&&strtotime($row['prazo_em'])<time())$metrics['atrasadas']++;if($row['duracao_minutos']!==null)$durations[]=(int)$row['duracao_minutos'];}
$metrics['duracao_media']=$durations?(int)round(array_sum($durations)/count($durations)):0;
$members=limpeza_members($pdo,true);$environments=$pdo->query("SELECT id,nome_publico FROM ambientes_qr WHERE ativo=1 ORDER BY nome_publico")->fetchAll(PDO::FETCH_ASSOC)?:[];
render_header('Relatórios OL');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 mb-1">Relatórios de Ordens de Limpeza</h1><p class="text-muted mb-0">Planejamento, equipe real, produtividade, prazo e validação.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?=e(app_path('ol'))?>">Ordens</a><a class="btn btn-success" href="<?=e(app_path('ol_relatorios',array_merge($_GET,['exportar'=>'csv'])))?>">Exportar CSV</a></div></div>
<div class="row g-3 mb-4"><?php foreach([['Registros',$metrics['total'],'primary'],['Encerradas',$metrics['encerradas'],'success'],['Canceladas',$metrics['canceladas'],'secondary'],['Atrasadas',$metrics['atrasadas'],'danger'],['Duração média',$metrics['duracao_media'].' min','info']] as $card): ?><div class="col-6 col-lg"><div class="card h-100 border-<?=$card[2]?>"><div class="card-body"><small class="text-muted"><?=e($card[0])?></small><strong class="d-block h4 mb-0"><?=e($card[1])?></strong></div></div></div><?php endforeach ?></div>
<form class="card card-body shadow-sm mb-4" method="get"><div class="row g-3 align-items-end"><div class="col-md-2"><label class="form-label">De</label><input class="form-control" type="date" name="de" value="<?=e($dateFrom)?>"></div><div class="col-md-2"><label class="form-label">Até</label><input class="form-control" type="date" name="ate" value="<?=e($dateTo)?>"></div><div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="">Todos</option><?php foreach(ol_statuses() as $code=>$label): ?><option value="<?=e($code)?>" <?=$status===$code?'selected':''?>><?=e($label)?></option><?php endforeach ?></select></div><div class="col-md-2"><label class="form-label">Prioridade</label><select class="form-select" name="prioridade"><option value="">Todas</option><?php foreach(ol_priorities() as $code=>$label): ?><option value="<?=e($code)?>" <?=$priority===$code?'selected':''?>><?=e($label)?></option><?php endforeach ?></select></div><div class="col-md-2"><label class="form-label">Funcionário</label><select class="form-select" name="membro_id"><option value="0">Todos</option><?php foreach($members as $member): ?><option value="<?=e($member['id'])?>" <?=$memberId===(int)$member['id']?'selected':''?>><?=e($member['nome_colaborador'])?></option><?php endforeach ?></select></div><div class="col-md-2"><label class="form-label">Ambiente</label><select class="form-select" name="ambiente_id"><option value="0">Todos</option><?php foreach($environments as $environment): ?><option value="<?=e($environment['id'])?>" <?=$environmentId===(int)$environment['id']?'selected':''?>><?=e($environment['nome_publico'])?></option><?php endforeach ?></select></div><div class="col-md-2 d-grid"><button class="btn btn-primary">Aplicar filtros</button></div></div></form>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-striped align-middle mb-0"><thead><tr><th>OL</th><th>Demanda</th><th>Local</th><th>Equipe planejada</th><th>Equipe executora</th><th>Resultado</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><td><a href="<?=e(app_path('ol_detalhe',['id'=>$row['id']]))?>"><strong><?=e($row['numero_ol'])?></strong></a><div><?=badge_prioridade($row['prioridade'])?></div></td><td><?=e($row['titulo'])?><small class="d-block text-muted"><?=e(ol_types()[$row['tipo']]??$row['tipo'])?></small></td><td><?=e($row['ambiente_nome']?:$row['local_nome'])?><small class="d-block text-muted"><?=e($row['supervisor_nome']?'Supervisor: '.$row['supervisor_nome']:'Sem supervisor definido')?></small></td><td><small><?=e($row['equipe_planejada']?:'-')?></small></td><td><small><?=e($row['equipe_executora']?:'-')?></small></td><td><?=e($row['itens_concluidos'].'/'.$row['total_itens'])?> itens<?php if($row['duracao_minutos']!==null): ?><small class="d-block text-muted"><?=e($row['duracao_minutos'])?> min</small><?php endif ?></td><td><span class="badge text-bg-<?=e(ol_status_class($row['status']))?>"><?=e(ol_status_label($row['status']))?></span></td></tr><?php endforeach ?><?php if(!$rows): ?><tr><td colspan="7" class="text-center text-muted py-5">Nenhum registro no período.</td></tr><?php endif ?></tbody></table></div></div>
<?php render_footer(); ?>
