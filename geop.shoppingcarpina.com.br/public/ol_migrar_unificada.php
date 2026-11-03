<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/ordens_unificadas.php';

require_login();
check_csrf();
$user=current_user();
if(strtoupper((string)($user['perfil']??''))!=='TI'){
    http_response_code(403);
    die('Acesso negado. Somente o TI pode migrar OL antigas.');
}
$id=(int)($_POST['id']??$_GET['id']??0);
if($id<=0) die('OL inválida.');
try{
    $osId=ordem_migrar_ol_legada($pdo,$id,(int)$user['id']);
    $_SESSION['mensagem_sucesso']='OL migrada para o ambiente unificado de Ordens. O prefixo OL foi preservado.';
    redirect('ordem_detalhe?id='.$osId);
}catch(Throwable $e){
    $_SESSION['mensagem_erro']=$e->getMessage();
    redirect('ol_detalhe?id='.$id);
}
