<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/exclusao_ordens.php';

require_login();
check_csrf();

$user = current_user();
if (!in_array(exclusao_perfil_atual($user), ['TI','GESTOR','GESTOR_SINDICO'], true)) {
    http_response_code(403);
    die('Acesso negado.');
}

$mensagem = $_SESSION['exclusao_ordens_ok'] ?? '';
$erro = $_SESSION['exclusao_ordens_erro'] ?? '';
unset($_SESSION['exclusao_ordens_ok'], $_SESSION['exclusao_ordens_erro']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!exclusao_schema_ready($pdo)) throw new RuntimeException('O TI precisa aplicar a atualização V7.5 antes de usar este módulo.');
        $action = (string)($_POST['action'] ?? '');
        $uid = (int)$user['id'];

        if ($action === 'solicitar') {
            exclusao_solicitar($pdo,(string)($_POST['entidade'] ?? ''),(int)($_POST['ordem_id'] ?? 0),$uid,(string)($_POST['motivo'] ?? ''));
            $_SESSION['exclusao_ordens_ok'] = 'Solicitação de exclusão enviada para análise.';
        } elseif ($action === 'aprovar') {
            exclusao_analisar($pdo,(int)($_POST['solicitacao_id'] ?? 0),$uid,'APROVAR',(string)($_POST['parecer'] ?? ''));
            $_SESSION['exclusao_ordens_ok'] = 'Solicitação aprovada. A exclusão aguarda execução pelo TI.';
        } elseif ($action === 'reprovar') {
            exclusao_analisar($pdo,(int)($_POST['solicitacao_id'] ?? 0),$uid,'REPROVAR',(string)($_POST['parecer'] ?? ''));
            $_SESSION['exclusao_ordens_ok'] = 'Solicitação reprovada.';
        } elseif ($action === 'executar') {
            exclusao_executar($pdo,(int)($_POST['solicitacao_id'] ?? 0),$uid);
            $_SESSION['exclusao_ordens_ok'] = 'Ordem enviada para a lixeira.';
        } elseif ($action === 'excluir_direto') {
            exclusao_excluir_direto($pdo,(string)($_POST['entidade'] ?? ''),(int)($_POST['ordem_id'] ?? 0),$uid,(string)($_POST['motivo'] ?? ''));
            $_SESSION['exclusao_ordens_ok'] = 'Ordem excluída diretamente pelo TI e enviada para a lixeira.';
        } else {
            throw new RuntimeException('Ação inválida.');
        }
    } catch (Throwable $e) {
        $_SESSION['exclusao_ordens_erro'] = $e->getMessage();
    }
    header('Location: ' . app_path('exclusoes_ordens'));
    exit;
}

$schemaReady = exclusao_schema_ready($pdo);
$statusFiltro = strtoupper(trim((string)($_GET['status'] ?? 'ATIVOS')));
$permitidos = ['ATIVOS','TODOS','PENDENTE','APROVADA','REPROVADA','EXECUTADA','CANCELADA'];
if (!in_array($statusFiltro,$permitidos,true)) $statusFiltro='ATIVOS';

$rows = [];
$contagens = ['PENDENTE'=>0,'APROVADA'=>0,'REPROVADA'=>0,'EXECUTADA'=>0];
if ($schemaReady) {
    $where = '';
    $params = [];
    if ($statusFiltro === 'ATIVOS') $where = "WHERE s.status IN ('PENDENTE','APROVADA')";
    elseif ($statusFiltro !== 'TODOS') { $where = 'WHERE s.status=?'; $params[]=$statusFiltro; }

    $st = $pdo->prepare("SELECT s.*,sol.nome AS solicitado_por_nome,ana.nome AS analisado_por_nome,exe.nome AS executado_por_nome
        FROM ordem_solicitacoes_exclusao s
        LEFT JOIN usuarios sol ON sol.id=s.solicitado_por
        LEFT JOIN usuarios ana ON ana.id=s.analisado_por
        LEFT JOIN usuarios exe ON exe.id=s.executado_por
        $where
        ORDER BY FIELD(s.status,'PENDENTE','APROVADA','REPROVADA','EXECUTADA','CANCELADA'),s.criado_em DESC
        LIMIT 500");
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($pdo->query("SELECT status,COUNT(*) total FROM ordem_solicitacoes_exclusao GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) ?: [] as $c) {
        $contagens[$c['status']] = (int)$c['total'];
    }
}

render_header('Solicitações de exclusão');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div><h1 class="h3 mb-1">Solicitações de exclusão</h1><p class="text-muted mb-0">Gestor solicita, outro Gestor/TI analisa e somente TI executa a exclusão.</p></div>
  <div class="d-flex gap-2">
    <?php if(exclusao_pode_executar($user)): ?><a class="btn btn-outline-danger" href="<?= e(app_path('lixeira_ordens')) ?>">Lixeira</a><?php endif ?>
    <?php if(exclusao_pode_executar($user)): ?><a class="btn btn-outline-secondary" href="<?= e(app_path('atualizar_exclusao_ordens_v7_5')) ?>">Atualização V7.5</a><?php endif ?>
  </div>
</div>

<?php if(!$schemaReady): ?>
<div class="alert alert-warning">
  <strong>Módulo ainda não ativado.</strong>
  <?php if(exclusao_pode_executar($user)): ?>Acesse <a href="<?= e(app_path('atualizar_exclusao_ordens_v7_5')) ?>">Atualização V7.5</a> e aplique a migração.<?php else: ?>Solicite ao TI a ativação da atualização V7.5.<?php endif ?>
</div>
<?php else: ?>

<?php if($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif ?>
<?php if($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif ?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="card p-3"><small class="text-muted">Pendentes</small><strong class="h3 mb-0"><?= (int)$contagens['PENDENTE'] ?></strong></div></div>
  <div class="col-6 col-lg-3"><div class="card p-3"><small class="text-muted">Aprovadas / aguardando TI</small><strong class="h3 mb-0"><?= (int)$contagens['APROVADA'] ?></strong></div></div>
  <div class="col-6 col-lg-3"><div class="card p-3"><small class="text-muted">Reprovadas</small><strong class="h3 mb-0"><?= (int)$contagens['REPROVADA'] ?></strong></div></div>
  <div class="col-6 col-lg-3"><div class="card p-3"><small class="text-muted">Executadas</small><strong class="h3 mb-0"><?= (int)$contagens['EXECUTADA'] ?></strong></div></div>
</div>

<form class="card card-body mb-3" method="get">
 <div class="row g-2 align-items-end">
  <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status">
   <?php foreach(['ATIVOS'=>'Pendentes + aprovadas','TODOS'=>'Todos','PENDENTE'=>'Pendentes','APROVADA'=>'Aprovadas','REPROVADA'=>'Reprovadas','EXECUTADA'=>'Executadas'] as $k=>$v): ?>
   <option value="<?= e($k) ?>" <?= $statusFiltro===$k?'selected':'' ?>><?= e($v) ?></option>
   <?php endforeach ?>
  </select></div>
  <div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Filtrar</button></div>
 </div>
</form>

<div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Ordem</th><th>Solicitação</th><th>Motivo</th><th>Status</th><th>Análise</th><th class="text-end">Ações</th></tr></thead>
<tbody>
<?php foreach($rows as $r):
    $ordem = exclusao_carregar_ordem($pdo,(string)$r['entidade'],(int)$r['ordem_id'],true);
    $estaExcluida = $ordem && !empty($ordem['excluida_em']);
?>
<tr>
 <td><strong><?= e($r['numero_ordem'] ?: '#'.$r['ordem_id']) ?></strong><div class="small text-muted"><?= e($r['titulo_ordem'] ?: '-') ?></div><div class="small"><?= $r['entidade']==='LIMPEZA_ORDEM'?'OL legada':'Ordem unificada' ?></div></td>
 <td><div><?= e($r['solicitado_por_nome'] ?: ('Usuário #'.$r['solicitado_por'])) ?></div><small class="text-muted"><?= e(date('d/m/Y H:i',strtotime($r['criado_em']))) ?> · <?= $r['origem']==='DIRETA_TI'?'Direta pelo TI':'Solicitação' ?></small></td>
 <td style="min-width:220px"><?= nl2br(e($r['motivo'])) ?></td>
 <td><span class="badge text-bg-<?= e(exclusao_status_class($r['status'])) ?>"><?= e(exclusao_status_label($r['status'])) ?></span><?php if($estaExcluida): ?><div class="small text-danger mt-1">Na lixeira desde <?= e(date('d/m/Y H:i',strtotime($ordem['excluida_em']))) ?></div><?php endif ?></td>
 <td><small><?= e($r['analisado_por_nome'] ?: '-') ?><?php if($r['analisado_em']): ?><br><?= e(date('d/m/Y H:i',strtotime($r['analisado_em']))) ?><?php endif ?><?php if($r['parecer']): ?><br><?= e($r['parecer']) ?><?php endif ?></small></td>
 <td class="text-end" style="min-width:230px">
   <?php if($ordem && !$estaExcluida): ?><a class="btn btn-sm btn-outline-primary mb-1" href="<?= e(app_path($ordem['detail'],['id'=>$ordem['id']])) ?>">Abrir ordem</a><?php endif ?>
   <?php if($r['status']==='PENDENTE' && exclusao_pode_analisar($user) && (int)$r['solicitado_por'] !== (int)$user['id']): ?>
   <form method="post" class="d-inline">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="solicitacao_id" value="<?= (int)$r['id'] ?>">
    <button class="btn btn-sm btn-success mb-1" name="action" value="aprovar" onclick="return confirm('Aprovar a exclusão?')">Aprovar</button>
    <button class="btn btn-sm btn-outline-danger mb-1" name="action" value="reprovar" onclick="return confirm('Reprovar a exclusão?')">Reprovar</button>
   </form>
   <?php endif ?>
   <?php if($r['status']==='APROVADA' && exclusao_pode_executar($user)): ?>
   <form method="post" class="d-inline" onsubmit="return confirm('Executar exclusão e enviar a ordem para a lixeira?');">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="solicitacao_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="executar">
    <button class="btn btn-sm btn-danger mb-1">Excluir agora</button>
   </form>
   <?php endif ?>
   <?php if($r['status']==='PENDENTE' && (int)$r['solicitado_por']===(int)$user['id']): ?><div class="small text-muted">Você não pode analisar sua própria solicitação.</div><?php endif ?>
 </td>
</tr>
<?php endforeach ?>
<?php if(!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">Nenhuma solicitação encontrada.</td></tr><?php endif ?>
</tbody></table></div></div>
<?php endif ?>
<?php render_footer(); ?>
