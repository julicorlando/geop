<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/layout.php';require_once __DIR__.'/../includes/geop_v8.php';require_once __DIR__.'/../includes/gestao_integrada.php';require_once __DIR__.'/../includes/exclusao_ordens.php';
require_role(['TI','GESTOR','GESTOR_SINDICO','COORDENADOR_OPERACOES_OBRAS']);check_csrf();geop_v8_boot($pdo);
if (!geop_v8_feature_ready($pdo)) {
    if ((current_user()['perfil'] ?? '') === 'TI') redirect('atualizar_geop_v8');
    http_response_code(503); die('O módulo GEOP V8 aguarda a atualização do banco pelo TI.');
}$user=current_user();$uid=(int)$user['id'];$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $fonte=$_POST['fonte']??'';$id=(int)($_POST['id']??0);$dec=strtoupper((string)($_POST['decisao']??''));$obs=trim((string)($_POST['observacao']??''));if(!in_array($dec,['APROVAR','REPROVAR'],true))throw new RuntimeException('Decisão inválida.');
  if($fonte==='EXCLUSAO'){
    exclusao_analisar($pdo,$id,$uid,$dec,$obs);$msg='Solicitação de exclusão analisada.';
  }elseif($fonte==='REABERTURA'){
    $pdo->beginTransaction();$st=$pdo->prepare("SELECT r.*,os.numero_os FROM os_reabertura_solicitacoes r JOIN ordens_servico os ON os.id=r.ordem_servico_id WHERE r.id=? AND r.status='PENDENTE' FOR UPDATE");$st->execute([$id]);$r=$st->fetch(PDO::FETCH_ASSOC);if(!$r)throw new RuntimeException('Solicitação não encontrada ou já analisada.');if((int)$r['solicitado_por']===$uid)throw new RuntimeException('O solicitante não pode aprovar a própria reabertura.');$status=$dec==='APROVAR'?'APROVADA':'REPROVADA';$pdo->prepare("UPDATE os_reabertura_solicitacoes SET status=?,analisado_por=?,analisado_em=NOW(),parecer=? WHERE id=?")->execute([$status,$uid,$obs?:null,$id]);
    if($dec==='APROVAR'){
      $token=(string)random_int(100000,999999);$hash=password_hash($token,PASSWORD_DEFAULT);$pdo->prepare("UPDATE tokens_reabertura_os SET usado_em=NOW(),usado_por=? WHERE ordem_servico_id=? AND usado_em IS NULL")->execute([$uid,$r['ordem_servico_id']]);$pdo->prepare("INSERT INTO tokens_reabertura_os(ordem_servico_id,token_hash,gerado_por,expira_em) VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL 24 HOUR))")->execute([$r['ordem_servico_id'],$hash,$uid]);$_SESSION['token_reabertura_gerado']=$token;if(function_exists('gestao_notificar'))gestao_notificar($pdo,(int)$r['solicitado_por'],'REABERTURA_APROVADA','Reabertura aprovada',$r['numero_os'].' aprovada. Solicite ao aprovador o token exibido no GEOP.',app_path('ordem_detalhe',['id'=>$r['ordem_servico_id']]),'reabertura-aprovada-'.$id);
    }
    $pdo->commit();$msg=$dec==='APROVAR'?'Reabertura aprovada. Token de 24h gerado: '.($_SESSION['token_reabertura_gerado']??''):'Reabertura reprovada.';
  }elseif($fonte==='APROVACOES'){
    $pdo->beginTransaction();$st=$pdo->prepare("SELECT * FROM aprovacoes WHERE id=? AND status='PENDENTE' FOR UPDATE");$st->execute([$id]);$a=$st->fetch(PDO::FETCH_ASSOC);if(!$a)throw new RuntimeException('Aprovação não encontrada ou já decidida.');if((int)$a['solicitante_id']===$uid)throw new RuntimeException('O solicitante não pode aprovar a própria solicitação.');$status=$dec==='APROVAR'?'APROVADA':'REPROVADA';
    if($a['entidade']==='estoque_reserva'){
      $stR=$pdo->prepare("SELECT er.*,em.nome,em.quantidade_atual FROM estoque_reservas er JOIN estoque_materiais em ON em.id=er.material_id WHERE er.id=? FOR UPDATE");$stR->execute([(int)$a['entidade_id']]);$r=$stR->fetch(PDO::FETCH_ASSOC);if($r&&$r['status']==='APROVACAO_NEGATIVO'){
       if($dec==='APROVAR'){$pdo->prepare("UPDATE estoque_reservas SET status='CONSUMIDA',aprovado_por=?,aprovado_em=NOW(),atualizado_em=NOW() WHERE id=?")->execute([$uid,$r['id']]);$pdo->prepare("UPDATE estoque_materiais SET quantidade_atual=quantidade_atual-?,atualizado_em=NOW() WHERE id=?")->execute([$r['quantidade'],$r['material_id']]);$pdo->prepare("INSERT INTO movimentacoes_estoque(material_id,ordem_servico_id,usuario_id,tipo,quantidade,custo_unitario,observacao) VALUES(?,?,?,'SAIDA_OS_NEGATIVA',?,?,?)")->execute([$r['material_id'],$r['ordem_servico_id'],$uid,$r['quantidade'],$r['custo_unitario'],'Estoque negativo aprovado na Central V8']);$c=(float)$r['quantidade']*(float)$r['custo_unitario'];$pdo->prepare("UPDATE ordens_servico SET custo_materiais=COALESCE(custo_materiais,0)+? WHERE id=?")->execute([$c,$r['ordem_servico_id']]);}else{$pdo->prepare("UPDATE estoque_reservas SET status='INDISPONIVEL',aprovado_por=?,aprovado_em=NOW(),atualizado_em=NOW() WHERE id=?")->execute([$uid,$r['id']]);}
      }
    }elseif($a['entidade']==='compra_solicitacao'){
      $pdo->prepare("UPDATE compras_solicitacoes SET status=?,aprovador_id=?,decidido_em=NOW() WHERE id=?")->execute([$dec==='APROVAR'?'APROVADA':'REPROVADA',$uid,$a['entidade_id']]);
    }
    $pdo->prepare("UPDATE aprovacoes SET status=?,aprovador_id=?,decidido_em=NOW(),observacao=CONCAT(COALESCE(observacao,''),?) WHERE id=?")->execute([$status,$uid,$obs!==''?"\nParecer: ".$obs:'',$id]);$pdo->commit();$msg='Aprovação registrada.';
  }
  geop_v8_security_event($pdo,$uid,'APROVACAO_'.$dec,$fonte.' #'.$id);
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$err=$e->getMessage();}
}
$rows=geop_v8_pending_approvals($pdo);render_header('Central de aprovações');
?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Central de aprovações</h1><p class="text-muted mb-0">Exclusões, reaberturas, estoque negativo, compras e aprovações gerais em um único lugar.</p></div><span class="badge text-bg-warning fs-6"><?=count($rows)?> pendente(s)</span></div><?php if($msg):?><div class="alert alert-success"><?=e($msg)?></div><?php endif?><?php if($err):?><div class="alert alert-danger"><?=e($err)?></div><?php endif?>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Tipo</th><th>Solicitante</th><th>Data</th><th>Detalhe</th><th style="min-width:300px">Decisão</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><span class="badge text-bg-secondary"><?=e($r['tipo'])?></span></td><td><?=e($r['solicitante_nome']??('#'.$r['solicitante_id']))?></td><td><?=e($r['created_at']??'')?></td><td><?=nl2br(e((string)($r['observacao']??'')))?></td><td><?php if((int)$r['solicitante_id']===$uid): ?><span class="text-muted small">Você é o solicitante; outro Gestor/TI deve decidir.</span><?php else: ?><form method="post" class="row g-1"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="fonte" value="<?=e($r['_fonte'])?>"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><div class="col-12"><input class="form-control form-control-sm" name="observacao" placeholder="Parecer (opcional)"></div><div class="col-6"><button class="btn btn-success btn-sm w-100" name="decisao" value="APROVAR">Aprovar</button></div><div class="col-6"><button class="btn btn-outline-danger btn-sm w-100" name="decisao" value="REPROVAR">Reprovar</button></div></form><?php endif; ?></td></tr><?php endforeach?><?php if(!$rows): ?><tr><td colspan="5" class="text-center text-muted py-4">Nenhuma aprovação pendente.</td></tr><?php endif; ?></tbody></table></div></div>
<?php render_footer(); ?>
