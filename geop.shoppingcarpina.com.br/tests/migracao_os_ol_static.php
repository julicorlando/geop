<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$checks=[
    [$root.'/public/ordem_detalhe.php','Migrar para OL'],
    [$root.'/public/ordem_detalhe.php','ol_form'],
    [$root.'/public/ol_form.php','origem_os_id'],
    [$root.'/public/ol_form.php','limpeza_ordem_migracoes'],
    [$root.'/public/ol_form.php','encerrar_os_origem'],
    [$root.'/public/ol_detalhe.php','OS de origem'],
    [$root.'/includes/ordens_limpeza.php','ol_migration_by_os'],
    [$root.'/includes/ordens_limpeza.php','ol_copy_os_images'],
    [$root.'/sql/ATUALIZACAO_MIGRACAO_OS_OL_V6.sql','CREATE TABLE IF NOT EXISTS limpeza_ordem_migracoes'],
    [$root.'/sql/ATUALIZACAO_MIGRACAO_OS_OL_V6.sql','ol.migrar_os'],
    [$root.'/public/ordem_detalhe.php',"((\$user['perfil'] ?? '') === 'TI')"],
    [$root.'/public/ol_form.php',"A migração de OS para OL é exclusiva do TI."],
    [$root.'/sql/ATUALIZACAO_RESTRICAO_MIGRACAO_TI_V6_1.sql',"pf.codigo <> 'TI'"],
    [$root.'/sql/ATUALIZACAO_RESTRICAO_MIGRACAO_TI_V6_1.sql',"pf.codigo='TI'"],
];
$fail=[];
foreach($checks as [$file,$needle]){
    $content=@file_get_contents($file);
    if($content===false || strpos($content,$needle)===false)$fail[]=$file.' -> '.$needle;
}
if($fail){fwrite(STDERR,"FALHAS:\n- ".implode("\n- ",$fail)."\n");exit(1);}
echo "OK: migração OS -> OL V6.1 exclusiva do TI validada estaticamente.\n";
