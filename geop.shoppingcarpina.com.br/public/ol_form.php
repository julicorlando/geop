<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ordens_limpeza.php';
require_once __DIR__ . '/../includes/conhecimento.php';

ol_require_access();
ol_require_schema($pdo);
exigir_permissao('ol.criar');
check_csrf();
$user=current_user();
$id=(int)($_GET['id'] ?? $_POST['id'] ?? 0);
$order=$id ? ol_get($pdo,$id) : null;
if($id && !$order) throw new RuntimeException('Ordem de Limpeza não encontrada.');
if($order && !in_array($order['status'],['ABERTA','ATRIBUIDA'],true)){
    throw new RuntimeException('Esta OL não pode mais ser editada por esta tela.');
}

$sourceOsId=(int)($_GET['origem_os_id'] ?? $_POST['origem_os_id'] ?? 0);
$sourceOs=null;
if($sourceOsId>0){
    if($order) throw new RuntimeException('Uma OL existente não pode receber uma nova OS de origem.');
    // A migração de OS para OL é exclusiva do TI.
    exigir_permissao('ol.migrar_os');
    $sourceOs=ol_source_os($pdo,$sourceOsId,false);
    if(!$sourceOs) throw new RuntimeException('A OS informada não foi encontrada.');
    $existingMigration=ol_migration_by_os($pdo,$sourceOsId);
    if($existingMigration){
        redirect('ol_detalhe?id='.(int)$existingMigration['ordem_limpeza_id']);
    }
}

$members=limpeza_members($pdo,true);
$membersById=[];foreach($members as $member)$membersById[(int)$member['id']]=$member;
$environments=$pdo->query("SELECT aq.id,aq.nome_publico,aq.local_id,l.nome AS local_nome FROM ambientes_qr aq JOIN locais l ON l.id=aq.local_id WHERE aq.ativo=1 ORDER BY aq.nome_publico")->fetchAll(PDO::FETCH_ASSOC)?:[];
$locals=$pdo->query("SELECT id,nome FROM locais WHERE ativo=1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC)?:[];
$tasks=limpeza_tasks($pdo,null,true);
$categoriaLimpezaId=0;
try {
    $stCategoriaLimpeza=$pdo->query("SELECT id FROM categorias WHERE ativo=1 AND LOWER(TRIM(nome))='limpeza' ORDER BY id LIMIT 1");
    $categoriaLimpezaId=(int)($stCategoriaLimpeza->fetchColumn()?:0);
} catch (Throwable $e) {
    $categoriaLimpezaId=0;
}
$artigosConhecimento=[];
try {
    $artigosConhecimento=conhecimento_buscar($pdo,'',$categoriaLimpezaId?:null,false);
} catch (Throwable $e) {
    $artigosConhecimento=[];
}
$existingParticipants=$order?ol_participants($pdo,$id):[];
$existingItems=$order?ol_items($pdo,$id):[];
$selectedMemberIds=[];$principalId=0;
foreach($existingParticipants as $participant){ if((int)$participant['planejado']===1){$selectedMemberIds[]=(int)$participant['membro_id'];if((int)$participant['responsavel_principal']===1)$principalId=(int)$participant['membro_id'];} }
$selectedTaskIds=[];$customItems=[];
foreach($existingItems as $item){ if($item['tarefa_plano_id'])$selectedTaskIds[]=(int)$item['tarefa_plano_id'];else $customItems[]=$item['descricao']; }
$prefill=[];
if(!$order && $sourceOs){
    $prefill=[
        'titulo'=>(string)$sourceOs['titulo'],
        'descricao'=>"Demanda migrada da ".(string)$sourceOs['numero_os'].".

".(string)$sourceOs['descricao'],
        'tipo'=>in_array((string)$sourceOs['prioridade'],['ALTA','CRITICA'],true)?'EMERGENCIAL':'OUTRA',
        'prioridade'=>(string)$sourceOs['prioridade'],
        'local_id'=>(int)$sourceOs['local_id'],
        'data_programada'=>!empty($sourceOs['prazo'])?date('Y-m-d',strtotime((string)$sourceOs['prazo'])):date('Y-m-d'),
        'hora_inicio_prevista'=>!empty($sourceOs['prazo'])?date('H:i',strtotime((string)$sourceOs['prazo'])):'',
        'prazo_em'=>$sourceOs['prazo'] ?? null,
    ];
    $customItems=['Executar a limpeza solicitada na '.(string)$sourceOs['numero_os'].': '.(string)$sourceOs['titulo']];
}

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $title=trim((string)($_POST['titulo']??''));
        $description=trim((string)($_POST['descricao']??''));
        $type=(string)($_POST['tipo']??'ROTINA');
        $priority=(string)($_POST['prioridade']??'MEDIA');
        $environmentId=(int)($_POST['ambiente_id']??0)?:null;
        $localId=(int)($_POST['local_id']??0)?:null;
        $supervisorId=(int)($_POST['supervisor_membro_id']??0)?:null;
        $date=trim((string)($_POST['data_programada']??''))?:null;
        $time=trim((string)($_POST['hora_inicio_prevista']??''))?:null;
        $deadline=trim((string)($_POST['prazo_em']??''))?:null;
        $memberIds=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['membro_ids']??[])))));
        $principal=(int)($_POST['principal_id']??0);
        $planTaskIds=array_values(array_unique(array_filter(array_map('intval',(array)($_POST['tarefa_ids']??[])))));
        $customText=trim((string)($_POST['itens_customizados']??''));
        $custom=array_values(array_filter(array_map('trim',preg_split('/\R+/u',$customText)?:[])));
        $fotosAntes=$_FILES['fotos_antes']??[];
        $fotosReferencia=$_FILES['fotos_referencia']??[];
        $closeSourceOs=!empty($_POST['encerrar_os_origem']);
        $migrationNote=trim((string)($_POST['observacao_migracao']??''));

        if($title==='' || mb_strlen($title)<5) throw new RuntimeException('Informe um título com pelo menos 5 caracteres.');
        if(!array_key_exists($type,ol_types())) throw new RuntimeException('Tipo de OL inválido.');
        if(!array_key_exists($priority,ol_priorities())) throw new RuntimeException('Prioridade inválida.');
        if($date!==null && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) throw new RuntimeException('Data programada inválida.');
        if($time!==null && !preg_match('/^\d{2}:\d{2}$/',$time)) throw new RuntimeException('Hora prevista inválida.');
        $deadlineDb=null;
        if($deadline!==null){$ts=strtotime($deadline);if($ts===false)throw new RuntimeException('Prazo inválido.');$deadlineDb=date('Y-m-d H:i:s',$ts);}
        if($environmentId){
            $verify=$pdo->prepare('SELECT local_id FROM ambientes_qr WHERE id=? AND ativo=1');$verify->execute([$environmentId]);$environmentLocal=(int)($verify->fetchColumn()?:0);
            if(!$environmentLocal)throw new RuntimeException('Ambiente inválido ou desativado.');
            $localId=$environmentLocal;
        }
        if(!$localId) throw new RuntimeException('Selecione um ambiente ou local.');
        $verifyLocal=$pdo->prepare('SELECT 1 FROM locais WHERE id=? AND ativo=1');$verifyLocal->execute([$localId]);if(!$verifyLocal->fetchColumn())throw new RuntimeException('Local inválido.');
        foreach($memberIds as $memberId){if(!isset($membersById[$memberId]))throw new RuntimeException('Um dos funcionários selecionados não está ativo no organograma.');}
        if(!$memberIds)throw new RuntimeException('Selecione pelo menos um funcionário, inclusive o supervisor quando ele fizer parte da equipe.');
        if(!in_array($principal,$memberIds,true))$principal=$memberIds[0];
        if($supervisorId!==null && !isset($membersById[$supervisorId]))throw new RuntimeException('Supervisor responsável inválido.');
        $taskRows=[];
        if($planTaskIds){
            $placeholders=implode(',',array_fill(0,count($planTaskIds),'?'));
            $st=$pdo->prepare("SELECT id,descricao FROM limpeza_plano_tarefas WHERE ativo=1 AND id IN ($placeholders)");$st->execute($planTaskIds);$taskRows=$st->fetchAll(PDO::FETCH_ASSOC)?:[];
            $validIds=array_map('intval',array_column($taskRows,'id'));sort($validIds);$expected=$planTaskIds;sort($expected);if($validIds!==$expected)throw new RuntimeException('Uma das atividades do plano não está disponível.');
        }
        if(!$taskRows && !$custom)throw new RuntimeException('Selecione uma atividade do plano ou informe itens personalizados.');

        $pdo->beginTransaction();
        if(!$order){
            $sourceLocked=null;
            if($sourceOsId>0){
                $sourceLocked=ol_source_os($pdo,$sourceOsId,true);
                if(!$sourceLocked) throw new RuntimeException('A OS de origem não foi encontrada durante a migração.');
                if(ol_migration_by_os($pdo,$sourceOsId)) throw new RuntimeException('Esta OS já foi migrada para uma Ordem de Limpeza.');
                if((int)$sourceLocked['local_id']!==$localId) throw new RuntimeException('O local da OL deve permanecer igual ao local da OS de origem.');
            }
            $number=ol_generate_number($pdo);
            $solicitanteId=$sourceLocked ? (int)$sourceLocked['solicitante_id'] : (int)$user['id'];
            $pdo->prepare("INSERT INTO limpeza_ordens(numero_ol,titulo,descricao,tipo,prioridade,status,ambiente_id,local_id,supervisor_membro_id,solicitante_id,criado_por,data_programada,hora_inicio_prevista,prazo_em)
                VALUES(?,?,?,?,?,'ATRIBUIDA',?,?,?,?,?,?,?,?)")
                ->execute([$number,$title,$description,$type,$priority,$environmentId,$localId,$supervisorId,$solicitanteId,(int)$user['id'],$date,$time,$deadlineDb]);
            $id=(int)$pdo->lastInsertId();
            ol_replace_team($pdo,$id,$memberIds,$principal);
            $itemSt=$pdo->prepare('INSERT INTO limpeza_ordem_itens(ordem_limpeza_id,tarefa_plano_id,descricao,obrigatorio,ordem) VALUES(?,?,?,?,?)');
            $position=1;
            foreach($taskRows as $taskRow)$itemSt->execute([$id,(int)$taskRow['id'],$taskRow['descricao'],1,$position++]);
            foreach($custom as $customItem)$itemSt->execute([$id,null,mb_substr($customItem,0,255),1,$position++]);
            ol_store_photos($pdo,$id,(int)$user['id'],'GESTAO','ANTES',$fotosAntes,false);
            ol_store_photos($pdo,$id,(int)$user['id'],'GESTAO','DURANTE',$fotosReferencia,false);
            $copiedImages=0;
            if($sourceLocked){
                $oldOsStatus=(string)$sourceLocked['status'];
                $copiedImages=ol_copy_os_images($pdo,$sourceOsId,$id,(int)$user['id']);
                $pdo->prepare("INSERT INTO limpeza_ordem_migracoes(ordem_servico_id,ordem_limpeza_id,migrado_por,status_os_anterior,os_encerrada,observacao) VALUES(?,?,?,?,?,?)")
                    ->execute([$sourceOsId,$id,(int)$user['id'],$oldOsStatus,$closeSourceOs?1:0,$migrationNote!==''?$migrationNote:null]);
                $migrationDescription='OL criada por migração da '.(string)$sourceLocked['numero_os'].'.'.($copiedImages?' '.$copiedImages.' imagem(ns) vinculada(s).':'');
                ol_add_history($pdo,$id,(int)$user['id'],null,'ATRIBUIDA','MIGRAR_OS',$migrationDescription);
                if($closeSourceOs && $oldOsStatus!=='ENCERRADA'){
                    $osNote='OS migrada para '.$number.'.'.($migrationNote!==''?' '.$migrationNote:'');
                    $pdo->prepare("UPDATE ordens_servico SET status='ENCERRADA',data_conclusao=COALESCE(data_conclusao,NOW()),data_encerramento=NOW(),aprovado_por=?,observacao_aprovacao=CONCAT(COALESCE(observacao_aprovacao,''),CASE WHEN COALESCE(observacao_aprovacao,'')='' THEN '' ELSE '\n' END,?) WHERE id=?")
                        ->execute([(int)$user['id'],$osNote,$sourceOsId]);
                    registrar_historico($pdo,$sourceOsId,(int)$user['id'],$oldOsStatus,'ENCERRADA',$osNote);
                }else{
                    registrar_historico($pdo,$sourceOsId,(int)$user['id'],$oldOsStatus,$oldOsStatus,'OS vinculada à '.$number.' por migração para o módulo de limpeza.');
                }
            }else{
                ol_add_history($pdo,$id,(int)$user['id'],null,'ATRIBUIDA','CRIAR','OL criada com equipe e checklist inicial.');
            }
            $pdo->commit();
            auditar($pdo,$sourceLocked?'MIGRAR_OS_PARA_OL':'CRIAR_OL','limpeza_ordens',$id,[],['numero_ol'=>$number,'equipe'=>$memberIds,'supervisor'=>$supervisorId,'os_origem_id'=>$sourceOsId?:null,'os_encerrada'=>$sourceLocked?$closeSourceOs:null]);
            ol_notify_team($pdo,$id,'OL_ATRIBUIDA','Nova Ordem de Limpeza atribuída',$number.' — '.$title,app_path('ol_detalhe',['id'=>$id]),'ol-atribuida-'.$id);
            ol_notify_profiles($pdo,['TI','GESTOR','GESTOR_SINDICO','SUPERVISOR_ASG'],$sourceLocked?'OS_MIGRADA_OL':'OL_CRIADA',$sourceLocked?'OS migrada para Ordem de Limpeza':'Nova Ordem de Limpeza',$number.' — '.$title,app_path('ol_detalhe',['id'=>$id]),($sourceLocked?'os-migrada-ol-':'ol-criada-').$id);
            $_SESSION['ol_success']=$sourceLocked?'OS migrada para Ordem de Limpeza com sucesso.':'Ordem de Limpeza criada com sucesso.';
        }else{
            $before=$order;
            $newStatus=$order['status']==='ABERTA'?'ATRIBUIDA':$order['status'];
            $pdo->prepare("UPDATE limpeza_ordens SET titulo=?,descricao=?,tipo=?,prioridade=?,status=?,ambiente_id=?,local_id=?,supervisor_membro_id=?,data_programada=?,hora_inicio_prevista=?,prazo_em=? WHERE id=?")
                ->execute([$title,$description,$type,$priority,$newStatus,$environmentId,$localId,$supervisorId,$date,$time,$deadlineDb,$id]);
            ol_replace_team($pdo,$id,$memberIds,$principal);
            $pdo->prepare('DELETE FROM limpeza_ordem_itens WHERE ordem_limpeza_id=?')->execute([$id]);
            $itemSt=$pdo->prepare('INSERT INTO limpeza_ordem_itens(ordem_limpeza_id,tarefa_plano_id,descricao,obrigatorio,ordem) VALUES(?,?,?,?,?)');
            $position=1;foreach($taskRows as $taskRow)$itemSt->execute([$id,(int)$taskRow['id'],$taskRow['descricao'],1,$position++]);foreach($custom as $customItem)$itemSt->execute([$id,null,mb_substr($customItem,0,255),1,$position++]);
            ol_store_photos($pdo,$id,(int)$user['id'],'GESTAO','ANTES',$fotosAntes,false);
            ol_store_photos($pdo,$id,(int)$user['id'],'GESTAO','DURANTE',$fotosReferencia,false);
            ol_add_history($pdo,$id,(int)$user['id'],$order['status'],$newStatus,'EDITAR','Dados, equipe ou checklist da OL atualizados.');
            $pdo->commit();
            auditar($pdo,'EDITAR_OL','limpeza_ordens',$id,$before,['titulo'=>$title,'equipe'=>$memberIds,'supervisor'=>$supervisorId]);
            ol_notify_team($pdo,$id,'OL_ATUALIZADA','Ordem de Limpeza atualizada',$order['numero_ol'].' — '.$title,app_path('ol_detalhe',['id'=>$id]),'ol-atualizada-'.$id);
            $_SESSION['ol_success']='Ordem de Limpeza atualizada.';
        }
        redirect('ol_detalhe?id='.$id);
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage();
        $selectedMemberIds=$memberIds??[];$principalId=$principal??0;$selectedTaskIds=$planTaskIds??[];$customItems=$custom??[];
    }
}

$form=$order?:$prefill;
render_header($order?'Editar Ordem de Limpeza':($sourceOs?'Migrar OS para OL':'Nova Ordem de Limpeza'));
$currentPriority=$_POST['prioridade']??$form['prioridade']??'MEDIA';
$currentLocal=(int)($_POST['local_id']??$form['local_id']??0);
$currentType=$_POST['tipo']??$form['tipo']??'ROTINA';
$currentEnvironment=(int)($_POST['ambiente_id']??$form['ambiente_id']??0);
$currentSupervisor=(int)($_POST['supervisor_membro_id']??$form['supervisor_membro_id']??0);
?>
<h1 class="h3 mb-1" id="tituloNovaOrdem"><?=e($order?'Editar '.$order['numero_ol']:($sourceOs?'Migrar '.$sourceOs['numero_os'].' para OL':'Nova Ordem de Limpeza'))?></h1>
<p class="text-muted mb-3" id="subtituloNovaOrdem"><?=e($sourceOs?'Revise a demanda e conclua a migração mantendo equipe, checklist e evidências.':'Preencha a OL no mesmo padrão de abertura da Ordem de Serviço.')?></p>

<?php if($sourceOs): ?>
<div class="alert alert-info">
    <strong>OS de origem: <?=e($sourceOs['numero_os'])?></strong> — <?=e($sourceOs['titulo'])?>
    <div class="small mt-1">Local: <?=e($sourceOs['local_nome'])?> · Categoria: <?=e($sourceOs['categoria_nome'])?> · Status atual: <?=e(str_replace('_',' ',$sourceOs['status']))?></div>
</div>
<?php endif ?>

<?php if($error): ?>
<div class="alert alert-danger"><strong>Erro ao salvar OL:</strong> <?=e($error)?></div>
<?php endif ?>

<div class="card p-3">
<form method="post" enctype="multipart/form-data" class="row g-3">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="id" value="<?=e($id)?>">
    <input type="hidden" name="origem_os_id" value="<?=e($sourceOsId)?>">

    <div class="col-md-8">
        <label class="form-label required">Título</label>
        <input class="form-control" name="titulo" required maxlength="180" value="<?=e($_POST['titulo']??$form['titulo']??'')?>" placeholder="Ex.: Limpeza reforçada da praça de alimentação">
    </div>

    <div class="col-md-4">
        <label class="form-label required">Prioridade</label>
        <select class="form-select" name="prioridade" required>
            <?php foreach(ol_priorities() as $code=>$label): ?>
                <option value="<?=e($code)?>" <?=$currentPriority===$code?'selected':''?>><?=e($label)?></option>
            <?php endforeach ?>
        </select>
        <div class="form-text">Se o prazo ficar vazio, a demanda seguirá a prioridade definida.</div>
    </div>

    <div class="col-md-6">
        <label class="form-label required">Local</label>
        <select class="form-select" id="local_id" name="local_id" required>
            <option value="0">Selecione</option>
            <?php foreach($locals as $local): ?>
                <option value="<?=e($local['id'])?>" <?=$currentLocal===(int)$local['id']?'selected':''?>><?=e($local['nome'])?></option>
            <?php endforeach ?>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label required">Categoria</label>
        <input class="form-control" value="Limpeza" readonly aria-readonly="true">
        <div class="form-text">A categoria da OL é definida automaticamente como Limpeza.</div>
    </div>

    <div class="col-12">
        <section class="border rounded p-3 bg-light" id="sugestoes-conhecimento">
            <div class="d-flex justify-content-between align-items-center gap-2">
                <strong>Soluções sugeridas</strong>
                <a class="btn btn-sm btn-outline-primary" href="<?=e(app_path('conhecimento'))?>" target="_blank">Abrir base completa</a>
            </div>
            <div class="small text-muted mt-1" id="sugestoes-conhecimento-vazio">Descreva a necessidade para consultar soluções de limpeza já registradas.</div>
            <div class="row g-2 mt-1" id="sugestoes-conhecimento-lista"></div>
        </section>
    </div>

    <div class="col-md-6">
        <label class="form-label">Prazo / SLA</label>
        <input class="form-control" type="datetime-local" name="prazo_em" value="<?=e(isset($_POST['prazo_em'])?$_POST['prazo_em']:(empty($form['prazo_em'])?'':date('Y-m-d\TH:i',strtotime($form['prazo_em']))))?>">
    </div>

    <div class="col-md-6">
        <label class="form-label required">Tipo de limpeza</label>
        <select class="form-select" name="tipo" required>
            <?php foreach(ol_types() as $code=>$label): ?>
                <option value="<?=e($code)?>" <?=$currentType===$code?'selected':''?>><?=e($label)?></option>
            <?php endforeach ?>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label required">Descrição</label>
        <textarea class="form-control" name="descricao" rows="5" required placeholder="Descreva a necessidade, condições do ambiente e resultado esperado."><?=e($_POST['descricao']??$form['descricao']??'')?></textarea>
    </div>

    <div class="col-12">
        <div class="card border-success-subtle p-3" id="blocoOlLimpeza">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-0">Dados da Ordem de Limpeza</h2>
                    <small class="text-muted">Equipe, programação, checklist e evidências da OL.</small>
                </div>
                <span class="badge text-bg-success">Numeração OL</span>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ambiente monitorado / QR</label>
                    <select class="form-select" id="ambiente_id" name="ambiente_id">
                        <option value="0">Sem ambiente QR específico</option>
                        <?php foreach($environments as $environment): ?>
                            <option data-local="<?=e($environment['local_id'])?>" value="<?=e($environment['id'])?>" <?=$currentEnvironment===(int)$environment['id']?'selected':''?>><?=e($environment['nome_publico'].' — '.$environment['local_nome'])?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data programada</label>
                    <input class="form-control" type="date" name="data_programada" value="<?=e($_POST['data_programada']??$form['data_programada']??'')?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hora prevista</label>
                    <input class="form-control" type="time" name="hora_inicio_prevista" value="<?=e(substr((string)($_POST['hora_inicio_prevista']??$form['hora_inicio_prevista']??''),0,5))?>">
                </div>

                <div class="col-12"><hr class="my-1"></div>
                <div class="col-12">
                    <label class="form-label required">Equipe responsável</label>
                    <small class="text-muted d-block mb-2">Todos os funcionários ativos do organograma aparecem aqui, inclusive o supervisor. Selecione um ou mais colaboradores; o responsável principal deve fazer parte da equipe.</small>
                </div>
                <div class="col-lg-8">
                    <div class="row g-2">
                        <?php foreach($members as $member): $isSupervisor=stripos((string)$member['cargo'],'supervisor')!==false || ($member['usuario_perfil']??'')==='SUPERVISOR_ASG'; ?>
                        <div class="col-md-6 col-xl-4">
                            <label class="form-check border rounded p-2 h-100">
                                <input class="form-check-input team-check" type="checkbox" name="membro_ids[]" value="<?=e($member['id'])?>" data-name="<?=e($member['nome_colaborador'])?>" <?=in_array((int)$member['id'],$selectedMemberIds,true)?'checked':''?>>
                                <span class="form-check-label ms-1"><strong><?=e($member['nome_colaborador'])?></strong><?php if($isSupervisor): ?> <span class="badge text-bg-info">Supervisor</span><?php endif ?><small class="d-block text-muted"><?=e($member['cargo'].($member['turno']?' · '.$member['turno']:''))?></small></span>
                            </label>
                        </div>
                        <?php endforeach ?>
                    </div>
                    <?php if(!$members): ?><div class="alert alert-warning py-2 mt-2 mb-0">Nenhum colaborador ativo foi encontrado no Organograma da Limpeza.</div><?php endif ?>
                </div>
                <div class="col-lg-4">
                    <label class="form-label required">Responsável principal</label>
                    <select class="form-select mb-3" id="principal_id" name="principal_id" required><option value="0">Selecione a equipe primeiro</option></select>
                    <label class="form-label">Supervisor responsável</label>
                    <select class="form-select" name="supervisor_membro_id">
                        <option value="0">Não definido</option>
                        <?php foreach($members as $member): ?>
                            <option value="<?=e($member['id'])?>" <?=$currentSupervisor===(int)$member['id']?'selected':''?>><?=e($member['nome_colaborador'].' — '.$member['cargo'])?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="col-12"><hr class="my-1"></div>
                <div class="col-12">
                    <h2 class="h5 mb-1">Checklist da Ordem de Limpeza</h2>
                    <small class="text-muted">Selecione o que deverá ser executado nesta OL.</small>
                </div>
                <div class="col-lg-8">
                    <div class="row g-2">
                        <?php $lastGroup=''; foreach($tasks as $task): ?>
                            <div class="col-md-6">
                                <label class="form-check border rounded p-2 h-100">
                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="tarefa_ids[]" value="<?=e($task['id'])?>" <?=in_array((int)$task['id'],$selectedTaskIds,true)?'checked':''?>>
                                    <span><strong><?=e($task['descricao'])?></strong><?php if(!empty($task['grupo_nome'])): ?><br><small class="text-muted"><?=e($task['grupo_nome'])?><?=!empty($task['periodicidade'])?' · '.e($task['periodicidade']):''?></small><?php endif ?></span>
                                </label>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <?php if(!$tasks): ?><div class="alert alert-warning py-2 mb-0">Nenhuma tarefa ativa foi encontrada. Use os itens adicionais.</div><?php endif ?>
                </div>
                <div class="col-lg-4">
                    <label class="form-label">Itens adicionais</label>
                    <textarea class="form-control" name="itens_customizados" rows="8" placeholder="Uma atividade por linha"><?=e($_POST['itens_customizados']??implode("\n",$customItems))?></textarea>
                </div>

                <div class="col-12"><hr class="my-1"></div>
                <div class="col-12">
                    <h2 class="h5 mb-1">Fotos na abertura da OL</h2>
                    <small class="text-muted">Anexe evidências iniciais no mesmo momento da abertura.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fotos antes da execução</label>
                    <input class="form-control" type="file" name="fotos_antes[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="form-text">Registre a condição inicial do ambiente.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fotos de referência / apoio</label>
                    <input class="form-control" type="file" name="fotos_referencia[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="form-text">Fotos do problema, orientação ou ponto específico.</div>
                </div>
            </div>
        </div>
    </div>

    <?php if($sourceOs): ?>
    <div class="col-12">
        <div class="border rounded p-3 bg-light">
            <h2 class="h5">Migração da OS</h2>
            <label class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="encerrar_os_origem" value="1" <?=!isset($_POST['origem_os_id'])||!empty($_POST['encerrar_os_origem'])?'checked':''?>>
                <span class="form-check-label"><strong>Encerrar a OS após a migração</strong><small class="d-block text-muted">O vínculo e o histórico serão preservados.</small></span>
            </label>
            <label class="form-label">Observação da migração</label>
            <textarea class="form-control" name="observacao_migracao" rows="3"><?=e($_POST['observacao_migracao']??'')?></textarea>
        </div>
    </div>
    <?php endif ?>

    <div class="col-12">
        <button class="btn btn-primary" id="btnSalvarOrdem"><?=e($order?'Salvar OL':($sourceOs?'Migrar para OL':'Salvar OL'))?></button>
        <a class="btn btn-outline-secondary" href="<?=e($order?app_path('ol_detalhe',['id'=>$id]):($sourceOs?app_path('ordem_detalhe',['id'=>$sourceOsId]):app_path('ol')))?>">Cancelar</a>
    </div>
</form>
</div>

<script>
(function(){
    const checks=[...document.querySelectorAll('.team-check')];
    const principal=document.getElementById('principal_id');
    const initial=<?=json_encode((int)($_POST['principal_id']??$principalId))?>;
    function refresh(){
        const selected=checks.filter(c=>c.checked);
        const previous=parseInt(principal.value||initial||0,10);
        principal.innerHTML='';
        if(!selected.length){principal.add(new Option('Selecione a equipe primeiro','0'));return;}
        selected.forEach(c=>principal.add(new Option(c.dataset.name,c.value,false,parseInt(c.value,10)===previous)));
        if(!principal.value||principal.value==='0') principal.value=selected[0].value;
    }
    checks.forEach(c=>c.addEventListener('change',refresh));
    refresh();

    const env=document.getElementById('ambiente_id');
    const local=document.getElementById('local_id');
    function syncLocal(){
        const opt=env.options[env.selectedIndex];
        if(opt&&opt.dataset.local){local.value=opt.dataset.local;local.disabled=true;}else local.disabled=false;
    }
    env.addEventListener('change',syncLocal);
    syncLocal();
    document.querySelector('form').addEventListener('submit',()=>{local.disabled=false;});
})();

document.addEventListener('DOMContentLoaded', function () {
    const artigos = <?= json_encode(array_map(static function ($a) {
        return [
            'id' => (int)$a['id'],
            'titulo' => $a['titulo'],
            'problema' => $a['problema'] ?? '',
            'solucao' => $a['solucao'] ?? $a['conteudo'] ?? '',
            'palavras' => $a['palavras_chave'] ?? '',
            'url' => app_path('conhecimento', ['q' => $a['titulo']]),
        ];
    }, $artigosConhecimento), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const titulo = document.querySelector('[name="titulo"]');
    const descricao = document.querySelector('[name="descricao"]');
    const lista = document.getElementById('sugestoes-conhecimento-lista');
    const vazio = document.getElementById('sugestoes-conhecimento-vazio');
    if (!lista) return;
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const atualizar = function () {
        const texto = ((titulo?.value || '') + ' ' + (descricao?.value || '')).toLocaleLowerCase('pt-BR');
        const termos = texto.split(/[^\p{L}\p{N}]+/u).filter(t => t.length >= 4);
        const encontrados = artigos.map(a => {
            const base = (a.titulo + ' ' + a.problema + ' ' + a.palavras).toLocaleLowerCase('pt-BR');
            let score = 1;
            termos.forEach(t => { if (base.includes(t)) score += 2; });
            return {...a, score};
        }).sort((a,b) => b.score-a.score).slice(0,3);
        vazio.style.display = encontrados.length ? 'none' : 'block';
        lista.innerHTML = encontrados.map(a => '<div class="col-md-4"><div class="card h-100 p-2"><strong>'+escapeHtml(a.titulo)+'</strong><small class="mt-1">'+escapeHtml(a.problema).slice(0,140)+'</small><a class="btn btn-sm btn-outline-success mt-2" target="_blank" href="'+escapeHtml(a.url)+'">Ver solução</a></div></div>').join('');
    };
    titulo?.addEventListener('input', atualizar);
    descricao?.addEventListener('input', atualizar);
    atualizar();
});
</script>
<?php render_footer(); ?>
