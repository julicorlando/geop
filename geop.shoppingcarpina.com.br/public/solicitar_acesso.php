<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/cftv.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';
ensure_cftv_schema($pdo);
if(!headers_sent()) header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
function solicitar_acesso_valid_time(?string $time): ?string {
    $time=trim((string)$time);
    return preg_match('/^\d{2}:\d{2}$/',$time)?$time.':00':null;
}
function solicitar_acesso_periodo_texto(array $solicitacao): string {
    $inicio=(string)($solicitacao['data_acesso']??'');
    $fim=(string)($solicitacao['data_fim']??$inicio);
    if($fim==='') $fim=$inicio;
    $texto=date('d/m/Y',strtotime($inicio));
    if($fim!==$inicio) $texto.=' a '.date('d/m/Y',strtotime($fim));
    $hi=substr((string)($solicitacao['hora_inicio']??''),0,5);
    $hf=substr((string)($solicitacao['hora_fim']??''),0,5);
    if($hi!==''&&$hf!=='') $texto.=', '.$hi.' às '.$hf;
    return $texto;
}
function solicitar_acesso_eh_periodo(array $solicitacao): bool {
    $inicio=(string)($solicitacao['data_acesso']??'');
    $fim=(string)($solicitacao['data_fim']??$inicio);
    return $fim!==''&&$fim>$inicio;
}
function solicitar_acesso_render_status(PDO $pdo, ?array $solicitacao): void {
    $voucherUtilizado=$solicitacao?cftv_voucher_utilizado($pdo,(int)$solicitacao['id']):false;
    $ehPeriodo=$solicitacao?solicitar_acesso_eh_periodo($solicitacao):false;
    $fimPeriodo=$solicitacao?(string)($solicitacao['data_fim']??$solicitacao['data_acesso']):'';
    $periodoEncerrado=$fimPeriodo!==''&&date('Y-m-d')>$fimPeriodo;
    $novoLink=app_path('solicitar_acesso',['nova'=>1]);
    render_header('Acompanhar solicitação de acesso'); ?>
    <script src="<?=e(app_path('assets/vendor/qrcode.min.js',['v'=>'20260724-2']))?>"></script>
    <div class="row justify-content-center"><div class="col-lg-7"><div class="card p-4">
      <?php if(!$solicitacao): ?><div class="alert alert-danger mb-0">Solicitação não encontrada.</div>
      <?php else: ?><h1 class="h4">Solicitação <?=e($solicitacao['protocolo'])?></h1>
      <p class="mb-2">Solicitante: <strong><?=e($solicitacao['nome_completo'])?></strong></p>
      <p class="mb-2">Acesso: <?=e(solicitar_acesso_periodo_texto($solicitacao))?></p>
      <div class="alert alert-<?=['APROVADA'=>'success','REPROVADA'=>'danger','PENDENTE'=>'warning'][$solicitacao['status']]??'secondary'?>"><strong>Situação: <?=e($solicitacao['status'])?></strong><?php if($solicitacao['decisao_justificativa']): ?><br><?=e($solicitacao['decisao_justificativa'])?><?php endif ?></div>
      <?php if($solicitacao['status']==='PENDENTE'): ?><p class="text-muted">A solicitação foi enviada e aguarda análise. Esta página verifica automaticamente a aprovação.</p><script>setTimeout(()=>location.reload(),5000);</script><?php endif ?>
      <?php if($solicitacao['status']==='APROVADA'&&!$voucherUtilizado): $codigoQr=cftv_qr_codigo($pdo,(int)$solicitacao['id']); ?><div class="alert alert-info"><?php if($ehPeriodo): ?><strong>Liberação por período.</strong> Este QR permite uma entrada por dia autorizado, sempre dentro da faixa de horário aprovada. Após a entrada do dia, ele volta a ficar disponível no próximo dia do período.<?php else: ?><strong>Voucher de entrada única.</strong> O QR Code desaparece assim que a entrada é registrada. Para uma nova entrada, será necessário fazer outra solicitação.<?php endif ?></div><div class="text-center border rounded p-3 mb-3"><strong class="d-block">QR Code de acesso</strong><div id="qr-status" class="d-flex justify-content-center my-3"></div><small class="text-muted d-block mb-2">Apresente este código no Controle de Acesso.</small><a class="btn btn-success" href="<?=e(app_path('acesso_qr',['codigo'=>$codigoQr]))?>">Abrir credencial em tela cheia</a></div><script>new QRCode(document.getElementById('qr-status'),{text:<?=json_encode(app_url('controle_acesso',['codigo'=>$codigoQr]),JSON_UNESCAPED_SLASHES)?>,width:240,height:240,correctLevel:QRCode.CorrectLevel.H});</script><?php elseif($voucherUtilizado): ?><div class="alert alert-secondary"><?php if($ehPeriodo&&!$periodoEncerrado): ?><strong>Entrada de hoje registrada.</strong> O QR ficará disponível novamente no próximo dia autorizado do período.<?php else: ?><strong>Entrada registrada e voucher finalizado.</strong> O QR Code foi removido. Para entrar novamente, faça uma nova solicitação de acesso.<?php endif ?></div><?php endif ?>
      <script><?php if($voucherUtilizado&&(!$ehPeriodo||$periodoEncerrado)): ?>localStorage.removeItem('geop_acesso_acompanhamento');<?php else: ?>localStorage.setItem('geop_acesso_acompanhamento',location.href);<?php endif ?></script>
      <?php if($solicitacao['status']==='APROVADA'&&!$voucherUtilizado): ?><script>setTimeout(()=>location.reload(),5000);</script><?php endif ?>
      <a class="btn btn-outline-primary" href="<?=e($novoLink)?>">Fazer nova solicitação</a>
      <?php endif ?>
    </div></div></div><?php render_footer(); exit;
}
function solicitar_acesso_render_consulta_cpf(PDO $pdo, string $cpf='', string $protocolo='', string $erro=''): void {
    $solicitacoes=[];
    if(strlen($cpf)===11||$protocolo!=='') {
        $where=[];$params=[];
        if($protocolo!==''){$where[]='s.protocolo=?';$params[]=$protocolo;}
        if(strlen($cpf)===11){$where[]='(s.cpf=? OR EXISTS(SELECT 1 FROM cftv_solicitacao_pessoas p WHERE p.solicitacao_id=s.id AND p.cpf=?))';$params[]=$cpf;$params[]=$cpf;}
        $st=$pdo->prepare("SELECT s.id,s.protocolo,s.data_acesso,s.data_fim,s.hora_inicio,s.hora_fim,s.destino_tipo,s.destino_nome,
            s.status,s.decisao_justificativa,s.criado_em,
            CASE WHEN COALESCE(s.data_fim,s.data_acesso)>s.data_acesso
              THEN (CURRENT_DATE>COALESCE(s.data_fim,s.data_acesso) OR EXISTS(SELECT 1 FROM cftv_acesso_eventos ev WHERE ev.solicitacao_id=s.id AND ev.tipo='ENTRADA' AND DATE(ev.registrado_em)=CURRENT_DATE))
              ELSE EXISTS(SELECT 1 FROM cftv_acesso_eventos ev WHERE ev.solicitacao_id=s.id AND ev.tipo='ENTRADA') END voucher_utilizado
            FROM cftv_solicitacoes_acesso s
            WHERE ".implode(' AND ',$where)."
            ORDER BY s.criado_em DESC LIMIT 100");
        $st->execute($params);
        $solicitacoes=$st->fetchAll(PDO::FETCH_ASSOC);
    }
    render_header('Acompanhar solicitações de acesso'); ?>
    <script src="<?=e(app_path('assets/vendor/qrcode.min.js',['v'=>'20260724-2']))?>"></script>
    <div class="row justify-content-center"><div class="col-xl-8">
      <div class="card p-4 mb-3"><h1 class="h4">Acompanhar pelo CPF</h1>
       <p class="text-muted">Informe o CPF ou o protocolo recebido ao enviar a solicitação. Se preencher os dois, ambos serão conferidos.</p>
       <?php if($erro): ?><div class="alert alert-danger"><?=e($erro)?></div><?php endif ?>
       <form method="post" action="<?=e(app_path('solicitar_acesso',['consultar'=>1]))?>" class="row g-2">
        <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="consultar_cpf">
         <div class="col-md-5"><label class="form-label">CPF</label><input class="form-control" name="cpf_consulta" value="<?=e($cpf?cftv_formatar_cpf($cpf):'')?>" autocomplete="off"></div>
         <div class="col-md-4"><label class="form-label">Protocolo</label><input class="form-control text-uppercase" name="protocolo_consulta" value="<?=e($protocolo)?>" placeholder="ACE-..." autocomplete="off"></div>
         <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary w-100">Consultar</button></div>
       </form>
      </div>
      <?php if(strlen($cpf)===11||$protocolo!==''): ?><div class="card p-3"><div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Resultado da consulta</h2><a class="btn btn-sm btn-outline-success" href="<?=e(app_path('solicitar_acesso',['nova'=>1]))?>">Nova solicitação</a></div>
       <?php $temPendente=false; foreach($solicitacoes as $sol): $temPendente=$temPendente||$sol['status']==='PENDENTE'; $solPeriodo=solicitar_acesso_eh_periodo($sol); $solFim=(string)($sol['data_fim']??$sol['data_acesso']); $solEncerrado=date('Y-m-d')>$solFim; ?><div class="border rounded p-3 mb-2"><div class="d-flex flex-wrap justify-content-between gap-2"><div><strong><?=e($sol['protocolo'])?></strong><br><span><?=e(cftv_destinos_liberacao()[$sol['destino_tipo']]??$sol['destino_tipo'])?> — <?=e($sol['destino_nome'])?></span></div><div><?=e(solicitar_acesso_periodo_texto($sol))?></div></div><div class="alert alert-<?=['APROVADA'=>'success','REPROVADA'=>'danger','PENDENTE'=>'warning'][$sol['status']]??'secondary'?> py-2 mt-2 mb-0"><strong><?=e($sol['status'])?></strong><?php if($sol['decisao_justificativa']): ?> — <?=e($sol['decisao_justificativa'])?><?php endif ?></div><?php if($sol['status']==='APROVADA'&&!(bool)$sol['voucher_utilizado']): $codigoQr=cftv_qr_codigo($pdo,(int)$sol['id']); $qrId='qr-cpf-'.(int)$sol['id']; ?><div class="alert alert-info mt-2 mb-0"><?php if($solPeriodo): ?><strong>Liberação por período.</strong> Uma entrada por dia autorizado, dentro do horário aprovado.<?php else: ?><strong>Voucher de entrada única.</strong> O QR desaparece depois que a entrada for registrada.<?php endif ?></div><div class="text-center mt-2"><strong class="d-block">QR Code de acesso</strong><div id="<?=e($qrId)?>" class="d-flex justify-content-center my-2"></div><a class="btn btn-success" href="<?=e(app_path('acesso_qr',['codigo'=>$codigoQr]))?>">Abrir em tela cheia</a></div><script>new QRCode(document.getElementById(<?=json_encode($qrId)?>),{text:<?=json_encode(app_url('controle_acesso',['codigo'=>$codigoQr]),JSON_UNESCAPED_SLASHES)?>,width:220,height:220,correctLevel:QRCode.CorrectLevel.H});</script><?php elseif((bool)$sol['voucher_utilizado']): ?><div class="alert alert-secondary mt-2 mb-0"><?php if($solPeriodo&&!$solEncerrado): ?><strong>Entrada de hoje registrada.</strong> O QR volta a ficar disponível no próximo dia autorizado.<?php else: ?><strong>Entrada registrada.</strong> O voucher foi finalizado; faça uma nova solicitação para outra entrada.<?php endif ?></div><?php endif ?></div><?php endforeach ?>
       <?php if($temPendente): ?><script>setTimeout(()=>location.reload(),5000);</script><?php endif ?><script>if(document.querySelector('[id^="qr-cpf-"]'))setTimeout(()=>location.reload(),5000);</script>
       <?php if(!$solicitacoes): ?><div class="text-center text-muted p-4">Nenhuma solicitação encontrada para este CPF.</div><?php endif ?>
      </div><?php endif ?>
    </div></div><?php render_footer(); exit;
}

if(isset($_GET['nova'])) {
    unset($_SESSION['acesso_publico_solicitacao_id'],$_SESSION['acesso_publico_cpf'],$_SESSION['acesso_publico_protocolo']);
    header('Location: '.app_path('solicitar_acesso',['t'=>cftv_link_publico_token($pdo),'reset'=>1])); exit;
}

$tokenAcompanhamento=trim((string)($_GET['acompanhar']??''));
if($tokenAcompanhamento!=='') {
    $st=$pdo->prepare("SELECT * FROM cftv_solicitacoes_acesso WHERE acompanhamento_hash=? LIMIT 1");
    $st->execute([hash('sha256',$tokenAcompanhamento)]);
    $solicitacao=$st->fetch(PDO::FETCH_ASSOC);
    if(!$solicitacao){http_response_code(404);}
        solicitar_acesso_render_status($pdo, $solicitacao);
}

if(isset($_GET['consultar'])||($_POST['action']??'')==='consultar_cpf') {
    $erroConsulta='';
    $cpfConsulta=(string)($_SESSION['acesso_publico_cpf']??'');
    $protocoloConsulta=(string)($_SESSION['acesso_publico_protocolo']??'');
    if($_SERVER['REQUEST_METHOD']==='POST') {
        check_csrf();
        $agora=time();
        $janela=$_SESSION['consulta_cpf_janela']??['inicio'=>$agora,'tentativas'=>0];
        if($agora-(int)$janela['inicio']>600) $janela=['inicio'=>$agora,'tentativas'=>0];
        $janela['tentativas']++;
        $_SESSION['consulta_cpf_janela']=$janela;
        $cpfConsulta=cftv_normalizar_cpf($_POST['cpf_consulta']??'');
        $protocoloConsulta=strtoupper(trim((string)($_POST['protocolo_consulta']??'')));
        if((int)$janela['tentativas']>10) {$erroConsulta='Muitas consultas. Aguarde alguns minutos.';$cpfConsulta='';}
        elseif($cpfConsulta===''&&$protocoloConsulta==='') {$erroConsulta='Informe o CPF ou o protocolo.';}
        elseif($cpfConsulta!==''&&strlen($cpfConsulta)!==11) {$erroConsulta='Informe um CPF válido.';$cpfConsulta='';}
        elseif($protocoloConsulta!==''&&!preg_match('/^ACE-[A-Z0-9-]{6,30}$/',$protocoloConsulta)) {$erroConsulta='Informe um protocolo válido.';$protocoloConsulta='';}
        else {$_SESSION['acesso_publico_cpf']=$cpfConsulta;$_SESSION['acesso_publico_protocolo']=$protocoloConsulta;}
    }
    solicitar_acesso_render_consulta_cpf($pdo,$cpfConsulta,$protocoloConsulta,$erroConsulta);
}

$token=trim((string)($_GET['t']??$_POST['t']??''));
$tokenPermanente=cftv_link_publico_token($pdo);
$linkPermanenteHash=hash('sha256',$tokenPermanente);
$pdo->prepare("INSERT INTO cftv_liberacao_links
    (token_hash,titulo,destino_padrao,expira_em,limite_usos,usos,ativo,criado_por)
    VALUES(?,'Solicitação de acesso',NULL,'2099-12-31 23:59:59',2147483647,0,1,0)
    ON DUPLICATE KEY UPDATE ativo=1,expira_em='2099-12-31 23:59:59',limite_usos=2147483647")
    ->execute([$linkPermanenteHash]);
if($token===''&&$_SERVER['REQUEST_METHOD']!=='POST'&&!isset($_SESSION['acesso_publico_solicitacao_id'])) {
    header('Location: '.app_path('solicitar_acesso',['t'=>$tokenPermanente])); exit;
}
if($token===''&&isset($_SESSION['acesso_publico_solicitacao_id'])&&$_SERVER['REQUEST_METHOD']!=='POST') {
    $st=$pdo->prepare("SELECT * FROM cftv_solicitacoes_acesso WHERE id=? LIMIT 1");
    $st->execute([(int)$_SESSION['acesso_publico_solicitacao_id']]);
        solicitar_acesso_render_status($pdo, $st->fetch(PDO::FETCH_ASSOC)?:null);
}
$st=$pdo->prepare("SELECT * FROM cftv_liberacao_links WHERE token_hash=? AND ativo=1 AND expira_em>=NOW() LIMIT 1");
$st->execute([$token===''?$linkPermanenteHash:hash('sha256',$token)]);
$link=$st->fetch(PDO::FETCH_ASSOC);
$destinosPublicos=$pdo->query("SELECT id,tipo,nome FROM cftv_destinos_acesso
    WHERE ativo=1 ORDER BY tipo,nome")->fetchAll(PDO::FETCH_ASSOC);
$linkDisponivel=$link && (int)$link['usos']<(int)$link['limite_usos'];
if($_SERVER['REQUEST_METHOD']!=='POST'&&$link&&!$linkDisponivel&&(int)$link['limite_usos']===1) {
    $st=$pdo->prepare("SELECT * FROM cftv_solicitacoes_acesso WHERE link_id=? ORDER BY id DESC LIMIT 1");
    $st->execute([$link['id']]);
        solicitar_acesso_render_status($pdo, $st->fetch(PDO::FETCH_ASSOC)?:null);
}
$erro='';
if($_SERVER['REQUEST_METHOD']==='POST'&&$linkDisponivel) {
    check_csrf();
    if(trim((string)($_POST['website']??''))!=='') { http_response_code(400); exit; }
    $nome=trim((string)($_POST['nome_completo']??''));
    $cpf=cftv_normalizar_cpf($_POST['cpf']??'');
    $email=strtolower(trim((string)($_POST['email']??'')));
    $data=(string)($_POST['data_acesso']??'');
    $tipoPeriodo=strtoupper(trim((string)($_POST['tipo_periodo']??'DIA')));
    if(!in_array($tipoPeriodo,['DIA','PERIODO'],true)) $tipoPeriodo='DIA';
    $dataFim=$tipoPeriodo==='PERIODO'?(string)($_POST['data_fim']??''):$data;
    $horaInicioInput=solicitar_acesso_valid_time($_POST['hora_inicio']??null);
    $horaFimInput=solicitar_acesso_valid_time($_POST['hora_fim']??null);
    $destino=trim((string)($_POST['destino_nome']??''));
    $destinoTipo=strtoupper(trim((string)($_POST['destino_tipo']??'LOJA_MALL')));
    if(!in_array($destinoTipo,['LOJA_MALL','ADMINISTRACAO','PRACA_ALIMENTACAO','AREA_SERVICO'],true)) $destinoTipo='LOJA_MALL';
    if($destinoTipo==='ADMINISTRACAO') $destino='Administração';
    if(in_array($destinoTipo,['LOJA_MALL','PRACA_ALIMENTACAO'],true)) {
        $validarDestino=$pdo->prepare("SELECT nome FROM cftv_destinos_acesso WHERE id=? AND tipo=? AND ativo=1 LIMIT 1");
        $validarDestino->execute([(int)($_POST['destino_id']??0),$destinoTipo]);
        $destino=(string)$validarDestino->fetchColumn();
    }
    if($destinoTipo==='AREA_SERVICO') $destino='Área de Serviço';
    $observacoes=trim((string)($_POST['observacoes']??''));
    $nomesExtras=$_POST['pessoa_nome']??[];
    $cpfsExtras=$_POST['pessoa_cpf']??[];
    $pessoasExtras=[];
    $cpfsVistos=[$cpf=>true];
    if(count($nomesExtras)>20) $erro='É permitido incluir no máximo 20 pessoas adicionais por solicitação.';
    foreach($nomesExtras as $indice=>$nomeExtra) {
        if($erro!=='') break;
        $nomeExtra=trim((string)$nomeExtra); $cpfExtra=cftv_normalizar_cpf($cpfsExtras[$indice]??'');
        if($nomeExtra===''&&$cpfExtra==='') continue;
        if($nomeExtra===''||strlen($cpfExtra)!==11){$erro='Preencha nome e CPF de todas as pessoas adicionais.';break;}
        if(isset($cpfsVistos[$cpfExtra])){$erro='Não repita o mesmo CPF na solicitação.';break;}
        $cpfsVistos[$cpfExtra]=true;
        $pessoasExtras[]=['nome'=>$nomeExtra,'cpf'=>$cpfExtra];
    }
    $ipHash=hash('sha256',(string)($_SERVER['REMOTE_ADDR']??''));
    $limite=$pdo->prepare("SELECT COUNT(*) FROM cftv_solicitacoes_acesso WHERE ip_hash=? AND criado_em>=DATE_SUB(NOW(),INTERVAL 1 HOUR)");
    $limite->execute([$ipHash]);
    if($erro!=='') {}
    elseif((int)$limite->fetchColumn()>=5) $erro='Muitas solicitações recentes. Aguarde antes de tentar novamente.';
    elseif($nome===''||strlen($cpf)!==11||!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$data)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$dataFim)||!$horaInicioInput||!$horaFimInput||$destino==='') $erro='Preencha nome, CPF, e-mail, datas, horários e destino corretamente.';
    elseif($destinoTipo==='AREA_SERVICO'&&$observacoes==='') $erro='Informe nas observações quais dependências da Área de Serviço serão acessadas.';
    elseif(strtotime($data)<strtotime(date('Y-m-d'))) $erro='A data inicial do acesso não pode estar no passado.';
    elseif(strtotime($dataFim)<strtotime($data)) $erro='A data final não pode ser anterior à data inicial.';
    elseif(strtotime('1970-01-01 '.$horaFimInput)<=strtotime('1970-01-01 '.$horaInicioInput)) $erro='O horário final deve ser posterior ao horário inicial.';
    else {
        $acompanhamento=bin2hex(random_bytes(24));
        $protocolo='ACE-'.date('ymd').'-'.strtoupper(substr(bin2hex(random_bytes(5)),0,8));
        $pdo->beginTransaction();
        try {
            $reservarUso=$pdo->prepare("UPDATE cftv_liberacao_links SET usos=usos+1
                WHERE id=? AND ativo=1 AND expira_em>=NOW() AND usos<limite_usos");
            $reservarUso->execute([$link['id']]);
            if($reservarUso->rowCount()!==1) throw new RuntimeException('Este link não está mais disponível.');
            $pdo->prepare("INSERT INTO cftv_solicitacoes_acesso
              (protocolo,link_id,acompanhamento_hash,nome_completo,cpf,telefone,email,empresa,data_acesso,data_fim,hora_inicio,hora_fim,
               destino_tipo,destino_nome,responsavel_interno,motivo,veiculo_placa,observacoes,ip_hash)
              VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([
                $protocolo,$link['id'],hash('sha256',$acompanhamento),$nome,$cpf,trim((string)($_POST['telefone']??'')),$email,
                trim((string)($_POST['empresa']??'')),$data,$dataFim,$horaInicioInput,$horaFimInput,$destinoTipo,$destino,
                trim((string)($_POST['responsavel_interno']??'')),trim((string)($_POST['motivo']??'')),
                strtoupper(trim((string)($_POST['veiculo_placa']??''))),$observacoes,$ipHash
              ]);
            $solicitacaoId=(int)$pdo->lastInsertId();
            $inserirPessoa=$pdo->prepare("INSERT INTO cftv_solicitacao_pessoas(solicitacao_id,nome_completo,cpf) VALUES(?,?,?)");
            foreach($pessoasExtras as $pessoaExtra) $inserirPessoa->execute([$solicitacaoId,$pessoaExtra['nome'],$pessoaExtra['cpf']]);
            $pdo->commit();
            $_SESSION['acesso_publico_solicitacao_id']=$solicitacaoId;
            $_SESSION['acesso_publico_cpf']=$cpf;
            $_SESSION['acesso_publico_protocolo']=$protocolo;
            $aprovadores=$pdo->query("SELECT id FROM usuarios WHERE status='ATIVO'
                AND perfil IN ('GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS')")
                ->fetchAll(PDO::FETCH_COLUMN);
            $aprovadores=array_unique(array_map('intval',array_merge($aprovadores,[(int)$link['criado_por']])));
            foreach($aprovadores as $uid) gestao_notificar($pdo,$uid,'ACESSO_PENDENTE','Nova solicitação de liberação',
                $protocolo.' — '.$nome.' — '.$destino,app_path('cftv',['view'=>'liberacoes']),'acesso-pendente-'.$solicitacaoId.'-'.$uid);
            cftv_email_status_solicitacao([
                'email'=>$email,
                'nome_completo'=>$nome,
                'protocolo'=>$protocolo,
                'data_acesso'=>$data,
                'data_fim'=>$dataFim,
                'hora_inicio'=>$horaInicioInput,
                'hora_fim'=>$horaFimInput,
            ],'PENDENTE');
            header('Location: '.app_path('solicitar_acesso',['acompanhar'=>$acompanhamento]));
            exit;
        } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}

render_header('Solicitar acesso');
?>
<div class="row justify-content-center"><div class="col-xl-8">
 <div class="card p-4">
  <h1 class="h3"><?=e($link['titulo']??'Solicitação de acesso')?></h1>
  <?php if(!$linkDisponivel): ?><div class="alert alert-danger mb-0">Este link é inválido, expirou ou atingiu o limite de solicitações.</div>
  <?php else: ?><?php if($erro): ?><div class="alert alert-danger"><?=e($erro)?></div><?php endif ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><p class="text-muted mb-0">Preencha os dados abaixo. A solicitação será analisada por um responsável autorizado.</p><a class="btn btn-outline-primary" href="<?=e(app_path('solicitar_acesso',['consultar'=>1]))?>">Acompanhar com CPF ou protocolo</a></div>
  <div class="alert alert-info mt-3"><strong>Você pode solicitar um único dia ou um período.</strong> Em liberações de vários dias, o mesmo QR poderá ser usado para uma entrada em cada dia autorizado, respeitando sempre o horário aprovado.</div>
  <script>(()=>{if(new URLSearchParams(location.search).has('reset')){localStorage.removeItem('geop_acesso_acompanhamento');return;}const salvo=localStorage.getItem('geop_acesso_acompanhamento');if(salvo&&salvo!==location.href)location.replace(salvo);})();</script>
  <form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="t" value="<?=e($token)?>"><input class="d-none" name="website" tabindex="-1" autocomplete="off">
   <div class="col-md-8"><label class="form-label">Nome completo</label><input class="form-control" name="nome_completo" required></div>
   <div class="col-md-4"><label class="form-label">CPF</label><input class="form-control" name="cpf" required></div>
   <div class="col-md-4"><label class="form-label">Telefone</label><input class="form-control" name="telefone" inputmode="tel" autocomplete="tel"></div>
   <div class="col-md-8"><label class="form-label">E-mail do solicitante</label><input class="form-control" type="email" name="email" autocomplete="email" required><div class="form-text">Usado para confirmação e atualização do pedido.</div></div>
   <div class="col-md-8"><label class="form-label">Empresa</label><input class="form-control" name="empresa"></div>
   <div class="col-md-4"><label class="form-label">Tipo de liberação</label><select class="form-select" name="tipo_periodo" id="tipo_periodo_publico"><option value="DIA">Somente um dia</option><option value="PERIODO">Período de vários dias</option></select></div>
   <div class="col-md-4"><label class="form-label">Data inicial</label><input class="form-control" id="data_acesso_publico" type="date" name="data_acesso" min="<?=e(date('Y-m-d'))?>" required></div>
   <div class="col-md-4 d-none" id="campo_data_fim_publico"><label class="form-label">Data final</label><input class="form-control" id="data_fim_publico" type="date" name="data_fim" min="<?=e(date('Y-m-d'))?>"></div>
   <div class="col-md-2"><label class="form-label">Hora inicial</label><input class="form-control" type="time" name="hora_inicio" required></div>
   <div class="col-md-2"><label class="form-label">Hora final</label><input class="form-control" type="time" name="hora_fim" required></div>
   <div class="col-12"><div class="form-text">No modo período, a mesma faixa de horário vale em cada dia entre a data inicial e a data final.</div></div>
   <div class="col-md-4"><label class="form-label">Onde será o acesso?</label><select class="form-select" name="destino_tipo" id="destino_tipo_publico"><option value="LOJA_MALL">Loja do Mall</option><option value="PRACA_ALIMENTACAO">Loja da Praça de Alimentação</option><option value="ADMINISTRACAO">Administração</option><option value="AREA_SERVICO">Área de Serviço</option></select></div>
   <div class="col-md-6" id="campo_destino_publico"><label class="form-label" id="rotulo_destino_publico">Qual loja?</label><select class="form-select" id="destino_id_publico" name="destino_id"><option value="">Selecione</option><?php foreach($destinosPublicos as $destino): ?><option value="<?=e($destino['id'])?>" data-tipo="<?=e($destino['tipo'])?>"><?=e($destino['nome'])?></option><?php endforeach ?></select><input type="hidden" name="destino_nome" value=""></div>
   <div class="col-md-6"><label class="form-label">Responsável interno</label><input class="form-control" name="responsavel_interno"></div>
   <div class="col-md-8"><label class="form-label">Motivo do acesso</label><textarea class="form-control" name="motivo" rows="2"></textarea></div>
   <div class="col-md-4"><label class="form-label">Placa do veículo</label><input class="form-control" name="veiculo_placa"></div>
   <div class="col-12"><label class="form-label">Observações <span id="observacao_area_aviso" class="text-danger d-none">(obrigatório: informe quais dependências serão acessadas)</span></label><textarea class="form-control" id="observacoes_publico" name="observacoes" rows="2"></textarea></div>
   <div class="col-12 border rounded p-3"><div class="d-flex justify-content-between align-items-center"><div><strong>Demais pessoas</strong><div class="small text-muted">Inclua acompanhantes que participarão do mesmo acesso.</div></div><button class="btn btn-sm btn-outline-primary" type="button" id="adicionar_pessoa">Adicionar pessoa</button></div><div id="pessoas_adicionais" class="mt-2"></div></div>
   <div class="col-12"><button class="btn btn-primary btn-lg">Enviar para aprovação</button></div>
  </form>
  <script>(()=>{const tipoPeriodo=document.getElementById('tipo_periodo_publico'),dataInicio=document.getElementById('data_acesso_publico'),dataFim=document.getElementById('data_fim_publico'),campoDataFim=document.getElementById('campo_data_fim_publico');const atualizarPeriodo=()=>{const periodo=tipoPeriodo.value==='PERIODO';campoDataFim.classList.toggle('d-none',!periodo);dataFim.required=periodo;dataFim.min=dataInicio.value||<?=json_encode(date('Y-m-d'))?>;if(!periodo||!dataFim.value)dataFim.value=dataInicio.value;};tipoPeriodo.addEventListener('change',atualizarPeriodo);dataInicio.addEventListener('change',()=>{if(dataFim.value===''||dataFim.value<dataInicio.value)dataFim.value=dataInicio.value;atualizarPeriodo();});atualizarPeriodo();const tipo=document.getElementById('destino_tipo_publico'),campo=document.getElementById('campo_destino_publico'),destino=document.getElementById('destino_id_publico'),rotulo=document.getElementById('rotulo_destino_publico'),obs=document.getElementById('observacoes_publico'),aviso=document.getElementById('observacao_area_aviso');const todas=[...destino.options].slice(1).map(o=>({value:o.value,text:o.textContent,tipo:o.dataset.tipo}));const atualizar=()=>{const exigeLoja=['LOJA_MALL','PRACA_ALIMENTACAO'].includes(tipo.value);campo.classList.toggle('d-none',!exigeLoja);destino.required=exigeLoja;rotulo.textContent=tipo.value==='PRACA_ALIMENTACAO'?'Qual loja da Praça de Alimentação?':'Qual loja do Mall?';destino.innerHTML='<option value=\"\">Selecione</option>';todas.filter(o=>o.tipo===tipo.value).forEach(o=>destino.add(new Option(o.text,o.value)));const area=tipo.value==='AREA_SERVICO';obs.required=area;aviso.classList.toggle('d-none',!area);};tipo.addEventListener('change',atualizar);atualizar();const lista=document.getElementById('pessoas_adicionais');document.getElementById('adicionar_pessoa').addEventListener('click',()=>{const linha=document.createElement('div');linha.className='row g-2 mb-2 pessoa-adicional';linha.innerHTML='<div class=\"col-md-6\"><input class=\"form-control\" name=\"pessoa_nome[]\" placeholder=\"Nome completo\" required></div><div class=\"col-md-4\"><input class=\"form-control\" name=\"pessoa_cpf[]\" placeholder=\"CPF\" required></div><div class=\"col-md-2\"><button type=\"button\" class=\"btn btn-outline-danger w-100\">Remover</button></div>';linha.querySelector('button').onclick=()=>linha.remove();lista.appendChild(linha);});})();</script><?php endif ?>
 </div>
</div></div>
<?php render_footer(); ?>
