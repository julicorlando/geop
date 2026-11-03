<?php
if (getenv('APP_DEBUG') === '1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';

require_login();
ensure_operational_schema($pdo);

$user = current_user();

if (($user['perfil'] ?? '') === 'CFTV') {
    redirect('cftv');
}
if (($user['perfil'] ?? '') === 'ASG') {
    http_response_code(403);
    die('Este perfil não possui acesso ao módulo Limpeza. Procure o Gestor ou Supervisor.');
}
if (($user['perfil'] ?? '') === 'SUPERVISOR_ASG') {
    redirect('limpeza');
}

if (in_array($user['perfil'] ?? '', ['DIRETOR_EXECUTIVO', 'COORDENADOR_OPERACOES_OBRAS'], true)) {
    redirect('ordens');
}

if (!function_exists('comeca_com')) {
    function comeca_com($texto, $inicio) {
        return substr((string)$texto, 0, strlen($inicio)) === $inicio;
    }
}

function table_exists_dashboard($pdo, $table) {
    try {
        $st = $pdo->prepare("SHOW TABLES LIKE ?");
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    } catch (Exception $e) {
        return false;
    }
}

function fetchOneDashboard($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch();
}

function fetchAllDashboard($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function render_ti_dashboard(PDO $pdo, array $user): void {
    $slaPrazoSql = sla_sql_prazo($pdo, 'os');
    $ordemAtivaSql = exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os');

    $osSemAgente = fetchAllDashboard($pdo, "
        SELECT
            os.*,
            l.nome AS local_nome,
            c.nome AS categoria_nome,
            g.nome AS gestor_nome
        FROM ordens_servico os
        LEFT JOIN locais l ON l.id = os.local_id
        LEFT JOIN categorias c ON c.id = os.categoria_id
        LEFT JOIN usuarios g ON g.id = os.gestor_id
        WHERE $ordemAtivaSql
          AND (os.agente_id IS NULL OR os.agente_id = 0 OR os.agente_id = '')
          AND UPPER(COALESCE(os.status,'')) NOT IN ('ENCERRADA','CANCELADA','CONCLUIDA','CONCLUIDA_PELO_AGENTE','REPROVADA')
        ORDER BY os.criado_em DESC
        LIMIT 12
    ");

    $tokensPendentes = function_exists('os_alertas_token_ti') ? os_alertas_token_ti($pdo) : [];

    $osCriticasSla = fetchAllDashboard($pdo, "
        SELECT
            os.*,
            COALESCE(a.nome, 'Sem agente') AS agente_nome,
            l.nome AS local_nome
        FROM ordens_servico os
        LEFT JOIN usuarios a ON a.id = os.agente_id
        LEFT JOIN locais l ON l.id = os.local_id
        WHERE $ordemAtivaSql
          AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','CANCELADA')
          AND (
              os.prioridade = 'CRITICA'
              OR $slaPrazoSql < NOW()
              OR $slaPrazoSql <= DATE_ADD(NOW(), INTERVAL 6 HOUR)
          )
        ORDER BY
            CASE WHEN $slaPrazoSql < NOW() THEN 0 ELSE 1 END,
            $slaPrazoSql ASC,
            os.criado_em DESC
        LIMIT 12
    ");

    $totalSemAgente = count($osSemAgente);
    $totalTokens = count($tokensPendentes);
    $totalCriticas = count($osCriticasSla);
    $totalAmbientesPendentes = 0;
    if (table_exists_dashboard($pdo, 'ambiente_registros')) {
        $ambientePendente = fetchOneDashboard($pdo, "SELECT COUNT(*) total FROM ambiente_registros WHERE status='AGUARDANDO_VALIDACAO'");
        $totalAmbientesPendentes = (int)($ambientePendente['total'] ?? 0);
    }

    render_header('Painel do TI');
    ?>
    <style>
        .ti-hero{background:linear-gradient(135deg,#0b2341,#164b7a);color:#fff;border-radius:22px;padding:24px;box-shadow:0 18px 45px rgba(11,35,65,.18)}
        .ti-card{border:0;border-radius:18px;box-shadow:0 10px 30px rgba(15,23,42,.08)}
        .ti-metric{font-size:32px;font-weight:900;line-height:1}.ti-action{text-decoration:none;color:inherit}.ti-action .card{transition:.2s}.ti-action .card:hover{transform:translateY(-2px);box-shadow:0 14px 34px rgba(15,23,42,.12)}
        .ti-table td,.ti-table th{vertical-align:middle}.ti-badge-soft{background:#eef2ff;color:#1e3a8a;border-radius:999px;padding:4px 10px;font-size:12px;font-weight:700}
    </style>

    <div class="ti-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="small opacity-75">GEOP Shopping Carpina</div>
                <h1 class="h3 mb-1 text-white">Painel exclusivo do TI</h1>
                <div class="opacity-75">Fila operacional, tokens de reabertura e SLA crítico em um único lugar.</div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-light" href="<?= e(app_path('ordem_form')) ?>">+ Nova OS</a>
                <a class="btn btn-outline-light" href="<?= e(app_path('relatorios')) ?>">Relatórios</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3"><div class="card ti-card p-3 border-start border-warning border-5"><div class="ti-metric"><?= e($totalSemAgente) ?></div><div class="text-muted">OS abertas sem agente</div></div></div>
        <div class="col-md-6 col-xl-3"><div class="card ti-card p-3 border-start border-danger border-5"><div class="ti-metric text-danger"><?= e($totalTokens) ?></div><div class="text-muted">Solicitações de token pendentes</div></div></div>
        <div class="col-md-6 col-xl-3"><div class="card ti-card p-3 border-start border-primary border-5"><div class="ti-metric text-primary"><?= e($totalCriticas) ?></div><div class="text-muted">OS críticas / SLA vencido</div></div></div>
        <div class="col-md-6 col-xl-3"><a class="ti-action" href="<?= e(app_path('limpeza')) ?>"><div class="card ti-card p-3 border-start border-success border-5"><div class="ti-metric text-success"><?= e($totalAmbientesPendentes) ?></div><div class="text-muted">Ambientes aguardando validação</div></div></a></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><a class="ti-action" href="<?= e(app_path('ordem_form')) ?>"><div class="card ti-card p-3"><strong>Nova OS</strong><small class="text-muted">Abrir ordem de serviço</small></div></a></div>
        <div class="col-md-3"><a class="ti-action" href="<?= e(app_path('relatorios')) ?>"><div class="card ti-card p-3"><strong>Relatórios</strong><small class="text-muted">Indicadores e impressão</small></div></a></div>
        <div class="col-md-3"><a class="ti-action" href="<?= e(app_path('auditoria')) ?>"><div class="card ti-card p-3"><strong>Auditoria</strong><small class="text-muted">Rastrear alterações</small></div></a></div>
        <div class="col-md-3"><a class="ti-action" href="<?= e(app_path('ambientes')) ?>"><div class="card ti-card p-3"><strong>Módulo Limpeza</strong><small class="text-muted">Cronograma, equipe, ambientes e validações</small></div></a></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-6">
            <div class="card ti-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">OS abertas sem agente</h2>
                    <span class="ti-badge-soft"><?= e($totalSemAgente) ?> pendente(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover ti-table">
                        <thead><tr><th>OS</th><th>Título</th><th>Gestor</th><th>Local</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($osSemAgente as $osItem): ?>
                            <tr>
                                <td><?= e($osItem['numero_os'] ?? ('#'.$osItem['id'])) ?></td>
                                <td><?= e($osItem['titulo'] ?? '-') ?><br><small class="text-muted"><?= badge_prioridade($osItem['prioridade'] ?? '') ?></small></td>
                                <td><?= e($osItem['gestor_nome'] ?? '-') ?></td>
                                <td><?= e($osItem['local_nome'] ?? '-') ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-warning" href="<?= e(app_path('ordem_detalhe', ['id'=>$osItem['id']])) ?>#atribuir-agente">Atribuir</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$osSemAgente): ?><tr><td colspan="5" class="text-center text-muted">Nenhuma OS sem agente.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card ti-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Solicitações de token pendentes</h2>
                    <span class="ti-badge-soft"><?= e($totalTokens) ?> solicitação(ões)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover ti-table">
                        <thead><tr><th>OS</th><th>Solicitado por</th><th>Motivo</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($tokensPendentes as $tk): ?>
                            <tr>
                                <td><?= e($tk['numero_os'] ?? ('#'.$tk['ordem_servico_id'])) ?><br><small class="text-muted"><?= e($tk['criado_em'] ?? '-') ?></small></td>
                                <td><?= e($tk['solicitado_por_nome'] ?: 'Usuário') ?></td>
                                <td><?= e($tk['motivo'] ?? '-') ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-danger" href="<?= e(app_path('ordem_detalhe', ['id'=>$tk['ordem_servico_id']])) ?>#token-reabertura">Gerar token</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$tokensPendentes): ?><tr><td colspan="4" class="text-center text-muted">Nenhum token pendente.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card ti-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">OS críticas / SLA vencido</h2>
                    <span class="ti-badge-soft"><?= e($totalCriticas) ?> alerta(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover ti-table">
                        <thead><tr><th>OS</th><th>Título</th><th>Local</th><th>Agente</th><th>Status</th><th>SLA / atraso em dias</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($osCriticasSla as $critica): ?>
                            <tr>
                                <td><?= e($critica['numero_os'] ?? ('#'.$critica['id'])) ?></td>
                                <td><?= e($critica['titulo'] ?? '-') ?></td>
                                <td><?= e($critica['local_nome'] ?? '-') ?></td>
                                <td><?= e($critica['agente_nome'] ?? '-') ?></td>
                                <td><?= badge_status($critica['status'] ?? '') ?></td>
                                <td><?= badge_sla($critica) ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('ordem_detalhe', ['id'=>$critica['id']])) ?>">Abrir</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$osCriticasSla): ?><tr><td colspan="7" class="text-center text-muted">Nenhuma OS crítica ou SLA vencido.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
    render_footer();
    exit;
}

if (($user['perfil'] ?? '') === 'TI') {
    render_ti_dashboard($pdo, $user);
}

$isAgente = ($user['perfil'] === 'AGENTE');

$whereOs = 'WHERE ' . exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os');
$paramsOs = [];

if ($isAgente) {
    $whereOs .= ' AND ' . os_where_agente_participante('os');
    $paramsOs[] = $user['id'];
    $paramsOs[] = $user['id'];
}

$condEssencial = "(os.prioridade = 'CRITICA' OR os.titulo LIKE '[SERVICO ESSENCIAL]%')";
$slaPrazoSql = sla_sql_prazo($pdo, 'os');

$statusLista = [
    'ABERTA',
    'ATRIBUIDA',
    'EM_ANDAMENTO',
    'AGUARDANDO_MATERIAL',
    'PAUSADA',
    'CONCLUIDA_PELO_AGENTE',
    'REPROVADA',
    'ENCERRADA'
];

$stats = [];

foreach ($statusLista as $status) {
    $sql = "
        SELECT COUNT(*) c 
        FROM ordens_servico os
        $whereOs
        " . ($whereOs ? " AND " : " WHERE ") . " os.status = ?
    ";

    $st = $pdo->prepare($sql);
    $st->execute(array_merge($paramsOs, [$status]));
    $stats[$status] = (int)$st->fetch()['c'];
}

$totalOs = fetchOneDashboard($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereOs
", $paramsOs);

$totalEssenciais = fetchOneDashboard($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereOs
    " . ($whereOs ? " AND " : " WHERE ") . "
    $condEssencial
", $paramsOs);

$totalAbertas = fetchOneDashboard($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereOs
    " . ($whereOs ? " AND " : " WHERE ") . "
    os.status IN ('ABERTA','ATRIBUIDA','EM_ANDAMENTO','AGUARDANDO_MATERIAL','PAUSADA')
", $paramsOs);

$totalEncerradas = fetchOneDashboard($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereOs
    " . ($whereOs ? " AND " : " WHERE ") . "
    os.status IN ('CONCLUIDA_PELO_AGENTE','ENCERRADA')
", $paramsOs);

$totalAtrasadas = fetchOneDashboard($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereOs
    " . ($whereOs ? " AND " : " WHERE ") . "
    os.prazo IS NOT NULL
    AND os.prazo < NOW()
    AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')
", $paramsOs);

$totalVencendo = fetchOneDashboard($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereOs
    " . ($whereOs ? " AND " : " WHERE ") . "
    $slaPrazoSql IS NOT NULL
    AND $slaPrazoSql >= NOW()
    AND $slaPrazoSql <= DATE_ADD(NOW(), INTERVAL 6 HOUR)
    AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')
", $paramsOs);

$ordensCriticasSla = fetchAllDashboard($pdo, "
    SELECT
        os.*,
        COALESCE(a.nome, 'Sem agente') AS agente_nome,
        a.telefone AS agente_telefone
    FROM ordens_servico os
    LEFT JOIN usuarios a ON a.id = os.agente_id
    $whereOs
    " . ($whereOs ? " AND " : " WHERE ") . "
    $slaPrazoSql IS NOT NULL
    AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')
    AND (
        $slaPrazoSql < NOW()
        OR $slaPrazoSql <= DATE_ADD(NOW(), INTERVAL 6 HOUR)
    )
    ORDER BY $slaPrazoSql ASC
    LIMIT 8
", $paramsOs);

$alertasEstoque = (!$isAgente && app_table_exists($pdo, 'estoque_materiais')) ? estoque_alertas($pdo) : [];

$recent = fetchAllDashboard($pdo, "
    SELECT 
        os.*, 
        l.nome AS local_nome, 
        c.nome AS categoria_nome, 
        COALESCE(a.nome, 'Sem agente') AS agente_nome
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    JOIN categorias c ON c.id = os.categoria_id
    LEFT JOIN usuarios a ON a.id = os.agente_id
    $whereOs
    ORDER BY os.criado_em DESC 
    LIMIT 10
", $paramsOs);

$essenciaisRecentes = fetchAllDashboard($pdo, "
    SELECT 
        os.*, 
        l.nome AS local_nome, 
        c.nome AS categoria_nome, 
        COALESCE(a.nome, 'Sem agente') AS agente_nome
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    JOIN categorias c ON c.id = os.categoria_id
    LEFT JOIN usuarios a ON a.id = os.agente_id
    $whereOs
    " . ($whereOs ? " AND " : " WHERE ") . "
    $condEssencial
    ORDER BY os.criado_em DESC
    LIMIT 5
", $paramsOs);

$porAgente = [];

if (!$isAgente) {
    $porAgente = fetchAllDashboard($pdo, "
        SELECT 
            COALESCE(u.nome, 'Sem agente') AS agente,
            COUNT(*) total
        FROM ordens_servico os
        LEFT JOIN usuarios u ON u.id = os.agente_id
        WHERE " . exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os') . "
        GROUP BY agente
        ORDER BY total DESC
        LIMIT 5
    ");
}

$cronogramaHoje = [];
$temCronograma = table_exists_dashboard($pdo, 'cronograma_agentes');

if ($temCronograma) {
    $diaSemanaHoje = (int)date('N');

    $whereCronograma = "WHERE ca.dia_semana = ?";
    $paramsCronograma = [$diaSemanaHoje];

    if ($isAgente) {
        $whereCronograma .= " AND ca.agente_id = ?";
        $paramsCronograma[] = $user['id'];
    }

    $cronogramaHoje = fetchAllDashboard($pdo, "
        SELECT 
            ca.*,
            a.nome AS agente_nome,
            COALESCE(l.nome, '-') AS local_nome
        FROM cronograma_agentes ca
        JOIN usuarios a ON a.id = ca.agente_id
        LEFT JOIN locais l ON l.id = ca.local_id
        $whereCronograma
        ORDER BY ca.hora_inicio ASC
        LIMIT 8
    ", $paramsCronograma);
}

render_header('Dashboard');
?>

<style>
.dashboard-card {
    border: 0;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
}

.metric {
    font-size: 30px;
    font-weight: 800;
    line-height: 1;
}

.metric-label {
    color: #666;
    font-size: 13px;
}

.quick-action {
    text-decoration: none;
}

.quick-action .card {
    transition: .2s;
}

.quick-action .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 18px rgba(0,0,0,.10);
}

.essencial-label {
    background: #dc3545;
    color: #fff;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.table td,
.table th {
    vertical-align: middle;
}
</style>

<?php render_whatsapp_queue(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-0">Dashboard</h1>
        <small class="text-muted">
            Bem-vindo, <?= e($user['nome'] ?? $user['email'] ?? 'Usuario') ?>
        </small>
    </div>

    <div class="d-flex gap-2">
        <?php if (can_create_os()): ?>
            <a class="btn btn-danger" href="<?= e(app_path('nova_os_essencial')) ?>">
                + Servico essencial
            </a>

            <a class="btn btn-primary" href="<?= e(app_path('ordem_form')) ?>">
                + Nova OS
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card p-3 dashboard-card">
            <div class="metric"><?= e($totalOs['total'] ?? 0) ?></div>
            <div class="metric-label">Total de OS</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card p-3 dashboard-card border border-danger">
            <div class="metric text-danger"><?= e($totalEssenciais['total'] ?? 0) ?></div>
            <div class="metric-label">Servicos essenciais</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card p-3 dashboard-card">
            <div class="metric"><?= e($totalAbertas['total'] ?? 0) ?></div>
            <div class="metric-label">Em aberto / andamento</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card p-3 dashboard-card">
            <div class="metric text-warning"><?= e($totalAtrasadas['total'] ?? 0) ?></div>
            <div class="metric-label">Atrasadas</div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card p-3 dashboard-card">
            <div class="metric text-danger"><?= e($totalVencendo['total'] ?? 0) ?></div>
            <div class="metric-label">Vencem em ate 6h</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($stats as $k => $v): ?>
        <div class="col-6 col-md-3 col-xl">
            <div class="card p-3 dashboard-card">
                <div class="text-muted small"><?= e(str_replace('_', ' ', $k)) ?></div>
                <div class="metric"><?= e($v) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($ordensCriticasSla || $alertasEstoque): ?>
    <div class="row g-3 mb-4">
        <?php if ($ordensCriticasSla): ?>
            <div class="col-lg-8">
                <div class="card p-3 dashboard-card border border-warning">
                    <h2 class="h5 mb-3">Alertas de SLA</h2>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>OS</th>
                                    <th>Titulo</th>
                                    <th>SLA / atraso em dias</th>
                                    <th>Agente</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordensCriticasSla as $alerta): ?>
                                    <tr>
                                        <td><?= e($alerta['numero_os']) ?></td>
                                        <td><?= e($alerta['titulo']) ?></td>
                                        <td><?= badge_sla($alerta) ?></td>
                                        <td><?= e($alerta['agente_nome']) ?></td>
                                        <td class="text-end">
                                            <?php $linkWhats = whatsapp_link($alerta['agente_telefone'] ?? '', mensagem_whatsapp_os('Alerta de SLA da OS', $alerta)); ?>
                                            <?php if ($linkWhats): ?>
                                                <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="<?= e($linkWhats) ?>">WhatsApp</a>
                                            <?php endif; ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('ordem_detalhe', ['id' => $alerta['id']])) ?>">Abrir</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($alertasEstoque): ?>
            <div class="col-lg-4">
                <div class="card p-3 dashboard-card border border-danger">
                    <h2 class="h5 text-danger">Estoque baixo</h2>
                    <?php foreach ($alertasEstoque as $itemEstoque): ?>
                        <div class="border rounded p-2 mb-2">
                            <strong><?= e($itemEstoque['nome']) ?></strong><br>
                            <small>
                                Atual: <?= e($itemEstoque['quantidade_atual']) ?> <?= e($itemEstoque['unidade']) ?>
                                | minimo: <?= e($itemEstoque['estoque_minimo']) ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                    <a class="btn btn-sm btn-outline-danger" href="<?= e(app_path('estoque')) ?>">Abrir estoque</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card p-3 dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 mb-0">Ultimas ordens</h2>
                <a href="<?= e(app_path('ordens')) ?>" class="btn btn-sm btn-outline-secondary">Ver todas</a>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>OS</th>
                            <th>Titulo</th>
                            <th>Local</th>
                            <th>Prioridade</th>
                            <th>Status</th>
                            <th>Agente</th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($recent as $r): ?>
                            <?php
                            $ehEssencial = (
                                $r['prioridade'] === 'CRITICA' ||
                                comeca_com($r['titulo'], '[SERVICO ESSENCIAL]')
                            );
                            ?>

                            <tr>
                                <td><?= e($r['numero_os']) ?></td>
                                <td>
                                    <?= e($r['titulo']) ?>

                                    <?php if ($ehEssencial): ?>
                                        <br>
                                        <span class="essencial-label">Essencial</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($r['local_nome']) ?></td>
                                <td><?= badge_prioridade($r['prioridade']) ?></td>
                                <td><?= badge_status($r['status']) ?></td>
                                <td><?= e($r['agente_nome'] ?: '-') ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('ordem_detalhe', ['id' => $r['id']])) ?>">
                                        Abrir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$recent): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Nenhuma ordem encontrada.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card p-3 dashboard-card mb-3 border border-danger">
            <h2 class="h5 text-danger">Essenciais recentes</h2>

            <?php foreach ($essenciaisRecentes as $eos): ?>
                <div class="border rounded p-2 mb-2">
                    <strong><?= e($eos['numero_os']) ?></strong>
                    <br>
                    <?= e($eos['titulo']) ?>
                    <br>
                    <small>
                        <?= e($eos['local_nome']) ?> |
                        <?= badge_status($eos['status']) ?>
                    </small>
                    <div class="mt-2">
                        <a class="btn btn-sm btn-outline-danger" href="<?= e(app_path('ordem_detalhe', ['id' => $eos['id']])) ?>">
                            Abrir
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$essenciaisRecentes): ?>
                <div class="text-muted small">
                    Nenhum servico essencial recente.
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$isAgente): ?>
            <div class="card p-3 dashboard-card">
                <h2 class="h5">Ranking por agente</h2>

                <table class="table table-sm">
                    <?php foreach ($porAgente as $pa): ?>
                        <tr>
                            <td><?= e($pa['agente']) ?></td>
                            <td class="text-end"><?= e($pa['total']) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$porAgente): ?>
                        <tr>
                            <td colspan="2" class="text-muted">Nenhum dado encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($temCronograma): ?>
    <div class="card p-3 dashboard-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0">Cronograma de hoje</h2>

            <?php if (in_array($user['perfil'], ['TI', 'GESTOR'], true)): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('cronograma_agentes')) ?>">
                    Abrir cronograma
                </a>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Horario</th>
                        <th>Atividade</th>
                        <th>Agente</th>
                        <th>Local</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($cronogramaHoje as $c): ?>
                        <tr>
                            <td>
                                <?= e(substr($c['hora_inicio'], 0, 5)) ?>
                                ate
                                <?= e(substr($c['hora_fim'], 0, 5)) ?>
                            </td>
                            <td>
                                <strong><?= e($c['titulo']) ?></strong>
                                <?php if (!empty($c['descricao'])): ?>
                                    <br>
                                    <small class="text-muted"><?= e($c['descricao']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= e($c['agente_nome']) ?></td>
                            <td><?= e($c['local_nome']) ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= e(str_replace('_', ' ', $c['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$cronogramaHoje): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                Nenhuma atividade no cronograma de hoje.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-3">
        <a class="quick-action" href="<?= e(app_path('ordens')) ?>">
            <div class="card p-3 dashboard-card">
                <strong>Ordens de servico</strong>
                <small class="text-muted">Consultar e acompanhar OS</small>
            </div>
        </a>
    </div>

    <div class="col-md-3">
        <a class="quick-action" href="<?= e(app_path('relatorios')) ?>">
            <div class="card p-3 dashboard-card">
                <strong>Relatorios</strong>
                <small class="text-muted">Indicadores e cronogramas</small>
            </div>
        </a>
    </div>

    <?php if (can_create_os()): ?>
        <div class="col-md-3">
            <a class="quick-action" href="<?= e(app_path('ordem_form')) ?>">
                <div class="card p-3 dashboard-card">
                    <strong>Nova OS</strong>
                    <small class="text-muted">Abrir nova ordem</small>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a class="quick-action" href="<?= e(app_path('nova_os_essencial')) ?>">
                <div class="card p-3 dashboard-card border border-danger">
                    <strong class="text-danger">Servico essencial</strong>
                    <small class="text-muted">Abrir demanda critica</small>
                </div>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php render_footer(); ?>
