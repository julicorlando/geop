<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ordens_limpeza.php';

ol_require_access();
ol_require_schema($pdo);
check_csrf();
$user=current_user();
$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
$order=ol_get($pdo,$id);
if(!$order) throw new RuntimeException('Ordem de Limpeza não encontrada.');

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=(string)($_POST['action']??'');
    try{
        if($action==='iniciar'){
            exigir_permissao('ol.executar');
            if(!in_array($order['status'],['ATRIBUIDA','CORRECAO_SOLICITADA'],true))throw new RuntimeException('Esta OL não pode ser iniciada neste status.');
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE limpeza_ordens SET status='EM_ANDAMENTO',iniciado_em=COALESCE(iniciado_em,NOW()) WHERE id=?")->execute([$id]);
            ol_add_history($pdo,$id,(int)$user['id'],$order['status'],'EM_ANDAMENTO','INICIAR','Execução iniciada.');
            $pdo->commit();
            auditar($pdo,'INICIAR_OL','limpeza_ordens',$id,['status'=>$order['status']],['status'=>'EM_ANDAMENTO']);
            $_SESSION['ol_success']='Execução iniciada.';
        }elseif(in_array($action,['salvar_execucao','enviar_validacao'],true)){
            exigir_permissao('ol.executar');
            if(!in_array($order['status'],['ATRIBUIDA','EM_ANDAMENTO','CORRECAO_SOLICITADA'],true))throw new RuntimeException('Esta OL não aceita lançamento de execução.');
            $memberIds=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['executores']??[])))));
            $principalId=(int)($_POST['executor_principal_id']??0);
            $itemStatuses=(array)($_POST['item_status']??[]);
            $itemObservations=(array)($_POST['item_obs']??[]);
            $executionNote=trim((string)($_POST['observacao_execucao']??''));
            $problem=trim((string)($_POST['problema_encontrado']??''));
            $supplies=trim((string)($_POST['insumos_utilizados']??''));
            $startRaw=trim((string)($_POST['iniciado_em']??''));
            $finishRaw=trim((string)($_POST['concluido_em']??''));
            $startDb=$startRaw!==''?date('Y-m-d H:i:s',strtotime($startRaw)?:time()):($order['iniciado_em']?:date('Y-m-d H:i:s'));
            $finishDb=$finishRaw!==''?date('Y-m-d H:i:s',strtotime($finishRaw)?:time()):date('Y-m-d H:i:s');
            if(strtotime($startDb)>strtotime($finishDb))throw new RuntimeException('O início não pode ser posterior ao término.');
            $items=ol_items($pdo,$id);
            if(!$items)throw new RuntimeException('A OL não possui checklist.');
            $completed=0;
            foreach($items as $item){
                $statusItem=(string)($itemStatuses[$item['id']]??'PENDENTE');
                $obs=trim((string)($itemObservations[$item['id']]??''));
                if(!in_array($statusItem,['PENDENTE','CONCLUIDO','NAO_REALIZADO'],true))throw new RuntimeException('Status de item inválido.');
                if($action==='enviar_validacao' && (int)$item['obrigatorio']===1 && $statusItem==='PENDENTE')throw new RuntimeException('Conclua ou justifique todos os itens obrigatórios.');
                if($statusItem==='NAO_REALIZADO' && $obs==='')throw new RuntimeException('Justifique os itens não realizados.');
                if($statusItem==='CONCLUIDO')$completed++;
            }
            if($action==='enviar_validacao' && $completed===0)throw new RuntimeException('Marque pelo menos uma atividade como concluída.');
            $newUploads=ol_normalize_uploads($_FILES['fotos_execucao']??[]);
            if($action==='enviar_validacao'){
                $countSt=$pdo->prepare('SELECT COUNT(*) FROM limpeza_ordem_fotos WHERE ordem_limpeza_id=?');$countSt->execute([$id]);
                if((int)$countSt->fetchColumn()===0 && !$newUploads)throw new RuntimeException('Envie pelo menos uma foto da execução antes da validação.');
            }
            $pdo->beginTransaction();
            ol_set_actual_team($pdo,$id,$memberIds,$principalId);
            $itemUpdate=$pdo->prepare('UPDATE limpeza_ordem_itens SET status_item=?,observacao_execucao=? WHERE id=? AND ordem_limpeza_id=?');
            foreach($items as $item){$itemUpdate->execute([(string)($itemStatuses[$item['id']]??'PENDENTE'),trim((string)($itemObservations[$item['id']]??''))?:null,(int)$item['id'],$id]);}
            $newStatus=$action==='enviar_validacao'?'AGUARDANDO_VALIDACAO':'EM_ANDAMENTO';
            $pdo->prepare("UPDATE limpeza_ordens SET status=?,iniciado_em=COALESCE(iniciado_em,?),concluido_em=?,observacao_execucao=?,problema_encontrado=?,insumos_utilizados=? WHERE id=?")
                ->execute([$newStatus,$startDb,$action==='enviar_validacao'?$finishDb:null,$executionNote?:null,$problem?:null,$supplies?:null,$id]);
            if($newUploads)ol_store_photos($pdo,$id,(int)$user['id'],'EXECUCAO',(string)($_POST['tipo_foto']??'DEPOIS'),$_FILES['fotos_execucao'],false);
            ol_add_history($pdo,$id,(int)$user['id'],$order['status'],$newStatus,$action==='enviar_validacao'?'ENVIAR_VALIDACAO':'SALVAR_EXECUCAO',$action==='enviar_validacao'?'Execução concluída e enviada para validação.':'Progresso da execução salvo.');
            $pdo->commit();
            auditar($pdo,$action==='enviar_validacao'?'ENVIAR_OL_VALIDACAO':'SALVAR_EXECUCAO_OL','limpeza_ordens',$id,['status'=>$order['status']],['status'=>$newStatus,'executores'=>$memberIds]);
            if($action==='enviar_validacao'){
                ol_notify_profiles($pdo,['TI','GESTOR','GESTOR_SINDICO','SUPERVISOR_ASG'],'OL_AGUARDA_VALIDACAO','OL aguardando validação',$order['numero_ol'].' — '.$order['titulo'],app_path('ol_detalhe',['id'=>$id]),'ol-validar-'.$id);
                $_SESSION['ol_success']='Execução enviada para validação.';
            }else $_SESSION['ol_success']='Progresso salvo.';
        }elseif($action==='validar'){
            exigir_permissao('ol.validar');
            if($order['status']!=='AGUARDANDO_VALIDACAO')throw new RuntimeException('Esta OL não está aguardando validação.');
            if(($user['perfil']??'')!=='TI' && ol_user_executed($pdo,$id,(int)$user['id']))throw new RuntimeException('Quem executou a limpeza não pode validar a própria OL.');
            $decision=(string)($_POST['decisao']??'');
            if(!in_array($decision,['APROVAR','CORRECAO'],true))throw new RuntimeException('Decisão inválida.');
            $opinion=trim((string)($_POST['parecer_validacao']??''));
            if($opinion==='')throw new RuntimeException('Informe o parecer da validação.');
            $inspectionUploads=ol_normalize_uploads($_FILES['fotos_supervisao']??[]);
            $pdo->beginTransaction();
            if($inspectionUploads)ol_store_photos($pdo,$id,(int)$user['id'],'SUPERVISAO','INSPECAO',$_FILES['fotos_supervisao'],!empty($_POST['publicar_fotos']));
            if($decision==='APROVAR'){
                $pdo->prepare("UPDATE limpeza_ordens SET status='ENCERRADA',validado_por=?,validado_em=NOW(),encerrado_em=NOW(),parecer_validacao=? WHERE id=?")
                    ->execute([(int)$user['id'],$opinion,$id]);
                ol_add_history($pdo,$id,(int)$user['id'],'AGUARDANDO_VALIDACAO','ENCERRADA','APROVAR','OL validada e encerrada.');
                $fresh=ol_get($pdo,$id);
                $environmentRecord=ol_sync_environment_record($pdo,$fresh,(int)$user['id'],!empty($_POST['publicar_fotos']));
                if(!empty($fresh['ambiente_id']) && !$environmentRecord)throw new RuntimeException('Não foi possível integrar a OL ao histórico do ambiente.');
                $pdo->commit();
                auditar($pdo,'APROVAR_OL','limpeza_ordens',$id,['status'=>'AGUARDANDO_VALIDACAO'],['status'=>'ENCERRADA','ambiente_registro_id'=>$environmentRecord]);
                ol_notify_team($pdo,$id,'OL_ENCERRADA','Ordem de Limpeza aprovada',$order['numero_ol'].' foi aprovada e encerrada.',app_path('ol_detalhe',['id'=>$id]),'ol-encerrada-'.$id);
                $_SESSION['ol_success']='OL aprovada e encerrada.'.(!empty($fresh['ambiente_id'])?' O histórico do ambiente foi atualizado.':'');
            }else{
                $pdo->prepare("UPDATE limpeza_ordens SET status='CORRECAO_SOLICITADA',validado_por=?,validado_em=NOW(),parecer_validacao=? WHERE id=?")
                    ->execute([(int)$user['id'],$opinion,$id]);
                ol_add_history($pdo,$id,(int)$user['id'],'AGUARDANDO_VALIDACAO','CORRECAO_SOLICITADA','SOLICITAR_CORRECAO',$opinion);
                $pdo->commit();
                auditar($pdo,'SOLICITAR_CORRECAO_OL','limpeza_ordens',$id,['status'=>'AGUARDANDO_VALIDACAO'],['status'=>'CORRECAO_SOLICITADA']);
                ol_notify_team($pdo,$id,'OL_CORRECAO','Correção solicitada na OL',$order['numero_ol'].' — '.$opinion,app_path('ol_detalhe',['id'=>$id]),'ol-correcao-'.$id);
                $_SESSION['ol_success']='Correção solicitada à equipe.';
            }
        }elseif($action==='excluir_foto'){
            $fotoId=(int)($_POST['foto_id']??0);
            if($fotoId<=0)throw new RuntimeException('Foto inválida para exclusão.');
            $stFoto=$pdo->prepare('SELECT * FROM limpeza_ordem_fotos WHERE id=? AND ordem_limpeza_id=? LIMIT 1');
            $stFoto->execute([$fotoId,$id]);
            $foto=$stFoto->fetch(PDO::FETCH_ASSOC);
            if(!$foto)throw new RuntimeException('Foto não encontrada nesta OL.');

            $perfil=(string)($user['perfil']??'');
            $gerencial=in_array($perfil,['TI','GESTOR','GESTOR_SINDICO'],true);
            $podeExcluir=$gerencial || (!in_array($order['status'],['ENCERRADA','CANCELADA'],true)
                && (int)($foto['usuario_id']??0)===(int)$user['id']
                && (usuario_pode('ol.executar') || usuario_pode('ol.validar')));
            if(!$podeExcluir)throw new RuntimeException('Você não tem permissão para apagar esta foto.');

            $arquivo=(string)($foto['arquivo_url']??'');
            $tipo=(string)($foto['tipo']??'');
            $origem=(string)($foto['origem']??'');

            $pdo->beginTransaction();
            try{
                // Se a foto foi sincronizada para o histórico do ambiente desta OL,
                // remove também a cópia visual correspondente daquele registro.
                if(!empty($order['ambiente_registro_id'])){
                    try{
                        $pdo->prepare('DELETE FROM ambiente_registro_fotos WHERE registro_id=? AND arquivo_url=?')
                            ->execute([(int)$order['ambiente_registro_id'],$arquivo]);
                    }catch(Throwable $ignored){}
                }
                $del=$pdo->prepare('DELETE FROM limpeza_ordem_fotos WHERE id=? AND ordem_limpeza_id=?');
                $del->execute([$fotoId,$id]);
                if($del->rowCount()<1)throw new RuntimeException('Não foi possível apagar a foto.');
                ol_add_history($pdo,$id,(int)$user['id'],$order['status'],$order['status'],'EXCLUIR_FOTO','Foto removida da OL ('.($origem?:'SEM ORIGEM').' · '.($tipo?:'SEM TIPO').').');
                $pdo->commit();
            }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}

            remover_upload_local_se_sem_referencia($pdo,$arquivo);
            auditar($pdo,'EXCLUIR_FOTO_OL','limpeza_ordem_fotos',$fotoId,
                ['ordem_limpeza_id'=>$id,'origem'=>$origem,'tipo'=>$tipo,'arquivo_url'=>$arquivo],[]);
            $_SESSION['ol_success']='Foto apagada com sucesso.';
        }elseif($action==='cancelar'){
            exigir_permissao('ol.cancelar');
            if(in_array($order['status'],['ENCERRADA','CANCELADA'],true))throw new RuntimeException('Esta OL não pode ser cancelada.');
            $reason=trim((string)($_POST['motivo_cancelamento']??''));if($reason==='')throw new RuntimeException('Informe o motivo do cancelamento.');
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE limpeza_ordens SET status='CANCELADA',cancelado_em=NOW(),motivo_cancelamento=? WHERE id=?")->execute([$reason,$id]);
            ol_add_history($pdo,$id,(int)$user['id'],$order['status'],'CANCELADA','CANCELAR',$reason);
            $pdo->commit();
            auditar($pdo,'CANCELAR_OL','limpeza_ordens',$id,['status'=>$order['status']],['status'=>'CANCELADA','motivo'=>$reason]);
            ol_notify_team($pdo,$id,'OL_CANCELADA','Ordem de Limpeza cancelada',$order['numero_ol'].' — '.$reason,app_path('ol_detalhe',['id'=>$id]),'ol-cancelada-'.$id);
            $_SESSION['ol_success']='OL cancelada.';
        }else throw new RuntimeException('Ação inválida.');
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$_SESSION['ol_error']=$e->getMessage();}
    redirect('ol_detalhe?id='.$id);
}

$order=ol_get($pdo,$id);$participants=ol_participants($pdo,$id);$items=ol_items($pdo,$id);$photos=ol_photos($pdo,$id);$history=ol_history($pdo,$id);$members=limpeza_members($pdo,true);
$success=$_SESSION['ol_success']??'';$error=$_SESSION['ol_error']??'';unset($_SESSION['ol_success'],$_SESSION['ol_error']);
$plannedIds=[];$executedIds=[];$actualPrincipal=0;
foreach($participants as $participant){if((int)$participant['planejado']===1)$plannedIds[]=(int)$participant['membro_id'];if((int)$participant['executou']===1){$executedIds[]=(int)$participant['membro_id'];if((int)$participant['responsavel_principal']===1)$actualPrincipal=(int)$participant['membro_id'];}}
$defaultExecutorIds=$executedIds?:$plannedIds;if(!$actualPrincipal && $defaultExecutorIds)$actualPrincipal=$defaultExecutorIds[0];
$canEdit=usuario_pode('ol.criar') && in_array($order['status'],['ABERTA','ATRIBUIDA'],true);
$canExecute=usuario_pode('ol.executar') && in_array($order['status'],['ATRIBUIDA','EM_ANDAMENTO','CORRECAO_SOLICITADA'],true);
$canValidate=usuario_pode('ol.validar') && $order['status']==='AGUARDANDO_VALIDACAO' && (($user['perfil']??'')==='TI' || !ol_user_executed($pdo,$id,(int)$user['id']));

render_header($order['numero_ol']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><div class="d-flex flex-wrap gap-2 mb-2"><span class="badge text-bg-<?=e(ol_status_class($order['status']))?>"><?=e(ol_status_label($order['status']))?></span><?=badge_prioridade($order['prioridade'])?><span class="badge text-bg-light"><?=e(ol_types()[$order['tipo']]??$order['tipo'])?></span></div><h1 class="h3 mb-1"><?=e($order['numero_ol'].' — '.$order['titulo'])?></h1><p class="text-muted mb-0"><?=e($order['ambiente_nome'] ?: $order['local_nome'] ?: 'Local não informado')?> · aberta por <?=e($order['criado_por_nome'])?></p></div><div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="<?=e(app_path('ol'))?>">Lista</a><?php if(strtoupper((string)(current_user()['perfil']??''))==='TI'): ?>
<form method="post" action="<?=e(app_path('ol_migrar_unificada'))?>" class="d-inline" onsubmit="return confirm('Migrar esta OL para o ambiente unificado de Ordens? O número OL será preservado.');">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=(int)$id?>">
<button class="btn btn-warning">Migrar para Ordens</button>
</form>
<?php endif; ?><a class="btn btn-outline-secondary" target="_blank" href="<?=e(app_path('ol_imprimir',['id'=>$id]))?>">Imprimir</a><?php if($canEdit): ?><a class="btn btn-primary" href="<?=e(app_path('ol_form',['id'=>$id]))?>">Editar</a><?php endif ?></div></div>
<?php if($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif ?><?php if($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif ?>
<?php if(!empty($order['os_origem_id'])): ?><div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2"><div><strong>Originada da <?=e($order['os_origem_numero'] ?: 'OS #'.$order['os_origem_id'])?>.</strong><div class="small">Status anterior da OS: <?=e(str_replace('_',' ',(string)$order['status_os_anterior']))?> · <?=((int)$order['os_encerrada']===1)?'A OS foi encerrada na migração.':'A OS permaneceu ativa após o vínculo.'?><?php if($order['migrado_por_nome']): ?> · Migrada por <?=e($order['migrado_por_nome'])?><?php endif ?></div><?php if($order['migracao_observacao']): ?><div class="small mt-1"><?=e($order['migracao_observacao'])?></div><?php endif ?></div><a class="btn btn-sm btn-info" href="<?=e(app_path('ordem_detalhe',['id'=>$order['os_origem_id']]))?>">Abrir OS de origem</a></div><?php endif ?>
<div class="row g-4"><div class="col-xl-8">
<div class="card shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5">Descrição e programação</h2><p><?=nl2br(e($order['descricao']?:'Sem descrição adicional.'))?></p><div class="row g-3"><div class="col-md-4"><small class="text-muted d-block">Data programada</small><strong><?=e($order['data_programada']?date('d/m/Y',strtotime($order['data_programada'])):'Não definida')?></strong></div><div class="col-md-4"><small class="text-muted d-block">Horário previsto</small><strong><?=e($order['hora_inicio_prevista']?substr($order['hora_inicio_prevista'],0,5):'Não definido')?></strong></div><div class="col-md-4"><small class="text-muted d-block">Prazo</small><strong><?=e($order['prazo_em']?date('d/m/Y H:i',strtotime($order['prazo_em'])):'Não definido')?></strong></div></div></div></div>
<div class="card shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5 mb-3">Checklist da OL</h2><div class="list-group list-group-flush"><?php foreach($items as $item): ?><div class="list-group-item px-0"><div class="d-flex justify-content-between gap-2"><div><strong><?=e($item['descricao'])?></strong><?php if($item['grupo_nome']): ?><small class="d-block text-muted"><?=e($item['grupo_nome'])?></small><?php endif ?><?php if($item['observacao_execucao']): ?><small class="d-block mt-1"><?=e($item['observacao_execucao'])?></small><?php endif ?></div><span class="badge text-bg-<?=$item['status_item']==='CONCLUIDO'?'success':($item['status_item']==='NAO_REALIZADO'?'danger':'secondary')?>"><?=e(str_replace('_',' ',$item['status_item']))?></span></div></div><?php endforeach ?></div></div></div>
<?php if($order['status']==='ATRIBUIDA' && usuario_pode('ol.executar')): ?><form method="post" class="card card-body shadow-sm mb-4"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=e($id)?>"><input type="hidden" name="action" value="iniciar"><h2 class="h5">Iniciar execução</h2><p class="text-muted">Registra o horário de início e libera o formulário de execução.</p><button class="btn btn-warning">Iniciar OL agora</button></form><?php endif ?>
<?php if($canExecute && $order['status']!=='ATRIBUIDA'): ?><form method="post" enctype="multipart/form-data" class="card shadow-sm mb-4"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=e($id)?>"><div class="card-body p-4"><h2 class="h5">Registrar execução</h2><p class="text-muted small">Confirme quem realmente participou. Todos os integrantes ativos do organograma, inclusive supervisores, podem ser selecionados.</p>
<div class="row g-2 mb-3"><?php foreach($members as $member): $isSup=stripos((string)$member['cargo'],'supervisor')!==false||($member['usuario_perfil']??'')==='SUPERVISOR_ASG';?><div class="col-md-6"><label class="form-check border rounded p-2 h-100"><input class="form-check-input exec-check" type="checkbox" name="executores[]" value="<?=e($member['id'])?>" data-name="<?=e($member['nome_colaborador'])?>" <?=in_array((int)$member['id'],$defaultExecutorIds,true)?'checked':''?>><span class="form-check-label"><strong><?=e($member['nome_colaborador'])?></strong><?php if($isSup): ?> <span class="badge text-bg-info">Supervisor</span><?php endif ?><small class="d-block text-muted"><?=e($member['cargo'])?></small></span></label></div><?php endforeach ?></div>
<div class="mb-3"><label class="form-label">Responsável principal da execução</label><select class="form-select" name="executor_principal_id" id="executor_principal_id"></select></div>
<div class="row g-3 mb-3"><div class="col-md-6"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="iniciado_em" value="<?=e($order['iniciado_em']?date('Y-m-d\TH:i',strtotime($order['iniciado_em'])):date('Y-m-d\TH:i'))?>"></div><div class="col-md-6"><label class="form-label">Término</label><input class="form-control" type="datetime-local" name="concluido_em" value="<?=e(date('Y-m-d\TH:i'))?>"></div></div>
<h3 class="h6">Resultado do checklist</h3><?php foreach($items as $item): ?><div class="border rounded p-3 mb-2"><strong><?=e($item['descricao'])?></strong><div class="row g-2 mt-1"><div class="col-md-4"><select class="form-select" name="item_status[<?=e($item['id'])?>]"><?php foreach(['PENDENTE'=>'Pendente','CONCLUIDO'=>'Concluído','NAO_REALIZADO'=>'Não realizado'] as $code=>$label): ?><option value="<?=e($code)?>" <?=$item['status_item']===$code?'selected':''?>><?=e($label)?></option><?php endforeach ?></select></div><div class="col-md-8"><input class="form-control" name="item_obs[<?=e($item['id'])?>]" value="<?=e($item['observacao_execucao'])?>" placeholder="Observação ou justificativa"></div></div></div><?php endforeach ?>
<div class="row g-3 mt-1"><div class="col-md-4"><label class="form-label">Tipo de foto</label><select class="form-select" name="tipo_foto"><option value="ANTES">Antes</option><option value="DURANTE">Durante</option><option value="DEPOIS" selected>Depois</option><option value="PROBLEMA">Problema</option></select></div><div class="col-md-8"><label class="form-label">Fotos</label><input class="form-control" type="file" name="fotos_execucao[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple><div class="form-text">Ao enviar para validação, ao menos uma foto deve existir.</div></div></div>
<div class="mt-3"><label class="form-label">Observação da execução</label><textarea class="form-control" name="observacao_execucao" rows="3"><?=e($order['observacao_execucao'])?></textarea></div><div class="row g-3 mt-0"><div class="col-md-6"><label class="form-label">Problema encontrado</label><textarea class="form-control" name="problema_encontrado" rows="2"><?=e($order['problema_encontrado'])?></textarea></div><div class="col-md-6"><label class="form-label">Insumos utilizados</label><textarea class="form-control" name="insumos_utilizados" rows="2"><?=e($order['insumos_utilizados'])?></textarea></div></div>
<div class="d-grid d-md-flex gap-2 mt-4"><button class="btn btn-outline-primary flex-fill" name="action" value="salvar_execucao">Salvar progresso</button><button class="btn btn-success flex-fill" name="action" value="enviar_validacao">Concluir e enviar para validação</button></div></div></form><?php endif ?>
<?php if($order['status']==='AGUARDANDO_VALIDACAO'): ?><?php if($canValidate): ?><form method="post" enctype="multipart/form-data" class="card shadow-sm mb-4"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=e($id)?>"><input type="hidden" name="action" value="validar"><div class="card-body p-4"><h2 class="h5">Validação da supervisão</h2><div class="mb-3"><label class="form-label">Parecer *</label><textarea class="form-control" name="parecer_validacao" rows="4" required placeholder="Descreva a conferência, resultado e eventuais correções"></textarea></div><div class="mb-3"><label class="form-label">Fotos da inspeção</label><input class="form-control" type="file" name="fotos_supervisao[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple></div><?php if($order['ambiente_id']): ?><label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="publicar_fotos" value="1"> Publicar evidências autorizadas na página pública do ambiente</label><?php endif ?><div class="d-grid d-md-flex gap-2"><button class="btn btn-success flex-fill" name="decisao" value="APROVAR">Aprovar e encerrar</button><button class="btn btn-warning flex-fill" name="decisao" value="CORRECAO">Solicitar correção</button></div></div></form><?php else: ?><div class="alert alert-warning">Você não pode validar esta OL porque participou da execução ou não possui a permissão necessária.</div><?php endif ?><?php endif ?>
<?php if($photos): ?>
<div class="card shadow-sm mb-4"><div class="card-body p-4"><h2 class="h5">Evidências</h2><div class="row g-3">
<?php foreach($photos as $photo):
    $perfilOl=(string)($user['perfil']??'');
    $podeExcluirFotoOl=in_array($perfilOl,['TI','GESTOR','GESTOR_SINDICO'],true)
        || (!in_array($order['status'],['ENCERRADA','CANCELADA'],true)
            && (int)($photo['usuario_id']??0)===(int)$user['id']
            && (usuario_pode('ol.executar') || usuario_pode('ol.validar')));
?>
<div class="col-6 col-md-4"><div class="ol-foto-card">
    <?php if($podeExcluirFotoOl): ?>
    <form method="post" class="ol-foto-excluir" onsubmit="return confirm('Apagar esta foto definitivamente?');">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=e($id)?>">
        <input type="hidden" name="action" value="excluir_foto"><input type="hidden" name="foto_id" value="<?=e($photo['id'])?>">
        <button type="submit" class="ol-foto-excluir-btn" title="Apagar foto" aria-label="Apagar foto">&times;</button>
    </form>
    <?php endif ?>
    <a href="<?=e(app_path($photo['arquivo_url']))?>" target="_blank"><img class="w-100 rounded border" style="height:170px;object-fit:cover" src="<?=e(app_path($photo['arquivo_url']))?>" alt="Evidência da OL"></a>
    <small class="d-block text-muted mt-1"><?=e($photo['origem'].' · '.$photo['tipo'].' · '.($photo['usuario_nome']?:'Sistema'))?></small>
</div></div>
<?php endforeach ?>
</div></div></div>
<style>
.ol-foto-card{position:relative}.ol-foto-excluir{position:absolute;top:8px;right:8px;z-index:5;margin:0}.ol-foto-excluir-btn{width:30px;height:30px;padding:0;border:2px solid #fff;border-radius:50%;background:#dc3545;color:#fff;font-size:20px;line-height:24px;font-weight:700;box-shadow:0 2px 8px rgba(0,0,0,.35);display:flex;align-items:center;justify-content:center}.ol-foto-excluir-btn:hover{background:#bb2d3b;color:#fff}
</style>
<?php endif ?>
</div><div class="col-xl-4">
<?= exclusao_render_card($pdo, 'LIMPEZA_ORDEM', $id, $user) ?>
<div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Equipe</h2><div class="mb-3"><small class="text-muted d-block">Supervisor responsável</small><strong><?=e($order['supervisor_membro_nome']?:'Não definido')?></strong></div><h3 class="h6">Participantes</h3><?php foreach($participants as $participant): ?><div class="border rounded p-2 mb-2"><div class="d-flex justify-content-between gap-2"><strong><?=e($participant['nome_colaborador'])?></strong><?php if((int)$participant['responsavel_principal']===1): ?><span class="badge text-bg-primary">Principal</span><?php endif ?></div><small class="text-muted"><?=e($participant['cargo'])?></small><div class="mt-1"><?php if((int)$participant['planejado']===1): ?><span class="badge text-bg-light">Planejado</span><?php endif ?><?php if((int)$participant['executou']===1): ?><span class="badge text-bg-success">Executou</span><?php endif ?></div></div><?php endforeach ?></div></div>
<?php if($order['observacao_execucao']||$order['problema_encontrado']||$order['insumos_utilizados']||$order['parecer_validacao']): ?><div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Registros</h2><?php foreach(['observacao_execucao'=>'Execução','problema_encontrado'=>'Problema','insumos_utilizados'=>'Insumos','parecer_validacao'=>'Parecer'] as $field=>$label): if(trim((string)$order[$field])!==''): ?><div class="mb-3"><small class="text-muted d-block"><?=e($label)?></small><?=nl2br(e($order[$field]))?></div><?php endif;endforeach ?></div></div><?php endif ?>
<div class="card shadow-sm mb-4"><div class="card-body"><h2 class="h5">Histórico</h2><div class="list-group list-group-flush"><?php foreach($history as $entry): ?><div class="list-group-item px-0"><div class="d-flex justify-content-between gap-2"><strong><?=e($entry['acao'])?></strong><small><?=e(date('d/m/Y H:i',strtotime($entry['criado_em'])))?></small></div><small class="text-muted"><?=e($entry['usuario_nome']?:'Sistema')?> · <?=e(ol_status_label($entry['status_novo']))?></small><?php if($entry['descricao']): ?><div class="small mt-1"><?=nl2br(e($entry['descricao']))?></div><?php endif ?></div><?php endforeach ?></div></div></div>
<?php if(usuario_pode('ol.cancelar')&&!in_array($order['status'],['ENCERRADA','CANCELADA'],true)): ?><form method="post" class="card border-danger"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=e($id)?>"><input type="hidden" name="action" value="cancelar"><div class="card-body"><h2 class="h6 text-danger">Cancelar OL</h2><textarea class="form-control mb-2" name="motivo_cancelamento" required placeholder="Motivo do cancelamento"></textarea><button class="btn btn-outline-danger w-100" onclick="return confirm('Confirma o cancelamento desta OL?')">Cancelar Ordem de Limpeza</button></div></form><?php endif ?>
</div></div>
<script>
(function(){const checks=[...document.querySelectorAll('.exec-check')],select=document.getElementById('executor_principal_id');if(!select)return;const initial=<?=json_encode($actualPrincipal)?>;function refresh(){const selected=checks.filter(c=>c.checked),prev=parseInt(select.value||initial||0,10);select.innerHTML='';if(!selected.length){select.add(new Option('Selecione os executores','0'));return;}selected.forEach(c=>select.add(new Option(c.dataset.name,c.value,false,parseInt(c.value,10)===prev)));if(!select.value||select.value==='0')select.value=selected[0].value;}checks.forEach(c=>c.addEventListener('change',refresh));refresh();})();
</script>
<?php render_footer(); ?>
