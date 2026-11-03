<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';

require_role(['TI', 'GESTOR']);
check_csrf();
ensure_operational_schema($pdo);
gestao_ensure_schema($pdo);

$user = current_user();

function preventiva_tem_os_aberta(PDO $pdo, array $prev): bool {
    $preventivaId = (int)($prev['id'] ?? 0);
    $agenteId = !empty($prev['agente_id']) ? (int)$prev['agente_id'] : 0;

    if ($preventivaId <= 0 || $agenteId <= 0) {
        return false;
    }

    try {
        $ordemAtivaSql = exclusao_sql_nao_excluida($pdo, 'ordens_servico');
        if (app_column_exists($pdo, 'ordens_servico', 'preventiva_id')) {
            $st = $pdo->prepare("\n                SELECT COUNT(*)\n                FROM ordens_servico\n                WHERE preventiva_id = ?\n                AND agente_id = ?\n                AND $ordemAtivaSql\n                AND status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')\n            ");
            $st->execute([$preventivaId, $agenteId]);
            return (int)$st->fetchColumn() > 0;
        }

        $titulo = '[PREVENTIVA] ' . ($prev['titulo'] ?? '');
        $st = $pdo->prepare("\n            SELECT COUNT(*)\n            FROM ordens_servico\n            WHERE titulo = ?\n            AND agente_id = ?\n            AND $ordemAtivaSql\n            AND status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')\n        ");
        $st->execute([$titulo, $agenteId]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function carregar_preventiva(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("SELECT * FROM manutencoes_preventivas WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $prev = $st->fetch();
    return $prev ?: null;
}

function atualizar_proxima_execucao_preventiva(PDO $pdo, array $prev): void {
    $id = (int)($prev['id'] ?? 0);
    if ($id <= 0) {
        return;
    }

    $intervalo = max(1, (int)($prev['intervalo_dias'] ?? 30));

    $st = $pdo->prepare("\n        UPDATE manutencoes_preventivas\n        SET ultimo_gerado_em = NOW(),\n            proxima_execucao = DATE_ADD(CURDATE(), INTERVAL ? DAY),\n            atualizado_em = NOW()\n        WHERE id = ?\n    ");
    $st->execute([$intervalo, $id]);
}

function gerar_os_preventiva(PDO $pdo, array $prev, array $user, bool $evitarDuplicada = true): int {
    if ($evitarDuplicada && preventiva_tem_os_aberta($pdo, $prev)) {
        return 0;
    }

    $numero = gerar_numero_os($pdo);
    $prioridade = $prev['prioridade'] ?: 'MEDIA';
    $status = !empty($prev['agente_id']) ? 'ATRIBUIDA' : 'ABERTA';
    $prazo = calcular_vencimento_sla($prioridade);

    $titulo = '[PREVENTIVA] ' . $prev['titulo'];
    $descricao = "Manutencao preventiva gerada automaticamente.\n\n" . ($prev['descricao'] ?? '');

    $columns = [
        'numero_os','titulo','descricao','local_id','categoria_id','prioridade','status',
        'solicitante_id','gestor_id','agente_id','prazo'
    ];
    $values = [
        $numero,$titulo,$descricao,$prev['local_id'],$prev['categoria_id'],$prioridade,$status,
        $user['id'],$user['id'],$prev['agente_id'] ?: null,$prazo
    ];

    if (app_column_exists($pdo, 'ordens_servico', 'sla_horas')) {
        $columns[] = 'sla_horas';
        $values[] = sla_horas_por_prioridade($prioridade);
    }

    if (app_column_exists($pdo, 'ordens_servico', 'sla_vencimento')) {
        $columns[] = 'sla_vencimento';
        $values[] = $prazo;
    }

    if (app_column_exists($pdo, 'ordens_servico', 'origem')) {
        $columns[] = 'origem';
        $values[] = 'PREVENTIVA';
    }

    if (app_column_exists($pdo, 'ordens_servico', 'preventiva_id')) {
        $columns[] = 'preventiva_id';
        $values[] = $prev['id'];
    }

    $st = $pdo->prepare("INSERT INTO ordens_servico (" . implode(',', $columns) . ") VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")");
    $st->execute($values);
    $osId = (int)$pdo->lastInsertId();

    registrar_historico($pdo, $osId, $user['id'], null, $status, 'OS gerada automaticamente por manutencao preventiva atribuida a agente.');

    if (function_exists('auditoria_log')) {
        auditoria_log($pdo, 'ordem_servico', $osId, 'GERAR_PREVENTIVA', 'preventiva_id', null, $prev['id'], $user['id']);
        auditoria_log($pdo, 'ordem_servico', $osId, 'ATRIBUIR_AGENTE_PREVENTIVA', 'agente_id', null, $prev['agente_id'] ?: null, $user['id']);
    }

    if (!empty($prev['agente_id'])) {
        whatsapp_queue_user($pdo, $prev['agente_id'], mensagem_whatsapp_os('Manutencao preventiva atribuida para voce', [
            'id' => $osId,
            'numero_os' => $numero,
            'titulo' => $titulo,
            'status' => $status,
            'prioridade' => $prioridade,
            'prazo' => $prazo,
            'sla_vencimento' => $prazo,
        ]), 'Preventiva');
    }

    return $osId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'salvar') {
        $id = (int)($_POST['id'] ?? 0);
        $agenteIdInformado = !empty($_POST['agente_id']) ? (int)$_POST['agente_id'] : null;
        $ativoInformado = isset($_POST['ativo']) ? 1 : 0;
        $tipoGatilho = ($_POST['tipo_gatilho'] ?? 'CALENDARIO') === 'MEDIDOR' ? 'MEDIDOR' : 'CALENDARIO';
        $ativoId = !empty($_POST['ativo_id']) ? (int)$_POST['ativo_id'] : null;
        $medidorTipo = $tipoGatilho === 'MEDIDOR' ? trim((string)($_POST['medidor_tipo'] ?? 'HORAS')) : null;
        $medidorLimite = $tipoGatilho === 'MEDIDOR' ? max(1, (float)($_POST['medidor_limite'] ?? 1)) : null;

        $dados = [
            trim($_POST['titulo'] ?? ''),
            trim($_POST['descricao'] ?? ''),
            (int)($_POST['local_id'] ?? 0),
            (int)($_POST['categoria_id'] ?? 0),
            $agenteIdInformado,
            $_POST['prioridade'] ?? 'MEDIA',
            max(1, (int)($_POST['intervalo_dias'] ?? 30)),
            $_POST['proxima_execucao'] ?? date('Y-m-d'),
            $ativoInformado,
        ];

        if ($dados[0] !== '' && $dados[2] > 0 && $dados[3] > 0) {
            try {
                $pdo->beginTransaction();

                $preventivaAntes = null;
                if ($id > 0) {
                    $preventivaAntes = carregar_preventiva($pdo, $id);

                    $st = $pdo->prepare("\n                        UPDATE manutencoes_preventivas\n                        SET titulo = ?, descricao = ?, local_id = ?, categoria_id = ?, agente_id = ?,\n                            prioridade = ?, intervalo_dias = ?, proxima_execucao = ?, ativo = ?,\n                            tipo_gatilho = ?, ativo_id = ?, medidor_tipo = ?, medidor_limite = ?, atualizado_em = NOW()\n                        WHERE id = ?\n                    ");
                    $st->execute(array_merge($dados, [$tipoGatilho, $ativoId, $medidorTipo, $medidorLimite, $id]));

                    if (function_exists('auditoria_log')) {
                        auditoria_log($pdo, 'manutencoes_preventivas', $id, 'EDITAR_PREVENTIVA', null, null, $dados[0], $user['id']);
                    }
                } else {
                    $st = $pdo->prepare("\n                        INSERT INTO manutencoes_preventivas\n                        (titulo, descricao, local_id, categoria_id, agente_id, prioridade, intervalo_dias, proxima_execucao, ativo,\n                         tipo_gatilho, ativo_id, medidor_tipo, medidor_limite, criado_por)\n                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n                    ");
                    $st->execute(array_merge($dados, [$tipoGatilho, $ativoId, $medidorTipo, $medidorLimite, $user['id']]));
                    $id = (int)$pdo->lastInsertId();

                    if (function_exists('auditoria_log')) {
                        auditoria_log($pdo, 'manutencoes_preventivas', $id, 'CRIAR_PREVENTIVA', null, null, $dados[0], $user['id']);
                    }
                }

                $preventivaDepois = carregar_preventiva($pdo, $id);

                $agenteAntes = $preventivaAntes ? (int)($preventivaAntes['agente_id'] ?? 0) : 0;
                $agenteDepois = $preventivaDepois ? (int)($preventivaDepois['agente_id'] ?? 0) : 0;
                $preventivaAtiva = $preventivaDepois && !empty($preventivaDepois['ativo']);

                $deveGerarOsAgora = $preventivaAtiva && $agenteDepois > 0 && ($agenteAntes !== $agenteDepois || $preventivaAntes === null);

                if ($deveGerarOsAgora) {
                    $osGeradaId = gerar_os_preventiva($pdo, $preventivaDepois, $user, true);

                    if ($osGeradaId > 0) {
                        atualizar_proxima_execucao_preventiva($pdo, $preventivaDepois);

                        if (function_exists('auditoria_log')) {
                            auditoria_log($pdo, 'manutencoes_preventivas', $id, 'GERAR_OS_AO_ATRIBUIR_AGENTE', 'ordem_servico_id', null, $osGeradaId, $user['id']);
                        }
                    }
                }

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }

        redirect('preventivas');
    }

    if ($action === 'gerar_vencidas') {
        $st = $pdo->query("\n            SELECT *\n            FROM manutencoes_preventivas\n            WHERE ativo = 1\n            AND proxima_execucao <= CURDATE()\n            ORDER BY proxima_execucao ASC\n        ");

        foreach ($st->fetchAll() as $prev) {
            $osGeradaId = gerar_os_preventiva($pdo, $prev, $user, true);

            if ($osGeradaId > 0) {
                $pdo->prepare("\n                    UPDATE manutencoes_preventivas\n                    SET ultimo_gerado_em = NOW(),\n                        proxima_execucao = DATE_ADD(proxima_execucao, INTERVAL intervalo_dias DAY),\n                        atualizado_em = NOW()\n                    WHERE id = ?\n                ")->execute([$prev['id']]);
            }
        }

        redirect('preventivas');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM manutencoes_preventivas WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$locais = $pdo->query("SELECT id, nome FROM locais WHERE ativo = 1 ORDER BY nome")->fetchAll();
$categorias = $pdo->query("SELECT id, nome FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
$agentes = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS') AND status = 'ATIVO' ORDER BY nome")->fetchAll();
$ativos = app_table_exists($pdo, 'ativos')
    ? $pdo->query("SELECT id,codigo_patrimonial,CONCAT_WS(' ',marca,modelo) descricao FROM ativos WHERE ativo=1 ORDER BY codigo_patrimonial")->fetchAll()
    : [];

$preventivas = $pdo->query("\n    SELECT p.*, l.nome AS local_nome, c.nome AS categoria_nome, a.nome AS agente_nome\n    FROM manutencoes_preventivas p\n    JOIN locais l ON l.id = p.local_id\n    JOIN categorias c ON c.id = p.categoria_id\n    LEFT JOIN usuarios a ON a.id = p.agente_id\n    ORDER BY p.ativo DESC, p.proxima_execucao ASC\n")->fetchAll();

$vencidas = array_filter($preventivas, fn($p) => !empty($p['ativo']) && $p['proxima_execucao'] <= date('Y-m-d'));

render_header('Preventivas');
render_whatsapp_queue();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Manutencao preventiva</h1>
        <small class="text-muted"><?= count($vencidas) ?> preventiva(s) prontas para gerar OS.</small>
    </div>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="gerar_vencidas">
        <button class="btn btn-success" <?= !$vencidas ? 'disabled' : '' ?>>Gerar OS vencidas</button>
    </form>
</div>

<div class="alert alert-info">
    Ao salvar uma preventiva ativa com agente definido, o sistema gera automaticamente uma OS atribuida para esse agente. Se ja existir uma OS aberta para a mesma preventiva e agente, uma nova OS nao sera duplicada.
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h2 class="h5"><?= $edit ? 'Editar preventiva' : 'Nova preventiva' ?></h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="salvar">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">

                <div class="col-12">
                    <label class="form-label">Titulo</label>
                    <input class="form-control" name="titulo" value="<?= e($edit['titulo'] ?? '') ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Descricao</label>
                    <textarea class="form-control" name="descricao" rows="3"><?= e($edit['descricao'] ?? '') ?></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Local</label>
                    <select class="form-select" name="local_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($locais as $l): ?>
                            <option value="<?= e($l['id']) ?>" <?= (int)($edit['local_id'] ?? 0) === (int)$l['id'] ? 'selected' : '' ?>><?= e($l['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Categoria</label>
                    <select class="form-select" name="categoria_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= e($c['id']) ?>" <?= (int)($edit['categoria_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Agente</label>
                    <select class="form-select" name="agente_id">
                        <option value="">Definir depois</option>
                        <?php foreach ($agentes as $a): ?>
                            <option value="<?= e($a['id']) ?>" <?= (int)($edit['agente_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Ao definir ou trocar o agente, sera gerada uma OS automaticamente.</small>
                </div>

                <div class="col-6">
                    <label class="form-label">Prioridade</label>
                    <select class="form-select" name="prioridade">
                        <?php foreach (['BAIXA','MEDIA','ALTA','CRITICA'] as $p): ?>
                            <option value="<?= e($p) ?>" <?= ($edit['prioridade'] ?? 'MEDIA') === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6">
                    <label class="form-label">Intervalo dias</label>
                    <input class="form-control" type="number" min="1" name="intervalo_dias" value="<?= e($edit['intervalo_dias'] ?? 30) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Gatilho da recorrência</label>
                    <select class="form-select" name="tipo_gatilho" id="tipo_gatilho">
                        <option value="CALENDARIO" <?= ($edit['tipo_gatilho'] ?? 'CALENDARIO') === 'CALENDARIO' ? 'selected' : '' ?>>Calendário</option>
                        <option value="MEDIDOR" <?= ($edit['tipo_gatilho'] ?? '') === 'MEDIDOR' ? 'selected' : '' ?>>Medidor</option>
                    </select>
                </div>
                <div class="col-12 medidor-config">
                    <label class="form-label">Equipamento</label>
                    <select class="form-select" name="ativo_id">
                        <option value="">Selecione</option>
                        <?php foreach ($ativos as $ativo): ?>
                            <option value="<?= e($ativo['id']) ?>" <?= (int)($edit['ativo_id'] ?? 0) === (int)$ativo['id'] ? 'selected' : '' ?>><?= e($ativo['codigo_patrimonial'] . ' — ' . $ativo['descricao']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 medidor-config"><label class="form-label">Tipo de medidor</label><input class="form-control" name="medidor_tipo" value="<?= e($edit['medidor_tipo'] ?? 'HORAS') ?>" placeholder="HORAS, KM, CICLOS"></div>
                <div class="col-6 medidor-config"><label class="form-label">Gerar ao atingir</label><input class="form-control" type="number" step="0.01" min="1" name="medidor_limite" value="<?= e($edit['medidor_limite'] ?? '') ?>"></div>

                <div class="col-12">
                    <label class="form-label">Proxima execucao</label>
                    <input class="form-control" type="date" name="proxima_execucao" value="<?= e($edit['proxima_execucao'] ?? date('Y-m-d')) ?>">
                </div>

                <div class="col-12 form-check ms-2">
                    <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= ($edit ? !empty($edit['ativo']) : true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ativo">Ativa</label>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary w-100">Salvar preventiva</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-3">
            <h2 class="h5">Plano preventivo</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Titulo</th>
                            <th>Local</th>
                            <th>Agente</th>
                            <th>Proxima</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preventivas as $p): ?>
                            <?php $vencida = !empty($p['ativo']) && $p['proxima_execucao'] <= date('Y-m-d'); ?>
                            <tr>
                                <td>
                                    <strong><?= e($p['titulo']) ?></strong><br>
                                    <small class="text-muted"><?= e($p['categoria_nome']) ?> | a cada <?= e($p['intervalo_dias']) ?> dias</small>
                                </td>
                                <td><?= e($p['local_nome']) ?></td>
                                <td><?= e($p['agente_nome'] ?: '-') ?></td>
                                <td><?= e($p['proxima_execucao']) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= !$p['ativo'] ? 'secondary' : ($vencida ? 'danger' : 'success') ?>">
                                        <?= !$p['ativo'] ? 'Inativa' : ($vencida ? 'Gerar OS' : 'Programada') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('preventivas', ['edit' => $p['id']])) ?>">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$preventivas): ?>
                            <tr><td colspan="6" class="text-center text-muted">Nenhuma preventiva cadastrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const tipo = document.getElementById('tipo_gatilho');
    const atualizar = () => document.querySelectorAll('.medidor-config').forEach(el => el.classList.toggle('d-none', tipo.value !== 'MEDIDOR'));
    tipo?.addEventListener('change', atualizar);
    if (tipo) atualizar();
})();
</script>
<?php render_footer(); ?>
