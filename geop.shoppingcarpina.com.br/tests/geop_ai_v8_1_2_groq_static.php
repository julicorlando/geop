<?php
$root=dirname(__DIR__);
$fail=[];

$required=[
 'includes/geop_ai.php',
 'includes/geop_ai_groq.php',
 'includes/geop_ai_openai.php',
 'includes/geop_ai_ollama.php',
 'includes/geop_ai_provider.php',
 'includes/layout.php',
 'public/ia_config.php',
 'public/ia_chat.php',
 'public/ia_chat_api.php',
];
foreach($required as $f)if(!is_file($root.'/'.$f))$fail[]='ausente '.$f;

$core=file_get_contents($root.'/includes/geop_ai.php');
foreach(['GROQ','qwen/qwen3.8-27b','geop_ai_groq_endpoint','geop_ai_groq_api_key_configured'] as $needle)
 if(strpos($core,$needle)===false)$fail[]='core '.$needle;

$groq=file_get_contents($root.'/includes/geop_ai_groq.php');
foreach(['/responses','/models','function_call_output','tool_choice'] as $needle)
 if(strpos($groq,$needle)===false)$fail[]='groq '.$needle;

$provider=file_get_contents($root.'/includes/geop_ai_provider.php');
if(strpos($provider,'geop_ai_chat_answer_groq')===false)$fail[]='provider groq';

$layout=file_get_contents($root.'/includes/layout.php');
if(strpos($layout,'left:18px;bottom:18px')===false)$fail[]='botao esquerda';
if(strpos($layout,'right:18px;bottom:18px')!==false)$fail[]='botao ainda direita';

if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}
echo "GEOP IA V8.1.2 Groq static: OK\n";
