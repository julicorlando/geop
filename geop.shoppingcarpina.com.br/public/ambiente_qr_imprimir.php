<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ambientes_qr.php';
limpeza_require_access();
ambiente_require_schema($pdo);
limpeza_require_schema($pdo);
exigir_permissao('ambientes.gerar_qr');
$id=(int)($_GET['id']??0);
$ambiente=ambiente_get($pdo,$id);
if(!$ambiente){http_response_code(404);die('Ambiente não encontrado.');}
$url=ambiente_public_url($ambiente);
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>QR - <?=e($ambiente['nome_publico'])?></title><style>
*{box-sizing:border-box}body{font-family:Arial,sans-serif;margin:0;background:#e9edf2;color:#102238}.sheet{width:148mm;min-height:210mm;margin:20px auto;background:#fff;padding:15mm;border:1px solid #ccd5df;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center}.brand{font-weight:800;letter-spacing:.12em;color:#0b2341}.title{font-size:28px;margin:18px 0 4px}.subtitle{font-size:16px;color:#526273;margin-bottom:18px}.qr{padding:16px;background:#fff;border:3px solid #0b2341;border-radius:16px}.call{font-size:20px;font-weight:700;margin:22px 0 6px}.url{font-size:10px;word-break:break-all;color:#657385}.code{margin-top:16px;padding:8px 14px;border-radius:99px;background:#eef4f8;font-size:12px}.actions{position:fixed;right:20px;top:20px;display:flex;gap:8px}.actions button,.actions a{border:0;border-radius:8px;padding:10px 14px;background:#0b2341;color:#fff;text-decoration:none;cursor:pointer}@media print{body{background:#fff}.sheet{margin:0;border:0;width:100%;min-height:100vh}.actions{display:none}}
</style></head><body><div class="actions"><a href="<?=e(app_path('ambientes'))?>">Voltar</a><button onclick="window.print()">Imprimir</button></div><main class="sheet"><div class="brand">GEOP · SHOPPING CARPINA</div><h1 class="title"><?=e($ambiente['nome_publico'])?></h1><div class="subtitle"><?=e($ambiente['descricao_publica'] ?: $ambiente['local_nome'])?></div><div id="qr" class="qr"></div><div class="call">Aponte a câmera e consulte as últimas higienizações</div><p>Gestor ou Supervisor: escaneie para registrar ou validar a limpeza.</p><div class="url"><?=e($url)?></div><div class="code">Ambiente #<?=e($ambiente['id'])?></div></main><script src="<?=e(app_path('assets/vendor/qrcode.min.js',['v'=>'20260803']))?>"></script><script>new QRCode(document.getElementById('qr'),{text:<?=json_encode($url,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>,width:300,height:300,correctLevel:QRCode.CorrectLevel.H});</script></body></html>
