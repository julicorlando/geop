<?php
$root=dirname(__DIR__);
$fail=[];

$groq=file_get_contents($root.'/includes/geop_ai_groq.php');
foreach([
 'GeopAiGroqRateLimitException',
 'retry-after',
 'geop_ai_groq_select_tools',
 'geop_ai_groq_trim_history',
 'geop_ai_groq_fallback_model',
 'groq_rate_limit_fallback',
] as $needle){
 if(strpos($groq,$needle)===false)$fail[]='groq '.$needle;
}

$core=file_get_contents($root.'/includes/geop_ai.php');
foreach([
 'ai_chat_groq_fallback_model',
 'openai/gpt-oss-20b',
 'ai_chat_groq_history_messages',
 'ai_chat_groq_max_tools'
] as $needle){
 if(strpos($core,$needle)===false)$fail[]='core '.$needle;
}

$layout=file_get_contents($root.'/includes/layout.php');
if(strpos($layout,'left:18px;bottom:18px')===false)$fail[]='botao nao esta esquerda';
if(strpos($layout,'right:18px;bottom:18px')!==false)$fail[]='botao ainda esta direita';

if($fail){fwrite(STDERR,implode(PHP_EOL,$fail).PHP_EOL);exit(1);}
echo "GEOP IA V8.1.3 rate-limit static: OK\n";
