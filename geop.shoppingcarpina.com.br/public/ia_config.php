<?php
declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/geop_ai_provider.php';

require_role('TI');
check_csrf();
geop_v8_boot($pdo);
geop_ai_schema($pdo);

$user=current_user();
$success='';$error='';$ollamaTest=null;$groqTest=null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $action=(string)($_POST['action']??'salvar');

        if($action==='testar_ollama'){
            $ollamaTest=geop_ai_ollama_test($pdo);
            if(empty($ollamaTest['modelo_instalado'])){
                $error='Ollama acessível, mas o modelo '.geop_ai_ollama_model($pdo).' não apareceu em /api/tags.';
            }else{
                $success='Conexão com Ollama OK. Modelo localizado: '.geop_ai_ollama_model($pdo).'.';
            }
        }elseif($action==='testar_groq'){
            $groqTest=geop_ai_groq_test($pdo);
            if(empty($groqTest['modelo_disponivel'])){
                $error='Groq acessível, mas o modelo '.geop_ai_groq_model($pdo).' não apareceu na lista de modelos da sua conta.';
            }else{
                $success='Conexão com Groq OK. Modelo disponível: '.geop_ai_groq_model($pdo).'.';
            }
        }else{
            $enabled=!empty($_POST['enabled'])?'1':'0';
            $provider=strtoupper(trim((string)($_POST['provider']??'GROQ')));
            if(!in_array($provider,['GROQ','OLLAMA','OPENAI'],true))$provider='GROQ';

            $groqModel=trim((string)($_POST['groq_model']??'qwen/qwen3.8-27b'));
            $groqFallbackModel=trim((string)($_POST['groq_fallback_model']??'openai/gpt-oss-20b'));
            $groqHistory=max(0,min(10,(int)($_POST['groq_history_messages']??4)));
            $groqMaxTools=max(2,min(10,(int)($_POST['groq_max_tools']??6)));
            $ollamaModel=trim((string)($_POST['ollama_model']??'qwen3:4b-instruct'));
            $openaiModel=trim((string)($_POST['openai_model']??'gpt-5.6'));
            $numCtx=max(2048,min(65536,(int)($_POST['ollama_num_ctx']??8192)));
            $maxCalls=max(1,min(20,(int)($_POST['max_tool_calls']??8)));
            $maxRows=max(5,min(100,(int)($_POST['max_rows']??50)));
            $history=max(2,min(30,(int)($_POST['history_messages']??12)));
            $daily=max(1,min(10000,(int)($_POST['daily_limit']??100)));

            $activeModel=match($provider){
                'OPENAI'=>$openaiModel,
                'OLLAMA'=>$ollamaModel,
                default=>$groqModel,
            };

            foreach([
                'ai_chat_enabled'=>$enabled,
                'ai_chat_provider'=>$provider,
                'ai_chat_model'=>$activeModel,
                'ai_chat_groq_model'=>$groqModel,
                'ai_chat_groq_fallback_model'=>$groqFallbackModel,
                'ai_chat_groq_history_messages'=>(string)$groqHistory,
                'ai_chat_groq_max_tools'=>(string)$groqMaxTools,
                'ai_chat_ollama_model'=>$ollamaModel,
                'ai_chat_openai_model'=>$openaiModel,
                'ai_chat_ollama_num_ctx'=>(string)$numCtx,
                'ai_chat_max_tool_calls'=>(string)$maxCalls,
                'ai_chat_max_rows'=>(string)$maxRows,
                'ai_chat_history_messages'=>(string)$history,
                'ai_chat_daily_limit'=>(string)$daily,
            ] as $key=>$value){
                geop_v8_config_set($pdo,$key,$value,(int)$user['id']);
            }

            geop_v8_security_event($pdo,(int)$user['id'],'GEOP_IA_CONFIG','Configuração do GEOP IA atualizada. Provedor: '.$provider);
            $success='Configuração salva.';
        }
    }catch(Throwable $e){$error=$e->getMessage();}
}

$provider=geop_ai_provider($pdo);
$stats=geop_ai_admin_stats($pdo);
$recent=[];
try{
    $recent=$pdo->query("
        SELECT e.*,u.nome usuario_nome
        FROM ia_chat_execucoes e
        LEFT JOIN usuarios u ON u.id=e.usuario_id
        ORDER BY e.criado_em DESC LIMIT 30
    ")->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){}

$ollamaUrl=geop_ai_ollama_base_url();
$ollamaHost=parse_url($ollamaUrl,PHP_URL_HOST) ?: $ollamaUrl;

render_header('Configurar GEOP IA');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h1 class="h3 mb-1">GEOP IA — Administração</h1>
    <p class="text-muted mb-0">Groq Cloud, Ollama local ou OpenAI usando as mesmas ferramentas seguras do GEOP.</p>
  </div>
  <a class="btn btn-primary" href="<?=e(app_path('ia_chat'))?>">Abrir GEOP IA</a>
</div>

<?php if($success):?><div class="alert alert-success"><?=e($success)?></div><?php endif;?>
<?php if($error):?><div class="alert alert-danger"><?=e($error)?></div><?php endif;?>

<div class="row g-3 mb-3">
  <div class="col-lg-4">
    <div class="alert <?=$provider==='GROQ'?'alert-success':'alert-secondary'?> h-100 mb-0">
      <strong>Groq Cloud:</strong> <?=$provider==='GROQ'?'ATIVA':'disponível como alternativa'?><br>
      <span class="small">Chave: <?=geop_ai_groq_api_key_configured()?'configurada':'não configurada'?> · Modelo: <code><?=e(geop_ai_groq_model($pdo))?></code></span>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="alert <?=$provider==='OLLAMA'?'alert-success':'alert-secondary'?> h-100 mb-0">
      <strong>Ollama:</strong> <?=$provider==='OLLAMA'?'ATIVO':'disponível como alternativa'?><br>
      <span class="small">Servidor: <?=e($ollamaHost)?> · Modelo: <code><?=e(geop_ai_ollama_model($pdo))?></code></span>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="alert <?=$provider==='OPENAI' && geop_ai_api_key_configured()?'alert-success':'alert-secondary'?> h-100 mb-0">
      <strong>OpenAI:</strong> <?=geop_ai_api_key_configured()?'chave configurada':'chave não configurada'?><br>
      <span class="small">Modelo: <code><?=e(geop_ai_openai_model($pdo))?></code></span>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php foreach([
      ['Conversas hoje',$stats['conversas_hoje']],
      ['Perguntas hoje',$stats['perguntas_hoje']],
      ['Ferramentas hoje',$stats['tool_calls_hoje']],
      ['Erros hoje',$stats['erros_hoje']],
  ] as $card):?>
  <div class="col-6 col-lg-3"><div class="card h-100"><div class="card-body">
    <div class="text-muted small"><?=e($card[0])?></div><div class="display-6"><?=e($card[1])?></div>
  </div></div></div>
  <?php endforeach;?>
</div>

<div class="card mb-3"><div class="card-body">
  <h2 class="h5">Configuração</h2>
  <form method="post" class="row g-3">
    <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
    <input type="hidden" name="action" value="salvar">

    <div class="col-12"><div class="form-check form-switch">
      <input class="form-check-input" type="checkbox" name="enabled" id="enabled" <?=geop_ai_enabled($pdo)?'checked':''?>>
      <label class="form-check-label" for="enabled">Ativar GEOP IA Chat</label>
    </div></div>

    <div class="col-md-4">
      <label class="form-label">Provedor</label>
      <select class="form-select" name="provider">
        <option value="GROQ" <?=$provider==='GROQ'?'selected':''?>>Groq Cloud — online</option>
        <option value="OLLAMA" <?=$provider==='OLLAMA'?'selected':''?>>Ollama local</option>
        <option value="OPENAI" <?=$provider==='OPENAI'?'selected':''?>>OpenAI API</option>
      </select>
    </div>

    <div class="col-md-4">
      <label class="form-label">Modelo Groq principal</label>
      <input class="form-control" name="groq_model" value="<?=e((string)geop_v8_config($pdo,'ai_chat_groq_model','qwen/qwen3.8-27b'))?>">
      <div class="form-text">Padrão: qwen/qwen3.8-27b.</div>
    </div>

    <div class="col-md-4">
      <label class="form-label">Fallback Groq</label>
      <input class="form-control" name="groq_fallback_model" value="<?=e((string)geop_v8_config($pdo,'ai_chat_groq_fallback_model','openai/gpt-oss-20b'))?>">
      <div class="form-text">Usado automaticamente quando o principal retornar 429.</div>
    </div>

    <div class="col-md-2">
      <label class="form-label">Histórico Groq</label>
      <input class="form-control" type="number" min="0" max="10" name="groq_history_messages" value="<?=e(geop_v8_config($pdo,'ai_chat_groq_history_messages',4))?>">
    </div>

    <div class="col-md-2">
      <label class="form-label">Ferramentas/Groq</label>
      <input class="form-control" type="number" min="2" max="10" name="groq_max_tools" value="<?=e(geop_v8_config($pdo,'ai_chat_groq_max_tools',6))?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Modelo OpenAI</label>
      <input class="form-control" name="openai_model" value="<?=e((string)geop_v8_config($pdo,'ai_chat_openai_model','gpt-5.6'))?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Modelo Ollama</label>
      <input class="form-control" name="ollama_model" value="<?=e((string)geop_v8_config($pdo,'ai_chat_ollama_model','qwen3:4b-instruct'))?>">
    </div>

    <div class="col-md-4">
      <label class="form-label">Contexto Ollama</label>
      <input class="form-control" type="number" min="2048" max="65536" name="ollama_num_ctx" value="<?=e(geop_v8_config($pdo,'ai_chat_ollama_num_ctx',8192))?>">
    </div>

    <div class="col-md-2"><label class="form-label">Ferramentas/pergunta</label><input class="form-control" type="number" min="1" max="20" name="max_tool_calls" value="<?=e(geop_v8_config($pdo,'ai_chat_max_tool_calls',8))?>"></div>
    <div class="col-md-2"><label class="form-label">Linhas/ferramenta</label><input class="form-control" type="number" min="5" max="100" name="max_rows" value="<?=e(geop_v8_config($pdo,'ai_chat_max_rows',50))?>"></div>
    <div class="col-md-2"><label class="form-label">Histórico</label><input class="form-control" type="number" min="2" max="30" name="history_messages" value="<?=e(geop_v8_config($pdo,'ai_chat_history_messages',12))?>"></div>
    <div class="col-md-2"><label class="form-label">Perguntas/dia</label><input class="form-control" type="number" min="1" max="10000" name="daily_limit" value="<?=e(geop_v8_config($pdo,'ai_chat_daily_limit',100))?>"></div>

    <div class="col-12"><button class="btn btn-primary">Salvar configuração</button></div>
  </form>

  <hr>
  <div class="d-flex gap-2 flex-wrap">
    <form method="post">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="testar_groq">
      <button class="btn btn-outline-primary">Testar conexão com Groq</button>
    </form>

    <form method="post">
      <input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
      <input type="hidden" name="action" value="testar_ollama">
      <button class="btn btn-outline-success">Testar conexão com Ollama</button>
    </form>
  </div>

  <?php if($groqTest):?>
    <div class="alert alert-info mt-3 mb-0">
      Groq acessível em <strong><?=e($groqTest['latencia_ms'])?> ms</strong>.<br>
      Principal: <code><?=e($groqTest['modelo'])?></code> —
      <strong><?=!empty($groqTest['modelo_disponivel'])?'DISPONÍVEL':'NÃO LOCALIZADO'?></strong>.<br>
      Fallback: <code><?=e($groqTest['fallback_model'])?></code> —
      <strong><?=!empty($groqTest['fallback_disponivel'])?'DISPONÍVEL':'NÃO LOCALIZADO'?></strong>.
    </div>
  <?php endif;?>

  <?php if($ollamaTest):?>
    <div class="alert alert-info mt-3 mb-0">
      Ollama acessível em <strong><?=e($ollamaTest['latencia_ms'])?> ms</strong>.
      Modelo: <code><?=e($ollamaTest['modelo'])?></code> —
      <strong><?=!empty($ollamaTest['modelo_instalado'])?'INSTALADO':'NÃO LOCALIZADO'?></strong>.
    </div>
  <?php endif;?>
</div></div>

<div class="card"><div class="card-body">
  <h2 class="h5">Execuções recentes</h2>
  <div class="table-responsive"><table class="table table-sm align-middle">
    <thead><tr><th>Data</th><th>Usuário</th><th>Ferramenta</th><th>Status</th><th>Linhas</th><th>Tempo</th></tr></thead>
    <tbody>
    <?php foreach($recent as $r):?>
      <tr>
        <td><?=e($r['criado_em'])?></td><td><?=e($r['usuario_nome']??('#'.$r['usuario_id']))?></td>
        <td><code><?=e($r['ferramenta'])?></code></td>
        <td><span class="badge <?=$r['status']==='OK'?'text-bg-success':'text-bg-danger'?>"><?=e($r['status'])?></span></td>
        <td><?=e($r['linhas_retornadas'])?></td><td><?=e($r['duracao_ms'])?> ms</td>
      </tr>
    <?php endforeach;?>
    <?php if(!$recent):?><tr><td colspan="6" class="text-center text-muted">Nenhuma execução registrada.</td></tr><?php endif;?>
    </tbody>
  </table></div>
</div></div>

<div class="alert alert-info mt-3">
  <strong>Controle do Free Tier:</strong> na Groq o GEOP envia somente as ferramentas relacionadas à pergunta,
  limita o histórico e troca automaticamente para o modelo fallback quando o modelo principal atingir rate limit.
</div>
<div class="alert alert-secondary mt-3 mb-0">
  <strong>Segurança:</strong> todos os provedores usam as mesmas ferramentas somente leitura. Nenhum modelo recebe usuário/senha do MariaDB nem executa SQL livre.
</div>
<?php render_footer(); ?>
