<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ambientes_qr.php';

if (!ambiente_schema_ready($pdo) || !limpeza_schema_ready($pdo)) {
    http_response_code(503);
    die('Módulo de ambientes indisponível até a atualização do banco de dados.');
}
$token = strtolower(trim((string)($_GET['t'] ?? '')));
$ambiente = ambiente_get_by_token($pdo,$token,true);
if (!$ambiente) {
    http_response_code(404);
    die('QR Code inválido, desativado ou substituído.');
}
ambiente_log_access($pdo,(int)$ambiente['id']);
$latestRecords = ambiente_public_records($pdo,(int)$ambiente['id'],max(1,(int)$ambiente['historico_limite']));
$last = $latestRecords[0] ?? null;
$records = !empty($ambiente['exibir_historico']) ? $latestRecords : [];
[$stateCode,$stateLabel,$stateClass] = ambiente_qr_current_state($ambiente,$last);
$next = 'ambiente_registro.php?t=' . rawurlencode($token);
$loginUrl = app_path('login',['next'=>$next]);
if (!is_logged_in()) {
    $actionUrl = $loginUrl;
    $actionLabel = 'Acesso Gestor/Supervisor';
} elseif (limpeza_access_allowed() && usuario_pode('ambientes.registrar')) {
    $actionUrl = app_path('ambiente_registro',['t'=>$token]);
    $actionLabel = 'Registrar limpeza';
} elseif (limpeza_access_allowed() && usuario_pode('ambientes.validar')) {
    $actionUrl = app_path('ambiente_validar');
    $actionLabel = 'Abrir validações';
} else {
    $actionUrl = app_path('index');
    $actionLabel = 'Voltar ao GEOP';
}
?>
<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($ambiente['nome_publico'])?> - GEOP</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="<?=e(app_path('assets/style.css'))?>"><meta name="theme-color" content="#0b2341"><style>
body{background:#f3f6f9}.public-hero{background:linear-gradient(135deg,#0b2341,#174d78);color:#fff}.status-ring{width:94px;height:94px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.13);font-size:2rem}.timeline-item{border-left:4px solid #dfe7ef}.public-photo{width:100%;height:190px;object-fit:cover;border-radius:.7rem}.brand-mark{letter-spacing:.08em}.record-actions{line-height:1.7}
</style></head><body>
<header class="public-hero py-4"><div class="container" style="max-width:900px"><div class="d-flex justify-content-between align-items-center mb-4"><strong class="brand-mark">GEOP · SHOPPING CARPINA</strong><span class="badge bg-light text-dark">Ambiente monitorado</span></div><div class="row align-items-center g-4"><div class="col-md"><h1 class="display-6 fw-bold mb-2"><?=e($ambiente['nome_publico'])?></h1><p class="lead mb-0 opacity-75"><?=e($ambiente['descricao_publica'] ?: $ambiente['local_nome'])?></p></div><div class="col-md-auto text-center"><div class="status-ring mx-auto">✓</div><div class="mt-2 fw-semibold"><?=e($stateLabel)?></div></div></div></div></header>
<main class="container py-4" style="max-width:900px">
  <div class="alert alert-<?=$stateClass?> shadow-sm"><div class="d-flex justify-content-between flex-wrap gap-2"><strong>Status: <?=e($stateLabel)?></strong><?php if($last): ?><span>Última validação: <?=e(date('d/m/Y \à\s H:i',strtotime((string)($last['validado_em'] ?: $last['finalizado_em']))))?></span><?php endif ?></div></div>
  <?php if($last): ?>
  <section class="card shadow-sm mb-4"><div class="card-body p-4"><div class="d-flex justify-content-between flex-wrap gap-2"><div><span class="badge text-bg-success mb-2">Limpeza validada</span><h2 class="h4">Última limpeza realizada</h2></div><?php if($last['numero_os']): ?><span class="badge text-bg-primary align-self-start"><?=e($last['numero_os'])?></span><?php endif ?></div><p class="record-actions mb-2"><?=e($last['acoes'] ?: 'Rotina de limpeza concluída')?></p><?php if($last['observacao_supervisor']): ?><p class="text-muted mb-2"><?=e($last['observacao_supervisor'])?></p><?php endif ?><small class="text-muted">Conferido pela supervisão em <?=e(date('d/m/Y H:i',strtotime((string)$last['validado_em'])))?></small>
  <?php if(!empty($ambiente['exibir_fotos'])): $photos=ambiente_record_photos($pdo,(int)$last['id'],true); if($photos): ?><div class="row g-2 mt-3"><?php foreach(array_slice($photos,0,4) as $photo): ?><div class="col-6 col-md-3"><img class="public-photo" src="<?=e(app_path($photo['arquivo_url']))?>" alt="Evidência validada"></div><?php endforeach ?></div><?php endif; endif ?>
  </div></section>
  <?php endif ?>
  <?php if(!empty($ambiente['exibir_historico'])): ?>
  <section class="mb-4"><h2 class="h4 mb-3">Últimas higienizações</h2><?php foreach($records as $record): ?><article class="card shadow-sm mb-3 timeline-item"><div class="card-body"><div class="d-flex justify-content-between flex-wrap gap-2"><strong><?=e(date('d/m/Y H:i',strtotime((string)($record['validado_em'] ?: $record['finalizado_em']))))?></strong><span class="badge text-bg-success">Conferido</span></div><div class="mt-2"><?=e($record['acoes'] ?: 'Rotina de limpeza')?></div><?php if($record['numero_os']): ?><small class="text-muted d-block mt-1">Serviço vinculado à <?=e($record['numero_os'])?></small><?php endif ?></div></article><?php endforeach ?><?php if(!$records): ?><div class="alert alert-secondary">Ainda não existem limpezas validadas para exibição.</div><?php endif ?></section>
  <?php endif ?>
  <section class="card border-0 shadow-sm"><div class="card-body p-4 text-center"><h2 class="h5">Área restrita da limpeza</h2><p class="text-muted">Somente Gestor, Supervisor de Limpeza e TI podem registrar ou validar serviços.</p><a class="btn btn-primary btn-lg" href="<?=e($actionUrl)?>"><?=e($actionLabel)?></a></div></section>
</main><footer class="text-center text-muted py-4 small">GEOP · Gestão de Ambientes e Limpeza</footer></body></html>
