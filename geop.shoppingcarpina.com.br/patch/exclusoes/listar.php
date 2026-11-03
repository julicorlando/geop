<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

geopRequireLogin();

if (!geopIsGestor() && !geopIsTI()) {
    http_response_code(403);
    exit('Sem permissão.');
}

$uid = geopCurrentUserId();
$csrf = geopCsrfToken();

$st = $pdo->query(
    "SELECT s.*
     FROM ordem_solicitacoes_exclusao s
     ORDER BY
       CASE s.status
         WHEN 'PENDENTE' THEN 1
         WHEN 'APROVADA' THEN 2
         WHEN 'REPROVADA' THEN 3
         WHEN 'EXECUTADA' THEN 4
         ELSE 5
       END,
       s.criado_em DESC"
);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

function h(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GEOP — Solicitações de exclusão</title>
<style>
body{font-family:Arial,sans-serif;margin:24px;background:#f5f6f8;color:#202124}
.card{background:#fff;border:1px solid #ddd;border-radius:10px;padding:18px;overflow:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;vertical-align:top}
.badge{padding:4px 8px;border-radius:12px;background:#eee;font-size:12px}
form{display:inline-block;margin:2px}
button{padding:7px 10px;cursor:pointer}
small{color:#666}
</style>
</head>
<body>
<h1>Solicitações de exclusão</h1>
<p><small>Gestor solicita. Outro Gestor/TI analisa. Apenas TI executa a exclusão.</small></p>
<div class="card">
<table>
<thead>
<tr>
<th>ID</th><th>Ordem</th><th>Solicitante</th><th>Motivo</th><th>Status</th><th>Criada em</th><th>Ações</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= (int)$r['id'] ?></td>
<td><?= h($r['ordem_tipo']) ?> #<?= (int)$r['ordem_id'] ?></td>
<td>#<?= (int)$r['solicitado_por'] ?></td>
<td><?= nl2br(h((string)$r['motivo'])) ?></td>
<td><span class="badge"><?= h((string)$r['status']) ?></span></td>
<td><?= h((string)$r['criado_em']) ?></td>
<td>
<?php if ($r['status'] === 'PENDENTE' && (int)$r['solicitado_por'] !== $uid): ?>
<form method="post" action="analisar.php">
<input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
<input type="hidden" name="solicitacao_id" value="<?= (int)$r['id'] ?>">
<input type="hidden" name="decisao" value="APROVAR">
<button type="submit">Aprovar</button>
</form>
<form method="post" action="analisar.php">
<input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
<input type="hidden" name="solicitacao_id" value="<?= (int)$r['id'] ?>">
<input type="hidden" name="decisao" value="REPROVAR">
<button type="submit">Reprovar</button>
</form>
<?php endif; ?>

<?php if (geopIsTI() && $r['status'] === 'APROVADA'): ?>
<form method="post" action="executar.php" onsubmit="return confirm('Enviar esta ordem para a lixeira?');">
<input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
<input type="hidden" name="solicitacao_id" value="<?= (int)$r['id'] ?>">
<button type="submit">Executar exclusão</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body>
</html>
