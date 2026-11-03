<?php
$root=dirname(__DIR__);
$files=[
  'includes/geop_ai.php',
  'includes/geop_ai_tools.php',
  'includes/geop_ai_openai.php',
  'includes/geop_v8.php',
  'includes/exclusao_ordens.php',
  'includes/layout.php',
  'public/ia_chat.php',
  'public/ia_chat_api.php',
  'public/ia_config.php',
  'public/atualizar_geop_ia_v8_1.php',
  'public/atualizacoes.php',
];
$fail=[];
foreach($files as $f) if(!is_file($root.'/'.$f)) $fail[]='ausente '.$f;

$toolCode=file_get_contents($root.'/includes/geop_ai_tools.php');
foreach([
 'buscar_ordens_servico',
 'resumir_ordem_servico',
 'consultar_ordens_limpeza',
 'consultar_estoque',
 'consultar_patrimonio',
 'consultar_preventivas',
 'consultar_reincidencias',
 'consultar_custos',
 'consultar_aprovacoes_pendentes',
 'consultar_auditoria',
] as $tool) {
    if(strpos($toolCode,$tool)===false) $fail[]='tool '.$tool;
}

$core=file_get_contents($root.'/includes/geop_ai_openai.php');
foreach(['function_call_output', "'store'=>false", "'tools'=>$tools"] as $needle) {
    if(strpos($core,$needle)===false) $fail[]='orquestrador '.$needle;
}

$v8=file_get_contents($root.'/includes/geop_v8.php');
if(strpos($v8,'function geop_v8_migration_schema')===false) $fail[]='compatibilidade schema_migrations';

$layout=file_get_contents($root.'/includes/layout.php');
if(strpos($layout,'GEOP IA Chat')===false) $fail[]='menu GEOP IA';

if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}
echo "GEOP IA V8.1 static: OK\n";
