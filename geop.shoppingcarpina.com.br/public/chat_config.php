<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/chat_core.php';
require_role('TI');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $enabled = !empty($_POST['chat_habilitado']) ? '1' : '0';
    $pdo->prepare("INSERT INTO configuracoes_sistema(chave,valor,descricao,atualizado_por)
        VALUES('chat_habilitado',?,'Controla a exibição e o uso do chat interno',?)
        ON DUPLICATE KEY UPDATE valor=VALUES(valor),atualizado_por=VALUES(atualizado_por),updated_at=NOW()")
        ->execute([$enabled,(int)current_user()['id']]);
    auditar($pdo, 'CHAT_CONFIGURAR', 'configuracoes_sistema', null, [], ['chat_habilitado'=>$enabled]);
    $_SESSION['mensagem_sucesso'] = $enabled === '1' ? 'Chat ativado.' : 'Chat desativado.';
    header('Location: ' . app_path('chat_config')); exit;
}

$enabled = chat_is_enabled($pdo);
$activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE status='ATIVO'")->fetchColumn();
$messageCount = (int)$pdo->query("SELECT COUNT(*) FROM chat_mensagens")->fetchColumn();
$message = $_SESSION['mensagem_sucesso'] ?? '';
unset($_SESSION['mensagem_sucesso']);
render_header('Configurar chat');
?>
<div class="row justify-content-center"><div class="col-lg-7">
 <div class="card p-4">
  <h1 class="h3">Chat interno</h1>
  <?php if($message): ?><div class="alert alert-success"><?=e($message)?></div><?php endif ?>
  <p>Quando desabilitado, o botão desaparece para todos os usuários e a API bloqueia leitura e envio. As mensagens existentes são preservadas.</p>
  <div class="alert alert-light border">
   <strong>Diagnóstico:</strong> <?=e($activeUsers)?> usuário(s) ativo(s) e <?=e($messageCount)?> mensagem(ns) armazenada(s).
  </div>
  <form method="post">
   <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
   <div class="form-check form-switch fs-5 mb-3">
    <input class="form-check-input" type="checkbox" role="switch" id="chat_habilitado" name="chat_habilitado" value="1" <?=$enabled?'checked':''?>>
    <label class="form-check-label" for="chat_habilitado">Chat habilitado</label>
   </div>
   <button class="btn btn-primary">Salvar configuração</button>
  </form>
 </div>
</div></div>
<?php render_footer(); ?>
