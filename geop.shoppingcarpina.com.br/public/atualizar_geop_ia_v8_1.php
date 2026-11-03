<?php
declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/geop_ai.php';

require_role('TI');
check_csrf();

$user=current_user();
$error='';$base=[];$ai=[];

try{
    geop_v8_core_schema($pdo);
    if($_SERVER['REQUEST_METHOD']==='POST'){
        $base=geop_v8_apply_migrations($pdo,(int)$user['id']);
        $ai=geop_ai_install($pdo,(int)$user['id']);
    }
}catch(Throwable $e){$error=$e->getMessage();}

$checks=[
    'V8 migrations compatíveis'=>function_exists('geop_v8_migration_schema'),
    'ia_chat_conversas'=>geop_v8_table_exists($pdo,'ia_chat_conversas'),
    'ia_chat_mensagens'=>geop_v8_table_exists($pdo,'ia_chat_mensagens'),
    'ia_chat_execucoes'=>geop_v8_table_exists($pdo,'ia_chat_execucoes'),
];
$ready=!in_array(false,$checks,true);

render_header('Atualizar GEOP IA V8.1');
?>
<h1 class="h3 mb-1">GEOP V8.1.4 — UI móvel + notificações por perfil</h1>
<p class="text-muted">Instala o copiloto de consulta e adiciona Groq Cloud como provedor online padrão, mantendo Ollama e OpenAI.</p>

<?php if($error):?><div class="alert alert-danger"><strong>Erro:</strong> <?=e($error)?></div><?php endif;?>

<div class="card mb-3"><div class="card-body">
  <h2 class="h5">Preflight</h2>
  <ul class="mb-0">
    <?php foreach($checks as $label=>$ok):?><li><?=e($label)?>: <strong><?=$ok?'OK':'PENDENTE'?></strong></li><?php endforeach;?>
    <li>PHP cURL: <strong><?=function_exists('curl_init')?'OK':'PENDENTE'?></strong></li>
    <li>Provedor padrão: <strong><?=e(geop_ai_provider($pdo))?></strong></li>
    <li>Modelo Groq: <strong><?=e(geop_ai_groq_model($pdo))?></strong></li>
    <li>GROQ_API_KEY: <strong><?=geop_ai_groq_api_key_configured()?'CONFIGURADA':'PENDENTE'?></strong></li>
    <li>Ollama URL: <strong><?=e(parse_url(geop_ai_ollama_base_url(),PHP_URL_HOST) ?: geop_ai_ollama_base_url())?></strong></li>
    <li>OPENAI_API_KEY: <strong><?=geop_ai_api_key_configured()?'CONFIGURADA':'OPCIONAL'?></strong></li>
  </ul>
</div></div>

<?php if($base||$ai):?>
<div class="alert alert-success">
  <strong>Atualização processada.</strong>
  <ul class="mb-0 mt-2">
    <?php foreach(array_merge($base,$ai) as $r):?><li><?=e($r['versao'])?> — <?=e($r['status'])?> — <?=e($r['descricao'])?></li><?php endforeach;?>
  </ul>
</div>
<?php endif;?>

<?php if(!$ready):?>
<form method="post" onsubmit="return confirm('Aplicar/continuar migrations da V8 e instalar o GEOP IA V8.1?');">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
  <button class="btn btn-primary">Aplicar GEOP IA V8.1.4</button>
</form>
<?php else:?>
<div class="alert alert-success">Banco do GEOP IA pronto.</div>
<div class="d-flex gap-2 flex-wrap">
  <a class="btn btn-primary" href="<?=e(app_path('ia_config'))?>">Configurar GEOP IA</a>
  <a class="btn btn-outline-primary" href="<?=e(app_path('ia_chat'))?>">Abrir chat</a>
</div>
<?php endif;?>
<?php render_footer(); ?>
