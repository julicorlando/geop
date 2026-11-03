<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/limpeza.php';
require_once __DIR__ . '/../includes/audit.php';

limpeza_require_access();
limpeza_require_schema($pdo);
exigir_permissao('limpeza.cronograma');
check_csrf();

$user = current_user();
$year = max(2020, min(2100, (int)($_GET['ano'] ?? $_POST['ano'] ?? date('Y'))));
$month = max(1, min(12, (int)($_GET['mes'] ?? $_POST['mes'] ?? date('n'))));
$daysInMonth = (int)date('t', strtotime(sprintf('%04d-%02d-01', $year, $month)));
$schedule = limpeza_get_or_create_schedule($pdo, $year, $month, (int)$user['id']);
$success = $_SESSION['limpeza_crono_success'] ?? '';
$error = $_SESSION['limpeza_crono_error'] ?? '';
unset($_SESSION['limpeza_crono_success'], $_SESSION['limpeza_crono_error']);

/** @return int[] */
function limpeza_post_member_ids(PDO $pdo): array
{
    $ids = array_values(array_unique(array_filter(
        array_map('intval', (array)($_POST['responsavel_membro_ids'] ?? [])),
        static fn(int $id): bool => $id > 0
    )));
    if (!$ids) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id FROM limpeza_organograma_nos WHERE ativo=1 AND id IN ($placeholders)");
    $st->execute($ids);
    $validIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
    sort($ids);
    sort($validIds);
    if ($ids !== $validIds) throw new RuntimeException('Um dos integrantes selecionados não está ativo no organograma.');
    return $ids;
}

function limpeza_save_schedule_team(PDO $pdo, int $itemId, array $memberIds, int $principalId): void
{
    $pdo->prepare('DELETE FROM limpeza_cronograma_responsaveis WHERE cronograma_item_id=?')->execute([$itemId]);
    if (!$memberIds) return;
    if (!in_array($principalId, $memberIds, true)) $principalId = $memberIds[0];
    $ins = $pdo->prepare('INSERT INTO limpeza_cronograma_responsaveis(cronograma_item_id,membro_id,responsavel_principal) VALUES(?,?,?)');
    foreach ($memberIds as $memberId) $ins->execute([$itemId, $memberId, $memberId === $principalId ? 1 : 0]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save_row') {
            $taskId = (int)($_POST['tarefa_id'] ?? 0);
            $selected = array_values(array_unique(array_filter(
                array_map('intval', (array)($_POST['dias'] ?? [])),
                static fn(int $day): bool => $day >= 1 && $day <= $daysInMonth
            )));
            $memberIds = limpeza_post_member_ids($pdo);
            $principalId = (int)($_POST['responsavel_principal_id'] ?? 0);
            if ($memberIds && !in_array($principalId, $memberIds, true)) $principalId = $memberIds[0];
            if (!$memberIds) $principalId = 0;

            $valid = $pdo->prepare('SELECT id FROM limpeza_plano_tarefas WHERE id=? AND ativo=1');
            $valid->execute([$taskId]);
            if (!$valid->fetchColumn()) throw new RuntimeException('Tarefa inválida ou inativa.');

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM limpeza_cronograma_itens WHERE cronograma_id=? AND tarefa_id=? AND status='PENDENTE'")
                ->execute([(int)$schedule['id'], $taskId]);

            $insertItem = $pdo->prepare("INSERT INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status,responsavel_membro_id)
                VALUES(?,?,?,'PENDENTE',?)
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),responsavel_membro_id=IF(status='CONCLUIDO',responsavel_membro_id,VALUES(responsavel_membro_id))");
            $readItem = $pdo->prepare('SELECT id,status FROM limpeza_cronograma_itens WHERE cronograma_id=? AND tarefa_id=? AND dia=? LIMIT 1');

            foreach ($selected as $day) {
                $insertItem->execute([(int)$schedule['id'], $taskId, $day, $principalId ?: null]);
                $readItem->execute([(int)$schedule['id'], $taskId, $day]);
                $item = $readItem->fetch(PDO::FETCH_ASSOC);
                if ($item && $item['status'] !== 'CONCLUIDO') {
                    limpeza_save_schedule_team($pdo, (int)$item['id'], $memberIds, $principalId);
                }
            }

            $pdo->prepare("UPDATE limpeza_cronogramas SET status='PUBLICADO',atualizado_em=NOW() WHERE id=?")
                ->execute([(int)$schedule['id']]);
            $pdo->commit();

            auditar($pdo, 'EDITAR_CRONOGRAMA_LIMPEZA', 'limpeza_cronograma_itens', $taskId, [], [
                'ano' => $year,
                'mes' => $month,
                'dias' => $selected,
                'responsaveis' => $memberIds,
                'responsavel_principal' => $principalId ?: null,
            ]);
            $_SESSION['limpeza_crono_success'] = 'Linha do cronograma e equipe atualizadas.';
        } elseif ($action === 'copy_month') {
            $sourceYear = max(2020, min(2100, (int)($_POST['source_year'] ?? $year)));
            $sourceMonth = max(1, min(12, (int)($_POST['source_month'] ?? max(1, $month - 1))));
            $sourceSt = $pdo->prepare('SELECT id FROM limpeza_cronogramas WHERE ano=? AND mes=? LIMIT 1');
            $sourceSt->execute([$sourceYear, $sourceMonth]);
            $sourceId = (int)$sourceSt->fetchColumn();
            if (!$sourceId) throw new RuntimeException('O mês de origem não possui cronograma.');

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM limpeza_cronograma_itens WHERE cronograma_id=? AND status='PENDENTE'")
                ->execute([(int)$schedule['id']]);

            $rows = $pdo->prepare('SELECT id,tarefa_id,dia,responsavel_membro_id FROM limpeza_cronograma_itens WHERE cronograma_id=?');
            $rows->execute([$sourceId]);
            $sourceTeam = $pdo->prepare('SELECT membro_id,responsavel_principal FROM limpeza_cronograma_responsaveis WHERE cronograma_item_id=? ORDER BY responsavel_principal DESC,id');
            $insertItem = $pdo->prepare("INSERT INTO limpeza_cronograma_itens(cronograma_id,tarefa_id,dia,status,responsavel_membro_id)
                VALUES(?,?,?,'PENDENTE',?)
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
            $readTarget = $pdo->prepare('SELECT id,status FROM limpeza_cronograma_itens WHERE cronograma_id=? AND tarefa_id=? AND dia=? LIMIT 1');

            foreach ($rows->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                if ((int)$row['dia'] > $daysInMonth) continue;
                $insertItem->execute([(int)$schedule['id'], (int)$row['tarefa_id'], (int)$row['dia'], $row['responsavel_membro_id'] ?: null]);
                $readTarget->execute([(int)$schedule['id'], (int)$row['tarefa_id'], (int)$row['dia']]);
                $target = $readTarget->fetch(PDO::FETCH_ASSOC);
                if (!$target || $target['status'] === 'CONCLUIDO') continue;

                $sourceTeam->execute([(int)$row['id']]);
                $teamRows = $sourceTeam->fetchAll(PDO::FETCH_ASSOC) ?: [];
                $memberIds = array_map('intval', array_column($teamRows, 'membro_id'));
                $principalId = 0;
                foreach ($teamRows as $teamRow) {
                    if ((int)$teamRow['responsavel_principal'] === 1) {
                        $principalId = (int)$teamRow['membro_id'];
                        break;
                    }
                }
                if (!$memberIds && !empty($row['responsavel_membro_id'])) {
                    $memberIds = [(int)$row['responsavel_membro_id']];
                    $principalId = (int)$row['responsavel_membro_id'];
                }
                limpeza_save_schedule_team($pdo, (int)$target['id'], $memberIds, $principalId);
            }
            $pdo->commit();
            $_SESSION['limpeza_crono_success'] = 'Marcações e equipes copiadas para o mês selecionado.';
        } elseif ($action === 'save_group') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['nome'] ?? ''));
            $description = trim((string)($_POST['descricao'] ?? ''));
            $order = max(0, min(999, (int)($_POST['ordem'] ?? 0)));
            if ($name === '') throw new RuntimeException('Informe o nome da área/grupo.');
            if ($id) {
                $pdo->prepare('UPDATE limpeza_plano_grupos SET nome=?,descricao=?,ordem=?,ativo=? WHERE id=?')
                    ->execute([$name, $description, $order, !empty($_POST['ativo']) ? 1 : 0, $id]);
            } else {
                $key = 'manual_' . bin2hex(random_bytes(8));
                $pdo->prepare('INSERT INTO limpeza_plano_grupos(chave,nome,descricao,ordem,ativo,criado_por) VALUES(?,?,?,?,1,?)')
                    ->execute([$key, $name, $description, $order, (int)$user['id']]);
            }
            $_SESSION['limpeza_crono_success'] = 'Área do plano salva.';
        } elseif ($action === 'save_task') {
            $id = (int)($_POST['id'] ?? 0);
            $groupId = (int)($_POST['grupo_id'] ?? 0);
            $description = trim((string)($_POST['descricao'] ?? ''));
            $note = trim((string)($_POST['observacao'] ?? ''));
            $period = trim((string)($_POST['periodicidade'] ?? ''));
            $order = max(0, min(999, (int)($_POST['ordem'] ?? 0)));
            if ($groupId <= 0 || $description === '' || $period === '') throw new RuntimeException('Informe área, atividade e periodicidade.');
            if ($id) {
                $pdo->prepare('UPDATE limpeza_plano_tarefas SET grupo_id=?,descricao=?,observacao=?,periodicidade=?,exige_foto=?,exige_observacao=?,ordem=?,ativo=? WHERE id=?')
                    ->execute([$groupId, $description, $note, $period, !empty($_POST['exige_foto']) ? 1 : 0, !empty($_POST['exige_observacao']) ? 1 : 0, $order, !empty($_POST['ativo']) ? 1 : 0, $id]);
            } else {
                $key = 'manual_' . bin2hex(random_bytes(8));
                $pdo->prepare('INSERT INTO limpeza_plano_tarefas(grupo_id,chave,descricao,observacao,periodicidade,exige_foto,exige_observacao,ordem,ativo,criado_por) VALUES(?,?,?,?,?,?,?,?,1,?)')
                    ->execute([$groupId, $key, $description, $note, $period, !empty($_POST['exige_foto']) ? 1 : 0, !empty($_POST['exige_observacao']) ? 1 : 0, $order, (int)$user['id']]);
            }
            $_SESSION['limpeza_crono_success'] = 'Atividade do plano salva.';
        } elseif ($action === 'toggle_task') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE limpeza_plano_tarefas SET ativo=IF(ativo=1,0,1) WHERE id=?')->execute([$id]);
            $_SESSION['limpeza_crono_success'] = 'Status da atividade alterado.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['limpeza_crono_error'] = $e->getMessage();
    }
    redirect('limpeza_cronograma?ano=' . $year . '&mes=' . $month);
}

$groups = limpeza_groups($pdo, false);
$tasks = limpeza_tasks($pdo, null, false);
$members = limpeza_members($pdo, true);
$map = limpeza_schedule_map($pdo, (int)$schedule['id']);
$editTaskId = (int)($_GET['edit_task'] ?? 0);
$editTask = null;
foreach ($tasks as $task) if ((int)$task['id'] === $editTaskId) $editTask = $task;
$editGroupId = (int)($_GET['edit_group'] ?? 0);
$editGroup = null;
foreach ($groups as $group) if ((int)$group['id'] === $editGroupId) $editGroup = $group;
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

render_header('Cronograma de Limpeza');
?>
<style>
.cleaning-calendar{font-size:.78rem}.cleaning-calendar th,.cleaning-calendar td{min-width:36px;text-align:center;vertical-align:middle;padding:.25rem}.cleaning-calendar .task-col{min-width:310px;text-align:left;position:sticky;left:0;background:var(--bs-body-bg);z-index:2}.cleaning-calendar .period-col{min-width:95px;position:sticky;left:310px;background:var(--bs-body-bg);z-index:2}.cleaning-calendar .resp-col{min-width:270px}.cleaning-calendar .group-row td{background:#343a40;color:#fff;font-weight:600;text-align:left;position:static}.day-check{width:1.1rem;height:1.1rem}.calendar-done{background:#d1e7dd!important}.calendar-failed{background:#f8d7da!important}.calendar-weekend{background:#f7f7f7}.calendar-note{max-width:420px}.schedule-scroller{overflow:auto;max-height:72vh}.team-select{min-height:92px}.team-summary{white-space:normal;text-align:left}
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div><h1 class="h3 mb-1">Cronograma editável de limpeza</h1><p class="text-muted mb-0">Defina os dias, a equipe prevista e um responsável principal por atividade.</p></div>
  <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza'))?>">Painel</a><a class="btn btn-outline-primary" href="<?=e(app_path('ambiente_registro'))?>">Registrar limpeza</a></div>
</div>
<?php if ($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif ?>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif ?>
<div class="alert alert-light border"><strong>Equipe por dia:</strong> a equipe selecionada é gravada separadamente em cada dia marcado. Ao salvar novamente uma linha, a equipe será aplicada aos dias pendentes marcados; dias já concluídos não são alterados.</div>
<div class="card shadow-sm mb-4"><div class="card-body"><div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
  <div class="btn-group"><a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza_cronograma',['ano'=>$prevYear,'mes'=>$prevMonth]))?>">←</a><span class="btn btn-light disabled fw-semibold"><?=e(limpeza_month_name($month).' / '.$year)?></span><a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza_cronograma',['ano'=>$nextYear,'mes'=>$nextMonth]))?>">→</a></div>
  <span class="badge text-bg-<?=$schedule['status']==='PUBLICADO'?'success':'secondary'?>"><?=e($schedule['status'])?></span>
  <form method="post" class="d-flex flex-wrap gap-2 align-items-end"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="copy_month"><input type="hidden" name="ano" value="<?=e($year)?>"><input type="hidden" name="mes" value="<?=e($month)?>"><div><label class="form-label small mb-1">Copiar ano</label><input class="form-control form-control-sm" type="number" name="source_year" value="<?=e($prevYear)?>" style="width:95px"></div><div><label class="form-label small mb-1">Mês</label><select class="form-select form-select-sm" name="source_month"><?php for($m=1;$m<=12;$m++): ?><option value="<?=$m?>" <?=$m===$prevMonth?'selected':''?>><?=e(limpeza_month_name($m))?></option><?php endfor ?></select></div><button class="btn btn-sm btn-outline-primary">Copiar marcações e equipes</button></form>
</div></div></div>
<div class="card shadow-sm"><div class="schedule-scroller"><table class="table table-bordered cleaning-calendar mb-0"><thead class="table-light"><tr><th class="task-col">Descrição dos serviços</th><th class="period-col">Periodicidade</th><th class="resp-col">Equipe prevista</th><?php for($d=1;$d<=$daysInMonth;$d++): $weekend=in_array(limpeza_weekday_short($year,$month,$d),['Sáb','Dom'],true); ?><th class="<?=$weekend?'calendar-weekend':''?>"><small class="d-block"><?=e(limpeza_weekday_short($year,$month,$d))?></small><?=$d?></th><?php endfor ?><th>Salvar</th></tr></thead><tbody>
<?php $lastGroup=0; foreach($tasks as $task): if((int)$task['grupo_id']!==$lastGroup): $lastGroup=(int)$task['grupo_id']; ?><tr class="group-row"><td colspan="<?=$daysInMonth+4?>"><?=e($task['grupo_nome'])?></td></tr><?php endif; $team=limpeza_schedule_team_for_task($map[(int)$task['id']]??[]); ?>
<tr class="<?=!$task['ativo']?'opacity-50':''?>">
  <td class="task-col"><form id="row-<?=e($task['id'])?>" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_row"><input type="hidden" name="ano" value="<?=e($year)?>"><input type="hidden" name="mes" value="<?=e($month)?>"><input type="hidden" name="tarefa_id" value="<?=e($task['id'])?>"></form><strong><?=e($task['descricao'])?></strong><?php if($task['observacao']): ?><small class="d-block text-danger calendar-note"><?=e($task['observacao'])?></small><?php endif ?><a class="small" href="<?=e(app_path('limpeza_cronograma',['ano'=>$year,'mes'=>$month,'edit_task'=>$task['id']]))?>">Editar atividade</a></td>
  <td class="period-col"><?=e($task['periodicidade'])?></td>
  <td class="resp-col text-start">
    <label class="small fw-semibold" for="team-<?=e($task['id'])?>">Integrantes</label>
    <select class="form-select form-select-sm team-select" multiple name="responsavel_membro_ids[]" id="team-<?=e($task['id'])?>" form="row-<?=e($task['id'])?>" data-principal="principal-<?=e($task['id'])?>">
      <?php foreach($members as $member): ?><option value="<?=e($member['id'])?>" <?=in_array((int)$member['id'],$team['ids'],true)?'selected':''?>><?=e($member['nome_colaborador'].' — '.$member['cargo'])?></option><?php endforeach ?>
    </select>
    <label class="small fw-semibold mt-1" for="principal-<?=e($task['id'])?>">Responsável principal</label>
    <select class="form-select form-select-sm principal-select" name="responsavel_principal_id" id="principal-<?=e($task['id'])?>" form="row-<?=e($task['id'])?>" data-current="<?=e($team['principal_id'])?>"><option value="">Sem equipe definida</option></select>
    <?php if($team['nomes']): ?><small class="d-block text-muted mt-1 team-summary">Atual: <?=e($team['nomes'])?></small><?php endif ?>
  </td>
<?php for($d=1;$d<=$daysInMonth;$d++): $cell=$map[(int)$task['id']][$d]??null;$weekend=in_array(limpeza_weekday_short($year,$month,$d),['Sáb','Dom'],true);$class=$cell&&$cell['status']==='CONCLUIDO'?'calendar-done':($cell&&$cell['status']==='NAO_REALIZADO'?'calendar-failed':($weekend?'calendar-weekend':''));$title=$cell&&$cell['responsavel_nomes']?'Equipe: '.$cell['responsavel_nomes']:''; ?><td class="<?=$class?>" title="<?=e($title)?>"><?php if($cell&&$cell['status']==='CONCLUIDO'): ?><a title="Limpeza registrada<?= $title?' · '.e($title):'' ?>" href="<?=e(app_path('ambiente_validar',['id'=>$cell['registro_id'],'status'=>'TODOS']))?>">✓</a><?php else: ?><input class="form-check-input day-check" type="checkbox" name="dias[]" value="<?=$d?>" form="row-<?=e($task['id'])?>" <?=$cell?'checked':''?> <?=$task['ativo']?'':'disabled'?>><?php endif ?></td><?php endfor ?>
  <td><button class="btn btn-sm btn-primary" form="row-<?=e($task['id'])?>" <?=$task['ativo']?'':'disabled'?>>Salvar</button></td>
</tr>
<?php endforeach ?><?php if(!$tasks): ?><tr><td colspan="<?=$daysInMonth+4?>" class="text-center text-muted py-4">Nenhuma atividade cadastrada.</td></tr><?php endif ?></tbody></table></div></div>
<div class="row g-4 mt-1">
<div class="col-lg-5"><div class="card shadow-sm"><div class="card-body"><h2 class="h5"><?=$editGroup?'Editar área':'Nova área do plano'?></h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_group"><input type="hidden" name="ano" value="<?=e($year)?>"><input type="hidden" name="mes" value="<?=e($month)?>"><input type="hidden" name="id" value="<?=e($editGroup['id']??0)?>"><div class="mb-3"><label class="form-label">Nome da área</label><input class="form-control" name="nome" required value="<?=e($editGroup['nome']??'')?>"></div><div class="mb-3"><label class="form-label">Descrição</label><textarea class="form-control" name="descricao" rows="2"><?=e($editGroup['descricao']??'')?></textarea></div><div class="row"><div class="col-6"><label class="form-label">Ordem</label><input class="form-control" type="number" name="ordem" value="<?=e($editGroup['ordem']??0)?>"></div><?php if($editGroup): ?><div class="col-6 d-flex align-items-end"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="ativo" <?=!empty($editGroup['ativo'])?'checked':''?>> Ativa</label></div><?php endif ?></div><button class="btn btn-primary w-100 mt-3">Salvar área</button></form><div class="mt-3 d-flex flex-wrap gap-2"><?php foreach($groups as $group): ?><a class="badge text-bg-<?=$group['ativo']?'light':'secondary'?> text-decoration-none" href="<?=e(app_path('limpeza_cronograma',['ano'=>$year,'mes'=>$month,'edit_group'=>$group['id']]))?>"><?=e($group['nome'])?></a><?php endforeach ?></div></div></div></div>
<div class="col-lg-7"><div class="card shadow-sm"><div class="card-body"><h2 class="h5"><?=$editTask?'Editar atividade':'Nova atividade'?></h2><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_task"><input type="hidden" name="ano" value="<?=e($year)?>"><input type="hidden" name="mes" value="<?=e($month)?>"><input type="hidden" name="id" value="<?=e($editTask['id']??0)?>"><div class="row g-3"><div class="col-md-5"><label class="form-label">Área</label><select class="form-select" name="grupo_id" required><option value="">Selecione</option><?php foreach($groups as $group): ?><option value="<?=e($group['id'])?>" <?=((int)($editTask['grupo_id']??0)===(int)$group['id'])?'selected':''?>><?=e($group['nome'])?></option><?php endforeach ?></select></div><div class="col-md-4"><label class="form-label">Periodicidade</label><select class="form-select" name="periodicidade"><option>Diária</option><option <?=($editTask['periodicidade']??'')==='Semanal'?'selected':''?>>Semanal</option><option <?=($editTask['periodicidade']??'')==='Quinzenal'?'selected':''?>>Quinzenal</option><option <?=($editTask['periodicidade']??'')==='Mensal'?'selected':''?>>Mensal</option><option <?=($editTask['periodicidade']??'')==='Eventual'?'selected':''?>>Eventual</option></select></div><div class="col-md-3"><label class="form-label">Ordem</label><input class="form-control" type="number" name="ordem" value="<?=e($editTask['ordem']??0)?>"></div></div><div class="mt-3"><label class="form-label">Atividade</label><input class="form-control" name="descricao" maxlength="500" required value="<?=e($editTask['descricao']??'')?>"></div><div class="mt-3"><label class="form-label">Observação operacional</label><textarea class="form-control" name="observacao" rows="2"><?=e($editTask['observacao']??'')?></textarea></div><div class="d-flex flex-wrap gap-4 mt-3"><label class="form-check"><input class="form-check-input" type="checkbox" name="exige_foto" <?=!empty($editTask['exige_foto'])?'checked':''?>> Exige foto</label><label class="form-check"><input class="form-check-input" type="checkbox" name="exige_observacao" <?=!empty($editTask['exige_observacao'])?'checked':''?>> Exige observação</label><?php if($editTask): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="ativo" <?=!empty($editTask['ativo'])?'checked':''?>> Ativa</label><?php endif ?></div><button class="btn btn-success mt-3">Salvar atividade</button><?php if($editTask): ?><a class="btn btn-link" href="<?=e(app_path('limpeza_cronograma',['ano'=>$year,'mes'=>$month]))?>">Cancelar</a><?php endif ?></form></div></div></div>
</div>
<script>
(function(){
  function syncTeam(select){
    const principal=document.getElementById(select.dataset.principal);
    if(!principal)return;
    const current=principal.dataset.current||principal.value;
    const selected=Array.from(select.selectedOptions);
    principal.innerHTML='';
    const empty=document.createElement('option');
    empty.value='';
    empty.textContent=selected.length?'Selecione o principal':'Sem equipe definida';
    principal.appendChild(empty);
    selected.forEach(function(option){
      const item=document.createElement('option');
      item.value=option.value;
      item.textContent=option.textContent;
      if(option.value===current)item.selected=true;
      principal.appendChild(item);
    });
    if(selected.length && !principal.value) principal.value=selected[0].value;
    principal.dataset.current=principal.value;
  }
  document.querySelectorAll('.team-select').forEach(function(select){
    syncTeam(select);
    select.addEventListener('change',function(){
      const principal=document.getElementById(select.dataset.principal);
      if(principal) principal.dataset.current=principal.value;
      syncTeam(select);
    });
  });
  document.querySelectorAll('.principal-select').forEach(function(select){
    select.addEventListener('change',function(){select.dataset.current=select.value;});
  });
})();
</script>
<?php render_footer(); ?>
