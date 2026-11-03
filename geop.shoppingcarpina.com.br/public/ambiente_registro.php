<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ambientes_qr.php';

limpeza_require_access();
check_csrf();
ambiente_require_schema($pdo);
limpeza_require_schema($pdo);
exigir_permissao('ambientes.registrar');
$user = current_user();
$token = strtolower(trim((string)($_GET['t'] ?? $_POST['token'] ?? '')));
$ambiente = $token !== '' ? ambiente_get_by_token($pdo, $token, true) : null;

if (!$ambiente) {
    $list = $pdo->query("SELECT aq.*,l.nome AS local_nome,pg.nome AS plano_grupo_nome
        FROM ambientes_qr aq
        JOIN locais l ON l.id=aq.local_id
        LEFT JOIN limpeza_plano_grupos pg ON pg.id=aq.plano_grupo_id
        WHERE aq.ativo=1 ORDER BY aq.nome_publico")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    render_header('Registrar limpeza');
    ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
      <div><h1 class="h3 mb-1">Registrar limpeza por ambiente</h1><p class="text-muted mb-0">Selecione o ambiente para informar o serviço realizado e toda a equipe responsável.</p></div>
      <a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza'))?>">Painel da limpeza</a>
    </div>
    <div class="row g-3">
      <?php foreach ($list as $item): ?>
        <div class="col-md-6 col-xl-4"><a class="card card-body h-100 text-decoration-none shadow-sm" href="<?=e(app_path('ambiente_registro',['t'=>$item['token_publico']]))?>">
          <strong class="h5 text-body mb-1"><?=e($item['nome_publico'])?></strong>
          <span class="text-muted"><?=e($item['local_nome'])?></span>
          <?php if($item['plano_grupo_nome']): ?><small class="mt-2"><span class="badge text-bg-light"><?=e($item['plano_grupo_nome'])?></span></small><?php endif ?>
        </a></div>
      <?php endforeach ?>
      <?php if (!$list): ?><div class="col-12"><div class="alert alert-info">Nenhum ambiente ativo foi cadastrado.</div></div><?php endif ?>
    </div>
    <?php render_footer(); exit;
}

$actions = ambiente_actions($pdo, (int)$ambiente['id']);
$planTasks = ambiente_plan_tasks($pdo, (int)$ambiente['id']);
$members = limpeza_members($pdo, true);
$membersById = [];
foreach ($members as $memberRow) $membersById[(int)$memberRow['id']] = $memberRow;
$openOs = ambiente_open_os($pdo, (int)$ambiente['local_id']);
$success = $_SESSION['registro_success'] ?? '';
$error = $_SESSION['registro_error'] ?? '';
unset($_SESSION['registro_success'], $_SESSION['registro_error']);

$todaySchedule = [];
try {
    $scheduleSt = $pdo->prepare("SELECT ci.tarefa_id,ci.status,
        (SELECT GROUP_CONCAT(n.nome_colaborador ORDER BY cr.responsavel_principal DESC,n.nome_colaborador SEPARATOR ', ')
         FROM limpeza_cronograma_responsaveis cr
         JOIN limpeza_organograma_nos n ON n.id=cr.membro_id
         WHERE cr.cronograma_item_id=ci.id) AS responsavel_nomes
        FROM limpeza_cronogramas c
        JOIN limpeza_cronograma_itens ci ON ci.cronograma_id=c.id
        WHERE c.ano=? AND c.mes=? AND ci.dia=?");
    $scheduleSt->execute([(int)date('Y'), (int)date('n'), (int)date('j')]);
    foreach ($scheduleSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) $todaySchedule[(int)$row['tarefa_id']] = $row;
} catch (Throwable $e) {
    $todaySchedule = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $selectedActions = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['acoes'] ?? [])))));
        $selectedTasks = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['tarefas'] ?? [])))));
        if (!$selectedActions && !$selectedTasks) throw new RuntimeException('Selecione pelo menos uma atividade realizada.');

        $allowedActions = array_map('intval', array_column($actions, 'id'));
        foreach ($selectedActions as $actionId) {
            if (!in_array($actionId, $allowedActions, true)) throw new RuntimeException('Uma das ações selecionadas não pertence a este ambiente.');
        }
        $allowedTasks = array_map('intval', array_column($planTasks, 'id'));
        foreach ($selectedTasks as $taskId) {
            if (!in_array($taskId, $allowedTasks, true)) throw new RuntimeException('Uma das tarefas selecionadas não pertence à área deste ambiente.');
        }

        $memberIds = array_values(array_unique(array_filter(
            array_map('intval', (array)($_POST['executor_membro_ids'] ?? [])),
            static fn(int $id): bool => $id > 0
        )));
        foreach ($memberIds as $memberId) {
            if (!isset($membersById[$memberId]) || !(int)$membersById[$memberId]['ativo']) {
                throw new RuntimeException('Um dos integrantes selecionados não está disponível no organograma.');
            }
        }
        $principalId = (int)($_POST['executor_principal_id'] ?? 0);
        if ($memberIds && !in_array($principalId, $memberIds, true)) $principalId = $memberIds[0];
        if (!$memberIds) $principalId = 0;

        $manualExecutor = trim((string)($_POST['executor_nome'] ?? ''));
        if (!$memberIds && $manualExecutor === '') throw new RuntimeException('Selecione pelo menos um integrante ou informe um nome avulso.');

        $participants = [];
        foreach ($memberIds as $memberId) {
            $member = $membersById[$memberId];
            $participants[] = [
                'membro_id' => $memberId,
                'usuario_id' => !empty($member['usuario_id']) ? (int)$member['usuario_id'] : null,
                'nome' => trim((string)$member['nome_colaborador']),
                'principal' => $memberId === $principalId,
            ];
        }
        if ($manualExecutor !== '') {
            $participants[] = [
                'membro_id' => null,
                'usuario_id' => null,
                'nome' => $manualExecutor,
                'principal' => !$memberIds,
            ];
        }

        $principal = null;
        foreach ($participants as $participant) {
            if ($participant['principal']) { $principal = $participant; break; }
        }
        if (!$principal) $principal = $participants[0];
        $teamNames = implode(', ', array_map(static fn(array $p): string => $p['nome'], $participants));
        $legacyExecutorId = $principal['usuario_id'] ?: (int)$user['id'];

        $selectedActionRows = array_values(array_filter($actions, fn($a) => in_array((int)$a['id'], $selectedActions, true)));
        $selectedTaskRows = array_values(array_filter($planTasks, fn($t) => in_array((int)$t['id'], $selectedTasks, true)));
        $observation = trim((string)($_POST['observacao_asg'] ?? ''));
        $requiresObservation = (bool)array_filter($selectedActionRows, fn($a) => (int)$a['exige_observacao'] === 1)
            || (bool)array_filter($selectedTaskRows, fn($t) => (int)$t['exige_observacao'] === 1);
        if ($requiresObservation && $observation === '') throw new RuntimeException('Uma das atividades selecionadas exige observação.');
        $photoRequired = (int)$ambiente['exige_foto_asg'] === 1
            || (bool)array_filter($selectedActionRows, fn($a) => (int)$a['exige_foto'] === 1)
            || (bool)array_filter($selectedTaskRows, fn($t) => (int)$t['exige_foto'] === 1);
        $uploads = ambiente_normalize_uploads($_FILES['fotos'] ?? []);
        if ($photoRequired && !$uploads) throw new RuntimeException('Este ambiente ou atividade exige pelo menos uma foto.');

        $osId = (int)($_POST['ordem_servico_id'] ?? 0) ?: null;
        if ($osId !== null) {
            $ordemAtivaSql = exclusao_sql_nao_excluida($pdo, 'ordens_servico');
            $verify = $pdo->prepare("SELECT id FROM ordens_servico WHERE id=? AND local_id=? AND $ordemAtivaSql AND status IN ('ABERTA','ATRIBUIDA','EM_ANDAMENTO','PAUSADA','AGUARDANDO_MATERIAL','REPROVADA')");
            $verify->execute([$osId, (int)$ambiente['local_id']]);
            if (!$verify->fetchColumn()) throw new RuntimeException('A OS selecionada não está aberta neste ambiente.');
        }

        $started = trim((string)($_POST['iniciado_em'] ?? ''));
        $finished = trim((string)($_POST['finalizado_em'] ?? ''));
        $startedTs = $started !== '' ? strtotime($started) : false;
        $finishedTs = $finished !== '' ? strtotime($finished) : time();
        if ($started !== '' && $startedTs === false) throw new RuntimeException('Horário de início inválido.');
        if ($finishedTs === false) throw new RuntimeException('Horário de término inválido.');
        $startedDb = $startedTs !== false ? date('Y-m-d H:i:s', $startedTs) : null;
        $finishedDb = date('Y-m-d H:i:s', $finishedTs);
        if ($startedDb && strtotime($startedDb) > $finishedTs) throw new RuntimeException('O horário de início não pode ser posterior ao término.');
        if ($finishedTs > time() + 300) throw new RuntimeException('O horário de término não pode estar no futuro.');

        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO ambiente_registros(
                ambiente_id,ordem_servico_id,asg_id,registrado_por,executor_membro_id,executor_usuario_id,executor_nome,origem_lancamento,
                status,iniciado_em,finalizado_em,observacao_asg,problema_encontrado,insumos_repostos
            ) VALUES(?,?,?,?,?,?,?,?,'AGUARDANDO_VALIDACAO',?,?,?,?,?)")
            ->execute([
                (int)$ambiente['id'], $osId, $legacyExecutorId, (int)$user['id'], $principal['membro_id'], $principal['usuario_id'], $principal['nome'],
                limpeza_origin_for_profile((string)$user['perfil']), $startedDb, $finishedDb, $observation,
                trim((string)($_POST['problema_encontrado'] ?? '')), trim((string)($_POST['insumos_repostos'] ?? ''))
            ]);
        $recordId = (int)$pdo->lastInsertId();

        $participantSt = $pdo->prepare('INSERT INTO ambiente_registro_responsaveis(registro_id,membro_id,usuario_id,nome_responsavel,responsavel_principal) VALUES(?,?,?,?,?)');
        foreach ($participants as $participant) {
            $participantSt->execute([
                $recordId,
                $participant['membro_id'],
                $participant['usuario_id'],
                $participant['nome'],
                $participant['principal'] ? 1 : 0,
            ]);
        }

        if ($selectedActions) {
            $itemSt = $pdo->prepare('INSERT INTO ambiente_registro_itens(registro_id,acao_id,concluida) VALUES(?,?,1)');
            foreach ($selectedActions as $actionId) $itemSt->execute([$recordId, $actionId]);
        }

        $recordDate = new DateTimeImmutable($finishedDb);
        if ($selectedTasks) {
            $findSchedule = $pdo->prepare("SELECT ci.id
                FROM limpeza_cronogramas c JOIN limpeza_cronograma_itens ci ON ci.cronograma_id=c.id
                WHERE c.ano=? AND c.mes=? AND ci.dia=? AND ci.tarefa_id=? LIMIT 1");
            $insertTask = $pdo->prepare('INSERT INTO ambiente_registro_plano_itens(registro_id,tarefa_id,cronograma_item_id,concluida) VALUES(?,?,?,1)');
            $finishSchedule = $pdo->prepare("UPDATE limpeza_cronograma_itens SET status='CONCLUIDO',responsavel_membro_id=COALESCE(responsavel_membro_id,?),registro_id=? WHERE id=?");
            foreach ($selectedTasks as $taskId) {
                $findSchedule->execute([(int)$recordDate->format('Y'), (int)$recordDate->format('n'), (int)$recordDate->format('j'), $taskId]);
                $scheduleItemId = (int)($findSchedule->fetchColumn() ?: 0) ?: null;
                $insertTask->execute([$recordId, $taskId, $scheduleItemId]);
                if ($scheduleItemId) $finishSchedule->execute([$principal['membro_id'], $recordId, $scheduleItemId]);
            }
        }

        if ($uploads) {
            ambiente_store_photos($pdo, $recordId, (int)$user['id'], limpeza_origin_for_profile((string)$user['perfil']), 'DEPOIS', $_FILES['fotos'], false);
        }
        $pdo->commit();

        auditar($pdo, 'REGISTRAR_LIMPEZA_AMBIENTE', 'ambiente_registros', $recordId, [], [
            'ambiente_id' => $ambiente['id'],
            'os_id' => $osId,
            'responsavel_principal' => $principal['nome'],
            'equipe' => array_column($participants, 'nome'),
            'acoes' => $selectedActions,
            'tarefas_plano' => $selectedTasks,
        ]);
        $message = $user['nome'].' registrou uma limpeza em '.$ambiente['nome_publico'].' executada por '.$teamNames.'.';
        ambiente_notify_profiles($pdo, ['TI','GESTOR','GESTOR_SINDICO','SUPERVISOR_ASG'], 'AMBIENTE_VALIDACAO', 'Limpeza aguardando validação', $message, app_path('ambiente_validar',['id'=>$recordId]), 'ambiente-validar-'.$recordId);
        $_SESSION['registro_success'] = 'Limpeza registrada com toda a equipe. O serviço foi enviado para validação.';
        redirect('ambiente_registro?t='.$token);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['registro_error'] = $e->getMessage();
        redirect('ambiente_registro?t='.$token);
    }
}

$recentSt = $pdo->prepare("SELECT ar.*,os.numero_os,u.nome AS registrador_nome,eu.nome AS executor_usuario_nome,m.nome_colaborador AS membro_nome,
    (SELECT GROUP_CONCAT(rr.nome_responsavel ORDER BY rr.responsavel_principal DESC,rr.nome_responsavel SEPARATOR ', ') FROM ambiente_registro_responsaveis rr WHERE rr.registro_id=ar.id) AS equipe_nomes,
    (SELECT GROUP_CONCAT(DISTINCT aa.nome ORDER BY aa.nome SEPARATOR ' • ') FROM ambiente_registro_itens ri JOIN ambiente_acoes aa ON aa.id=ri.acao_id WHERE ri.registro_id=ar.id) AS acoes_genericas,
    (SELECT GROUP_CONCAT(DISTINCT t.descricao ORDER BY t.ordem,t.descricao SEPARATOR ' • ') FROM ambiente_registro_plano_itens rpi JOIN limpeza_plano_tarefas t ON t.id=rpi.tarefa_id WHERE rpi.registro_id=ar.id) AS tarefas_plano
    FROM ambiente_registros ar
    LEFT JOIN usuarios u ON u.id=ar.registrado_por
    LEFT JOIN usuarios eu ON eu.id=ar.executor_usuario_id
    LEFT JOIN limpeza_organograma_nos m ON m.id=ar.executor_membro_id
    LEFT JOIN ordens_servico os ON os.id=ar.ordem_servico_id
    WHERE ar.ambiente_id=? ORDER BY ar.criado_em DESC LIMIT 15");
$recentSt->execute([(int)$ambiente['id']]);
$recentRecords = $recentSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($recentRecords as &$recordRow) {
    $recordRow['acoes'] = limpeza_merge_action_names($recordRow['acoes_genericas'] ?? '', $recordRow['tarefas_plano'] ?? '');
    $recordRow['executor_exibicao'] = limpeza_record_executor($recordRow);
}
unset($recordRow);

render_header('Registrar limpeza - '.$ambiente['nome_publico']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div><span class="badge text-bg-primary mb-2">Ambiente identificado</span><h1 class="h3 mb-1"><?=e($ambiente['nome_publico'])?></h1><p class="text-muted mb-0"><?=e($ambiente['descricao_publica'] ?: $ambiente['local_nome'])?><?php if($ambiente['plano_grupo_nome'] ?? null): ?> · <?=e($ambiente['plano_grupo_nome'])?><?php endif ?></p></div>
  <div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza'))?>">Painel</a><a class="btn btn-outline-secondary" target="_blank" href="<?=e(ambiente_public_url($ambiente))?>">Página pública</a></div>
</div>
<?php if($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif ?>
<?php if($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif ?>
<div class="row g-4">
  <div class="col-lg-7"><div class="card shadow-sm"><div class="card-body p-4">
    <h2 class="h5 mb-3">Adicionar limpeza realizada</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="token" value="<?=e($token)?>">
      <div class="mb-3"><label class="form-label fw-semibold">Equipe que realizou a limpeza</label><div class="border rounded p-3">
        <?php foreach($members as $member): ?>
          <div class="row g-2 align-items-center py-2 border-bottom team-member-row">
            <div class="col"><label class="form-check mb-0"><input class="form-check-input executor-check" type="checkbox" name="executor_membro_ids[]" value="<?=e($member['id'])?>" data-radio="executor-principal-<?=e($member['id'])?>"> <span class="form-check-label"><strong><?=e($member['nome_colaborador'])?></strong> <small class="text-muted">— <?=e($member['cargo'].($member['turno']?' · '.$member['turno']:''))?></small></span></label></div>
            <div class="col-auto"><label class="form-check mb-0"><input class="form-check-input executor-principal" type="radio" name="executor_principal_id" id="executor-principal-<?=e($member['id'])?>" value="<?=e($member['id'])?>" disabled> <span class="form-check-label small">Principal</span></label></div>
          </div>
        <?php endforeach ?>
        <?php if(!$members): ?><div class="text-muted">Cadastre integrantes no organograma para formar equipes.</div><?php endif ?>
      </div><div class="form-text">Marque todos que participaram e indique um responsável principal. O principal é usado apenas para compatibilidade com registros antigos.</div></div>
      <div class="mb-3"><label class="form-label">Participante avulso <span class="text-muted">(opcional)</span></label><input class="form-control" name="executor_nome" maxlength="160" placeholder="Ex.: terceirizado ainda não cadastrado"><div class="form-text">O nome avulso será acrescentado à equipe. Quando nenhum integrante for marcado, ele será o responsável principal.</div></div>

      <?php if($planTasks): ?><div class="mt-4"><label class="form-label fw-semibold">Atividades do plano de limpeza</label><div class="border rounded p-3">
        <?php foreach($planTasks as $task): $scheduled=$todaySchedule[(int)$task['id']]??null; ?>
          <label class="d-flex gap-2 align-items-start py-2 border-bottom">
            <input class="form-check-input mt-1" type="checkbox" name="tarefas[]" value="<?=e($task['id'])?>" <?=($scheduled && $scheduled['status']!=='CONCLUIDO')?'checked':''?>>
            <span><strong><?=e($task['descricao'])?></strong><small class="d-block text-muted"><?=e($task['periodicidade'])?><?php if($task['observacao']): ?> · <?=e($task['observacao'])?><?php endif ?><?php if($task['exige_foto']): ?> · exige foto<?php endif ?><?php if($scheduled): ?> · <span class="text-<?=$scheduled['status']==='CONCLUIDO'?'success':'primary'?>">programada hoje<?= $scheduled['responsavel_nomes']?' para '.e($scheduled['responsavel_nomes']):'' ?></span><?php endif ?></small></span>
          </label>
        <?php endforeach ?>
      </div></div><?php endif ?>

      <?php if($actions): ?><div class="mt-4"><label class="form-label fw-semibold">Ações avulsas adicionais</label><div class="border rounded p-3">
        <?php foreach($actions as $actionRow): ?><label class="d-flex gap-2 align-items-start py-2 border-bottom"><input class="form-check-input mt-1" type="checkbox" name="acoes[]" value="<?=e($actionRow['id'])?>"><span><strong><?=e($actionRow['nome'])?></strong><small class="d-block text-muted"><?=e($actionRow['descricao'])?><?= $actionRow['exige_foto']?' · exige foto':'' ?><?= $actionRow['exige_observacao']?' · exige observação':'' ?></small></span></label><?php endforeach ?>
      </div></div><?php endif ?>

      <?php if(!$planTasks && !$actions): ?><div class="alert alert-warning mt-3">Este ambiente ainda não possui atividades vinculadas. Configure uma área do plano ou ações avulsas.</div><?php endif ?>
      <div class="row g-3 mt-1"><div class="col-md-6"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="iniciado_em" value="<?=e(date('Y-m-d\TH:i',strtotime('-20 minutes')))?>"></div><div class="col-md-6"><label class="form-label">Término</label><input class="form-control" type="datetime-local" name="finalizado_em" value="<?=e(date('Y-m-d\TH:i'))?>" required></div></div>
      <div class="mt-3"><label class="form-label">Vincular a uma OS aberta <span class="text-muted">(opcional)</span></label><select class="form-select" name="ordem_servico_id"><option value="">Rotina sem OS</option><?php foreach($openOs as $os): ?><option value="<?=e($os['id'])?>"><?=e($os['numero_os'].' · '.$os['titulo'].' · '.$os['status'])?></option><?php endforeach ?></select><div class="form-text">Após a validação, uma OS vinculada será encaminhada para encerramento pelo Gestor/TI.</div></div>
      <div class="mt-3"><label class="form-label">Observação da execução</label><textarea class="form-control" rows="3" name="observacao_asg" placeholder="Detalhes importantes do serviço"></textarea></div>
      <div class="row g-3 mt-0"><div class="col-md-6"><label class="form-label">Problema encontrado</label><textarea class="form-control" rows="2" name="problema_encontrado" placeholder="Ex.: torneira com vazamento"></textarea></div><div class="col-md-6"><label class="form-label">Insumos repostos</label><textarea class="form-control" rows="2" name="insumos_repostos" placeholder="Ex.: papel, sabonete e sacos"></textarea></div></div>
      <div class="mt-3"><label class="form-label">Fotos da execução <?= $ambiente['exige_foto_asg']?'<span class="text-danger">*</span>':'' ?></label><input class="form-control" type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" capture="environment" multiple><div class="form-text">Até 8 imagens em JPG, PNG ou WEBP.</div></div>
      <button class="btn btn-primary btn-lg w-100 mt-4" <?=(!$planTasks && !$actions)?'disabled':''?>>Registrar equipe e enviar para validação</button>
    </form>
  </div></div></div>
  <div class="col-lg-5"><div class="card shadow-sm"><div class="card-body"><h2 class="h5">Últimos registros do ambiente</h2>
    <?php foreach($recentRecords as $recordRow): ?><div class="border rounded p-3 mb-2"><div class="d-flex justify-content-between gap-2"><strong><?=e(date('d/m H:i',strtotime($recordRow['finalizado_em'])))?></strong><span class="badge text-bg-<?=e(ambiente_status_class($recordRow['status']))?>"><?=e(ambiente_status_label($recordRow['status']))?></span></div><div class="small mt-2"><strong>Equipe:</strong> <?=e($recordRow['executor_exibicao'])?></div><div class="small mt-1"><?=e($recordRow['acoes'] ?: 'Atividade não detalhada')?></div><?php if($recordRow['registrador_nome']): ?><small class="text-muted d-block mt-1">Lançado por <?=e($recordRow['registrador_nome'])?></small><?php endif ?><?php if($recordRow['numero_os']): ?><small class="text-muted"><?=e($recordRow['numero_os'])?></small><?php endif ?><?php if($recordRow['observacao_supervisor']): ?><div class="alert alert-light py-2 mt-2 mb-0 small"><strong>Validação:</strong> <?=e($recordRow['observacao_supervisor'])?></div><?php endif ?></div><?php endforeach ?>
    <?php if(!$recentRecords): ?><p class="text-muted mb-0">Nenhuma limpeza registrada neste ambiente.</p><?php endif ?>
  </div></div></div>
</div>
<script>
(function(){
  const checks=Array.from(document.querySelectorAll('.executor-check'));
  function sync(){
    let firstChecked=null;
    checks.forEach(function(check){
      const radio=document.getElementById(check.dataset.radio);
      if(!radio)return;
      radio.disabled=!check.checked;
      if(!check.checked)radio.checked=false;
      if(check.checked&&!firstChecked)firstChecked=radio;
    });
    const selected=document.querySelector('.executor-principal:checked');
    if(!selected&&firstChecked)firstChecked.checked=true;
  }
  checks.forEach(function(check){check.addEventListener('change',sync);});
  document.querySelectorAll('.executor-principal').forEach(function(radio){
    radio.addEventListener('change',function(){
      const check=document.querySelector('.executor-check[data-radio="'+radio.id+'"]');
      if(check&&!check.checked)check.checked=true;
      sync();
    });
  });
  sync();
})();
</script>
<?php render_footer(); ?>
