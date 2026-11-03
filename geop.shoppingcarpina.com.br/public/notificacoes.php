<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';
require_login();
gestao_ensure_schema($pdo);
$user = current_user();

if (isset($_GET['abrir'])) {
    $id = (int)$_GET['abrir'];
    $st = $pdo->prepare("SELECT url FROM notificacoes WHERE id=? AND usuario_id=? LIMIT 1");
    $st->execute([$id,(int)$user['id']]);
    $url = (string)$st->fetchColumn();
    $pdo->prepare("UPDATE notificacoes SET lida_em=NOW() WHERE id=? AND usuario_id=?")
        ->execute([$id,(int)$user['id']]);
    header('Location: ' . ($url !== '' ? $url : app_path('notificacoes')));
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $action = (string)($_POST['action'] ?? 'marcar_lidas');
    if ($action === 'limpar') {
        // O filtro pelo usuário autenticado impede que uma pessoa limpe
        // notificações pertencentes a qualquer outro usuário.
        $st = $pdo->prepare("DELETE FROM notificacoes WHERE usuario_id=?");
        $st->execute([(int)$user['id']]);
        $_SESSION['mensagem_sucesso'] = $st->rowCount() . ' notificação(ões) removida(s).';
    } else {
        $pdo->prepare("UPDATE notificacoes SET lida_em=NOW() WHERE usuario_id=? AND lida_em IS NULL")
            ->execute([(int)$user['id']]);
        $_SESSION['mensagem_sucesso'] = 'Todas as notificações foram marcadas como lidas.';
    }
    redirect('notificacoes');
}

$items = gestao_notificacoes_usuario($pdo, (int)$user['id'], 200);
render_header('Central de notificações');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
 <div><h1 class="h3 mb-0">Central de notificações</h1><small class="text-muted">OS, SLA, materiais, aprovações, mensagens e preventivas.</small></div>
 <div class="d-flex gap-2">
  <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="marcar_lidas"><button class="btn btn-outline-primary">Marcar todas como lidas</button></form>
  <form method="post" onsubmit="return confirm('Limpar somente as suas notificações? Esta ação não afeta outros usuários.')"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="limpar"><button class="btn btn-outline-danger">Limpar minhas notificações</button></form>
 </div>
</div>
<div class="card">
 <?php foreach ($items as $item): ?>
  <a class="p-3 border-bottom text-decoration-none text-dark <?= empty($item['lida_em'])?'bg-light':'' ?>" href="<?= e(app_path('notificacoes',['abrir'=>$item['id']])) ?>">
   <div class="d-flex justify-content-between gap-3"><strong><?= e($item['titulo']) ?></strong><small class="text-muted"><?= e($item['created_at']) ?></small></div>
   <div><?= e($item['mensagem']) ?></div><small class="badge text-bg-secondary"><?= e($item['tipo']) ?></small>
  </a>
 <?php endforeach; ?>
 <?php if (!$items): ?><div class="p-5 text-center text-muted">Nenhuma notificação registrada.</div><?php endif; ?>
</div>
<?php render_footer(); ?>
