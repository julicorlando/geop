<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/cftv.php';
require_once __DIR__ . '/../includes/layout.php';

if(!is_logged_in()){
    header('Location: '.app_path('login',['app'=>'controle_acesso']));
    exit;
}

$perfil=(string)(current_user()['perfil']??'');
$destino=(string)($_GET['destino']??'');
if(in_array($perfil,['CFTV','CONTROLE_ACESSO'],true)){
    header('Location: '.app_path($destino==='liberacoes'?'liberacoes_aprovadas':'controle_acesso'));
    exit;
}
if(in_array($perfil,cftv_liberacao_perfis(),true)){
    header('Location: '.app_path('cftv',['view'=>'liberacoes']));
    exit;
}

http_response_code(403);
render_header('Controle de acesso');
?>
<div class="row justify-content-center"><div class="col-lg-6"><div class="card p-4 text-center">
 <h1 class="h4">Perfil sem acesso ao aplicativo</h1>
 <p class="text-muted">Este aplicativo é exclusivo para CFTV, Controle de Acesso, gestores autorizados e TI.</p>
 <a class="btn btn-outline-dark" href="<?=e(app_path('logout'))?>">Sair</a>
</div></div></div>
<?php render_footer(); ?>
