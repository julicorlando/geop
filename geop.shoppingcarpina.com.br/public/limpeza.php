<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ambientes_qr.php';
require_once __DIR__ . '/../includes/limpeza.php';

limpeza_require_access();
limpeza_require_schema($pdo);
ambiente_require_schema($pdo);

$today=(int)date('j');
$year=(int)date('Y');
$month=(int)date('n');
$metrics=['previstas'=>0,'concluidas'=>0,'pendentes_validacao'=>0,'ambientes'=>0,'equipe'=>0,'ol_ativas'=>0];
try {
    $st=$pdo->prepare("SELECT
        SUM(ci.status='PENDENTE') previstas,
        SUM(ci.status='CONCLUIDO') concluidas
        FROM limpeza_cronogramas c JOIN limpeza_cronograma_itens ci ON ci.cronograma_id=c.id
        WHERE c.ano=? AND c.mes=? AND ci.dia=?");
    $st->execute([$year,$month,$today]);
    $dayMetrics=$st->fetch(PDO::FETCH_ASSOC)?:[];
    $metrics['previstas']=(int)($dayMetrics['previstas']??0);
    $metrics['concluidas']=(int)($dayMetrics['concluidas']??0);
    $metrics['pendentes_validacao']=(int)$pdo->query("SELECT COUNT(*) FROM ambiente_registros WHERE status='AGUARDANDO_VALIDACAO'")->fetchColumn();
    $metrics['ambientes']=(int)$pdo->query("SELECT COUNT(*) FROM ambientes_qr WHERE ativo=1")->fetchColumn();
    $metrics['equipe']=(int)$pdo->query("SELECT COUNT(*) FROM limpeza_organograma_nos WHERE ativo=1")->fetchColumn();
    if (function_exists('ol_schema_ready') && ol_schema_ready($pdo)) {
        $olAtivaSql=exclusao_sql_nao_excluida($pdo,'limpeza_ordens');
        $metrics['ol_ativas']=(int)$pdo->query("SELECT COUNT(*) FROM limpeza_ordens WHERE $olAtivaSql AND status NOT IN ('ENCERRADA','CANCELADA')")->fetchColumn();
    }
} catch(Throwable $e) {
    error_log('[GEOP limpeza dashboard] '.$e->getMessage());
}

$todayTasks=[];
try {
    $st=$pdo->prepare("SELECT ci.*,t.descricao,t.periodicidade,t.observacao,g.nome AS grupo_nome,ar.id AS registro_id,
        (SELECT GROUP_CONCAT(n.nome_colaborador ORDER BY cr.responsavel_principal DESC,n.nome_colaborador SEPARATOR ', ') FROM limpeza_cronograma_responsaveis cr JOIN limpeza_organograma_nos n ON n.id=cr.membro_id WHERE cr.cronograma_item_id=ci.id) AS responsavel_nomes
        FROM limpeza_cronogramas c
        JOIN limpeza_cronograma_itens ci ON ci.cronograma_id=c.id
        JOIN limpeza_plano_tarefas t ON t.id=ci.tarefa_id
        JOIN limpeza_plano_grupos g ON g.id=t.grupo_id
        LEFT JOIN ambiente_registros ar ON ar.id=ci.registro_id
        WHERE c.ano=? AND c.mes=? AND ci.dia=?
        ORDER BY g.ordem,t.ordem,t.descricao");
    $st->execute([$year,$month,$today]);
    $todayTasks=$st->fetchAll(PDO::FETCH_ASSOC)?:[];
} catch(Throwable $e) {}

$recent=$pdo->query("SELECT ar.id,ar.status,ar.finalizado_em,aq.nome_publico,
    COALESCE((SELECT GROUP_CONCAT(rr.nome_responsavel ORDER BY rr.responsavel_principal DESC,rr.nome_responsavel SEPARATOR ', ') FROM ambiente_registro_responsaveis rr WHERE rr.registro_id=ar.id),NULLIF(ar.executor_nome,''),m.nome_colaborador,eu.nome,u.nome) AS executor_nome,
    s.nome AS supervisor_nome
    FROM ambiente_registros ar
    JOIN ambientes_qr aq ON aq.id=ar.ambiente_id
    JOIN usuarios u ON u.id=ar.asg_id
    LEFT JOIN usuarios eu ON eu.id=ar.executor_usuario_id
    LEFT JOIN limpeza_organograma_nos m ON m.id=ar.executor_membro_id
    LEFT JOIN usuarios s ON s.id=ar.supervisor_id
    ORDER BY ar.criado_em DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC)?:[];

render_header('Módulo Limpeza');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div><h1 class="h3 mb-1">Módulo Limpeza</h1><p class="text-muted mb-0">Cronograma, equipe, ambientes, registros e validação em um único fluxo.</p></div>
  <a class="btn btn-primary" href="<?=e(app_path('ambiente_registro'))?>">Registrar limpeza</a>
</div>
<div class="row g-3 mb-4">
  <?php foreach([
    ['Previstas hoje',$metrics['previstas'],'primary'],['Concluídas hoje',$metrics['concluidas'],'success'],['Aguardando validação',$metrics['pendentes_validacao'],'warning'],['Ambientes ativos',$metrics['ambientes'],'info'],['Equipe ativa',$metrics['equipe'],'secondary'],['OL ativas',$metrics['ol_ativas'],'primary']
  ] as $card): ?>
  <div class="col-6 col-lg"><div class="card h-100 border-<?=$card[2]?>"><div class="card-body"><small class="text-muted"><?=e($card[0])?></small><strong class="d-block h3 mb-0"><?=e($card[1])?></strong></div></div></div>
  <?php endforeach ?>
</div>
<div class="row g-3 mb-4">
  <?php if(function_exists('ol_access_allowed') && ol_access_allowed()): ?><div class="col-md-6 col-xl-3"><a class="card card-body h-100 text-decoration-none shadow-sm" href="<?=e(app_path('ol'))?>"><strong class="h5 text-body">Ordens de Limpeza</strong><span class="text-muted">Abra OL, atribua toda a equipe, execute e valide.</span></a></div><?php endif ?>
  <div class="col-md-6 col-xl-3"><a class="card card-body h-100 text-decoration-none shadow-sm" href="<?=e(app_path('limpeza_cronograma'))?>"><strong class="h5 text-body">Cronograma editável</strong><span class="text-muted">Plano mensal baseado na planilha, com marcações por dia.</span></a></div>
  <div class="col-md-6 col-xl-3"><a class="card card-body h-100 text-decoration-none shadow-sm" href="<?=e(app_path('limpeza_organograma'))?>"><strong class="h5 text-body">Organograma e equipe</strong><span class="text-muted">Cargos, hierarquia, turnos e equipes responsáveis.</span></a></div>
  <div class="col-md-6 col-xl-3"><a class="card card-body h-100 text-decoration-none shadow-sm" href="<?=e(app_path('ambientes'))?>"><strong class="h5 text-body">Ambientes e QR</strong><span class="text-muted">Vincule áreas do plano aos ambientes monitorados.</span></a></div>
  <div class="col-md-6 col-xl-3"><a class="card card-body h-100 text-decoration-none shadow-sm" href="<?=e(app_path('ambiente_validar'))?>"><strong class="h5 text-body">Validações</strong><span class="text-muted">Conferência, fotos, aprovação e OS vinculadas.</span></a></div>
</div>
<div class="row g-4">
  <div class="col-xl-7">
    <div class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Programação de hoje</h2><a class="btn btn-sm btn-outline-primary" href="<?=e(app_path('limpeza_cronograma',['ano'=>$year,'mes'=>$month]))?>">Abrir mês</a></div>
      <div class="list-group list-group-flush">
      <?php foreach($todayTasks as $task): ?>
        <div class="list-group-item px-0"><div class="d-flex justify-content-between gap-2"><div><strong><?=e($task['grupo_nome'])?></strong><div><?=e($task['descricao'])?></div><?php if($task['observacao']): ?><small class="text-muted"><?=e($task['observacao'])?></small><?php endif ?></div><span class="badge text-bg-<?=$task['status']==='CONCLUIDO'?'success':'warning'?> align-self-start"><?=e($task['status']==='CONCLUIDO'?'Concluída':'Pendente')?></span></div><?php if($task['responsavel_nomes']): ?><small class="text-muted">Equipe prevista: <?=e($task['responsavel_nomes'])?></small><?php endif ?></div>
      <?php endforeach ?>
      <?php if(!$todayTasks): ?><div class="text-muted py-3">Nenhuma tarefa programada para hoje.</div><?php endif ?>
      </div>
    </div></div>
  </div>
  <div class="col-xl-5">
    <div class="card shadow-sm"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Últimos lançamentos</h2><a class="btn btn-sm btn-outline-secondary" href="<?=e(app_path('ambiente_relatorios'))?>">Relatório</a></div>
      <div class="list-group list-group-flush">
      <?php foreach($recent as $row): ?><a class="list-group-item list-group-item-action px-0" href="<?=e(app_path('ambiente_validar',['id'=>$row['id'],'status'=>'TODOS']))?>"><div class="d-flex justify-content-between gap-2"><strong><?=e($row['nome_publico'])?></strong><span class="badge text-bg-<?=e(ambiente_status_class($row['status']))?>"><?=e(ambiente_status_label($row['status']))?></span></div><small class="text-muted"><?=e($row['executor_nome'])?> · <?=e(date('d/m/Y H:i',strtotime($row['finalizado_em'])))?></small></a><?php endforeach ?>
      <?php if(!$recent): ?><div class="text-muted py-3">Ainda não existem lançamentos.</div><?php endif ?>
      </div>
    </div></div>
  </div>
</div>
<?php render_footer(); ?>
