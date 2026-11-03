<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/cftv.php';
ensure_cftv_schema($pdo);
if(!headers_sent()) header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$codigo=trim((string)($_GET['codigo']??''));
$id=cftv_qr_validar($pdo,$codigo);
$sol=null;$pessoas=[];$voucherUtilizado=false;
if($id){
    $st=$pdo->prepare("SELECT * FROM cftv_solicitacoes_acesso WHERE id=? AND status='APROVADA' LIMIT 1");
    $st->execute([$id]);$sol=$st->fetch(PDO::FETCH_ASSOC);
    if($sol){$voucherUtilizado=cftv_voucher_utilizado($pdo,$id);$st=$pdo->prepare("SELECT nome_completo,cpf FROM cftv_solicitacao_pessoas WHERE solicitacao_id=? ORDER BY id");$st->execute([$id]);$pessoas=$st->fetchAll(PDO::FETCH_ASSOC);}
}
if(!$sol)http_response_code(404);
render_header('Credencial digital de acesso');
?>
<script src="<?=e(app_path('assets/vendor/qrcode.min.js',['v'=>'20260724-2']))?>"></script>
<div class="row justify-content-center"><div class="col-lg-6"><div class="card p-4 text-center">
<?php if(!$sol): ?><div class="alert alert-danger mb-0">Credencial inválida ou não aprovada.</div><?php else: ?>
 <div class="badge text-bg-success align-self-center mb-2">ACESSO APROVADO</div><h1 class="h4"><?=e($sol['nome_completo'])?></h1><p class="mb-1"><?=e($sol['destino_nome'])?></p><p class="text-muted"><?=e(date('d/m/Y',strtotime($sol['data_acesso'])))?><?=(!empty($sol['data_fim'])&&$sol['data_fim']!==$sol['data_acesso'])?' a '.e(date('d/m/Y',strtotime($sol['data_fim']))):''?> · <?=e(substr((string)$sol['hora_inicio'],0,5))?> às <?=e(substr((string)$sol['hora_fim'],0,5))?></p>
 <?php $ehPeriodo=!empty($sol['data_fim'])&&$sol['data_fim']>$sol['data_acesso']; $periodoEncerrado=$ehPeriodo&&date('Y-m-d')>$sol['data_fim']; ?>
 <?php if($voucherUtilizado): ?><div class="alert alert-secondary"><?php if($ehPeriodo&&!$periodoEncerrado): ?><strong>Entrada de hoje registrada.</strong><br>O QR ficará disponível novamente no próximo dia autorizado do período.<?php else: ?><strong>Entrada registrada e voucher finalizado.</strong><br>O QR Code foi removido. Para entrar novamente, é necessário fazer uma nova solicitação.<?php endif ?></div><?php else: ?><div class="alert alert-info"><?php if($ehPeriodo): ?><strong>Liberação por período.</strong><br>Este QR permite uma entrada por dia autorizado, dentro do horário aprovado.<?php else: ?><strong>Voucher de entrada única.</strong><br>Este QR Code desaparece assim que a entrada for registrada. Uma nova entrada exigirá outra solicitação.<?php endif ?></div><div id="qr-credencial" class="d-flex justify-content-center my-3"></div><strong><?=e($sol['protocolo'])?></strong><small class="text-muted d-block">Apresente este QR Code no Controle de Acesso.</small><?php endif ?>
 <?php if($pessoas): ?><div class="text-start border rounded p-2 mt-3"><strong>Pessoas adicionais</strong><?php foreach($pessoas as $p): ?><div><?=e($p['nome_completo'])?> · CPF ***.***.***-<?=e(substr($p['cpf'],-2))?></div><?php endforeach ?></div><?php endif ?>
 <?php if(!$voucherUtilizado): ?><script>new QRCode(document.getElementById('qr-credencial'),{text:<?=json_encode(app_url('controle_acesso',['codigo'=>$codigo]),JSON_UNESCAPED_SLASHES)?>,width:280,height:280,correctLevel:QRCode.CorrectLevel.H});setTimeout(()=>location.reload(),5000);</script><?php endif ?>
<?php endif ?>
</div></div></div>
<?php render_footer(); ?>
