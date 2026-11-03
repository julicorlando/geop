<?php
declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/layout.php';
require_once __DIR__.'/../includes/geop_ai.php';

require_login();
check_csrf();
geop_v8_boot($pdo);
geop_ai_schema($pdo);

$user=current_user();
if(!geop_ai_access_allowed($user)){
    http_response_code(403);
    die('Acesso negado.');
}

$conversationId=(int)($_GET['c']??0);
if($conversationId>0 && !geop_ai_conversation_owned($pdo,$conversationId,(int)$user['id'])){
    $conversationId=0;
}
$conversations=geop_ai_conversations($pdo,(int)$user['id'],30);
$messages=$conversationId>0?geop_ai_messages($pdo,$conversationId,(int)$user['id'],100):[];
$enabled=geop_ai_enabled($pdo);
$providerStatus=geop_ai_provider_ready($pdo);
$providerOk=!empty($providerStatus['ok']);
$provider=geop_ai_provider($pdo);
$limit=geop_ai_check_daily_limit($pdo,(int)$user['id']);

render_header('GEOP IA');
?>
<style>
.geop-ai-shell{display:grid;grid-template-columns:280px minmax(0,1fr);gap:16px;min-height:68vh}
.geop-ai-side{border:1px solid #dee2e6;border-radius:16px;background:#fff;padding:12px;max-height:74vh;overflow:auto}
.geop-ai-main{border:1px solid #dee2e6;border-radius:16px;background:#fff;display:flex;flex-direction:column;min-height:68vh}
.geop-ai-messages{flex:1;overflow:auto;padding:18px;max-height:62vh}
.geop-ai-msg{max-width:88%;padding:12px 14px;border-radius:14px;margin-bottom:12px;white-space:pre-wrap;line-height:1.45}
.geop-ai-user{margin-left:auto;background:#0d6efd;color:#fff}
.geop-ai-assistant{margin-right:auto;background:#f4f6f8;color:#212529}
.geop-ai-sources{margin-top:8px;display:flex;gap:6px;flex-wrap:wrap}
.geop-ai-source{font-size:.76rem;border:1px solid #ced4da;border-radius:999px;padding:3px 8px;background:#fff;color:#495057}
.geop-ai-input{border-top:1px solid #dee2e6;padding:12px}
.geop-ai-conv{display:block;text-decoration:none;color:#212529;padding:9px;border-radius:10px;margin-bottom:4px}
.geop-ai-conv:hover,.geop-ai-conv.active{background:#eef4ff}
.geop-ai-status{font-size:.8rem}
@media(max-width:900px){.geop-ai-shell{grid-template-columns:1fr}.geop-ai-side{max-height:220px}}
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">🤖 GEOP IA</h1>
        <p class="text-muted mb-0">Copiloto de consulta operacional. Somente leitura.</p>
    </div>
    <?php if(($user['perfil']??'')==='TI'):?>
        <a class="btn btn-outline-secondary" href="<?=e(app_path('ia_config'))?>">Configurar IA</a>
    <?php endif;?>
</div>

<?php if(!$enabled):?>
<div class="alert alert-warning">O GEOP IA está instalado, mas desativado. O TI precisa habilitá-lo em <strong>Configurar IA</strong>.</div>
<?php elseif(!$providerOk):?>
<div class="alert alert-warning"><?=e($providerStatus['mensagem']??'Provedor de IA não configurado.')?></div>
<?php endif;?>
<div class="small text-muted mb-2">
  Provedor ativo: <strong><?=e($provider)?></strong> · Modelo: <code><?=e(geop_ai_model($pdo))?></code>
  <?php if($provider==='GROQ'):?> · Fallback: <code><?=e(geop_ai_groq_fallback_model($pdo))?></code><?php endif;?>
</div>

<div class="geop-ai-shell">
    <aside class="geop-ai-side">
        <button class="btn btn-primary w-100 mb-3" id="btnNovaConversa">+ Nova conversa</button>
        <?php foreach($conversations as $conv):?>
            <a class="geop-ai-conv <?=$conversationId===(int)$conv['id']?'active':''?>" href="<?=e(app_path('ia_chat',['c'=>$conv['id']]))?>">
                <div class="fw-semibold text-truncate"><?=e($conv['titulo'])?></div>
                <div class="small text-muted"><?=e($conv['atualizado_em'])?> · <?=e($conv['total_mensagens'])?> msg</div>
            </a>
        <?php endforeach;?>
        <?php if(!$conversations):?><div class="text-muted small">Nenhuma conversa ainda.</div><?php endif;?>
    </aside>

    <section class="geop-ai-main">
        <div class="geop-ai-messages" id="aiMessages">
            <?php if(!$messages):?>
                <div class="text-center text-muted py-5" id="aiEmpty">
                    <div class="display-6 mb-2">🤖</div>
                    <strong>Como posso ajudar no GEOP?</strong>
                    <div class="small mt-2">Ex.: “Quais OS estão vencidas?”, “Quanto gastamos este mês?” ou “Resuma a OS-2026-0187”.</div>
                </div>
            <?php endif;?>
            <?php foreach($messages as $m):?>
                <div class="geop-ai-msg <?=$m['papel']==='USER'?'geop-ai-user':'geop-ai-assistant'?>">
                    <?=nl2br(e($m['conteudo']))?>
                    <?php if($m['papel']==='ASSISTANT' && !empty($m['fontes'])):?>
                        <div class="geop-ai-sources">
                            <?php foreach($m['fontes'] as $src):?>
                                <span class="geop-ai-source" title="<?=e($src['detalhe']??'')?>"><?=e($src['rotulo']??$src['nome']??'Fonte GEOP')?></span>
                            <?php endforeach;?>
                        </div>
                    <?php endif;?>
                </div>
            <?php endforeach;?>
        </div>

        <div class="geop-ai-input">
            <div class="d-flex justify-content-between mb-1 geop-ai-status text-muted">
                <span id="aiStatus"><?= $enabled && $providerOk ? 'Pronto para consultar o GEOP.' : 'Configuração pendente.' ?></span>
                <span>Hoje: <?=e($limit['used'])?>/<?=e($limit['limit'])?></span>
            </div>
            <form id="aiForm" class="d-flex gap-2">
                <input type="hidden" id="conversationId" value="<?=$conversationId?>">
                <textarea id="aiQuestion" class="form-control" rows="2" maxlength="4000" placeholder="Pergunte sobre OS, custos, estoque, patrimônio, preventivas..." <?=(!$enabled||!$providerOk||!$limit['ok'])?'disabled':''?>></textarea>
                <button class="btn btn-primary px-4" id="aiSend" <?=(!$enabled||!$providerOk||!$limit['ok'])?'disabled':''?>>Enviar</button>
            </form>
        </div>
    </section>
</div>

<script>
const csrf = <?=json_encode(csrf_token())?>;
const apiUrl = <?=json_encode(app_path('ia_chat_api'))?>;
const messagesEl = document.getElementById('aiMessages');
const statusEl = document.getElementById('aiStatus');
const questionEl = document.getElementById('aiQuestion');
const sendBtn = document.getElementById('aiSend');
const convInput = document.getElementById('conversationId');

function escapeHtml(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
function appendMessage(role,text,sources=[]){
    document.getElementById('aiEmpty')?.remove();
    const div=document.createElement('div');
    div.className='geop-ai-msg '+(role==='USER'?'geop-ai-user':'geop-ai-assistant');
    div.innerHTML=escapeHtml(text).replace(/\n/g,'<br>');
    if(role==='ASSISTANT' && Array.isArray(sources) && sources.length){
        const src=document.createElement('div');src.className='geop-ai-sources';
        sources.forEach(s=>{
            const span=document.createElement('span');span.className='geop-ai-source';
            span.textContent=s.rotulo||s.nome||'Fonte GEOP';
            if(s.detalhe) span.title=s.detalhe;
            src.appendChild(span);
        });
        div.appendChild(src);
    }
    messagesEl.appendChild(div);
    messagesEl.scrollTop=messagesEl.scrollHeight;
}
async function callApi(action,payload={}){
    const res=await fetch(apiUrl,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},
        body:JSON.stringify({action,csrf,...payload})
    });
    const data=await res.json().catch(()=>({ok:false,erro:'Resposta inválida do servidor.'}));
    if(!res.ok || !data.ok) throw new Error(data.erro||'Falha no GEOP IA.');
    return data;
}
document.getElementById('btnNovaConversa').addEventListener('click',async()=>{
    try{
        const data=await callApi('nova_conversa');
        window.location.href=<?=json_encode(app_path('ia_chat'))?>+'?c='+encodeURIComponent(data.conversa_id);
    }catch(e){alert(e.message);}
});
document.getElementById('aiForm').addEventListener('submit',async(ev)=>{
    ev.preventDefault();
    const q=questionEl.value.trim();if(!q)return;
    let cid=Number(convInput.value||0);
    questionEl.value='';questionEl.disabled=true;sendBtn.disabled=true;
    appendMessage('USER',q);
    statusEl.textContent='Consultando dados do GEOP…';
    try{
        const data=await callApi('perguntar',{conversa_id:cid,pergunta:q});
        if(!cid && data.conversa_id){cid=data.conversa_id;convInput.value=cid;history.replaceState(null,'',<?=json_encode(app_path('ia_chat'))?>+'?c='+cid);}
        appendMessage('ASSISTANT',data.resposta,data.fontes||[]);
        statusEl.textContent=(data.fallback_usado?'Resposta via fallback Groq · ':'Resposta gerada com dados do GEOP · ')+(data.latencia_ms||0)+' ms';
    }catch(e){
        appendMessage('ASSISTANT','Erro: '+e.message,[]);
        statusEl.textContent='Falha ao consultar.';
    }finally{
        questionEl.disabled=false;sendBtn.disabled=false;questionEl.focus();
    }
});
messagesEl.scrollTop=messagesEl.scrollHeight;
</script>
<?php render_footer(); ?>
