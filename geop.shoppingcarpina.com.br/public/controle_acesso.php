<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/cftv.php';
require_once __DIR__ . '/../includes/audit.php';
require_role(['TI','CFTV','CONTROLE_ACESSO']);
ensure_cftv_schema($pdo);
$user=current_user();$resultado=null;$erro='';

if($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $codigo=trim((string)($_POST['codigo']??''));
    if(filter_var($codigo,FILTER_VALIDATE_URL)) {
        parse_str((string)parse_url($codigo,PHP_URL_QUERY),$queryQr);
        $codigo=trim((string)($queryQr['codigo']??''));
    }
    $solicitacaoId=cftv_qr_validar($pdo,$codigo);
    if(!$solicitacaoId) $erro='QR Code inválido ou adulterado.';
    else {
        $pdo->beginTransaction();
        try {
            $st=$pdo->prepare("SELECT * FROM cftv_solicitacoes_acesso WHERE id=? FOR UPDATE");
            $st->execute([$solicitacaoId]);$sol=$st->fetch(PDO::FETCH_ASSOC);
            if(!$sol||$sol['status']!=='APROVADA') $erro='A solicitação não está aprovada.';
            else {
                $hoje=date('Y-m-d');
                $dataFim=(string)($sol['data_fim']??$sol['data_acesso']);
                $st=$pdo->prepare("SELECT * FROM cftv_acesso_eventos WHERE solicitacao_id=? AND DATE(registrado_em)=? ORDER BY id DESC LIMIT 1");
                $st->execute([$solicitacaoId,$hoje]);$ultimo=$st->fetch(PDO::FETCH_ASSOC);
                $inicioPrevisto=strtotime($hoje.' '.($sol['hora_inicio']?:'00:00:00'));
                $fimPrevisto=strtotime($hoje.' '.($sol['hora_fim']?:'23:59:59'));
                if($fimPrevisto<=$inicioPrevisto)$fimPrevisto=strtotime('+1 day',$fimPrevisto);
                $ehPeriodo=$dataFim>(string)$sol['data_acesso'];
                $antecedencia=$ehPeriodo?0:3600;
                if($hoje<(string)$sol['data_acesso']) $erro='A credencial ainda não está válida. O período autorizado ainda não começou.';
                elseif($hoje>$dataFim) $erro='Credencial expirada: o período autorizado terminou.';
                elseif(!$ultimo&&time()<$inicioPrevisto-$antecedencia) $erro=$ehPeriodo?'A entrada ainda não está liberada. Respeite o horário inicial autorizado.':'A entrada ainda não está liberada. Permitida uma hora antes do horário previsto.';
                elseif(!$ultimo&&time()>$fimPrevisto) $erro='A faixa de horário autorizada para hoje terminou.';
                elseif($ultimo&&$ultimo['tipo']==='SAIDA') $erro='O acesso de hoje já foi finalizado.';
                elseif($ultimo&&time()-strtotime($ultimo['registrado_em'])<120) {
                    $resultado=['tipo'=>'ENTRADA_JA_REGISTRADA','sol'=>$sol,'hora'=>$ultimo['registrado_em']];
                } else {
                    $tipoEvento=$ultimo?'SAIDA':'ENTRADA';
                    $pdo->prepare("INSERT INTO cftv_acesso_eventos(solicitacao_id,tipo,registrado_por,observacao)
                        VALUES(?,?,?,?)")->execute([$solicitacaoId,$tipoEvento,$user['id'],trim((string)($_POST['observacao']??''))]);
                    $resultado=['tipo'=>$tipoEvento,'sol'=>$sol,'hora'=>date('Y-m-d H:i:s')];
                    auditoria_log($pdo,'cftv_acesso',$solicitacaoId,'REGISTRAR_'.$tipoEvento,null,null,$sol['protocolo'],$user['id']);
                }
                if($resultado) {
                    $stPessoas=$pdo->prepare("SELECT nome_completo,cpf FROM cftv_solicitacao_pessoas WHERE solicitacao_id=? ORDER BY id");
                    $stPessoas->execute([$solicitacaoId]);$resultado['pessoas']=$stPessoas->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            $pdo->commit();
        } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}

$st=$pdo->prepare("SELECT s.*,
 (SELECT MIN(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='ENTRADA' AND DATE(e.registrado_em)=CURRENT_DATE) entrada,
 (SELECT MAX(e.registrado_em) FROM cftv_acesso_eventos e WHERE e.solicitacao_id=s.id AND e.tipo='SAIDA' AND DATE(e.registrado_em)=CURRENT_DATE) saida,
 (SELECT COUNT(*) FROM cftv_solicitacao_pessoas p WHERE p.solicitacao_id=s.id) acompanhantes
 FROM cftv_solicitacoes_acesso s WHERE s.status='APROVADA' AND CURRENT_DATE BETWEEN s.data_acesso AND COALESCE(s.data_fim,s.data_acesso)
 ORDER BY COALESCE(s.hora_inicio,'00:00'),s.nome_completo");
$st->execute();$acessosHoje=$st->fetchAll(PDO::FETCH_ASSOC);
$presentes=count(array_filter($acessosHoje,fn($a)=>$a['entrada']&&!$a['saida']));
$aguardando=count(array_filter($acessosHoje,fn($a)=>!$a['entrada']));
$finalizados=count(array_filter($acessosHoje,fn($a)=>$a['saida']));
render_header('Controle de acesso digital');
?>
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h1 class="h3 mb-0">Controle de acesso digital</h1><small class="text-muted">Leitura de QR Code, entrada e saída em tempo real.</small></div><div class="d-flex gap-2"><a class="btn btn-outline-primary" href="<?=e(app_path('liberacoes_aprovadas'))?>">Liberações aprovadas</a><a class="btn btn-outline-dark" href="<?=e(app_path('cftv_historico_acessos'))?>">Histórico</a></div></div>
<div class="row g-2 mb-3"><?php foreach([['Previstos',count($acessosHoje),'primary'],['Aguardando',$aguardando,'warning'],['Presentes',$presentes,'success'],['Finalizados',$finalizados,'secondary']] as $card): ?><div class="col-6 col-lg-3"><div class="card p-3 border-start border-<?=$card[2]?> border-4"><strong class="h3 mb-0"><?=$card[1]?></strong><small><?=$card[0]?></small></div></div><?php endforeach ?></div>
<?php if($erro): ?><div class="alert alert-danger"><strong>Acesso não autorizado:</strong> <?=e($erro)?></div><?php endif ?>
<?php if($resultado): ?><div class="alert alert-<?=$resultado['tipo']==='SAIDA'?'secondary':'success'?>"><h2 class="h5"><?=$resultado['tipo']==='SAIDA'?'Saída registrada':($resultado['tipo']==='ENTRADA'?'Entrada autorizada':'Entrada já registrada')?></h2><strong><?=e($resultado['sol']['nome_completo'])?></strong> · <?=e($resultado['sol']['destino_nome'])?><br><small><?=e(date('d/m/Y H:i:s',strtotime($resultado['hora'])))?></small><?php if($resultado['pessoas']): ?><hr><strong>Validar também:</strong><?php foreach($resultado['pessoas'] as $p): ?><div><?=e($p['nome_completo'])?> · CPF ***.***.***-<?=e(substr($p['cpf'],-2))?></div><?php endforeach ?><?php endif ?></div><?php endif ?>
<div class="row g-3">
<?php if($presentes): ?><div class="col-12"><div class="card p-3"><h2 class="h5">Registrar saída</h2><div class="d-flex flex-wrap gap-2"><?php foreach($acessosHoje as $presente): if(!$presente['entrada']||$presente['saida'])continue; ?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="codigo" value="<?=e(cftv_qr_codigo($pdo,(int)$presente['id']))?>"><button class="btn btn-outline-secondary"><?=e($presente['nome_completo'])?> · Registrar saída</button></form><?php endforeach ?></div></div></div><?php endif ?>
<div class="col-lg-5"><div class="card p-3"><h2 class="h5">Ler QR Code</h2><div id="leitor-qr" style="max-width:440px;margin:auto"></div><form method="post" id="form-qr" class="mt-3"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label class="form-label">Código manual</label><div class="input-group"><input class="form-control" id="codigo-qr" name="codigo" required autocomplete="off"><button class="btn btn-primary">Validar</button></div><input class="form-control mt-2" name="observacao" placeholder="Observação opcional"></form><small class="text-muted d-block mt-2">O QR Code registra somente a entrada e desaparece para o solicitante. A saída pode ser registrada na lista ao lado.</small></div></div>
 <div class="col-lg-7"><div class="card p-3"><h2 class="h5">Movimento previsto para hoje</h2><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Pessoa</th><th>Destino</th><th>Previsto</th><th>Situação</th></tr></thead><tbody><?php foreach($acessosHoje as $a): ?><tr><td><strong><?=e($a['nome_completo'])?></strong><?php if($a['acompanhantes']): ?><br><small>+<?=e($a['acompanhantes'])?> pessoa(s)</small><?php endif ?></td><td><?=e($a['destino_nome'])?></td><td><?=e(substr((string)$a['hora_inicio'],0,5))?>–<?=e(substr((string)$a['hora_fim'],0,5))?></td><td><?php if($a['saida']): ?><span class="badge text-bg-secondary">Finalizado <?=e(substr($a['saida'],11,5))?></span><?php elseif($a['entrada']): ?><span class="badge text-bg-success">Presente desde <?=e(substr($a['entrada'],11,5))?></span><?php else: ?><span class="badge text-bg-warning">Aguardando</span><?php endif ?></td></tr><?php endforeach; if(!$acessosHoje): ?><tr><td colspan="4" class="text-center text-muted">Nenhum acesso aprovado para hoje.</td></tr><?php endif ?></tbody></table></div></div></div>
</div>
<script>
let bloqueado=false;
function qrLido(texto){if(bloqueado)return;bloqueado=true;const valor=texto.includes('codigo=')?new URL(texto).searchParams.get('codigo'):texto;document.getElementById('codigo-qr').value=valor;document.getElementById('form-qr').submit();}
if(window.Html5QrcodeScanner){const scanner=new Html5QrcodeScanner('leitor-qr',{fps:10,qrbox:{width:240,height:240},rememberLastUsedCamera:true},false);scanner.render(qrLido,()=>{});}
<?php if($resultado): ?>navigator.vibrate?.([120,60,120]);window.speechSynthesis?.speak(new SpeechSynthesisUtterance(<?=json_encode($resultado['tipo']==='SAIDA'?'Saída registrada':'Entrada autorizada',JSON_UNESCAPED_UNICODE)?>));<?php elseif($erro): ?>navigator.vibrate?.([350,100,350]);window.speechSynthesis?.speak(new SpeechSynthesisUtterance('Acesso não autorizado'));<?php endif ?>
</script>
<?php render_footer(); ?>
