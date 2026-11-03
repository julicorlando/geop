<?php
$root=dirname(__DIR__);
$fail=[];

$layout=file_get_contents($root.'/includes/layout.php');
foreach([
 'data-geop-draggable="ai"',
 'localStorage.setItem',
 'pointerdown',
 'geop-floating-pos-v1-',
 'gestao_notificacoes_resumo_usuario'
] as $n) if(strpos($layout,$n)===false)$fail[]='layout '.$n;

$chat=file_get_contents($root.'/public/chat.php');
foreach(['data-geop-draggable="chat"','touch-action:none','cursor:grab'] as $n)
 if(strpos($chat,$n)===false)$fail[]='chat '.$n;

$gestao=file_get_contents($root.'/includes/gestao_integrada.php');
foreach([
 'function gestao_perfis_gestao',
 'function gestao_perfis_ti',
 'function gestao_perfis_cftv',
 'function gestao_notificacao_pertinente_perfil',
 'function gestao_notificacoes_resumo_usuario',
 "'ACESSO_APROVADO'",
 "'ESTOQUE_MINIMO'"
] as $n) if(strpos($gestao,$n)===false)$fail[]='gestao '.$n;

$cftv=file_get_contents($root.'/public/cftv.php');
if(strpos($cftv,"perfil IN ('TI','CFTV','CONTROLE_ACESSO')")!==false)$fail[]='CFTV ainda notifica TI';

$sol=file_get_contents($root.'/public/solicitar_acesso.php');
if(strpos($sol,"perfil IN ('TI','GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS')")!==false)
 $fail[]='solicitação de acesso ainda notifica TI como aprovador geral';

if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}
echo "GEOP V8.1.4 movable buttons + role notifications: OK\n";
