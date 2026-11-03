<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';

require_role(['TI', 'GESTOR']);
check_csrf();

$user = current_user();

$agentes = $pdo->query("
    SELECT id, nome 
    FROM usuarios 
    WHERE perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS') 
    AND status = 'ATIVO' 
    ORDER BY nome
")->fetchAll();

$locais = $pdo->query("
    SELECT id, nome 
    FROM locais 
    WHERE ativo = 1 
    ORDER BY nome
")->fetchAll();

function inicio_semana($data) {
    $timestamp = strtotime($data);
    $diaSemana = date('N', $timestamp);
    return date('Y-m-d', strtotime('-' . ($diaSemana - 1) . ' days', $timestamp));
}

function fim_semana($data) {
    return date('Y-m-d', strtotime(inicio_semana($data) . ' +6 days'));
}

$semanaBase = $_GET['semana'] ?? date('Y-m-d');
$semanaInicio = inicio_semana($semanaBase);
$semanaFim = fim_semana($semanaBase);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'criar') {
        $agente_id = (int)($_POST['agente_id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $local_id = !empty($_POST['local_id']) ? (int)$_POST['local_id'] : null;
        $dia_semana = (int)($_POST['dia_semana'] ?? 0);
        $hora_inicio = $_POST['hora_inicio'] ?? '';
        $hora_fim = $_POST['hora_fim'] ?? '';
        $semana_inicio = $_POST['semana_inicio'] ?? $semanaInicio;
        $semana_fim = fim_semana($semana_inicio);

        if (
            $agente_id > 0 &&
            $titulo !== '' &&
            $dia_semana >= 1 &&
            $dia_semana <= 7 &&
            $hora_inicio !== '' &&
            $hora_fim !== ''
        ) {
            $st = $pdo->prepare("
                INSERT INTO cronograma_agentes
                (
                    agente_id,
                    gestor_id,
                    titulo,
                    descricao,
                    local_id,
                    dia_semana,
                    hora_inicio,
                    hora_fim,
                    semana_inicio,
                    semana_fim,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PLANEJADO')
            ");

            $st->execute([
                $agente_id,
                $user['id'],
                $titulo,
                $descricao,
                $local_id,
                $dia_semana,
                $hora_inicio,
                $hora_fim,
                inicio_semana($semana_inicio),
                $semana_fim
            ]);
        }

        redirect('cronograma_agentes?semana=' . urlencode($semana_inicio));
    }

    if ($action === 'status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'PLANEJADO';

        $permitidos = ['PLANEJADO', 'EM_ANDAMENTO', 'CONCLUIDO', 'CANCELADO'];

        if ($id > 0 && in_array($status, $permitidos, true)) {
            $st = $pdo->prepare("
                UPDATE cronograma_agentes 
                SET status = ? 
                WHERE id = ?
            ");
            $st->execute([$status, $id]);
        }

        redirect('cronograma_agentes?semana=' . urlencode($semanaInicio));
    }

    if ($action === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $st = $pdo->prepare("
                DELETE FROM cronograma_agentes 
                WHERE id = ?
            ");
            $st->execute([$id]);
        }

        redirect('cronograma_agentes?semana=' . urlencode($semanaInicio));
    }
}

$filtroAgente = (int)($_GET['agente_id'] ?? 0);

$where = [
    'ca.semana_inicio = ?'
];

$params = [
    $semanaInicio
];

if ($filtroAgente > 0) {
    $where[] = 'ca.agente_id = ?';
    $params[] = $filtroAgente;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$st = $pdo->prepare("
    SELECT 
        ca.*,
        a.nome AS agente_nome,
        g.nome AS gestor_nome,
        l.nome AS local_nome
    FROM cronograma_agentes ca
    JOIN usuarios a ON a.id = ca.agente_id
    JOIN usuarios g ON g.id = ca.gestor_id
    LEFT JOIN locais l ON l.id = ca.local_id
    $whereSql
    ORDER BY ca.dia_semana ASC, ca.hora_inicio ASC, a.nome ASC
");
$st->execute($params);
$cronogramas = $st->fetchAll();

$dias = [
    1 => 'Segunda-feira',
    2 => 'Terca-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sabado',
    7 => 'Domingo'
];

$statusLista = [
    'PLANEJADO' => 'Planejado',
    'EM_ANDAMENTO' => 'Em andamento',
    'CONCLUIDO' => 'Concluido',
    'CANCELADO' => 'Cancelado'
];

render_header('Cronograma Semanal dos Agentes');
?>

<style>
.cronograma-card {
    border: 0;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
}

.dia-box {
    border-left: 4px solid #0d6efd;
    background: #f8f9fa;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 12px;
}

.atividade {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    padding: 10px;
    margin-top: 8px;
}

.status-PLANEJADO {
    background: #6c757d;
}

.status-EM_ANDAMENTO {
    background: #0d6efd;
}

.status-CONCLUIDO {
    background: #198754;
}

.status-CANCELADO {
    background: #dc3545;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Cronograma Semanal dos Agentes</h1>
        <small class="text-muted">
            Semana de <?= e(date('d/m/Y', strtotime($semanaInicio))) ?> ate <?= e(date('d/m/Y', strtotime($semanaFim))) ?>
        </small>
    </div>

    <button class="btn btn-outline-secondary" onclick="window.print()">
        Imprimir
    </button>
</div>

<div class="card p-3 mb-3 cronograma-card">
    <form method="get" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Semana</label>
            <input 
                class="form-control" 
                type="date" 
                name="semana" 
                value="<?= e($semanaInicio) ?>"
            >
        </div>

        <div class="col-md-5">
            <label class="form-label">Agente</label>
            <select class="form-select" name="agente_id">
                <option value="">Todos os agentes</option>

                <?php foreach ($agentes as $a): ?>
                    <option value="<?= e($a['id']) ?>" <?= $filtroAgente === (int)$a['id'] ? 'selected' : '' ?>>
                        <?= e($a['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100">
                Filtrar
            </button>

            <a href="<?= e(app_path('cronograma_agentes')) ?>" class="btn btn-outline-secondary w-100">
                Limpar
            </a>
        </div>
    </form>
</div>

<div class="card p-3 mb-3 cronograma-card">
    <h2 class="h5">Nova atividade semanal</h2>

    <form method="post" class="row g-3">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="criar">

        <div class="col-md-4">
            <label class="form-label required">Agente</label>
            <select class="form-select" name="agente_id" required>
                <option value="">Selecione</option>

                <?php foreach ($agentes as $a): ?>
                    <option value="<?= e($a['id']) ?>">
                        <?= e($a['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label required">Titulo da atividade</label>
            <input 
                class="form-control" 
                name="titulo" 
                placeholder="Ex: Ronda preventiva / manutencao / vistoria"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label">Local</label>
            <select class="form-select" name="local_id">
                <option value="">Sem local especifico</option>

                <?php foreach ($locais as $l): ?>
                    <option value="<?= e($l['id']) ?>">
                        <?= e($l['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label required">Dia da semana</label>
            <select class="form-select" name="dia_semana" required>
                <option value="">Selecione</option>

                <?php foreach ($dias as $num => $nome): ?>
                    <option value="<?= e($num) ?>">
                        <?= e($nome) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label required">Hora inicio</label>
            <input 
                class="form-control" 
                type="time" 
                name="hora_inicio" 
                required
            >
        </div>

        <div class="col-md-3">
            <label class="form-label required">Hora fim</label>
            <input 
                class="form-control" 
                type="time" 
                name="hora_fim" 
                required
            >
        </div>

        <div class="col-md-3">
            <label class="form-label required">Semana de referencia</label>
            <input 
                class="form-control" 
                type="date" 
                name="semana_inicio" 
                value="<?= e($semanaInicio) ?>" 
                required
            >
        </div>

        <div class="col-12">
            <label class="form-label">Descricao / orientacoes</label>
            <textarea 
                class="form-control" 
                name="descricao" 
                rows="3"
                placeholder="Detalhe o que o agente deve realizar nessa atividade."
            ></textarea>
        </div>

        <div class="col-12">
            <button class="btn btn-success">
                Adicionar ao cronograma
            </button>
        </div>
    </form>
</div>

<div class="card p-3 cronograma-card">
    <h2 class="h5 mb-3">Cronograma da semana</h2>

    <?php foreach ($dias as $diaNumero => $diaNome): ?>
        <div class="dia-box">
            <h3 class="h6 mb-2"><?= e($diaNome) ?></h3>

            <?php
            $temAtividade = false;
            foreach ($cronogramas as $c):
                if ((int)$c['dia_semana'] !== $diaNumero) {
                    continue;
                }

                $temAtividade = true;
            ?>
                <div class="atividade">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= e($c['titulo']) ?></strong>
                            <br>

                            <small>
                                <?= e(substr($c['hora_inicio'], 0, 5)) ?> ate <?= e(substr($c['hora_fim'], 0, 5)) ?>
                                |
                                Agente: <?= e($c['agente_nome']) ?>
                                |
                                Local: <?= e($c['local_nome'] ?: '-') ?>
                            </small>

                            <?php if (!empty($c['descricao'])): ?>
                                <div class="mt-2">
                                    <?= nl2br(e($c['descricao'])) ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-2">
                                <span class="badge status-<?= e($c['status']) ?>">
                                    <?= e($statusLista[$c['status']] ?? $c['status']) ?>
                                </span>

                                <small class="text-muted">
                                    Criado por <?= e($c['gestor_nome']) ?> em <?= e($c['criado_em']) ?>
                                </small>
                            </div>
                        </div>

                        <div class="d-flex gap-1">
                            <form method="post">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="id" value="<?= e($c['id']) ?>">

                                <select class="form-select form-select-sm" name="status" onchange="this.form.submit()">
                                    <?php foreach ($statusLista as $valor => $label): ?>
                                        <option value="<?= e($valor) ?>" <?= $c['status'] === $valor ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>

                            <form method="post" onsubmit="return confirm('Deseja excluir esta atividade do cronograma?')">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="action" value="excluir">
                                <input type="hidden" name="id" value="<?= e($c['id']) ?>">

                                <button class="btn btn-sm btn-outline-danger">
                                    Excluir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$temAtividade): ?>
                <div class="text-muted small">
                    Nenhuma atividade cadastrada para este dia.
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php render_footer(); ?>
