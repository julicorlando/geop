<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';

require_role(['AGENTE', 'COORDENADOR_OPERACOES_OBRAS']);
check_csrf();
ensure_operational_schema($pdo);

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $osId = (int)($_POST['os_id'] ?? 0);

    $ordemAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico');
    $stOs = $pdo->prepare("SELECT * FROM ordens_servico WHERE id = ? AND agente_id = ? AND $ordemAtivaSql LIMIT 1");
    $stOs->execute([$osId, $user['id']]);
    $os = $stOs->fetch();

    if ($os && $os['status'] !== 'ENCERRADA') {
        if ($action === 'status') {
            $novo = $_POST['status'] ?? '';
            $permitidos = ['EM_ANDAMENTO', 'PAUSADA', 'AGUARDANDO_MATERIAL', 'CONCLUIDA_PELO_AGENTE'];

            if (in_array($novo, $permitidos, true)) {
                $sql = "UPDATE ordens_servico SET status = ?";
                $params = [$novo];

                if ($novo === 'EM_ANDAMENTO') {
                    $sql .= ", data_inicio = COALESCE(data_inicio, NOW())";
                }

                if ($novo === 'CONCLUIDA_PELO_AGENTE') {
                    $sql .= ", data_conclusao = NOW()";
                }

                $sql .= " WHERE id = ?";
                $params[] = $osId;
                $pdo->prepare($sql)->execute($params);

                registrar_historico($pdo, $osId, $user['id'], $os['status'], $novo, $_POST['obs'] ?? 'Atualizacao pelo agente.');
                auditoria_log($pdo, 'ordem_servico', $osId, 'ALTERAR_STATUS_MOBILE', 'status', $os['status'], $novo, $user['id']);

                $osNotificacao = array_merge($os, ['status' => $novo]);
                whatsapp_queue_user($pdo, $os['gestor_id'] ?? null, mensagem_whatsapp_os('Status atualizado pelo agente', $osNotificacao), 'Avisar gestor');
            }
        }

        if ($action === 'foto' && isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($ext, $permitidos, true)) {
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $nome = 'os_' . $osId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadDir . '/' . $nome;

                if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
                    $url = 'uploads/' . $nome;
                    $tipo = $_POST['tipo'] ?? 'FOTO_DEPOIS';

                    $st = $pdo->prepare("
                        INSERT INTO anexos_os (ordem_servico_id, usuario_id, tipo, arquivo_url, descricao)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $st->execute([$osId, $user['id'], $tipo, $url, $_POST['descricao'] ?? 'Foto enviada pelo agente']);

                    registrar_historico($pdo, $osId, $user['id'], $os['status'], $os['status'], 'Foto enviada pelo painel mobile.');
                    auditoria_log($pdo, 'ordem_servico', $osId, 'ANEXO_MOBILE', 'arquivo', null, $url, $user['id']);
                }
            }
        }
    }

    redirect('agente');
}

$slaPrazoSql = sla_sql_prazo($pdo, 'os');
$ordemAtivaListaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico','os');
$st = $pdo->prepare("
    SELECT
        os.*,
        l.nome AS local_nome,
        c.nome AS categoria_nome
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    JOIN categorias c ON c.id = os.categoria_id
    WHERE os.agente_id = ?
    AND $ordemAtivaListaSql
    AND os.status NOT IN ('ENCERRADA', 'REPROVADA')
    ORDER BY
        CASE
            WHEN $slaPrazoSql IS NOT NULL
             AND $slaPrazoSql < NOW()
             AND os.status NOT IN ('CONCLUIDA_PELO_AGENTE')
            THEN 0 ELSE 1 END,
        $slaPrazoSql ASC,
        os.criado_em DESC
");
$st->execute([$user['id']]);
$ordens = $st->fetchAll();

render_header('Minha rota');
render_whatsapp_queue();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Minha rota</h1>
        <small class="text-muted">OS atribuidas para <?= e($user['nome']) ?></small>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(app_path('ordens')) ?>">Lista</a>
</div>

<div class="mobile-stack">
    <?php foreach ($ordens as $os): ?>
        <?php $sla = sla_info($os); ?>
        <div class="card p-3 agent-card sla-strip sla-<?= e($sla['classe']) ?>">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <strong><?= e($os['numero_os']) ?></strong>
                    <div><?= e($os['titulo']) ?></div>
                </div>
                <?= badge_sla($os) ?>
            </div>

            <div class="small text-muted mb-2">
                <?= e($os['local_nome']) ?> | <?= e($os['categoria_nome']) ?> | <?= badge_prioridade($os['prioridade']) ?>
            </div>

            <p class="mb-3"><?= nl2br(e($os['descricao'])) ?></p>

            <form method="post" class="row g-2 mb-3">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="os_id" value="<?= e($os['id']) ?>">

                <div class="col-12">
                    <select class="form-select touch-target" name="status">
                        <?php foreach (['EM_ANDAMENTO','PAUSADA','AGUARDANDO_MATERIAL','CONCLUIDA_PELO_AGENTE'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= $os['status'] === $status ? 'selected' : '' ?>><?= e(str_replace('_', ' ', $status)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <textarea class="form-control" name="obs" rows="2" placeholder="Observacao"></textarea>
                </div>

                <div class="col-12">
                    <button class="btn btn-success w-100 touch-target">Atualizar status</button>
                </div>
            </form>

            <form method="post" enctype="multipart/form-data" class="row g-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="foto">
                <input type="hidden" name="os_id" value="<?= e($os['id']) ?>">

                <div class="col-5">
                    <select class="form-select touch-target" name="tipo">
                        <option>FOTO_ANTES</option>
                        <option selected>FOTO_DEPOIS</option>
                    </select>
                </div>

                <div class="col-7">
                    <input class="form-control touch-target" type="file" name="arquivo" accept="image/*" capture="environment" required>
                </div>

                <div class="col-12">
                    <input class="form-control" name="descricao" placeholder="Descricao da foto">
                </div>

                <div class="col-12 d-grid gap-2">
                    <button class="btn btn-dark touch-target">Anexar foto</button>
                    <a class="btn btn-outline-primary touch-target" href="<?= e(app_path('ordem_detalhe', ['id' => $os['id']])) ?>">Abrir detalhes</a>
                </div>
            </form>
        </div>
    <?php endforeach; ?>

    <?php if (!$ordens): ?>
        <div class="card p-4 text-center text-muted">
            Nenhuma OS atribuida para voce no momento.
        </div>
    <?php endif; ?>
</div>

<?php render_footer(); ?>
