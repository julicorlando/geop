<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ambientes_qr.php';

limpeza_require_access();
check_csrf();
ambiente_require_schema($pdo);
limpeza_require_schema($pdo);
exigir_permissao('ambientes.validar');
$user = current_user();
$success = $_SESSION['validacao_success'] ?? '';
$error = $_SESSION['validacao_error'] ?? '';
unset($_SESSION['validacao_success'], $_SESSION['validacao_error']);

function load_ambiente_record(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare("SELECT ar.*,aq.nome_publico,aq.local_id,aq.exige_foto_supervisor,aq.exibir_fotos,aq.token_publico,
        l.nome AS local_nome,legacy.nome AS asg_nome,eu.nome AS executor_usuario_nome,m.nome_colaborador AS membro_nome,
        (SELECT GROUP_CONCAT(rr.nome_responsavel ORDER BY rr.responsavel_principal DESC,rr.nome_responsavel SEPARATOR ', ') FROM ambiente_registro_responsaveis rr WHERE rr.registro_id=ar.id) AS equipe_nomes,
        registrador.nome AS registrador_nome,os.numero_os,os.titulo AS os_titulo,os.status AS os_status,os.gestor_id
        FROM ambiente_registros ar
        JOIN ambientes_qr aq ON aq.id=ar.ambiente_id
        JOIN locais l ON l.id=aq.local_id
        JOIN usuarios legacy ON legacy.id=ar.asg_id
        LEFT JOIN usuarios eu ON eu.id=ar.executor_usuario_id
        LEFT JOIN limpeza_organograma_nos m ON m.id=ar.executor_membro_id
        LEFT JOIN usuarios registrador ON registrador.id=ar.registrado_por
        LEFT JOIN ordens_servico os ON os.id=ar.ordem_servico_id
        WHERE ar.id=? LIMIT 1");
    $st->execute([$id]);
    $record = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($record) $record['executor_exibicao'] = limpeza_record_executor($record);
    return $record;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recordId = (int)($_POST['id'] ?? 0);
    try {
        $record = load_ambiente_record($pdo, $recordId);
        if (!$record) throw new RuntimeException('Registro não encontrado.');
        if (!in_array($record['status'], ['AGUARDANDO_VALIDACAO','CORRECAO_SOLICITADA'], true)) throw new RuntimeException('Este registro já foi processado.');
        $selfCheck=$pdo->prepare('SELECT COUNT(*) FROM ambiente_registro_responsaveis WHERE registro_id=? AND usuario_id=?');
        $selfCheck->execute([$recordId,(int)$user['id']]);
        if((int)$selfCheck->fetchColumn()>0) throw new RuntimeException('Um integrante da equipe executora não pode validar o próprio serviço.');

        $decision = (string)($_POST['decision'] ?? '');
        $observation = trim((string)($_POST['observacao_supervisor'] ?? ''));
        if (!in_array($decision, ['APROVAR','REPROVAR','CORRECAO'], true)) throw new RuntimeException('Decisão inválida.');
        if ($decision !== 'APROVAR' && $observation === '') throw new RuntimeException('Informe a justificativa da reprovação ou correção.');

        $uploads = ambiente_normalize_uploads($_FILES['fotos'] ?? []);
        $existingSupervisor = $pdo->prepare("SELECT COUNT(*) FROM ambiente_registro_fotos WHERE registro_id=? AND origem='SUPERVISOR'");
        $existingSupervisor->execute([$recordId]);
        if ($decision === 'APROVAR' && (int)$record['exige_foto_supervisor'] === 1 && !$uploads && (int)$existingSupervisor->fetchColumn() === 0) {
            throw new RuntimeException('Este ambiente exige foto de inspeção do supervisor.');
        }

        $pdo->beginTransaction();
        if ($uploads) ambiente_store_photos($pdo, $recordId, (int)$user['id'], 'SUPERVISOR', 'INSPECAO', $_FILES['fotos'], false);

        if ($decision === 'APROVAR') {
            $newStatus = $record['ordem_servico_id'] ? 'SOLICITADO_FECHAMENTO' : 'VALIDADA';
            $pdo->prepare("UPDATE ambiente_registros SET status=?,supervisor_id=?,validado_em=NOW(),observacao_supervisor=?,solicitado_fechamento_em=IF(ordem_servico_id IS NULL,NULL,NOW()) WHERE id=?")
                ->execute([$newStatus, (int)$user['id'], $observation, $recordId]);
            $pdo->prepare("INSERT INTO ambiente_validacoes(registro_id,supervisor_id,resultado,observacao) VALUES(?,?,'APROVADA',?)")
                ->execute([$recordId, (int)$user['id'], $observation]);
            $pdo->prepare("UPDATE limpeza_cronograma_itens ci JOIN ambiente_registro_plano_itens rpi ON rpi.cronograma_item_id=ci.id SET ci.status='CONCLUIDO',ci.registro_id=? WHERE rpi.registro_id=?")
                ->execute([$recordId, $recordId]);

            if (!empty($_POST['publicar_fotos']) && (int)$record['exibir_fotos'] === 1 && usuario_pode('ambientes.publicar_fotos')) {
                $pdo->prepare("UPDATE ambiente_registro_fotos SET publica=1 WHERE registro_id=?")->execute([$recordId]);
            }

            if ($record['ordem_servico_id']) {
                $osId = (int)$record['ordem_servico_id'];
                $ordemAtivaSql = exclusao_sql_nao_excluida($pdo, 'ordens_servico');
                $osLock = $pdo->prepare("SELECT status,gestor_id FROM ordens_servico WHERE id=? AND $ordemAtivaSql FOR UPDATE");
                $osLock->execute([$osId]);
                $os = $osLock->fetch(PDO::FETCH_ASSOC);
                if (!$os) throw new RuntimeException('A OS vinculada não foi encontrada.');
                if ($os['status'] !== 'ENCERRADA') {
                    $oldStatus = (string)$os['status'];
                    $pdo->prepare("UPDATE ordens_servico SET status='CONCLUIDA_PELO_AGENTE',data_conclusao=COALESCE(data_conclusao,NOW()),solicitacao_encerramento=1,solicitacao_encerramento_data=NOW(),solicitacao_encerramento_obs=? WHERE id=?")
                        ->execute(['Limpeza conferida no ambiente '.$record['nome_publico'].'; equipe: '.$record['executor_exibicao'].'. '.$observation, $osId]);
                    registrar_historico($pdo, $osId, (int)$user['id'], $oldStatus, 'CONCLUIDA_PELO_AGENTE', 'Supervisor validou a limpeza pelo módulo de Ambientes e solicitou o encerramento. Registro #'.$recordId.'.');
                    ambiente_copy_photos_to_os($pdo, $recordId, $osId, (int)$user['id']);
                }
            }
        } else {
            $result = $decision === 'REPROVAR' ? 'REPROVADA' : 'CORRECAO_SOLICITADA';
            $pdo->prepare("UPDATE ambiente_registros SET status=?,supervisor_id=?,validado_em=NOW(),observacao_supervisor=? WHERE id=?")
                ->execute([$result, (int)$user['id'], $observation, $recordId]);
            $pdo->prepare("INSERT INTO ambiente_validacoes(registro_id,supervisor_id,resultado,observacao) VALUES(?,?,?,?)")
                ->execute([$recordId, (int)$user['id'], $decision === 'REPROVAR' ? 'REPROVADA' : 'CORRECAO_SOLICITADA', $observation]);
            $pdo->prepare("UPDATE limpeza_cronograma_itens ci JOIN ambiente_registro_plano_itens rpi ON rpi.cronograma_item_id=ci.id SET ci.status='PENDENTE',ci.registro_id=NULL WHERE rpi.registro_id=?")
                ->execute([$recordId]);
        }
        $pdo->commit();

        auditar($pdo, 'VALIDAR_LIMPEZA_AMBIENTE', 'ambiente_registros', $recordId, ['status'=>$record['status']], ['decisao'=>$decision,'observacao'=>$observation]);
        $teamUsers=$pdo->prepare('SELECT DISTINCT usuario_id FROM ambiente_registro_responsaveis WHERE registro_id=? AND usuario_id IS NOT NULL');
        $teamUsers->execute([$recordId]);
        foreach(array_map('intval',$teamUsers->fetchAll(PDO::FETCH_COLUMN) ?: []) as $executorUserId){
            if($executorUserId===(int)$user['id']) continue;
            if ($decision === 'APROVAR') {
                gestao_notificar($pdo, $executorUserId, 'AMBIENTE_APROVADO', 'Limpeza validada', 'A limpeza em '.$record['nome_publico'].' foi aprovada.', app_path('ambiente_registro',['t'=>$record['token_publico']]), 'ambiente-aprovado-'.$recordId.'-'.$executorUserId);
            } else {
                gestao_notificar($pdo, $executorUserId, 'AMBIENTE_CORRECAO', 'Correção solicitada na limpeza', $observation, app_path('ambiente_registro',['t'=>$record['token_publico']]), 'ambiente-correcao-'.$recordId.'-'.$executorUserId);
            }
        }
        if ($decision === 'APROVAR' && $record['ordem_servico_id']) {
            ambiente_notify_profiles($pdo, ['TI','GESTOR','GESTOR_SINDICO'], 'OS_AGUARDA_ENCERRAMENTO', 'OS aguardando encerramento', 'A OS '.$record['numero_os'].' teve a limpeza validada e aguarda encerramento.', app_path('ordem_detalhe',['id'=>$record['ordem_servico_id']]), 'ambiente-os-fechar-'.$recordId);
        }
        $_SESSION['validacao_success'] = $decision === 'APROVAR'
            ? 'Limpeza validada com sucesso.'.($record['ordem_servico_id'] ? ' A OS foi enviada para encerramento.' : '')
            : ($decision === 'REPROVAR' ? 'Limpeza reprovada.' : 'Correção solicitada.');
        redirect('ambiente_validar');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['validacao_error'] = $e->getMessage();
        redirect('ambiente_validar?id='.$recordId);
    }
}

$filter = (string)($_GET['status'] ?? 'AGUARDANDO_VALIDACAO');
$allowedFilters = ['AGUARDANDO_VALIDACAO','CORRECAO_SOLICITADA','VALIDADA','SOLICITADO_FECHAMENTO','REPROVADA','TODOS'];
if (!in_array($filter, $allowedFilters, true)) $filter = 'AGUARDANDO_VALIDACAO';
$where = $filter === 'TODOS' ? '1=1' : 'ar.status=?';
$sql = "SELECT ar.id,ar.status,ar.finalizado_em,ar.criado_em,ar.executor_nome,aq.nome_publico,legacy.nome AS asg_nome,
    eu.nome AS executor_usuario_nome,m.nome_colaborador AS membro_nome,os.numero_os,
    (SELECT GROUP_CONCAT(rr.nome_responsavel ORDER BY rr.responsavel_principal DESC,rr.nome_responsavel SEPARATOR ', ') FROM ambiente_registro_responsaveis rr WHERE rr.registro_id=ar.id) AS equipe_nomes,
    (SELECT GROUP_CONCAT(DISTINCT aa.nome ORDER BY aa.nome SEPARATOR ' • ') FROM ambiente_registro_itens ri JOIN ambiente_acoes aa ON aa.id=ri.acao_id WHERE ri.registro_id=ar.id) AS acoes_genericas,
    (SELECT GROUP_CONCAT(DISTINCT t.descricao ORDER BY t.ordem,t.descricao SEPARATOR ' • ') FROM ambiente_registro_plano_itens rpi JOIN limpeza_plano_tarefas t ON t.id=rpi.tarefa_id WHERE rpi.registro_id=ar.id) AS tarefas_plano
    FROM ambiente_registros ar
    JOIN ambientes_qr aq ON aq.id=ar.ambiente_id
    JOIN usuarios legacy ON legacy.id=ar.asg_id
    LEFT JOIN usuarios eu ON eu.id=ar.executor_usuario_id
    LEFT JOIN limpeza_organograma_nos m ON m.id=ar.executor_membro_id
    LEFT JOIN ordens_servico os ON os.id=ar.ordem_servico_id
    WHERE $where ORDER BY FIELD(ar.status,'AGUARDANDO_VALIDACAO','CORRECAO_SOLICITADA','SOLICITADO_FECHAMENTO','VALIDADA','REPROVADA'),ar.criado_em DESC LIMIT 200";
$listSt = $pdo->prepare($sql);
$filter === 'TODOS' ? $listSt->execute() : $listSt->execute([$filter]);
$records = $listSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($records as &$r) {
    $r['executor_exibicao'] = limpeza_record_executor($r);
    $r['acoes'] = limpeza_merge_action_names($r['acoes_genericas'] ?? '', $r['tarefas_plano'] ?? '');
}
unset($r);
$detailId = (int)($_GET['id'] ?? 0);
$detail = $detailId ? load_ambiente_record($pdo, $detailId) : null;
$detailItems = $detail ? ambiente_record_items($pdo, $detailId) : [];
$detailPhotos = $detail ? ambiente_record_photos($pdo, $detailId) : [];

render_header('Validação de limpezas');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 mb-1">Validação de limpezas</h1><p class="text-muted mb-0">Confira as atividades, evidências e toda a equipe informada antes de aprovar.</p></div><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza'))?>">Painel</a><a class="btn btn-outline-secondary" href="<?=e(app_path('ambientes'))?>">Ambientes</a></div></div>
<?php if($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif ?><?php if($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif ?>
<div class="d-flex flex-wrap gap-2 mb-3"><?php foreach($allowedFilters as $f): ?><a class="btn btn-sm <?=$filter===$f?'btn-primary':'btn-outline-primary'?>" href="<?=e(app_path('ambiente_validar',['status'=>$f]))?>"><?=e($f==='TODOS'?'Todos':ambiente_status_label($f))?></a><?php endforeach ?></div>
<div class="row g-4">
  <div class="col-lg-5"><div class="list-group shadow-sm"><?php foreach($records as $r): ?><a class="list-group-item list-group-item-action <?=((int)$r['id']===$detailId)?'active':''?>" href="<?=e(app_path('ambiente_validar',['status'=>$filter,'id'=>$r['id']]))?>"><div class="d-flex justify-content-between gap-2"><strong><?=e($r['nome_publico'])?></strong><span class="badge text-bg-<?=e(ambiente_status_class($r['status']))?>"><?=e(ambiente_status_label($r['status']))?></span></div><div class="small mt-1"><?=e($r['executor_exibicao'])?> · <?=e(date('d/m/Y H:i',strtotime($r['finalizado_em'])))?></div><div class="small opacity-75 mt-1"><?=e($r['acoes'] ?: 'Atividade não detalhada')?></div><?php if($r['numero_os']): ?><span class="badge bg-light text-dark mt-2"><?=e($r['numero_os'])?></span><?php endif ?></a><?php endforeach ?><?php if(!$records): ?><div class="list-group-item text-muted">Nenhum registro neste filtro.</div><?php endif ?></div></div>
  <div class="col-lg-7"><?php if($detail): ?><div class="card shadow-sm"><div class="card-body p-4">
    <div class="d-flex justify-content-between flex-wrap gap-2"><div><span class="badge text-bg-<?=e(ambiente_status_class($detail['status']))?> mb-2"><?=e(ambiente_status_label($detail['status']))?></span><h2 class="h4 mb-1"><?=e($detail['nome_publico'])?></h2><p class="text-muted mb-1"><?=e($detail['local_nome'])?> · equipe: <?=e($detail['executor_exibicao'])?></p><?php if($detail['registrador_nome']): ?><small class="text-muted">Lançado no sistema por <?=e($detail['registrador_nome'])?></small><?php endif ?></div><?php if($detail['numero_os']): ?><a class="btn btn-outline-primary align-self-start" href="<?=e(app_path('ordem_detalhe',['id'=>$detail['ordem_servico_id']]))?>"><?=e($detail['numero_os'])?></a><?php endif ?></div>
    <h3 class="h6 mt-3">Atividades informadas</h3><ul><?php foreach($detailItems as $item): ?><li><?=e($item['nome'])?><?php if(($item['origem']??'')==='PLANO'): ?> <small class="text-muted">(plano)</small><?php endif ?></li><?php endforeach ?></ul>
    <div class="row g-2 mb-3"><div class="col-sm-6"><div class="border rounded p-2"><small class="text-muted d-block">Início</small><?=e($detail['iniciado_em']?date('d/m/Y H:i',strtotime($detail['iniciado_em'])):'Não informado')?></div></div><div class="col-sm-6"><div class="border rounded p-2"><small class="text-muted d-block">Término</small><?=e(date('d/m/Y H:i',strtotime($detail['finalizado_em'])))?></div></div></div>
    <?php foreach(['observacao_asg'=>'Observação da execução','problema_encontrado'=>'Problema encontrado','insumos_repostos'=>'Insumos repostos'] as $key=>$label): if(trim((string)$detail[$key])!==''): ?><div class="mb-2"><strong><?=e($label)?>:</strong> <?=nl2br(e($detail[$key]))?></div><?php endif; endforeach ?>
    <?php if($detailPhotos): ?><h3 class="h6 mt-3">Fotos</h3><div class="row g-2 mb-3"><?php foreach($detailPhotos as $photo): ?><div class="col-6 col-md-4"><a target="_blank" href="<?=e(app_path($photo['arquivo_url']))?>"><img src="<?=e(app_path($photo['arquivo_url']))?>" class="w-100 rounded border" style="height:150px;object-fit:cover" alt="Foto do serviço"></a><small class="d-block text-muted"><?=e($photo['origem'].' · '.$photo['tipo'])?></small></div><?php endforeach ?></div><?php endif ?>
    <?php if(in_array($detail['status'],['AGUARDANDO_VALIDACAO','CORRECAO_SOLICITADA'],true)): ?>
      <form method="post" enctype="multipart/form-data" class="border-top pt-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=e($detail['id'])?>"><div class="mb-3"><label class="form-label">Fotos da inspeção <?= $detail['exige_foto_supervisor']?'<span class="text-danger">*</span>':'' ?></label><input class="form-control" type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple></div><div class="mb-3"><label class="form-label">Parecer do supervisor</label><textarea class="form-control" name="observacao_supervisor" rows="3" placeholder="Descreva a conferência ou a correção necessária"></textarea></div><?php if($detail['exibir_fotos'] && usuario_pode('ambientes.publicar_fotos')): ?><label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="publicar_fotos"> Publicar as fotos validadas na página pública</label><?php endif ?><div class="d-grid d-md-flex gap-2"><button class="btn btn-success flex-fill" name="decision" value="APROVAR">Aprovar limpeza<?= $detail['ordem_servico_id']?' e solicitar fechamento':'' ?></button><button class="btn btn-warning" name="decision" value="CORRECAO">Solicitar correção</button><button class="btn btn-outline-danger" name="decision" value="REPROVAR">Reprovar</button></div></form>
    <?php elseif($detail['observacao_supervisor']): ?><div class="alert alert-light mt-3"><strong>Parecer:</strong> <?=nl2br(e($detail['observacao_supervisor']))?></div><?php endif ?>
  </div></div><?php else: ?><div class="alert alert-info">Selecione um registro para conferir.</div><?php endif ?></div>
</div>
<?php render_footer(); ?>
