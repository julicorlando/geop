<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/layout.php';require_once __DIR__.'/../includes/geop_v8.php';
require_role('TI');check_csrf();geop_v8_boot($pdo);
if (!geop_v8_feature_ready($pdo)) {
    if ((current_user()['perfil'] ?? '') === 'TI') redirect('atualizar_geop_v8');
    http_response_code(503); die('O módulo GEOP V8 aguarda a atualização do banco pelo TI.');
}$user=current_user();$msg='';$err='';
if(isset($_GET['download'])){
 $id=(int)$_GET['download'];$st=$pdo->prepare('SELECT * FROM sistema_backups WHERE id=?');$st->execute([$id]);$b=$st->fetch(PDO::FETCH_ASSOC);if(!$b)die('Backup não encontrado.');$path=geop_v8_backup_dir().'/'.basename($b['arquivo']);if(!is_file($path))die('Arquivo de backup não encontrado.');header('Content-Type: application/gzip');header('Content-Disposition: attachment; filename="'.basename($b['arquivo']).'"');header('Content-Length: '.filesize($path));readfile($path);exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'){try{$b=geop_v8_create_database_backup($pdo,(int)$user['id']);$msg='Backup criado: '.$b['arquivo'];geop_v8_security_event($pdo,(int)$user['id'],'BACKUP_CRIADO',$b['arquivo']);}catch(Throwable $e){$err=$e->getMessage();}}
$list=$pdo->query("SELECT b.*,u.nome criado_por_nome FROM sistema_backups b LEFT JOIN usuarios u ON u.id=b.criado_por ORDER BY b.id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);render_header('Backups');
?>
<div class="d-flex justify-content-between align-items-center mb-3"><div><h1 class="h3 mb-1">Backups</h1><p class="text-muted mb-0">Backups SQL compactados armazenados fora da pasta pública.</p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="btn btn-primary" onclick="return confirm('Criar backup completo do banco agora?')">Criar backup agora</button></form></div>
<?php if($msg):?><div class="alert alert-success"><?=e($msg)?></div><?php endif?><?php if($err):?><div class="alert alert-danger"><?=e($err)?></div><?php endif?>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Arquivo</th><th>Data</th><th>Tamanho</th><th>Por</th><th>SHA-256</th><th></th></tr></thead><tbody><?php foreach($list as $b):?><tr><td><?=e($b['arquivo'])?></td><td><?=e($b['criado_em'])?></td><td><?=e(number_format($b['tamanho_bytes']/1024/1024,1,',','.'))?> MB</td><td><?=e($b['criado_por_nome']??'-')?></td><td><code class="small"><?=e(substr((string)$b['sha256'],0,16))?>…</code></td><td><a class="btn btn-sm btn-outline-primary" href="?download=<?=(int)$b['id']?>">Baixar</a></td></tr><?php endforeach?><?php if(!$list):?><tr><td colspan="6" class="text-muted">Nenhum backup interno registrado.</td></tr><?php endif?></tbody></table></div></div>
<?php render_footer(); ?>
