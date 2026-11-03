<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$required=['includes/ordens_limpeza.php','public/ol.php','public/ol_form.php','public/ol_detalhe.php','public/ol_relatorios.php','public/ol_imprimir.php','sql/ATUALIZACAO_MODULO_OL_V5.sql','sql/INSTALACAO_COMPLETA_MODULO_LIMPEZA_V5.sql','sql/migrations/007_ordem_limpeza_ol_v5.sql'];
foreach($required as $file){if(!is_file($root.'/'.$file)){fwrite(STDERR,"Arquivo OL ausente: $file\n");exit(1);}}
$sql=(string)file_get_contents($root.'/sql/ATUALIZACAO_MODULO_OL_V5.sql');
foreach(['limpeza_ordens','limpeza_ordem_participantes','limpeza_ordem_itens','limpeza_ordem_fotos','limpeza_ordem_historico','ol.acessar','ol.criar','ol.validar',"pf.codigo='SUPERVISOR_ASG'",'origem_lancamento'] as $needle){if(strpos($sql,$needle)===false){fwrite(STDERR,"SQL OL sem: $needle\n");exit(1);}}
$form=(string)file_get_contents($root.'/public/ol_form.php');
foreach(['Todos os funcionários ativos do organograma aparecem aqui, inclusive o supervisor','membro_ids[]','supervisor_membro_id','principal_id','limpeza_members($pdo,true)'] as $needle){if(strpos($form,$needle)===false){fwrite(STDERR,"Formulário OL sem: $needle\n");exit(1);}}
$detail=(string)file_get_contents($root.'/public/ol_detalhe.php');
foreach(['Planejado','executores[]','ol_user_executed','Aprovar e encerrar','ol_sync_environment_record','CORRECAO_SOLICITADA'] as $needle){if(stripos($detail,$needle)===false){fwrite(STDERR,"Detalhe OL sem: $needle\n");exit(1);}}
$layout=(string)file_get_contents($root.'/includes/layout.php');
foreach(["'OL — Ordens de Limpeza'","'Visualizar OL'","'Nova OL'","'Relatórios OL'"] as $needle){if(strpos($layout,$needle)===false){fwrite(STDERR,"Menu OL sem: $needle\n");exit(1);}}
$helper=(string)file_get_contents($root.'/includes/ordens_limpeza.php');
foreach(['ol_generate_number','ol_replace_team','ol_set_actual_team','ol_store_photos','ol_notify_team','ol_sync_environment_record'] as $needle){if(strpos($helper,$needle)===false){fwrite(STDERR,"Helper OL sem: $needle\n");exit(1);}}
echo "Módulo OL V5 static test: OK\n";
