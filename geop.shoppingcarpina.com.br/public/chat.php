<?php
declare(strict_types=1);
$user = function_exists('current_user') ? current_user() : null;
if (!$user || empty($user['id'])) return;
$meId = (int)$user['id'];
$apiUrl = app_path('chat_api.php');
?>
<style>
.geop-msg-launcher{position:fixed;right:22px;bottom:20px;touch-action:none;cursor:grab;user-select:none;z-index:99998;width:58px;height:58px;border:0;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#087cf0,#0756c9);color:#fff;box-shadow:0 12px 34px rgba(8,85,201,.38);transition:.2s transform}.geop-msg-launcher:hover{transform:translateY(-2px)}.geop-msg-launcher svg{width:28px;height:28px}.geop-msg-badge{display:none;position:absolute;right:-3px;top:-4px;min-width:22px;height:22px;border-radius:999px;padding:0 6px;background:#e11d48;color:#fff;border:2px solid #fff;font:700 11px/18px system-ui}
@keyframes geopMsgNotify{0%,100%{transform:scale(1)}35%{transform:scale(1.14)}70%{transform:scale(.96)}}.geop-msg-launcher.notify{animation:geopMsgNotify .65s ease 3}
.geop-msg-panel{position:fixed;right:20px;bottom:88px;z-index:99999;width:min(760px,calc(100vw - 30px));height:min(640px,calc(100vh - 110px));display:none;background:#fff;border:1px solid #dfe3e8;border-radius:18px;box-shadow:0 22px 70px rgba(15,23,42,.28);overflow:hidden}.geop-msg-panel.open{display:grid;grid-template-columns:260px minmax(0,1fr)}
.geop-msg-sidebar{min-width:0;display:flex;flex-direction:column;border-right:1px solid #e6e9ed;background:#fff}.geop-msg-sidebar-head{padding:16px 14px 9px;display:flex;align-items:center;justify-content:space-between}.geop-msg-sidebar-head h2{font-size:24px;font-weight:800;margin:0;color:#111827}.geop-msg-icon-btn{width:34px;height:34px;border:0;border-radius:50%;background:#eef1f5;color:#253858;display:grid;place-items:center;font-weight:900}.geop-msg-search{margin:0 12px 10px;position:relative}.geop-msg-search input{border:0;background:#f0f2f5;border-radius:999px;padding:9px 13px 9px 36px;font-size:13px}.geop-msg-search span{position:absolute;left:13px;top:8px;color:#667085}.geop-msg-users{overflow:auto;padding:0 8px 12px}.geop-msg-user{width:100%;border:0;background:transparent;display:grid;grid-template-columns:46px minmax(0,1fr) auto;gap:10px;align-items:center;text-align:left;padding:8px;border-radius:12px;color:#111827;cursor:pointer;touch-action:manipulation;position:relative;z-index:1}.geop-msg-user:hover,.geop-msg-user.active{background:#eef5ff}.geop-msg-avatar{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#0b2341,#1685df);color:#fff;font-weight:800;letter-spacing:.4px;flex:0 0 auto}.geop-msg-user-name{font-size:13px;font-weight:750;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.geop-msg-user-meta{font-size:11px;color:#667085;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.geop-msg-unread{min-width:20px;height:20px;border-radius:999px;background:#087cf0;color:#fff;text-align:center;font:700 11px/20px system-ui;padding:0 5px}.geop-msg-loading{padding:20px;text-align:center;color:#667085;font-size:13px}
.geop-msg-chat{min-width:0;min-height:0;height:100%;overflow:hidden;display:flex;flex-direction:column;background:#fff}.geop-msg-chat-head{height:68px;flex:0 0 68px;padding:9px 13px;border-bottom:1px solid #e6e9ed;display:flex;align-items:center;gap:10px;box-shadow:0 2px 8px rgba(15,23,42,.04);z-index:1;cursor:move}.geop-msg-chat-head .geop-msg-avatar{width:42px;height:42px}.geop-msg-chat-person{min-width:0;flex:1}.geop-msg-chat-name{font-size:15px;font-weight:800;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.geop-msg-chat-role{font-size:11px;color:#667085}.geop-msg-back{display:none}.geop-msg-messages{flex:1 1 auto;min-height:0;overflow-y:auto;padding:15px 17px;background:linear-gradient(#fff,#fbfcfe)}.geop-msg-empty{height:100%;display:grid;place-items:center;text-align:center;color:#667085;padding:35px}.geop-msg-empty .geop-msg-avatar{width:72px;height:72px;font-size:22px;margin:auto auto 10px}.geop-msg-older{display:none;margin:0 auto 12px;border:0;border-radius:999px;background:#eef5ff;color:#0756c9;padding:6px 13px;font-size:12px;font-weight:700}.geop-msg-row{display:flex;align-items:flex-end;gap:7px;margin:4px 0}.geop-msg-row.mine{justify-content:flex-end}.geop-msg-row .geop-msg-avatar{width:28px;height:28px;font-size:10px}.geop-msg-bubble{max-width:min(76%,520px);padding:9px 12px;border-radius:18px;background:#e9edf2;color:#111827;font-size:13px;line-height:1.35;word-break:break-word}.geop-msg-row.mine .geop-msg-bubble{background:#087cf0;color:#fff}.geop-msg-time{display:block;margin-top:4px;font-size:9px;opacity:.72;text-align:right}.geop-msg-compose{flex:0 0 auto;padding:10px 12px;border-top:1px solid #e6e9ed;display:flex;align-items:flex-end;gap:8px;background:#fff}.geop-msg-compose textarea{min-height:42px;max-height:110px;resize:none;border:0;background:#f0f2f5;border-radius:20px;padding:11px 14px;font-size:13px}.geop-msg-send{width:42px;height:42px;flex:0 0 auto;border:0;border-radius:50%;background:#087cf0;color:#fff;display:grid;place-items:center}.geop-msg-send:disabled{background:#b8c7da}.geop-msg-send svg{width:19px;height:19px}
@media(max-width:700px){.geop-msg-launcher{right:14px;bottom:14px}.geop-msg-panel{inset:0;width:100%;height:100%;max-width:none;max-height:none;border:0;border-radius:0}.geop-msg-panel.open{grid-template-columns:1fr}.geop-msg-chat{display:none}.geop-msg-panel.show-chat .geop-msg-sidebar{display:none}.geop-msg-panel.show-chat .geop-msg-chat{display:flex}.geop-msg-back{display:grid}.geop-msg-chat-head{cursor:default}.geop-msg-bubble{max-width:84%}}
/* Exibe um único estágio por vez: lista de contatos ou conversa selecionada. */
.geop-msg-panel.open{display:block;width:min(360px,calc(100vw - 30px))}
.geop-msg-panel.open.show-chat{width:min(560px,calc(100vw - 30px))}
.geop-msg-panel .geop-msg-chat{display:none}
.geop-msg-panel.show-chat .geop-msg-sidebar{display:none}
.geop-msg-panel.show-chat .geop-msg-chat{display:flex}
.geop-msg-panel .geop-msg-back{display:grid}
.geop-msg-head-actions{display:flex;gap:5px}.geop-msg-section-title{padding:10px 9px 4px;color:#667085;font-size:11px;font-weight:800;text-transform:uppercase}.geop-msg-group-avatar{background:linear-gradient(145deg,#7c3aed,#4f46e5)}.geop-msg-modal{display:none;position:fixed;inset:0;z-index:100000;background:rgba(15,23,42,.48);place-items:center;padding:16px}.geop-msg-modal.open{display:grid}.geop-msg-modal-card{width:min(430px,100%);max-height:min(620px,90vh);overflow:auto;background:#fff;border-radius:16px;padding:18px;box-shadow:0 20px 60px rgba(15,23,42,.3)}.geop-msg-modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}.geop-msg-modal-head h3{margin:0}.geop-msg-members{display:grid;gap:4px;max-height:300px;overflow:auto;margin:10px 0}.geop-msg-member{display:flex;gap:9px;align-items:center;padding:7px;border-radius:9px}.geop-msg-member:hover{background:#f5f7fa}.geop-msg-modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:12px}
.geop-msg-compose{position:relative;flex-wrap:wrap}.geop-msg-compose-tools{display:flex;gap:4px}.geop-msg-tool{width:38px;height:38px;border:0;border-radius:50%;background:#f0f2f5;color:#344054;display:grid;place-items:center;font-size:19px;cursor:pointer}.geop-msg-compose textarea{flex:1;min-width:120px}.geop-msg-emoji-picker{display:none;position:absolute;left:10px;bottom:62px;width:240px;padding:9px;border:1px solid #dfe3e8;border-radius:14px;background:#fff;box-shadow:0 12px 35px rgba(15,23,42,.2);grid-template-columns:repeat(8,1fr);gap:3px;z-index:4}.geop-msg-emoji-picker.open{display:grid}.geop-msg-emoji-picker button{border:0;background:transparent;border-radius:7px;padding:5px;font-size:18px}.geop-msg-emoji-picker button:hover{background:#eef5ff}.geop-msg-file-preview{display:none;flex:1 0 100%;align-items:center;gap:8px;padding:7px 10px;border-radius:10px;background:#eef5ff;color:#344054;font-size:12px}.geop-msg-file-preview.show{display:flex}.geop-msg-file-preview span{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.geop-msg-file-preview button{border:0;background:transparent;color:#b42318;font-weight:800}.geop-msg-attachment{display:block;margin-top:7px;color:inherit;text-decoration:none}.geop-msg-attachment img{display:block;max-width:240px;max-height:220px;border-radius:12px;object-fit:cover}.geop-msg-file-card{display:flex;align-items:center;gap:8px;padding:8px;border-radius:10px;background:rgba(255,255,255,.55);font-weight:700}.geop-msg-actions{display:flex;justify-content:flex-end;gap:3px;margin-top:4px}.geop-msg-action{border:0;background:transparent;color:inherit;opacity:.7;padding:1px 4px;font-size:10px}.geop-msg-deleted{font-style:italic;opacity:.72}.geop-msg-edited{font-size:9px;opacity:.7}.geop-msg-live{width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block;margin-right:4px}
.geop-msg-enter-hint{flex:1 0 100%;font-size:10px;color:#667085;padding:0 8px}.geop-msg-enter-progress{height:3px;width:0;background:#7c3aed;border-radius:999px;transition:width 5s linear}.geop-msg-enter-progress.holding{width:100%}.geop-msg-once{border:0;border-radius:10px;padding:8px 10px;background:rgba(255,255,255,.55);color:inherit;font-weight:700}.geop-msg-once-viewed{font-style:italic;opacity:.75}
.geop-msg-messages,.geop-msg-messages *{-webkit-user-select:none;user-select:none;-webkit-touch-callout:none}.geop-msg-quote{display:block;border-left:3px solid currentColor;padding:5px 8px;margin-bottom:6px;border-radius:5px;background:rgba(255,255,255,.3);font-size:11px;opacity:.82}.geop-msg-forwarded{display:block;font-size:10px;font-style:italic;opacity:.72;margin-bottom:3px}.geop-msg-reply-preview{display:none;flex:1 0 100%;align-items:center;gap:8px;padding:7px 10px;border-left:3px solid #087cf0;border-radius:8px;background:#eef5ff;font-size:11px}.geop-msg-reply-preview.show{display:flex}.geop-msg-reply-preview span{flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.geop-msg-reply-preview button{border:0;background:transparent;font-weight:900}.geop-msg-forward-targets{display:grid;gap:5px;max-height:360px;overflow:auto}.geop-msg-forward-target{border:0;background:#f8fafc;border-radius:10px;padding:9px;display:flex;align-items:center;gap:9px;text-align:left}.geop-msg-forward-target:hover{background:#eef5ff}
@media(max-width:700px){.geop-msg-panel.open,.geop-msg-panel.open.show-chat{width:100%}}
</style>

<button type="button" class="geop-msg-launcher geop-floating-draggable" id="geopMsgLauncher" data-geop-draggable="chat" aria-label="Abrir mensagens" title="CHAT · arraste para mover">
 <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.15 2 11.27c0 2.9 1.43 5.49 3.67 7.19V22l3.35-1.84c.95.26 1.95.39 2.98.39 5.52 0 10-4.15 10-9.28S17.52 2 12 2Zm1 12-2.55-2.72L5.47 14l5.48-5.82L13.5 10.9l4.98-2.72L13 14Z"/></svg>
 <span class="geop-msg-badge" id="geopMsgBadge">0</span>
</button>

<section class="geop-msg-panel" id="geopMsgPanel" aria-label="Mensagens internas">
 <aside class="geop-msg-sidebar">
  <div class="geop-msg-sidebar-head"><h2>Conversas</h2><div class="geop-msg-head-actions"><button type="button" class="geop-msg-icon-btn" id="geopMsgNewGroup" aria-label="Criar grupo">＋</button><button type="button" class="geop-msg-icon-btn" id="geopMsgClose" aria-label="Fechar">×</button></div></div>
  <div class="geop-msg-search"><span>⌕</span><input class="form-control" id="geopMsgSearch" placeholder="Pesquisar pessoas"></div>
  <div class="geop-msg-users" id="geopMsgUsers"><div class="geop-msg-loading">Carregando contatos…</div></div>
 </aside>
 <div class="geop-msg-chat">
  <header class="geop-msg-chat-head" id="geopMsgDrag">
   <button type="button" class="geop-msg-icon-btn geop-msg-back" id="geopMsgBack" aria-label="Voltar">‹</button>
   <div class="geop-msg-avatar" id="geopMsgCurrentAvatar">?</div>
   <div class="geop-msg-chat-person"><div class="geop-msg-chat-name" id="geopMsgCurrentName">Selecione uma pessoa</div><div class="geop-msg-chat-role" id="geopMsgCurrentRole">Chat interno GEOP</div></div>
   <button type="button" class="geop-msg-icon-btn" id="geopMsgRefresh" aria-label="Atualizar">↻</button>
  </header>
  <div class="geop-msg-messages" id="geopMsgMessages"><div class="geop-msg-empty">Selecione uma pessoa para começar uma conversa.</div></div>
  <form class="geop-msg-compose" id="geopMsgForm">
   <div class="geop-msg-reply-preview" id="geopMsgReplyPreview"><span></span><button type="button" id="geopMsgReplyCancel" aria-label="Cancelar resposta">×</button></div>
   <div class="geop-msg-file-preview" id="geopMsgFilePreview"><span></span><button type="button" id="geopMsgFileRemove" aria-label="Remover anexo">×</button></div>
   <div class="geop-msg-compose-tools">
    <button type="button" class="geop-msg-tool" id="geopMsgEmoji" aria-label="Escolher emoji">☺</button>
    <button type="button" class="geop-msg-tool" id="geopMsgAttach" aria-label="Anexar imagem ou PDF">📎</button>
    <input type="file" id="geopMsgFile" accept="image/jpeg,image/png,image/webp,application/pdf" hidden>
   </div>
   <div class="geop-msg-emoji-picker" id="geopMsgEmojiPicker"></div>
   <textarea class="form-control" id="geopMsgText" maxlength="2000" placeholder="Aa" disabled></textarea>
   <button class="geop-msg-send" id="geopMsgSend" disabled aria-label="Enviar"><svg viewBox="0 0 24 24" fill="currentColor"><path d="m3.4 20.4 17.45-7.48c.8-.35.8-1.48 0-1.83L3.4 3.6c-.65-.28-1.35.2-1.29.9l.58 6.1 10.96 1.4-10.96 1.4-.58 6.1c-.06.7.64 1.18 1.29.9Z"/></svg></button>
   <div class="geop-msg-enter-hint">Enter: enviar normalmente · segure Enter por 5 segundos: visualização única<div class="geop-msg-enter-progress" id="geopMsgEnterProgress"></div></div>
  </form>
 </div>
</section>
<div class="geop-msg-modal" id="geopMsgGroupModal" role="dialog" aria-modal="true" aria-label="Criar grupo">
 <div class="geop-msg-modal-card">
  <div class="geop-msg-modal-head"><h3>Novo grupo</h3><button type="button" class="geop-msg-icon-btn" id="geopMsgGroupClose">×</button></div>
  <label for="geopMsgGroupName">Nome do grupo</label>
  <input class="form-control" id="geopMsgGroupName" maxlength="120" placeholder="Ex.: Equipe de manutenção">
  <div class="geop-msg-section-title">Participantes</div>
  <div class="geop-msg-members" id="geopMsgGroupMembers"></div>
  <div class="geop-msg-modal-actions"><button type="button" class="btn btn-secondary" id="geopMsgGroupCancel">Cancelar</button><button type="button" class="btn btn-primary" id="geopMsgGroupCreate">Criar grupo</button></div>
 </div>
</div>
<div class="geop-msg-modal" id="geopMsgForwardModal" role="dialog" aria-modal="true" aria-label="Encaminhar mensagem">
 <div class="geop-msg-modal-card">
  <div class="geop-msg-modal-head"><h3>Encaminhar para</h3><button type="button" class="geop-msg-icon-btn" id="geopMsgForwardClose">×</button></div>
  <div class="geop-msg-forward-targets" id="geopMsgForwardTargets"></div>
 </div>
</div>

<script>
(()=>{
 const me=<?=json_encode($meId)?>, apiUrl=<?=json_encode($apiUrl)?>, csrf=<?=json_encode(csrf_token())?>;
 const $=id=>document.getElementById(id), launcher=$('geopMsgLauncher'), panel=$('geopMsgPanel'), badge=$('geopMsgBadge'), usersBox=$('geopMsgUsers'), search=$('geopMsgSearch'), messages=$('geopMsgMessages'), form=$('geopMsgForm'), text=$('geopMsgText'), send=$('geopMsgSend'), currentName=$('geopMsgCurrentName'), currentRole=$('geopMsgCurrentRole'), currentAvatar=$('geopMsgCurrentAvatar'), fileInput=$('geopMsgFile'), filePreview=$('geopMsgFilePreview'), emojiPicker=$('geopMsgEmojiPicker'), groupModal=$('geopMsgGroupModal'), groupMembers=$('geopMsgGroupMembers'), enterProgress=$('geopMsgEnterProgress'), replyPreview=$('geopMsgReplyPreview'), forwardModal=$('geopMsgForwardModal'), forwardTargets=$('geopMsgForwardTargets');
 let users=[],groups=[],selected=0,selectedType='',lastHash='',oldestId=0,poll=null,disabled=false,notificationReady=false,notifiedIds=new Set(),enterTimer=null,enterLongSent=false,viewOncePauseUntil=0,replyToId=0,forwardMessageId=0,messageCache=new Map();
 const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
 const initials=s=>String(s||'?').trim().split(/\s+/).slice(0,2).map(v=>v[0]||'').join('').toUpperCase();
 const formatBytes=n=>{n=Number(n||0);return n<1024?n+' B':n<1048576?(n/1024).toFixed(1)+' KB':(n/1048576).toFixed(1)+' MB'};
 function disableWidget(){if(disabled)return;disabled=true;if(poll)clearInterval(poll);groupModal.remove();forwardModal.remove();panel.remove();launcher.remove()}
 async function parse(response){const type=response.headers.get('content-type')||'';if(!type.includes('application/json'))throw new Error('Resposta inválida do servidor.');const data=await response.json();if(data.disabled){disableWidget();return data}if(response.status===401)throw new Error('Sessão expirada. Atualize a página.');return data}
 const api=params=>fetch(apiUrl+'?'+new URLSearchParams(params),{credentials:'same-origin',headers:{Accept:'application/json'}}).then(parse);
 function setBadge(n){n=Number(n||0);badge.textContent=n>99?'99+':n;badge.style.display=n?'block':'none'}
 function renderUsers(){
  const q=(search.value||'').toLocaleLowerCase('pt-BR');
  const list=users.filter(u=>(u.nome+' '+u.email+' '+u.perfil_label).toLocaleLowerCase('pt-BR').includes(q));
  const groupList=groups.filter(g=>String(g.nome||'').toLocaleLowerCase('pt-BR').includes(q));
  const groupHtml=groupList.length?'<div class="geop-msg-section-title">Grupos</div>'+groupList.map(g=>`<button type="button" class="geop-msg-user ${selectedType==='group'&&g.id===selected?'active':''}" data-group-id="${g.id}"><span class="geop-msg-avatar geop-msg-group-avatar">👥</span><span><div class="geop-msg-user-name">${esc(g.nome)}</div><div class="geop-msg-user-meta">${g.participantes} participantes</div></span>${g.nao_lidas?`<span class="geop-msg-unread">${g.nao_lidas}</span>`:''}</button>`).join(''):'';
  const userHtml=list.length?'<div class="geop-msg-section-title">Pessoas</div>'+list.map(u=>`<button type="button" class="geop-msg-user ${selectedType==='user'&&u.id===selected?'active':''}" data-id="${u.id}"><span class="geop-msg-avatar">${esc(initials(u.nome))}</span><span><div class="geop-msg-user-name">${esc(u.nome)}</div><div class="geop-msg-user-meta">${esc(u.perfil_label||u.perfil||'Usuário')}</div></span>${u.nao_lidas?`<span class="geop-msg-unread">${u.nao_lidas}</span>`:''}</button>`).join(''):'';
  usersBox.innerHTML=groupHtml+userHtml||'<div class="geop-msg-loading">Nenhuma conversa encontrada.</div>';
  usersBox.querySelectorAll('.geop-msg-user').forEach(button=>{
   button.addEventListener('click',event=>{
    event.preventDefault();
    event.stopPropagation();
    selectUser(Number(button.dataset.id),true);
   });
  });
  usersBox.querySelectorAll('[data-group-id]').forEach(button=>button.addEventListener('click',event=>{event.preventDefault();event.stopPropagation();selectGroup(Number(button.dataset.groupId))}));
 }
 function handleNotifications(items){items=items||[];if(!notificationReady){items.forEach(n=>notifiedIds.add(String(n.id)));notificationReady=true;return}items.slice().reverse().forEach(n=>{const id=String(n.id);if(notifiedIds.has(id))return;notifiedIds.add(id);if(panel.classList.contains('open'))return;launcher.classList.remove('notify');void launcher.offsetWidth;launcher.classList.add('notify');document.title='● Nova mensagem | '+document.title.replace(/^● Nova mensagem \| /,'');if('Notification'in window&&Notification.permission==='granted'){const title=n.tipo==='GRUPO'?(n.grupo_nome||'Nova mensagem no grupo'):(n.remetente_nome||'Nova mensagem');const body=(n.remetente_nome?n.remetente_nome+': ':'')+(n.mensagem||n.anexo_nome||'Enviou um anexo');const notice=new Notification(title,{body,tag:'geop-chat-'+id});notice.onclick=()=>{window.focus();panel.classList.add('open')}}})}
 async function loadUsers(){try{const d=await api({action:'users'});if(!d||d.disabled)return;if(!d.ok)throw new Error(d.error);users=(d.users||[]).map(u=>({...u,id:Number(u.id)}));groups=(d.groups||[]).map(g=>({...g,id:Number(g.id)}));handleNotifications(d.notifications);setBadge(d.total_unread);if(selected&&selectedType==='user'&&!users.some(u=>u.id===selected))showUserList();if(selected&&selectedType==='group'&&!groups.some(g=>g.id===selected))showUserList();renderUsers()}catch(e){usersBox.innerHTML=`<div class="geop-msg-loading">${esc(e.message||'Não foi possível carregar os contatos.')}</div>`}}
 function showUserList(){selected=0;selectedType='';lastHash='';oldestId=0;viewOncePauseUntil=0;clearReply();panel.classList.remove('show-chat');currentName.textContent='Selecione uma conversa';currentRole.textContent='Chat interno GEOP';currentAvatar.textContent='?';text.value='';text.disabled=true;send.disabled=true;text.placeholder='Aa';fileInput.value='';filePreview.classList.remove('show');emojiPicker.classList.remove('open');messages.innerHTML='<div class="geop-msg-empty">Selecione uma pessoa ou grupo para começar.</div>';renderUsers()}
 function selectUser(id,openConversation=true){selected=id;selectedType='user';const u=users.find(x=>x.id===id);if(!u)return;currentName.textContent=u.nome||'Usuário';currentRole.textContent=u.perfil_label||u.perfil||'GEOP';currentAvatar.textContent=initials(u.nome);currentAvatar.classList.remove('geop-msg-group-avatar');text.disabled=false;send.disabled=false;text.placeholder='Mensagem para '+u.nome;if(openConversation)panel.classList.add('show-chat');renderUsers();loadMessages(true)}
 function selectGroup(id){selected=id;selectedType='group';const g=groups.find(x=>x.id===id);if(!g)return;currentName.textContent=g.nome||'Grupo';currentRole.textContent=g.participantes+' participantes';currentAvatar.textContent='👥';currentAvatar.classList.add('geop-msg-group-avatar');text.disabled=false;send.disabled=false;text.placeholder='Mensagem no grupo '+g.nome;panel.classList.add('show-chat');renderUsers();loadMessages(true)}
 function renderMessages(list,prepend=false){
  if(!list.length&&!prepend){messages.innerHTML=`<div class="geop-msg-empty"><div><span class="geop-msg-avatar">${esc(initials(currentName.textContent))}</span><strong>${esc(currentName.textContent)}</strong><div>Vocês ainda não trocaram mensagens.</div></div></div>`;return}
  if(!prepend)messageCache=new Map();
  list.forEach(m=>messageCache.set(Number(m.id),m));
  const html=list.map(m=>{
   const mine=Number(m.remetente_id)===me,deleted=!!m.excluida_em;
   let body;
   if(deleted)body='<span class="geop-msg-deleted">Mensagem apagada</span>';
   else if(m.visualizacao_unica){
    if(mine)body='<span class="geop-msg-once-viewed">◉ Mensagem de visualização única enviada</span>';
    else if(m.visualizada_por_mim)body='<span class="geop-msg-once-viewed">◉ Mensagem já visualizada</span>';
    else body=`<button type="button" class="geop-msg-once" data-view-once="${m.id}">◉ Clique para visualizar uma vez</button>`;
   }else body=esc(m.mensagem||'').replace(/\n/g,'<br>');
   if(!deleted&&m.anexo_url){
    const safeUrl=esc(m.anexo_url),safeName=esc(m.anexo_nome||'Anexo');
    body+=String(m.anexo_mime||'').startsWith('image/')
     ?`<a class="geop-msg-attachment" href="${safeUrl}" target="_blank" rel="noopener"><img src="${safeUrl}" alt="${safeName}" loading="lazy"></a>`
     :`<a class="geop-msg-attachment geop-msg-file-card" href="${safeUrl}" target="_blank" rel="noopener">📄 <span>${safeName}<small> · ${formatBytes(m.anexo_tamanho)}</small></span></a>`;
   }
   const quote=m.resposta_a_id?`<span class="geop-msg-quote"><strong>${esc(m.resposta_autor||'Mensagem')}</strong><br>${esc(m.resposta_texto||'Mensagem indisponível')}</span>`:'';
   const forwarded=m.encaminhada_de_id?'<span class="geop-msg-forwarded">↪ Encaminhada</span>':'';
   const commonActions=!deleted&&!m.visualizacao_unica?`<button type="button" class="geop-msg-action" data-reply="${m.id}">Responder</button>${m.mensagem?`<button type="button" class="geop-msg-action" data-forward="${m.id}">Encaminhar</button>`:''}`:'';
   const ownActions=mine&&!deleted&&m.pode_alterar?`${m.mensagem&&!m.visualizacao_unica?`<button type="button" class="geop-msg-action" data-edit="${m.id}">Editar</button>`:''}<button type="button" class="geop-msg-action" data-delete="${m.id}">Apagar</button>`:'';
   const actions=commonActions||ownActions?`<span class="geop-msg-actions">${commonActions}${ownActions}</span>`:'';
   const senderName=selectedType==='group'&&!mine?`<small>${esc(m.remetente_nome||'Usuário')}</small><br>`:'';
   return `<div class="geop-msg-row ${mine?'mine':''}" data-message-id="${m.id}">${mine?'':`<span class="geop-msg-avatar">${esc(initials(m.remetente_nome||currentName.textContent))}</span>`}<div class="geop-msg-bubble">${senderName}${forwarded}${quote}${body}${m.editada_em&&!deleted?'<span class="geop-msg-edited"> · editada</span>':''}<span class="geop-msg-time">${esc(m.criado_em||'')}${mine&&m.lida_em?' · lida':''}</span>${actions}</div></div>`;
  }).join('');
  if(prepend){const oldHeight=messages.scrollHeight;messages.insertAdjacentHTML('afterbegin',html);messages.scrollTop=messages.scrollHeight-oldHeight}
  else{const wasNearBottom=messages.scrollHeight-messages.scrollTop-messages.clientHeight<90;messages.innerHTML=`<button class="geop-msg-older" id="geopMsgOlder" type="button">Ver mensagens anteriores</button>`+html;const older=$('geopMsgOlder');if(older){older.style.display=list.length>=200?'block':'none';older.onclick=loadOlder}if(wasNearBottom||!lastHash)messages.scrollTop=messages.scrollHeight}
 }
 async function loadMessages(force=false){if(!selected||Date.now()<viewOncePauseUntil)return;try{const params={action:'messages'};params[selectedType==='group'?'conversation_id':'user_id']=selected;const d=await api(params);if(!d?.ok)return;const list=d.messages||[],hash=list.map(m=>[m.id,m.lida_em,m.editada_em,m.excluida_em,m.mensagem,m.anexo_url,m.pode_alterar,m.visualizacao_unica,m.visualizada_por_mim].join(':')).join('|');if(!force&&hash===lastHash)return;lastHash=hash;oldestId=list.length?Number(list[0].id):0;renderMessages(list);if(d.users)users=d.users.map(u=>({...u,id:Number(u.id)}));if(d.groups)groups=d.groups.map(g=>({...g,id:Number(g.id)}));renderUsers();setBadge(d.total_unread)}catch(e){messages.innerHTML=`<div class="geop-msg-empty">${esc(e.message||'Falha ao carregar mensagens.')}</div>`}}
 async function loadOlder(){if(!selected||!oldestId)return;const params={action:'messages',before_id:oldestId};params[selectedType==='group'?'conversation_id':'user_id']=selected;const d=await api(params);if(!d?.ok)return;const list=d.messages||[];if(list.length){oldestId=Number(list[0].id);renderMessages(list,true)}}
 async function sendMessage(viewOnce=false){const message=text.value.trim(),file=fileInput.files[0];if(!selected||(!message&&!file))return;if(viewOnce&&file){alert('A visualização única está disponível somente para texto.');return}if(file&&file.size>10485760){alert('O anexo deve ter no máximo 10 MB.');return}send.disabled=true;const fd=new FormData();fd.append('action','send');fd.append(selectedType==='group'?'conversation_id':'user_id',selected);fd.append('message',message);fd.append('csrf',csrf);if(replyToId)fd.append('reply_to_id',replyToId);if(viewOnce)fd.append('view_once','1');if(file)fd.append('attachment',file);try{const d=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}}).then(parse);if(d?.ok){text.value='';clearFile();clearReply();await loadUsers();await loadMessages(true)}else if(d&&!d.disabled)alert(d.error||'Não foi possível enviar.')}catch(e){alert(e.message||'Não foi possível enviar.')}finally{send.disabled=false;text.focus()}}
 function clearFile(){fileInput.value='';filePreview.classList.remove('show');filePreview.querySelector('span').textContent=''}
 function clearReply(){replyToId=0;replyPreview.classList.remove('show');replyPreview.querySelector('span').textContent=''}
 function startReply(id){const m=messageCache.get(Number(id));if(!m||m.visualizacao_unica||m.excluida_em)return;replyToId=Number(id);const summary=(m.mensagem||m.anexo_nome||'Anexo').slice(0,140);replyPreview.querySelector('span').textContent='Respondendo a '+(m.remetente_nome||'mensagem')+': '+summary;replyPreview.classList.add('show');text.focus()}
 function openForward(id){forwardMessageId=Number(id);const m=messageCache.get(forwardMessageId);if(!m||m.visualizacao_unica||!m.mensagem)return;const groupHtml=groups.map(g=>`<button type="button" class="geop-msg-forward-target" data-forward-group="${g.id}"><span class="geop-msg-avatar geop-msg-group-avatar">👥</span><span>${esc(g.nome)}<small class="d-block text-muted">Grupo</small></span></button>`).join('');const userHtml=users.map(u=>`<button type="button" class="geop-msg-forward-target" data-forward-user="${u.id}"><span class="geop-msg-avatar">${esc(initials(u.nome))}</span><span>${esc(u.nome)}<small class="d-block text-muted">${esc(u.perfil_label||u.perfil||'Usuário')}</small></span></button>`).join('');forwardTargets.innerHTML=groupHtml+userHtml;forwardModal.classList.add('open')}
 async function forwardTo(kind,id,button){button.disabled=true;const fd=new FormData();fd.append('action','forward');fd.append('message_id',forwardMessageId);fd.append(kind==='group'?'conversation_id':'user_id',id);fd.append('csrf',csrf);try{const d=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}}).then(parse);if(!d?.ok)throw new Error(d?.error||'Não foi possível encaminhar.');forwardModal.classList.remove('open');forwardMessageId=0;await loadUsers();alert('Mensagem encaminhada.')}catch(e){alert(e.message)}finally{button.disabled=false}}
 async function messageAction(action,id,message=''){const fd=new FormData();fd.append('action',action);fd.append('message_id',id);fd.append('csrf',csrf);if(message)fd.append('message',message);const d=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}}).then(parse);if(!d?.ok)throw new Error(d?.error||'Não foi possível concluir.');await loadMessages(true)}
 async function viewOnceMessage(id,button){const fd=new FormData();fd.append('action','view_once');fd.append('message_id',id);fd.append('csrf',csrf);button.disabled=true;try{const d=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}}).then(parse);if(!d?.ok)throw new Error(d?.error||'Não foi possível visualizar.');viewOncePauseUntil=Date.now()+15000;button.replaceWith(document.createTextNode(d.message));setTimeout(()=>{viewOncePauseUntil=0;loadMessages(true)},15000)}catch(e){button.textContent=e.message||'Mensagem indisponível';setTimeout(()=>loadMessages(true),1800)}}
 usersBox.addEventListener('click',event=>{const button=event.target.closest('[data-id]');if(button)selectUser(Number(button.dataset.id),true)});
 messages.addEventListener('click',async event=>{
  const edit=event.target.closest('[data-edit]'),del=event.target.closest('[data-delete]'),once=event.target.closest('[data-view-once]'),reply=event.target.closest('[data-reply]'),forward=event.target.closest('[data-forward]');
  try{
   if(once){await viewOnceMessage(once.dataset.viewOnce,once);return}
   if(reply){startReply(reply.dataset.reply);return}
   if(forward){openForward(forward.dataset.forward);return}
   if(edit){const row=edit.closest('[data-message-id]'),bubble=row?.querySelector('.geop-msg-bubble');const original=bubble?Array.from(bubble.childNodes).filter(n=>n.nodeType===Node.TEXT_NODE).map(n=>n.textContent).join('').trim():'';const updated=prompt('Editar mensagem:',original);if(updated!==null&&updated.trim())await messageAction('edit',edit.dataset.edit,updated.trim())}
   if(del&&confirm('Apagar esta mensagem para todos?'))await messageAction('delete',del.dataset.delete);
  }catch(e){alert(e.message||'Não foi possível concluir.')}
 });
 messages.addEventListener('copy',e=>e.preventDefault());
 messages.addEventListener('cut',e=>e.preventDefault());
 messages.addEventListener('contextmenu',e=>e.preventDefault());
 messages.addEventListener('dragstart',e=>e.preventDefault());
 const emojis=['😀','😂','😊','😍','🥰','😎','🤔','😢','😭','😡','👍','👎','👏','🙏','💪','🎉','❤️','🔥','✅','⚠️','📌','💡','👀','🤝','👌','🙌','😁','😉','😅','🤗','😴','🚀'];
 emojiPicker.innerHTML=emojis.map(e=>`<button type="button" data-emoji="${e}">${e}</button>`).join('');
 $('geopMsgEmoji').onclick=()=>{if(selected)emojiPicker.classList.toggle('open')};
 emojiPicker.onclick=e=>{const b=e.target.closest('[data-emoji]');if(!b)return;const start=text.selectionStart??text.value.length,end=text.selectionEnd??start;text.value=text.value.slice(0,start)+b.dataset.emoji+text.value.slice(end);text.focus();text.setSelectionRange(start+b.dataset.emoji.length,start+b.dataset.emoji.length)};
 $('geopMsgAttach').onclick=()=>{if(selected)fileInput.click()};
 fileInput.onchange=()=>{const file=fileInput.files[0];if(!file)return;if(file.size>10485760){alert('O anexo deve ter no máximo 10 MB.');clearFile();return}filePreview.querySelector('span').textContent=file.name+' · '+formatBytes(file.size);filePreview.classList.add('show')};
 $('geopMsgFileRemove').onclick=clearFile;
 $('geopMsgReplyCancel').onclick=clearReply;
 $('geopMsgForwardClose').onclick=()=>{forwardModal.classList.remove('open');forwardMessageId=0};
 forwardTargets.onclick=e=>{const group=e.target.closest('[data-forward-group]'),user=e.target.closest('[data-forward-user]');if(group)forwardTo('group',Number(group.dataset.forwardGroup),group);if(user)forwardTo('user',Number(user.dataset.forwardUser),user)};
 function closeGroupModal(){groupModal.classList.remove('open')}
 $('geopMsgNewGroup').onclick=()=>{groupMembers.innerHTML=users.map(u=>`<label class="geop-msg-member"><input type="checkbox" value="${u.id}"><span class="geop-msg-avatar">${esc(initials(u.nome))}</span><span>${esc(u.nome)}<small class="d-block text-muted">${esc(u.perfil_label||u.perfil||'Usuário')}</small></span></label>`).join('');$('geopMsgGroupName').value='';groupModal.classList.add('open');$('geopMsgGroupName').focus()};
 $('geopMsgGroupClose').onclick=closeGroupModal;$('geopMsgGroupCancel').onclick=closeGroupModal;
 $('geopMsgGroupCreate').onclick=async()=>{const name=$('geopMsgGroupName').value.trim(),memberIds=Array.from(groupMembers.querySelectorAll('input:checked')).map(i=>Number(i.value));if(!name||!memberIds.length){alert('Informe o nome e selecione pelo menos um participante.');return}const button=$('geopMsgGroupCreate');button.disabled=true;const fd=new FormData();fd.append('action','create_group');fd.append('name',name);fd.append('member_ids',JSON.stringify(memberIds));fd.append('csrf',csrf);try{const d=await fetch(apiUrl,{method:'POST',body:fd,credentials:'same-origin',headers:{Accept:'application/json'}}).then(parse);if(!d?.ok)throw new Error(d?.error||'Não foi possível criar o grupo.');closeGroupModal();await loadUsers();selectGroup(Number(d.conversation_id))}catch(e){alert(e.message)}finally{button.disabled=false}};
 launcher.onclick=()=>{const opening=!panel.classList.contains('open');panel.classList.toggle('open');launcher.classList.remove('notify');document.title=document.title.replace(/^● Nova mensagem \| /,'');if(opening){if('Notification'in window&&Notification.permission==='default')Notification.requestPermission();showUserList();loadUsers()}};
 function resetEnterHold(){if(enterTimer){clearTimeout(enterTimer);enterTimer=null}enterProgress.classList.remove('holding')}
 $('geopMsgClose').onclick=()=>{panel.classList.remove('open');closeGroupModal();forwardModal.classList.remove('open');showUserList()};$('geopMsgBack').onclick=showUserList;$('geopMsgRefresh').onclick=()=>loadUsers().then(()=>selected&&loadMessages(true));search.oninput=renderUsers;form.onsubmit=e=>{e.preventDefault();sendMessage(false)};
 text.onkeydown=e=>{if(e.key!=='Enter'||e.shiftKey)return;e.preventDefault();if(e.repeat||enterTimer||enterLongSent)return;enterLongSent=false;enterProgress.classList.remove('holding');void enterProgress.offsetWidth;enterProgress.classList.add('holding');enterTimer=setTimeout(()=>{enterTimer=null;enterLongSent=true;enterProgress.classList.remove('holding');sendMessage(true)},5000)};
 text.onkeyup=e=>{if(e.key!=='Enter'||e.shiftKey)return;e.preventDefault();const shouldSend=!enterLongSent&&!!enterTimer;resetEnterHold();if(shouldSend)sendMessage(false);enterLongSent=false};
 window.addEventListener('blur',()=>{resetEnterHold();enterLongSent=false});
 poll=setInterval(()=>{if(!disabled)loadUsers().then(()=>selected&&loadMessages())},2000);loadUsers();
})();
</script>
