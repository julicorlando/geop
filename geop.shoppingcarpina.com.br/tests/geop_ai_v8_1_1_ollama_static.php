<?php
$root=dirname(__DIR__);
$required=[
 'includes/geop_ai.php',
 'includes/geop_ai_openai.php',
 'includes/geop_ai_ollama.php',
 'includes/geop_ai_provider.php',
 'includes/geop_ai_tools.php',
 'public/ia_config.php',
 'public/ia_chat.php',
 'public/ia_chat_api.php',
];
$fail=[];
foreach($required as $f) if(!is_file($root.'/'.$f)) $fail[]='ausente '.$f;

$core=file_get_contents($root.'/includes/geop_ai.php');
foreach(['ai_chat_provider','OLLAMA','qwen3:4b-instruct','geop_ai_ollama_base_url','geop_ai_provider_ready'] as $n)
    if(strpos($core,$n)===false)$fail[]='core '.$n;

$ollama=file_get_contents($root.'/includes/geop_ai_ollama.php');
foreach(['/api/chat','/api/tags','tool_calls','tool_name','num_ctx'] as $n)
    if(strpos($ollama,$n)===false)$fail[]='ollama '.$n;

$provider=file_get_contents($root.'/includes/geop_ai_provider.php');
if(strpos($provider,'geop_ai_chat_answer_ollama')===false || strpos($provider,'geop_ai_chat_answer_openai')===false)
    $fail[]='provider dispatch';

if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}
echo "GEOP IA V8.1.1 Ollama static: OK\n";
