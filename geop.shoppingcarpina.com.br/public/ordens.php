<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';

require_login();
ensure_operational_schema($pdo);

$user = current_user();
if (in_array(($user['perfil'] ?? ''), ['CFTV','ASG','SUPERVISOR_ASG'], true)) {
    http_response_code(403);
    die('Acesso negado.');
}

$status = $_GET['status'] ?? '';
$prioridade = $_GET['prioridade'] ?? '';
$q = trim($_GET['q'] ?? '');
$porPagina = 15;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$slaPrazoSql = sla_sql_prazo($pdo, 'os');

$where = [exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os')];
$params = [];

if ($user['perfil'] === 'AGENTE') {
    $where[] = os_where_agente_participante('os');
    $params[] = $user['id'];
    $params[] = $user['id'];
}

if ($status !== '') {
    $where[] = 'os.status = ?';
    $params[] = $status;
}

if ($prioridade !== '') {
    $where[] = 'os.prioridade = ?';
    $params[] = $prioridade;
}

if ($q !== '') {
    $where[] = '(os.numero_os LIKE ? OR os.titulo LIKE ? OR os.descricao LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM ordens_servico os $sqlWhere");
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));
if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}
$offset = ($pagina - 1) * $porPagina;

$stmt = $pdo->prepare("
    SELECT
        os.*,
        l.nome AS local_nome,
        c.nome AS categoria_nome,
        g.nome AS gestor_nome,
        a.nome AS agente_nome,
        a.telefone AS agente_telefone,
        GROUP_CONCAT(DISTINCT CASE WHEN oa.papel = 'AUXILIAR' THEN ux.nome END ORDER BY ux.nome SEPARATOR ', ') AS agentes_auxiliares_nomes
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    JOIN categorias c ON c.id = os.categoria_id
    JOIN usuarios g ON g.id = os.gestor_id
    LEFT JOIN usuarios a ON a.id = os.agente_id
    LEFT JOIN ordem_agentes oa ON oa.ordem_servico_id = os.id
    LEFT JOIN usuarios ux ON ux.id = oa.usuario_id
    $sqlWhere
    GROUP BY os.id
    ORDER BY
        CASE os.status
            WHEN 'ABERTA' THEN 1
            WHEN 'ATRIBUIDA' THEN 2
            WHEN 'EM_ANDAMENTO' THEN 3
            WHEN 'PAUSADA' THEN 4
            WHEN 'AGUARDANDO_MATERIAL' THEN 5
            WHEN 'CONCLUIDA_PELO_AGENTE' THEN 6
            WHEN 'REPROVADA' THEN 7
            WHEN 'ENCERRADA' THEN 8
            ELSE 9
        END,
        CASE
            WHEN $slaPrazoSql IS NOT NULL
             AND $slaPrazoSql < NOW()
             AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')
            THEN 0 ELSE 1 END,
        os.criado_em DESC
    LIMIT $porPagina OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Ordens de Servico');
render_whatsapp_queue();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Ordens de Servico</h1>
    <?php if (can_create_os()): ?>
        <a class="btn btn-primary" href="<?= e(app_path('ordem_form')) ?>">+ Nova OS</a>
    <?php endif; ?>
</div>

<div class="card p-3 mb-3">
    <form class="row g-2">
        <div class="col-md-4">
            <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar por numero, titulo ou descricao">
        </div>

        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">Todos os status</option>
                <?php foreach (['ABERTA','ATRIBUIDA','EM_ANDAMENTO','PAUSADA','AGUARDANDO_MATERIAL','CONCLUIDA_PELO_AGENTE','REPROVADA','ENCERRADA'] as $s): ?>
                    <option <?= $status === $s ? 'selected' : '' ?> value="<?= e($s) ?>"><?= e(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <select class="form-select" name="prioridade">
                <option value="">Prioridade</option>
                <?php foreach (['BAIXA','MEDIA','ALTA','CRITICA'] as $p): ?>
                    <option <?= $prioridade === $p ? 'selected' : '' ?> value="<?= e($p) ?>"><?= e($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <button class="btn btn-dark w-100">Filtrar</button>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <span class="text-muted">
            <?= e($totalRegistros) ?> ordem(ns) — página <?= e($pagina) ?> de <?= e($totalPaginas) ?>
        </span>
        <small class="text-muted">15 registros por página</small>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Numero</th>
                    <th>Titulo</th>
                    <th>Local</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <!--<th>SLA</th>-->
                    <th>Agentes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e($r['numero_os']) ?></td>
                        <td><?= e($r['titulo']) ?></td>
                        <td><?= e($r['local_nome']) ?></td>
                        <td><?= badge_prioridade($r['prioridade']) ?></td>
                        <td><?= badge_status($r['status']) ?></td>
                        <!--<td><?= badge_sla($r) ?></td>-->
                        <td>
                            <div><strong>Principal:</strong> <?= e($r['agente_nome'] ?: '-') ?></div>
                            <?php if (!empty($r['agentes_auxiliares_nomes'])): ?>
                                <div class="small text-muted"><strong>Auxiliar:</strong> <?= e($r['agentes_auxiliares_nomes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php $linkWhats = whatsapp_link($r['agente_telefone'] ?? '', mensagem_whatsapp_os('Atualizacao da OS', $r)); ?>
                            <?php if ($linkWhats && can_manage_os()): ?>
                                <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="<?= e($linkWhats) ?>">WhatsApp</a>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('ordem_detalhe', ['id' => $r['id']])) ?>">Detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted">Nenhuma ordem encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPaginas > 1): ?>
        <?php
        $queryPaginacao = $_GET;
        unset($queryPaginacao['pagina']);
        $inicioPagina = max(1, $pagina - 2);
        $fimPagina = min($totalPaginas, $pagina + 2);
        ?>
        <nav class="mt-3" aria-label="Paginação das ordens de serviço">
            <ul class="pagination justify-content-center flex-wrap mb-0">
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e(app_path('ordens', array_merge($queryPaginacao, ['pagina' => max(1, $pagina - 1)]))) ?>">Anterior</a>
                </li>

                <?php if ($inicioPagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= e(app_path('ordens', array_merge($queryPaginacao, ['pagina' => 1]))) ?>">1</a>
                    </li>
                    <?php if ($inicioPagina > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($numeroPagina = $inicioPagina; $numeroPagina <= $fimPagina; $numeroPagina++): ?>
                    <li class="page-item <?= $numeroPagina === $pagina ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e(app_path('ordens', array_merge($queryPaginacao, ['pagina' => $numeroPagina]))) ?>">
                            <?= e($numeroPagina) ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($fimPagina < $totalPaginas): ?>
                    <?php if ($fimPagina < $totalPaginas - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= e(app_path('ordens', array_merge($queryPaginacao, ['pagina' => $totalPaginas]))) ?>"><?= e($totalPaginas) ?></a>
                    </li>
                <?php endif; ?>

                <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e(app_path('ordens', array_merge($queryPaginacao, ['pagina' => min($totalPaginas, $pagina + 1)]))) ?>">Próxima</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php render_footer(); ?>
