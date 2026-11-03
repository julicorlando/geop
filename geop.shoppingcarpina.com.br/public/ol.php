<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ordens_limpeza.php';

ol_require_access();
ol_require_schema($pdo);

$status=(string)($_GET['status'] ?? 'ATIVAS');
$allowedStatus=array_merge(['ATIVAS','TODAS'],array_keys(ol_statuses()));
if(!in_array($status,$allowedStatus,true)) $status='ATIVAS';
$priority=(string)($_GET['prioridade'] ?? '');
if($priority!=='' && !array_key_exists($priority,ol_priorities())) $priority='';
$memberId=(int)($_GET['membro_id'] ?? 0);
$search=trim((string)($_GET['q'] ?? ''));
$dateFrom=trim((string)($_GET['de'] ?? ''));
$dateTo=trim((string)($_GET['ate'] ?? ''));

$where=[exclusao_sql_nao_excluida($pdo,'limpeza_ordens','ol')];$params=[];
if($status==='ATIVAS') $where[]="ol.status NOT IN ('ENCERRADA','CANCELADA')";
elseif($status!=='TODAS'){ $where[]='ol.status=?'; $params[]=$status; }
if($priority!==''){ $where[]='ol.prioridade=?'; $params[]=$priority; }
if($memberId>0){ $where[]='EXISTS(SELECT 1 FROM limpeza_ordem_participantes fp WHERE fp.ordem_limpeza_id=ol.id AND fp.membro_id=?)'; $params[]=$memberId; }
if($search!==''){ $where[]='(ol.numero_ol LIKE ? OR ol.titulo LIKE ? OR ol.descricao LIKE ? OR aq.nome_publico LIKE ? OR l.nome LIKE ?)'; $term='%'.$search.'%'; array_push($params,$term,$term,$term,$term,$term); }
if($dateFrom!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateFrom)){ $where[]='DATE(COALESCE(ol.data_programada,ol.criado_em))>=?'; $params[]=$dateFrom; }
if($dateTo!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$dateTo)){ $where[]='DATE(COALESCE(ol.data_programada,ol.criado_em))<=?'; $params[]=$dateTo; }

$sql="SELECT ol.*,aq.nome_publico AS ambiente_nome,l.nome AS local_nome,sm.nome_colaborador AS supervisor_nome,
    (SELECT GROUP_CONCAT(CASE WHEN p.responsavel_principal=1 THEN CONCAT(n.nome_colaborador,' (principal)') ELSE n.nome_colaborador END ORDER BY p.responsavel_principal DESC,n.nome_colaborador SEPARATOR ', ')
     FROM limpeza_ordem_participantes p JOIN limpeza_organograma_nos n ON n.id=p.membro_id WHERE p.ordem_limpeza_id=ol.id AND p.planejado=1) AS equipe,
    (SELECT COUNT(*) FROM limpeza_ordem_itens i WHERE i.ordem_limpeza_id=ol.id) AS total_itens,
    (SELECT COUNT(*) FROM limpeza_ordem_itens i WHERE i.ordem_limpeza_id=ol.id AND i.status_item='CONCLUIDO') AS itens_concluidos
    FROM limpeza_ordens ol
    LEFT JOIN ambientes_qr aq ON aq.id=ol.ambiente_id
    LEFT JOIN locais l ON l.id=ol.local_id
    LEFT JOIN limpeza_organograma_nos sm ON sm.id=ol.supervisor_membro_id";
if($where) $sql.=' WHERE '.implode(' AND ',$where);
$sql.=" ORDER BY FIELD(ol.status,'AGUARDANDO_VALIDACAO','CORRECAO_SOLICITADA','EM_ANDAMENTO','ATRIBUIDA','ABERTA','ENCERRADA','CANCELADA'),
    FIELD(ol.prioridade,'CRITICA','ALTA','MEDIA','BAIXA'),COALESCE(ol.prazo_em,CONCAT(ol.data_programada,' 23:59:59'),ol.criado_em) ASC LIMIT 400";
$st=$pdo->prepare($sql);$st->execute($params);$orders=$st->fetchAll(PDO::FETCH_ASSOC)?:[];

$metrics=['ativas'=>0,'andamento'=>0,'validacao'=>0,'atrasadas'=>0,'encerradas_mes'=>0];
try{
    $row=$pdo->query("SELECT
        SUM(status NOT IN ('ENCERRADA','CANCELADA')) AS ativas,
        SUM(status='EM_ANDAMENTO') AS andamento,
        SUM(status='AGUARDANDO_VALIDACAO') AS validacao,
        SUM(status NOT IN ('ENCERRADA','CANCELADA') AND prazo_em IS NOT NULL AND prazo_em<NOW()) AS atrasadas,
        SUM(status='ENCERRADA' AND YEAR(encerrado_em)=YEAR(CURDATE()) AND MONTH(encerrado_em)=MONTH(CURDATE())) AS encerradas_mes
        FROM limpeza_ordens WHERE ".exclusao_sql_nao_excluida($pdo,'limpeza_ordens'))->fetch(PDO::FETCH_ASSOC)?:[];
    foreach($metrics as $key=>$value) $metrics[$key]=(int)($row[$key]??0);
}catch(Throwable $e){ error_log('[GEOP OL métricas] '.$e->getMessage()); }
$members=limpeza_members($pdo,true);

render_header('Ordens de Limpeza');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div><h1 class="h3 mb-1">OL — Ordens de Limpeza</h1><p class="text-muted mb-0">Abertura, equipe, execução, evidências, supervisão e encerramento das demandas de limpeza.</p></div>
  <div class="d-flex gap-2"><?php if(usuario_pode('ol.relatorios')): ?><a class="btn btn-outline-secondary" href="<?=e(app_path('ol_relatorios'))?>">Relatórios</a><?php endif ?><?php if(usuario_pode('ol.criar')): ?><a class="btn btn-primary" href="<?=e(app_path('ol_form'))?>">Nova OL</a><?php endif ?></div>
</div>
<div class="row g-3 mb-4">
<?php foreach([
 ['Ativas',$metrics['ativas'],'primary'],['Em andamento',$metrics['andamento'],'warning'],['Aguardando validação',$metrics['validacao'],'info'],['Atrasadas',$metrics['atrasadas'],'danger'],['Encerradas no mês',$metrics['encerradas_mes'],'success']
] as $card): ?><div class="col-6 col-lg"><div class="card h-100 border-<?=$card[2]?>"><div class="card-body"><small class="text-muted"><?=e($card[0])?></small><strong class="d-block h3 mb-0"><?=e($card[1])?></strong></div></div></div><?php endforeach ?>
</div>
<form class="card card-body shadow-sm mb-4" method="get">
  <div class="row g-3 align-items-end">
    <div class="col-md-3"><label class="form-label">Pesquisar</label><input class="form-control" name="q" value="<?=e($search)?>" placeholder="Número, título ou ambiente"></div>
    <div class="col-md-2"><label class="form-label">Status</label><select class="form-select" name="status"><option value="ATIVAS" <?=$status==='ATIVAS'?'selected':''?>>Ativas</option><option value="TODAS" <?=$status==='TODAS'?'selected':''?>>Todas</option><?php foreach(ol_statuses() as $code=>$label): ?><option value="<?=e($code)?>" <?=$status===$code?'selected':''?>><?=e($label)?></option><?php endforeach ?></select></div>
    <div class="col-md-2"><label class="form-label">Prioridade</label><select class="form-select" name="prioridade"><option value="">Todas</option><?php foreach(ol_priorities() as $code=>$label): ?><option value="<?=e($code)?>" <?=$priority===$code?'selected':''?>><?=e($label)?></option><?php endforeach ?></select></div>
    <div class="col-md-3"><label class="form-label">Funcionário</label><select class="form-select" name="membro_id"><option value="0">Todos</option><?php foreach($members as $member): ?><option value="<?=e($member['id'])?>" <?=$memberId===(int)$member['id']?'selected':''?>><?=e($member['nome_colaborador'].' — '.$member['cargo'])?></option><?php endforeach ?></select></div>
    <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Filtrar</button></div>
    <div class="col-md-2"><label class="form-label">De</label><input class="form-control" type="date" name="de" value="<?=e($dateFrom)?>"></div>
    <div class="col-md-2"><label class="form-label">Até</label><input class="form-control" type="date" name="ate" value="<?=e($dateTo)?>"></div>
    <div class="col-md-2"><a class="btn btn-outline-secondary w-100" href="<?=e(app_path('ol'))?>">Limpar filtros</a></div>
  </div>
</form>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>OL</th><th>Demanda</th><th>Local/ambiente</th><th>Equipe</th><th>Programação</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach($orders as $order): $overdue=!in_array($order['status'],['ENCERRADA','CANCELADA'],true)&&$order['prazo_em']&&strtotime($order['prazo_em'])<time(); ?>
<tr>
 <td><strong><?=e($order['numero_ol'])?></strong><div><?=badge_prioridade($order['prioridade'])?></div></td>
 <td><strong><?=e($order['titulo'])?></strong><div class="small text-muted"><?=e(ol_types()[$order['tipo']]??$order['tipo'])?> · <?=e($order['itens_concluidos'].'/'.$order['total_itens'])?> itens</div></td>
 <td><?=e($order['ambiente_nome'] ?: $order['local_nome'] ?: 'Não informado')?><?php if($order['supervisor_nome']): ?><div class="small text-muted">Supervisão: <?=e($order['supervisor_nome'])?></div><?php endif ?></td>
 <td><span class="small"><?=e($order['equipe'] ?: 'Sem equipe definida')?></span></td>
 <td><?php if($order['data_programada']): ?><?=e(date('d/m/Y',strtotime($order['data_programada'])))?><?php if($order['hora_inicio_prevista']): ?> às <?=e(substr($order['hora_inicio_prevista'],0,5))?><?php endif ?><?php else: ?><span class="text-muted">Sem data</span><?php endif ?><?php if($overdue): ?><div class="badge text-bg-danger mt-1">Atrasada</div><?php endif ?></td>
 <td><span class="badge text-bg-<?=e(ol_status_class($order['status']))?>"><?=e(ol_status_label($order['status']))?></span></td>
 <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?=e(app_path('ol_detalhe',['id'=>$order['id']]))?>">Abrir</a></td>
</tr>
<?php endforeach ?>
<?php if(!$orders): ?><tr><td colspan="7" class="text-center text-muted py-5">Nenhuma Ordem de Limpeza encontrada.</td></tr><?php endif ?>
</tbody></table></div></div>
<?php render_footer(); ?>
