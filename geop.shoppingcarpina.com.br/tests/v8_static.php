<?php
$root = dirname(__DIR__);
$checks = [
    'includes/geop_v8.php' => ['schema_migrations','geop_v8_apply_migrations','geop_v8_create_database_backup','geop_v8_totp_verify','geop_v8_find_duplicates','geop_v8_quality','compras_solicitacoes'],
    'includes/auth.php' => ['geop_v8_session_guard','geop_v8_login_blocked','2FA_REQUIRED'],
    'includes/layout.php' => ['Central de aprovações','Dashboard financeiro','Saúde do GEOP','Atualizações'],
    'public/ordem_form.php' => ['geop_v8_find_duplicates','ignorar_duplicidade','assistente-v8','geop_v8_checklist_instantiate'],
    'public/ordem_detalhe.php' => ['v8_checklist_update','Qualidade da OS','custos_os'],
    'public/central_aprovacoes.php' => ['EXCLUSAO','REABERTURA','estoque_reserva','compra_solicitacao'],
    'public/compras.php' => ['ENTRADA_COMPRA','Solicitar compra'],
    'public/seguranca.php' => ['Configurar 2FA','2fa_ti_required'],
    'public/saude_sistema.php' => ['geop_v8_health','Integridade de anexos'],
];
$fail=[];
foreach($checks as $file=>$needles){
    $path=$root.'/'.$file;
    if(!is_file($path)){$fail[]="Arquivo ausente: $file";continue;}
    $src=file_get_contents($path);
    foreach($needles as $n) if(strpos($src,$n)===false)$fail[]="$file sem marcador: $n";
}
if($fail){fwrite(STDERR,"GEOP V8 static FALHOU:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo "GEOP V8 static: OK\n";
