<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/exclusao_ordens.php';

require_role('TI');
check_csrf();
$user = current_user();

$mensagem = $_SESSION['lixeira_ordens_ok'] ?? '';
$erro = $_SESSION['lixeira_ordens_erro'] ?? '';
unset($_SESSION['lixeira_ordens_ok'],$_SESSION['lixeira_ordens_erro']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        exclusao_restaurar($pdo,(string)($_POST['entidade'] ?? ''),(int)($_POST['ordem_id'] ?? 0),(int)$user['id']);
        $_SESSION['lixeira_ordens_ok']='Ordem restaurada com sucesso.';
    } catch(Throwable $e) {
        $_SESSION['lixeira_ordens_erro']=$e->getMessage();
    }
    header('Location: '.app_path('lixeira_ordens'));
    exit;
}

$rows=[];
$ready=exclusao_schema_ready($pdo);
if($ready){
    $os=$pdo->query("SELECT 'ORDEM_SERVICO' entidade,id,numero_os numero,titulo,excluida_em,excluida_por,exclusao_solicitacao_id
        FROM ordens_servico WHERE excluida_em IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC)?:[];
    $ol=$pdo->query("SELECT 'LIMPEZA_ORDEM' entidade,id,numero_ol numero,titulo,excluida_em,excluida_por,exclusao_solicitacao_id
        FROM limpeza_ordens WHERE excluida_em IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC)?:[];
    $rows=array_merge($os,$ol);
    usort($rows,fn($a,$b)=>strcmp((string)$b['excluida_em'],(string)$a['excluida_em']));
    $userIds=array_values(array_unique(array_filter(array_map(fn($r)=>(int)$r['excluida_por'],$rows))));
    $users=[];
    if($userIds){
        $ph=implode(',',array_fill(0,count($userIds),'?'));
        $st=$pdo->prepare("SELECT id,nome FROM usuarios WHERE id IN ($ph)");
        $st->execute($userIds);
        foreach($st->fetchAll(PDO::FETCH_ASSOC)?:[] as $u)$users[(int)$u['id']]=$u['nome'];
    }
}
render_header('Lixeira de ordens');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
 <div><h1 class="h3 mb-1">Lixeira de ordens</h1><p class="text-muted mb-0">Somente TI pode visualizar e restaurar ordens excluídas.</p></div>
 <a class="btn btn-outline-primary" href="<?= e(app_path('exclusoes_ordens')) ?>">Solicitações</a>
</div>
<?php if(!$ready): ?><div class="alert alert-warning">A atualização V7.5 ainda não foi aplicada. <a href="<?= e(app_path('atualizar_exclusao_ordens_v7_5')) ?>">Aplicar agora</a>.</div>
<?php else: ?>
<?php if($mensagem): ?><div class="alert alert-success"><?= e($mensagem) ?></div><?php endif ?>
<?php if($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif ?>
<div class="card shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
<thead><tr><th>Ordem</th><th>Tipo</th><th>Excluída em</th><th>Excluída por</th><th>Solicitação</th><th class="text-end">Ação</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
 <td><strong><?= e($r['numero']) ?></strong><div class="small text-muted"><?= e($r['titulo']) ?></div></td>
 <td><?= $r['entidade']==='LIMPEZA_ORDEM'?'OL legada':'Ordem unificada' ?></td>
 <td><?= e(date('d/m/Y H:i',strtotime($r['excluida_em']))) ?></td>
 <td><?= e($users[(int)$r['excluida_por']] ?? ('Usuário #'.$r['excluida_por'])) ?></td>
 <td><?= $r['exclusao_solicitacao_id'] ? '#'.(int)$r['exclusao_solicitacao_id'] : '-' ?></td>
 <td class="text-end"><form method="post" onsubmit="return confirm('Restaurar esta ordem?');"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="entidade" value="<?= e($r['entidade']) ?>"><input type="hidden" name="ordem_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-success">Restaurar</button></form></td>
</tr>
<?php endforeach ?>
<?php if(!$rows): ?><tr><td colspan="6" class="text-center text-muted py-5">A lixeira está vazia.</td></tr><?php endif ?>
</tbody></table></div></div>
<?php endif ?>
<?php render_footer(); ?>
