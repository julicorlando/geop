<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/cftv.php';
require_role(array_values(array_unique(array_merge(['TI','CFTV'],cftv_liberacao_perfis()))));
ensure_cftv_schema($pdo);

$inicio=preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['inicio']??''))?(string)$_GET['inicio']:date('Y-m-01');
$fim=preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)($_GET['fim']??''))?(string)$_GET['fim']:date('Y-m-d');
$status=strtoupper(trim((string)($_GET['status']??'')));
$busca=trim((string)($_GET['busca']??''));
$tipo=strtoupper(trim((string)($_GET['tipo']??'')));

$where=['s.data_acesso <= ? AND COALESCE(s.data_fim,s.data_acesso) >= ?'];$params=[$fim,$inicio];
if(in_array($status,['PENDENTE','APROVADA','REPROVADA'],true)){$where[]='s.status=?';$params[]=$status;}
if(in_array($tipo,['LOJA_MALL','PRACA_ALIMENTACAO','ADMINISTRACAO','AREA_SERVICO'],true)){$where[]='s.destino_tipo=?';$params[]=$tipo;}
if($busca!==''){
    $cpf=cftv_normalizar_cpf($busca);
    $where[]="(s.protocolo LIKE ? OR s.nome_completo LIKE ? OR s.destino_nome LIKE ? OR s.cpf LIKE ?
      OR EXISTS(SELECT 1 FROM cftv_solicitacao_pessoas px WHERE px.solicitacao_id=s.id AND (px.nome_completo LIKE ? OR px.cpf LIKE ?)))";
    foreach([$busca,$busca,$busca,$cpf?:$busca,$busca,$cpf?:$busca] as $valor)$params[]='%'.$valor.'%';
}
$st=$pdo->prepare("SELECT s.*,d.nome decidido_por_nome,
 (SELECT MIN(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='ENTRADA') entrada_registrada,
 (SELECT MAX(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='SAIDA') saida_registrada,
 (SELECT GROUP_CONCAT(CONCAT(p.nome_completo,' — CPF ',p.cpf) SEPARATOR '\n')
  FROM cftv_solicitacao_pessoas p WHERE p.solicitacao_id=s.id) acompanhantes
 FROM cftv_solicitacoes_acesso s LEFT JOIN usuarios d ON d.id=s.decidido_por
 WHERE ".implode(' AND ',$where)." ORDER BY s.criado_em DESC LIMIT 500");
$st->execute($params);$solicitacoes=$st->fetchAll(PDO::FETCH_ASSOC);

$whereLegado=['l.data_acesso <= ? AND COALESCE(l.data_fim,l.data_acesso) >= ?','NOT EXISTS(SELECT 1 FROM cftv_solicitacoes_acesso sx WHERE sx.liberacao_id=l.id)'];$paramsLegado=[$fim,$inicio];
if($busca!==''){$cpf=cftv_normalizar_cpf($busca);$whereLegado[]='(l.nome_completo LIKE ? OR l.destino_nome LIKE ? OR l.cpf LIKE ?)';$paramsLegado[]='%'.$busca.'%';$paramsLegado[]='%'.$busca.'%';$paramsLegado[]='%'.($cpf?:$busca).'%';}
$st=$pdo->prepare("SELECT l.*,u.nome criado_por_nome FROM cftv_liberacoes l LEFT JOIN usuarios u ON u.id=l.criado_por
 WHERE ".implode(' AND ',$whereLegado)." ORDER BY l.criado_em DESC LIMIT 500");
$st->execute($paramsLegado);$liberacoesAntigas=$st->fetchAll(PDO::FETCH_ASSOC);

$resumo=['total'=>count($solicitacoes),'pendentes'=>0,'aprovadas'=>0,'reprovadas'=>0];
foreach($solicitacoes as $s){$chave=strtolower($s['status']).'s';if(isset($resumo[$chave]))$resumo[$chave]++;}
render_header('Histórico de solicitações de acesso');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h1 class="h3 mb-0">Base histórica de acessos</h1><small class="text-muted">Consulta e controle. Os registros desta página não podem ser alterados.</small></div><a class="btn btn-outline-primary" href="<?=e(app_path('cftv',['view'=>'liberacoes']))?>">Voltar às liberações</a></div>
<div class="row g-2 mb-3"><?php foreach([['Solicitações',$resumo['total'],'primary'],['Pendentes',$resumo['pendentes'],'warning'],['Aprovadas',$resumo['aprovadas'],'success'],['Reprovadas',$resumo['reprovadas'],'danger'],['Liberações anteriores',count($liberacoesAntigas),'secondary']] as $card): ?><div class="col-6 col-lg"><div class="card p-3 border-start border-<?=$card[2]?> border-4"><strong class="h4 mb-0"><?=e($card[1])?></strong><small><?=e($card[0])?></small></div></div><?php endforeach ?></div>
<div class="card p-3 mb-3"><form class="row g-2"><div class="col-md-2"><label class="form-label">Início</label><input class="form-control" type="date" name="inicio" value="<?=e($inicio)?>"></div><div class="col-md-2"><label class="form-label">Fim</label><input class="form-control" type="date" name="fim" value="<?=e($fim)?>"></div><div class="col-md-2"><label class="form-label">Situação</label><select class="form-select" name="status"><option value="">Todas</option><?php foreach(['PENDENTE','APROVADA','REPROVADA'] as $s): ?><option <?=$status===$s?'selected':''?>><?=$s?></option><?php endforeach ?></select></div><div class="col-md-2"><label class="form-label">Setor</label><select class="form-select" name="tipo"><option value="">Todos</option><?php foreach(['LOJA_MALL','PRACA_ALIMENTACAO','ADMINISTRACAO','AREA_SERVICO'] as $t): ?><option value="<?=$t?>" <?=$tipo===$t?'selected':''?>><?=e(cftv_destinos_liberacao()[$t])?></option><?php endforeach ?></select></div><div class="col-md-3"><label class="form-label">Busca</label><input class="form-control" name="busca" value="<?=e($busca)?>" placeholder="Nome, CPF, protocolo ou destino"></div><div class="col-md-1 d-flex align-items-end"><button class="btn btn-dark w-100">Filtrar</button></div></form></div>
<div class="card p-3 mb-3"><h2 class="h5">Solicitações públicas</h2><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Protocolo</th><th>Solicitante e grupo</th><th>Acesso</th><th>Destino</th><th>Situação</th><th>Entrada/saída</th><th>Decisão</th></tr></thead><tbody><?php foreach($solicitacoes as $s): ?><tr><td><strong><?=e($s['protocolo'])?></strong><br><small><?=e($s['criado_em'])?></small></td><td><?=e($s['nome_completo'])?><br><small>CPF <?=e(cftv_formatar_cpf($s['cpf']))?></small><?php if($s['acompanhantes']): ?><details><summary class="small">Demais pessoas</summary><small><?=nl2br(e($s['acompanhantes']))?></small></details><?php endif ?></td><td><?=e(date('d/m/Y',strtotime($s['data_acesso'])))?><?=(!empty($s['data_fim'])&&$s['data_fim']!==$s['data_acesso'])?' a '.e(date('d/m/Y',strtotime($s['data_fim']))):''?><br><small><?=e(substr((string)$s['hora_inicio'],0,5))?>–<?=e(substr((string)$s['hora_fim'],0,5))?></small></td><td><?=e(cftv_destinos_liberacao()[$s['destino_tipo']]??$s['destino_tipo'])?><br><small><?=e($s['destino_nome'])?></small></td><td><span class="badge text-bg-<?=['PENDENTE'=>'warning','APROVADA'=>'success','REPROVADA'=>'danger'][$s['status']]??'secondary'?>"><?=e($s['status'])?></span></td><td><small>Entrada: <?=e($s['entrada_registrada']?:'-')?><br>Saída: <?=e($s['saida_registrada']?:'-')?></small></td><td><?=e($s['decidido_por_nome']?:'-')?><br><small><?=e($s['decisao_justificativa']?:'')?></small></td></tr><?php endforeach; if(!$solicitacoes): ?><tr><td colspan="7" class="text-center text-muted">Nenhuma solicitação encontrada.</td></tr><?php endif ?></tbody></table></div></div>
<div class="card p-3"><h2 class="h5">Liberações existentes anteriores ao formulário público</h2><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Data</th><th>Pessoa</th><th>Destino</th><th>Status</th><th>Cadastrada por</th></tr></thead><tbody><?php foreach($liberacoesAntigas as $l): ?><tr><td><?=e(date('d/m/Y',strtotime($l['data_acesso'])))?><?=(!empty($l['data_fim'])&&$l['data_fim']!==$l['data_acesso'])?' a '.e(date('d/m/Y',strtotime($l['data_fim']))):''?><br><small><?=e(substr((string)$l['hora_inicio'],0,5)?:'-')?>–<?=e(substr((string)$l['hora_fim'],0,5)?:'-')?></small></td><td><?=e($l['nome_completo'])?><br><small><?=e(cftv_formatar_cpf($l['cpf']))?></small></td><td><?=e($l['destino_nome'])?></td><td><?=e($l['status'])?></td><td><?=e($l['criado_por_nome']?:'-')?></td></tr><?php endforeach; if(!$liberacoesAntigas): ?><tr><td colspan="5" class="text-center text-muted">Nenhuma liberação anterior encontrada.</td></tr><?php endif ?></tbody></table></div></div>
<?php render_footer(); ?>
