<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/cftv.php';
require_once __DIR__ . '/../includes/audit.php';

require_login();
ensure_cftv_schema($pdo);
$user=current_user();
$perfil=strtoupper((string)($user['perfil']??''));
$canRegister=in_array($perfil,['TI','CFTV','CONTROLE_ACESSO'],true);

if($_SERVER['REQUEST_METHOD']==='POST'){
    check_csrf();
    if(!$canRegister){http_response_code(403);die('Acesso negado.');}
    $acao=(string)($_POST['acao']??'');
    $solicitacaoId=(int)($_POST['solicitacao_id']??0);
    $observacao=trim((string)($_POST['observacao']??''));
    if(!$solicitacaoId) throw new RuntimeException('Liberação inválida.');
    $pdo->beginTransaction();
    try{
        $st=$pdo->prepare("SELECT * FROM cftv_solicitacoes_acesso WHERE id=? AND status='APROVADA' FOR UPDATE");
        $st->execute([$solicitacaoId]);
        $sol=$st->fetch(PDO::FETCH_ASSOC);
        if(!$sol) throw new RuntimeException('Liberação aprovada não encontrada.');
        $dataOperacao=preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_POST['data']??''))?(string)$_POST['data']:date('Y-m-d');
        if($dataOperacao!==date('Y-m-d')) throw new RuntimeException('Entrada e saída só podem ser registradas no dia atual.');
        $fimSol=(string)($sol['data_fim']??$sol['data_acesso']);
        if($dataOperacao<(string)$sol['data_acesso']||$dataOperacao>$fimSol) throw new RuntimeException('A data selecionada está fora do período autorizado.');
        $st=$pdo->prepare("SELECT * FROM cftv_acesso_eventos WHERE solicitacao_id=? AND DATE(registrado_em)=? ORDER BY id DESC LIMIT 1");
        $st->execute([$solicitacaoId,$dataOperacao]);
        $ultimo=$st->fetch(PDO::FETCH_ASSOC)?:null;
        if($acao==='entrada'){
            if($ultimo && in_array($ultimo['tipo'],['ENTRADA','SAIDA'],true)) throw new RuntimeException('A entrada desta liberação já foi registrada.');
            $tipo='ENTRADA';
        }elseif($acao==='saida'){
            if(!$ultimo || $ultimo['tipo']!=='ENTRADA') throw new RuntimeException('Registre a entrada antes da saída.');
            $tipo='SAIDA';
        }else{
            throw new RuntimeException('Ação inválida.');
        }
        $pdo->prepare("INSERT INTO cftv_acesso_eventos(solicitacao_id,tipo,registrado_por,observacao) VALUES(?,?,?,?)")
            ->execute([$solicitacaoId,$tipo,(int)$user['id'],$observacao!==''?$observacao:null]);
        auditoria_log($pdo,'cftv_acesso',$solicitacaoId,'REGISTRAR_'.$tipo,null,null,$sol['protocolo'],(int)$user['id']);
        $pdo->commit();
        $_SESSION['flash_success']=$tipo==='ENTRADA'?'Entrada registrada automaticamente.':'Saída registrada automaticamente.';
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        $_SESSION['flash_error']=$e->getMessage();
    }
    $retMonth=preg_match('/^\d{4}-\d{2}$/',(string)($_POST['mes']??''))?(string)$_POST['mes']:date('Y-m');
    $retData=preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_POST['data']??''))?(string)$_POST['data']:date('Y-m-d');
    redirect('liberacoes_aprovadas?mes='.urlencode($retMonth).'&data='.urlencode($retData).'&detalhe='.$solicitacaoId);
}

$mes=preg_match('/^\d{4}-\d{2}$/',(string)($_GET['mes']??''))?(string)$_GET['mes']:date('Y-m');
$primeiroDia=$mes.'-01';
$mesTs=strtotime($primeiroDia);
if($mesTs===false){$mes=date('Y-m');$primeiroDia=$mes.'-01';$mesTs=strtotime($primeiroDia);}
$ultimoDia=date('Y-m-t',$mesTs);
$data=preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['data']??''))?(string)$_GET['data']:date('Y-m-d');
if(substr($data,0,7)!==$mes)$data=$primeiroDia;
$busca=trim((string)($_GET['busca']??''));

// Resumo mensal para o calendário.
$st=$pdo->prepare("SELECT s.id,s.protocolo,s.nome_completo,s.data_acesso,s.data_fim,s.hora_inicio,s.hora_fim,s.destino_nome,
 (SELECT MIN(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='ENTRADA') entrada,
 (SELECT MAX(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='SAIDA') saida
 FROM cftv_solicitacoes_acesso s
 WHERE s.status='APROVADA' AND s.data_acesso <= ? AND COALESCE(s.data_fim,s.data_acesso) >= ?
 ORDER BY s.data_acesso,COALESCE(s.hora_inicio,'00:00:00'),s.nome_completo");
$st->execute([$ultimoDia,$primeiroDia]);
$mensais=$st->fetchAll(PDO::FETCH_ASSOC)?:[];
$porDia=[];
foreach($mensais as $item){
    $ini=max($primeiroDia,(string)$item['data_acesso']);
    $fim=min($ultimoDia,(string)($item['data_fim']??$item['data_acesso']));
    for($ts=strtotime($ini),$end=strtotime($fim);$ts!==false&&$end!==false&&$ts<=$end;$ts=strtotime('+1 day',$ts)) $porDia[date('Y-m-d',$ts)][]=$item;
}

$where=["s.status='APROVADA'","? BETWEEN s.data_acesso AND COALESCE(s.data_fim,s.data_acesso)"];$params=[$data];
if($busca!==''){
    $where[]="(s.nome_completo LIKE ? OR s.cpf LIKE ? OR s.email LIKE ? OR s.protocolo LIKE ? OR s.destino_nome LIKE ? OR s.empresa LIKE ? OR s.responsavel_interno LIKE ?)";
    $like='%'.$busca.'%';array_push($params,$like,$like,$like,$like,$like,$like,$like);
}
$st=$pdo->prepare("SELECT s.*,
 (SELECT MIN(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='ENTRADA' AND DATE(e.registrado_em)=".$pdo->quote($data).") entrada,
 (SELECT MAX(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='SAIDA' AND DATE(e.registrado_em)=".$pdo->quote($data).") saida,
 (SELECT COUNT(*) FROM cftv_solicitacao_pessoas p WHERE p.solicitacao_id=s.id) acompanhantes
 FROM cftv_solicitacoes_acesso s WHERE ".implode(' AND ',$where)."
 ORDER BY COALESCE(s.hora_inicio,'00:00:00'),s.nome_completo");
$st->execute($params);$liberacoes=$st->fetchAll(PDO::FETCH_ASSOC)?:[];

$detalhe=null;$acompanhantesDetalhe=[];$eventosDetalhe=[];
$detalheId=(int)($_GET['detalhe']??0);
if($detalheId>0){
    $sd=$pdo->prepare("SELECT s.*,u.nome AS decidido_por_nome,
      (SELECT MIN(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='ENTRADA') entrada,
      (SELECT MAX(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='SAIDA') saida
      FROM cftv_solicitacoes_acesso s LEFT JOIN usuarios u ON u.id=s.decidido_por
      WHERE s.id=? AND s.status='APROVADA' LIMIT 1");
    $sd->execute([$detalheId]);$detalhe=$sd->fetch(PDO::FETCH_ASSOC)?:null;
    if($detalhe){
        $sa=$pdo->prepare("SELECT nome_completo,cpf FROM cftv_solicitacao_pessoas WHERE solicitacao_id=? ORDER BY id");$sa->execute([$detalheId]);$acompanhantesDetalhe=$sa->fetchAll(PDO::FETCH_ASSOC)?:[];
        $se=$pdo->prepare("SELECT e.*,u.nome AS usuario_nome FROM cftv_acesso_eventos e LEFT JOIN usuarios u ON u.id=e.registrado_por WHERE e.solicitacao_id=? ORDER BY e.registrado_em");$se->execute([$detalheId]);$eventosDetalhe=$se->fetchAll(PDO::FETCH_ASSOC)?:[];
    }
}
$aguardando=count(array_filter($liberacoes,fn($i)=>!$i['entrada']));
$presentes=count(array_filter($liberacoes,fn($i)=>$i['entrada']&&!$i['saida']));
$finalizadas=count(array_filter($liberacoes,fn($i)=>(bool)$i['saida']));
$prevMes=date('Y-m',strtotime('-1 month',$mesTs));$nextMes=date('Y-m',strtotime('+1 month',$mesTs));
$inicioSemana=(int)date('w',$mesTs);$diasMes=(int)date('t',$mesTs);
$flashSuccess=$_SESSION['flash_success']??'';$flashError=$_SESSION['flash_error']??'';unset($_SESSION['flash_success'],$_SESSION['flash_error']);

render_header('Liberações ativas');
?>
<style>
.cal-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:.4rem}.cal-head{text-align:center;font-size:.78rem;color:var(--bs-secondary-color);font-weight:600}.cal-day{min-height:92px;border:1px solid var(--bs-border-color);border-radius:.7rem;padding:.45rem;text-decoration:none;color:inherit;background:var(--bs-body-bg);display:block}.cal-day:hover{border-color:var(--bs-primary);background:var(--bs-tertiary-bg)}.cal-day.selected{border:2px solid var(--bs-primary)}.cal-day.empty{border-color:transparent;background:transparent}.cal-num{font-weight:700}.cal-count{font-size:.72rem}.cal-item{font-size:.7rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem}.detail-box{border:1px solid var(--bs-border-color);border-radius:.65rem;padding:.75rem;background:var(--bs-tertiary-bg)}.detail-box small{display:block;color:var(--bs-secondary-color)}.wide{grid-column:1/-1}@media(max-width:767px){.cal-grid{gap:.2rem}.cal-day{min-height:60px;padding:.3rem}.cal-item{display:none}.detail-grid{grid-template-columns:1fr}.cal-count{font-size:.66rem}}
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h1 class="h3 mb-0">Liberações ativas</h1><small class="text-muted">Entrada e saída com horário automático e calendário mensal.</small></div><div class="d-flex gap-2"><a class="btn btn-outline-dark" href="<?=e(app_path('cftv_historico_acessos'))?>">Histórico</a><a class="btn btn-primary" href="<?=e(app_path('controle_acesso'))?>">Leitor de QR</a></div></div>
<?php if($flashSuccess):?><div class="alert alert-success"><?=e($flashSuccess)?></div><?php endif?><?php if($flashError):?><div class="alert alert-danger"><?=e($flashError)?></div><?php endif?>
<div class="card p-3 mb-3">
 <div class="d-flex justify-content-between align-items-center mb-3"><a class="btn btn-sm btn-outline-secondary" href="<?=e(app_path('liberacoes_aprovadas',['mes'=>$prevMes,'data'=>$prevMes.'-01']))?>">‹ Mês anterior</a><h2 class="h5 mb-0"><?=e(ucfirst(strftime('%B de %Y',$mesTs)))?></h2><a class="btn btn-sm btn-outline-secondary" href="<?=e(app_path('liberacoes_aprovadas',['mes'=>$nextMes,'data'=>$nextMes.'-01']))?>">Próximo mês ›</a></div>
 <div class="cal-grid mb-1"><?php foreach(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $d):?><div class="cal-head"><?=e($d)?></div><?php endforeach?></div>
 <div class="cal-grid">
 <?php for($i=0;$i<$inicioSemana;$i++):?><div class="cal-day empty"></div><?php endfor?>
 <?php for($dia=1;$dia<=$diasMes;$dia++):$date=$mes.'-'.str_pad((string)$dia,2,'0',STR_PAD_LEFT);$items=$porDia[$date]??[]; ?>
  <a class="cal-day <?=$date===$data?'selected':''?>" href="<?=e(app_path('liberacoes_aprovadas',['mes'=>$mes,'data'=>$date]))?>"><div class="d-flex justify-content-between"><span class="cal-num"><?=$dia?></span><?php if($items):?><span class="badge text-bg-primary cal-count"><?=count($items)?></span><?php endif?></div><?php foreach(array_slice($items,0,2) as $it):?><div class="cal-item mt-1"><?=e(substr((string)$it['hora_inicio'],0,5).' '.$it['nome_completo'])?></div><?php endforeach?></a>
 <?php endfor?>
 </div>
</div>
<div class="row g-2 mb-3"><?php foreach([['Liberados',count($liberacoes),'primary'],['Aguardando entrada',$aguardando,'warning'],['No local',$presentes,'success'],['Saída registrada',$finalizadas,'secondary']] as $c):?><div class="col-6 col-lg-3"><div class="card p-3 border-start border-<?=$c[2]?> border-4"><strong class="h3 mb-0"><?=$c[1]?></strong><small><?=$c[0]?></small></div></div><?php endforeach?></div>
<div class="card p-3 mb-3"><form method="get" class="row g-2"><input type="hidden" name="mes" value="<?=e($mes)?>"><div class="col-md-3"><label class="form-label">Dia selecionado</label><input class="form-control" type="date" name="data" value="<?=e($data)?>"></div><div class="col-md-7"><label class="form-label">Pesquisar</label><input class="form-control" name="busca" value="<?=e($busca)?>" placeholder="Nome, CPF, e-mail, protocolo, empresa ou destino"></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-dark w-100">Consultar</button></div></form></div>
<?php if($detalhe):?>
<div class="card p-3 mb-3 border-primary"><div class="d-flex flex-wrap justify-content-between gap-2 mb-3"><div><span class="badge text-bg-success">Aprovada</span><h2 class="h4 mt-2 mb-0"><?=e($detalhe['nome_completo'])?></h2><small>Protocolo <?=e($detalhe['protocolo'])?></small></div><a class="btn btn-sm btn-outline-secondary align-self-start" href="<?=e(app_path('liberacoes_aprovadas',['mes'=>$mes,'data'=>$data,'busca'=>$busca]))?>">Fechar</a></div>
<div class="detail-grid"><div class="detail-box"><small>CPF</small><?=e(cftv_formatar_cpf($detalhe['cpf']))?></div><div class="detail-box"><small>Telefone</small><?=e($detalhe['telefone']?:'—')?></div><div class="detail-box"><small>E-mail</small><?=e($detalhe['email']?:'—')?></div><div class="detail-box"><small>Empresa</small><?=e($detalhe['empresa']?:'—')?></div><div class="detail-box"><small>Responsável interno</small><?=e($detalhe['responsavel_interno']?:'—')?></div><div class="detail-box"><small>Veículo</small><?=e($detalhe['veiculo_placa']?:'—')?></div><div class="detail-box"><small>Destino</small><?=e($detalhe['destino_nome'])?></div><div class="detail-box"><small>Autorizado</small><?=e(date('d/m/Y',strtotime($detalhe['data_acesso'])).((!empty($detalhe['data_fim'])&&$detalhe['data_fim']!==$detalhe['data_acesso'])?' a '.date('d/m/Y',strtotime($detalhe['data_fim'])):'').' '.substr((string)$detalhe['hora_inicio'],0,5).'–'.substr((string)$detalhe['hora_fim'],0,5))?></div><div class="detail-box"><small>Aprovado por</small><?=e($detalhe['decidido_por_nome']?:'—')?></div><div class="detail-box wide"><small>Motivo</small><?=nl2br(e($detalhe['motivo']?:'—'))?></div><div class="detail-box wide"><small>Observações</small><?=nl2br(e($detalhe['observacoes']?:'—'))?></div><div class="detail-box wide"><small>Acompanhantes</small><?php if($acompanhantesDetalhe):?><ul class="mb-0"><?php foreach($acompanhantesDetalhe as $a):?><li><?=e($a['nome_completo'].' — '.cftv_formatar_cpf($a['cpf']))?></li><?php endforeach?></ul><?php else:?>Nenhum.<?php endif?></div><div class="detail-box wide"><small>Movimentação</small><?php if($eventosDetalhe):?><ul class="mb-0"><?php foreach($eventosDetalhe as $ev):?><li><strong><?=e($ev['tipo'])?></strong> em <?=e(date('d/m/Y H:i:s',strtotime($ev['registrado_em'])))?> por <?=e($ev['usuario_nome']?:'Usuário')?><?=!empty($ev['observacao'])?' — '.e($ev['observacao']):''?></li><?php endforeach?></ul><?php else:?>Sem entrada registrada.<?php endif?></div></div></div>
<?php endif?>
<div class="card p-3"><h2 class="h5">Liberações de <?=e(date('d/m/Y',strtotime($data)))?></h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pessoa</th><th>Destino</th><th>Horário</th><th>Situação</th><th>Ações</th></tr></thead><tbody>
<?php foreach($liberacoes as $item):?><tr><td><strong><?=e($item['nome_completo'])?></strong><br><small><?=e($item['protocolo'])?> · CPF <?=e(cftv_formatar_cpf($item['cpf']))?></small><?php if(!empty($item['empresa'])):?><br><small><?=e($item['empresa'])?></small><?php endif?></td><td><?=e($item['destino_nome'])?></td><td><?=e(substr((string)$item['hora_inicio'],0,5).'–'.substr((string)$item['hora_fim'],0,5))?></td><td><?php if($item['saida']):?><span class="badge text-bg-secondary">Finalizada</span><br><small>Saída <?=e(date('H:i:s',strtotime($item['saida'])))?></small><?php elseif($item['entrada']):?><span class="badge text-bg-success">No local</span><br><small>Entrada <?=e(date('H:i:s',strtotime($item['entrada'])))?></small><?php else:?><span class="badge text-bg-warning">Aguardando</span><?php endif?></td><td><div class="d-flex flex-wrap gap-1"><a class="btn btn-sm btn-outline-primary" href="<?=e(app_path('liberacoes_aprovadas',['mes'=>$mes,'data'=>$data,'busca'=>$busca,'detalhe'=>$item['id']]))?>">Detalhes</a><?php if($canRegister && $data===date('Y-m-d') && !$item['entrada']):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="acao" value="entrada"><input type="hidden" name="solicitacao_id" value="<?=$item['id']?>"><input type="hidden" name="mes" value="<?=e($mes)?>"><input type="hidden" name="data" value="<?=e($data)?>"><button class="btn btn-sm btn-success">Registrar entrada</button></form><?php elseif($canRegister && $data===date('Y-m-d') && $item['entrada'] && !$item['saida']):?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="acao" value="saida"><input type="hidden" name="solicitacao_id" value="<?=$item['id']?>"><input type="hidden" name="mes" value="<?=e($mes)?>"><input type="hidden" name="data" value="<?=e($data)?>"><button class="btn btn-sm btn-outline-secondary">Registrar saída</button></form><?php endif?></div></td></tr><?php endforeach;if(!$liberacoes):?><tr><td colspan="5" class="text-center text-muted p-4">Nenhuma liberação aprovada neste dia.</td></tr><?php endif?></tbody></table></div></div>
<?php render_footer(); ?>
