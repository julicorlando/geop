<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

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

if ($user['perfil'] === 'GESTOR') {
    $tokenErro = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $token = trim((string)($_POST['token'] ?? ''));
        if ($token !== '' && validate_gestor_token($token, 'GESTOR_REPORTS')) {
            mark_gestor_reports_access();
        } else {
            $tokenErro = 'Token inválido ou expirado. Solicite um token novo ao TI.';
        }
    }

    if (!gestor_reports_access_allowed()) {
        render_header('Acesso restrito');
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-0">Acesso restrito</h1>
                <small class="text-muted">Informe o token gerado pelo TI para acessar Relatórios.</small>
            </div>
            <a class="btn btn-outline-secondary" href="<?= e(app_path('index')) ?>">Voltar</a>
        </div>
        <div class="card p-3" style="max-width: 520px;">
            <?php if ($tokenErro): ?><div class="alert alert-danger"><?= e($tokenErro) ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="mb-3">
                    <label class="form-label">Token TI</label>
                    <input class="form-control" type="text" name="token" autocomplete="off" required>
                </div>
                <button class="btn btn-primary w-100">Validar token</button>
            </form>
        </div>
        <?php
        render_footer();
        exit;
    }
}

function comeca_com($texto, $inicio) {
    return substr((string)$texto, 0, strlen($inicio)) === $inicio;
}

function fetchAllRelatorio($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}

function fetchOneRelatorio($pdo, $sql, $params = []) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetch();
}

function tabela_existe_relatorio(PDO $pdo, string $tabela): bool {
    try {
        $st = $pdo->prepare("SHOW TABLES LIKE ?");
        $st->execute([$tabela]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function coluna_existe_relatorio(PDO $pdo, string $tabela, string $coluna): bool {
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$tabela` LIKE ?");
        $st->execute([$coluna]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function primeira_coluna_existente_relatorio(PDO $pdo, string $tabela, array $colunas): ?string {
    foreach ($colunas as $coluna) {
        if (coluna_existe_relatorio($pdo, $tabela, $coluna)) {
            return $coluna;
        }
    }

    return null;
}

function montar_url_relativa_script_relatorio(string $caminho): string {
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($base === '/' || $base === '.') {
        $base = '';
    }

    $partes = array_map('rawurlencode', array_filter(explode('/', ltrim($caminho, '/')), 'strlen'));
    return $base . '/' . implode('/', $partes);
}

function caminho_anexo_relatorio($arquivo): string {
    $arquivo = trim((string)$arquivo);
    if ($arquivo === '') {
        return '';
    }

    $arquivo = str_replace('\\', '/', $arquivo);

    if (preg_match('/^https?:\/\//i', $arquivo)) {
        return $arquivo;
    }

    $arquivo = preg_replace('/\?.*$/', '', $arquivo);

    if (strpos($arquivo, '/uploads/') !== false) {
        $arquivo = substr($arquivo, strpos($arquivo, '/uploads/') + 1);
    }

    if (preg_match('/^[A-Za-z]:\//', $arquivo) || strpos($arquivo, '/home/') === 0 || strpos($arquivo, __DIR__) === 0) {
        $arquivo = 'uploads/' . basename($arquivo);
    }

    $arquivo = ltrim($arquivo, '/');

    if (strpos($arquivo, 'uploads/') === 0) {
        return montar_url_relativa_script_relatorio($arquivo);
    }

    if (strpos($arquivo, '../') === 0 || strpos($arquivo, './') === 0) {
        return $arquivo;
    }

    if (strpos($arquivo, '/') === false) {
        return montar_url_relativa_script_relatorio('uploads/' . $arquivo);
    }

    return montar_url_relativa_script_relatorio($arquivo);
}

function caminho_foto_relatorio($arquivo): string {
    return caminho_anexo_relatorio($arquivo);
}

function anexo_eh_imagem_relatorio($arquivoUrl): bool {
    $ext = strtolower(pathinfo((string)$arquivoUrl, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
}

function anexo_url_segura_relatorio($arquivoUrl): string {
    return caminho_anexo_relatorio($arquivoUrl);
}

function tipo_foto_relatorio(array $foto): string {
    $texto = mb_strtolower(
        trim(
            (string)($foto['tipo'] ?? '') . ' ' .
            (string)($foto['categoria'] ?? '') . ' ' .
            (string)($foto['etapa'] ?? '') . ' ' .
            (string)($foto['momento'] ?? '') . ' ' .
            (string)($foto['descricao'] ?? '') . ' ' .
            (string)($foto['observacao'] ?? '')
        ),
        'UTF-8'
    );

    if (
        str_contains($texto, 'antes') ||
        str_contains($texto, 'before') ||
        str_contains($texto, 'inicial') ||
        str_contains($texto, 'inicio') ||
        str_contains($texto, 'abertura')
    ) {
        return 'ANTES';
    }

    if (
        str_contains($texto, 'depois') ||
        str_contains($texto, 'after') ||
        str_contains($texto, 'final') ||
        str_contains($texto, 'conclusao') ||
        str_contains($texto, 'conclusão') ||
        str_contains($texto, 'encerramento')
    ) {
        return 'DEPOIS';
    }

    return 'GERAL';
}

function candidatos_caminho_foto_relatorio($arquivo): array {
    $arquivo = trim((string)$arquivo);

    if ($arquivo === '') {
        return [];
    }

    $arquivo = str_replace('\\', '/', $arquivo);

    if (preg_match('/^https?:\/\//i', $arquivo)) {
        return [$arquivo];
    }

    $limpo = ltrim($arquivo, './');
    $candidatos = [];

    $candidatos[] = caminho_anexo_relatorio($limpo);
    if (str_starts_with($limpo, 'uploads/')) {
        $candidatos[] = $limpo;
    }
    $candidatos[] = $limpo;
    $candidatos[] = '../' . $limpo;
    $candidatos[] = '../../' . $limpo;
    $candidatos[] = '../uploads/' . basename($limpo);
    $candidatos[] = '../../uploads/' . basename($limpo);
    $candidatos[] = 'uploads/' . basename($limpo);

    return array_values(array_unique(array_filter($candidatos)));
}

function eh_imagem_relatorio($arquivo, $mime = ''): bool {
    $arquivo = (string)$arquivo;
    $mime = strtolower((string)$mime);

    if ($mime !== '' && str_contains($mime, 'image/')) {
        return true;
    }

    $path = parse_url($arquivo, PHP_URL_PATH) ?: $arquivo;

    if (preg_match('/\.(jpg|jpeg|png|gif|webp|bmp)$/i', $path)) {
        return true;
    }

    return $mime === '' && !preg_match('/\.(pdf|doc|docx|xls|xlsx|zip|rar|txt)$/i', $path);
}

function adicionar_foto_relatorio(array &$fotos, $arquivo, string $grupo = 'GERAL', string $descricao = '', $criadoEm = null): void {
    $arquivo = trim((string)$arquivo);

    if ($arquivo === '') {
        return;
    }

    $srcs = candidatos_caminho_foto_relatorio($arquivo);

    if (!$srcs) {
        return;
    }

    $fotos[] = [
        'arquivo' => $arquivo,
        'src' => $srcs[0],
        'srcs' => $srcs,
        'grupo' => $grupo,
        'tipo' => $grupo,
        'descricao' => $descricao,
        'criado_em' => $criadoEm,
    ];
}

function buscar_anexos_os_relatorio(PDO $pdo, int $osId): array {
    try {
        $st = $pdo->prepare("
            SELECT a.*, u.nome AS usuario_nome
            FROM anexos_os a
            JOIN usuarios u ON u.id = a.usuario_id
            WHERE ordem_servico_id = ?
            ORDER BY a.criado_em DESC
        ");
        $st->execute([$osId]);
        $linhas = $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }

    $anexos = [];
    foreach ($linhas as $linha) {
        $arquivo = trim((string)($linha['arquivo_url'] ?? ''));
        if ($arquivo === '') {
            continue;
        }

        $tipo = strtoupper(trim((string)($linha['tipo'] ?? '')));
        $srcs = candidatos_caminho_foto_relatorio($arquivo);
        $anexos[] = [
            'id' => (int)($linha['id'] ?? 0),
            'tipo' => (string)($linha['tipo'] ?? ''),
            'arquivo_url' => $arquivo,
            'arquivo' => $arquivo,
            'src' => $srcs[0] ?? caminho_anexo_relatorio($arquivo),
            'srcs' => $srcs,
            'descricao' => (string)($linha['descricao'] ?? ''),
            'criado_em' => $linha['criado_em'] ?? null,
            'usuario_nome' => (string)($linha['usuario_nome'] ?? 'Sistema'),
            'imagem' => anexo_eh_imagem_relatorio($arquivo),
        ];
    }

    return $anexos;
}

function agrupar_anexos_os_relatorio(array $anexos): array {
    $fotosAntes = [];
    $fotosDepois = [];
    $fotosOutras = [];
    $documentosAnexos = [];

    foreach ($anexos as $anexoFoto) {
        if (!anexo_eh_imagem_relatorio($anexoFoto['arquivo_url'] ?? $anexoFoto['arquivo'] ?? '')) {
            $documentosAnexos[] = $anexoFoto;
            continue;
        }

        $tipoFoto = strtoupper((string)($anexoFoto['tipo'] ?? ''));
        if ($tipoFoto === 'FOTO_ANTES') {
            $fotosAntes[] = $anexoFoto;
        } elseif ($tipoFoto === 'FOTO_DEPOIS') {
            $fotosDepois[] = $anexoFoto;
        } else {
            $fotosOutras[] = $anexoFoto;
        }
    }

    return [
        'antes' => $fotosAntes,
        'depois' => $fotosDepois,
        'geral' => $fotosOutras,
        'documentos' => $documentosAnexos,
    ];
}

function buscar_fotos_os_relatorio(PDO $pdo, int $osId): array {
    $fotos = [];
    $numeroOsBusca = '';

    try {
        $stNumero = $pdo->prepare('SELECT numero_os FROM ordens_servico WHERE id = ? LIMIT 1');
        $stNumero->execute([$osId]);
        $numeroOsBusca = (string)($stNumero->fetchColumn() ?: '');
    } catch (Throwable $e) {
        $numeroOsBusca = '';
    }

    /* 1) Fotos salvas diretamente na tabela ordens_servico */
    $colunasAntes = ['foto_antes', 'fotos_antes', 'imagem_antes', 'imagens_antes', 'antes_foto', 'antes_imagem', 'arquivo_antes'];
    $colunasDepois = ['foto_depois', 'fotos_depois', 'imagem_depois', 'imagens_depois', 'depois_foto', 'depois_imagem', 'arquivo_depois'];
    $colunasGerais = ['foto', 'fotos', 'imagem', 'imagens', 'arquivo', 'anexo', 'evidencia', 'evidencias'];

    try {
        $selects = ['id'];
        $mapaColunas = [];

        foreach (array_merge($colunasAntes, $colunasDepois, $colunasGerais) as $col) {
            if (coluna_existe_relatorio($pdo, 'ordens_servico', $col)) {
                $selects[] = "`$col`";
                $mapaColunas[] = $col;
            }
        }

        if ($mapaColunas) {
            $st = $pdo->prepare('SELECT ' . implode(', ', $selects) . ' FROM ordens_servico WHERE id = ? LIMIT 1');
            $st->execute([$osId]);
            $osLinha = $st->fetch();

            if ($osLinha) {
                foreach ($mapaColunas as $col) {
                    $valor = trim((string)($osLinha[$col] ?? ''));

                    if ($valor === '') {
                        continue;
                    }

                    $grupo = 'GERAL';
                    if (in_array($col, $colunasAntes, true) || str_contains($col, 'antes')) {
                        $grupo = 'ANTES';
                    } elseif (in_array($col, $colunasDepois, true) || str_contains($col, 'depois')) {
                        $grupo = 'DEPOIS';
                    }

                    $possiveisArquivos = preg_split('/[,;\n]+/', $valor);
                    foreach ($possiveisArquivos as $arquivo) {
                        if (eh_imagem_relatorio($arquivo)) {
                            adicionar_foto_relatorio($fotos, $arquivo, $grupo, ucfirst(str_replace('_', ' ', $col)));
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) {
        // continua procurando nas tabelas de anexos/fotos
    }

    /* 2) Fotos salvas em tabelas de anexos/fotos */
    $tabelasPossiveis = [
        'ordem_servico_fotos',
        'ordens_servico_fotos',
        'os_fotos',
        'fotos_ordem_servico',
        'anexos_ordem_servico',
        'ordem_servico_anexos',
        'ordens_servico_anexos',
        'ordem_anexos',
        'os_anexos',
        'arquivos_os',
        'uploads_os',
        'evidencias_os',
        'ordem_servico_arquivos',
        'ordens_servico_arquivos',
        'os_uploads',
        'uploads_ordem_servico',
        'fotos_os',
        'anexos_os',
        'arquivos_ordem',
        'fotos_ordem',
        'registros_fotograficos',
        'registros_fotograficos_os',
        'ordem_servico_registros',
        'evidencias_ordem_servico'
    ];

    try {
        $stTabs = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND (TABLE_NAME LIKE '%foto%' OR TABLE_NAME LIKE '%anexo%' OR TABLE_NAME LIKE '%arquivo%' OR TABLE_NAME LIKE '%upload%' OR TABLE_NAME LIKE '%evidencia%')");
        foreach ($stTabs->fetchAll(PDO::FETCH_COLUMN) as $t) {
            if (!in_array($t, $tabelasPossiveis, true)) {
                $tabelasPossiveis[] = $t;
            }
        }
    } catch (Throwable $e) {
        // ignora caso o usuário do banco não tenha permissão no information_schema
    }

    $colunasOs = [
        'ordem_servico_id',
        'ordem_id',
        'os_id',
        'id_os',
        'chamado_id',
        'ticket_id',
        'servico_id',
        'id_ordem_servico',
        'id_ordem',
        'id_chamado',
        'ordem_servico',
        'numero_os',
        'os_numero',
        'numero'
    ];

    $colunasArquivo = [
        'arquivo',
        'caminho',
        'foto',
        'imagem',
        'path',
        'url',
        'nome_arquivo',
        'arquivo_nome',
        'file_path',
        'filename',
        'nome',
        'anexo',
        'file',
        'src',
        'caminho_arquivo',
        'url_arquivo',
        'arquivo_url',
        'imagem_url',
        'foto_url',
        'local_arquivo',
        'arquivo_path',
        'file_name',
        'original_name',
        'nome_original'
    ];

    foreach ($tabelasPossiveis as $tabela) {
        if (!tabela_existe_relatorio($pdo, $tabela)) {
            continue;
        }

        $colunaOs = primeira_coluna_existente_relatorio($pdo, $tabela, $colunasOs);
        $colunaArquivo = primeira_coluna_existente_relatorio($pdo, $tabela, $colunasArquivo);

        if (!$colunaOs || !$colunaArquivo) {
            continue;
        }

        $colunaId = primeira_coluna_existente_relatorio($pdo, $tabela, ['id', 'foto_id', 'arquivo_id']);
        $colunaTipo = primeira_coluna_existente_relatorio($pdo, $tabela, ['tipo', 'categoria', 'etapa', 'momento', 'origem', 'classificacao']);
        $colunaDescricao = primeira_coluna_existente_relatorio($pdo, $tabela, ['descricao', 'observacao', 'legenda', 'titulo', 'nome_original']);
        $colunaCriado = primeira_coluna_existente_relatorio($pdo, $tabela, ['criado_em', 'created_at', 'data_criacao', 'data_upload', 'data']);
        $colunaMime = primeira_coluna_existente_relatorio($pdo, $tabela, ['mime', 'mime_type', 'tipo_arquivo', 'content_type']);

        $select = ["`$colunaArquivo` AS arquivo"];
        $select[] = $colunaId ? "`$colunaId` AS id_foto" : "0 AS id_foto";
        $select[] = $colunaTipo ? "`$colunaTipo` AS tipo" : "'' AS tipo";
        $select[] = $colunaDescricao ? "`$colunaDescricao` AS descricao" : "'' AS descricao";
        $select[] = $colunaCriado ? "`$colunaCriado` AS criado_em" : "NULL AS criado_em";
        $select[] = $colunaMime ? "`$colunaMime` AS mime" : "'' AS mime";

        $orderPartes = [];
        if ($colunaCriado) $orderPartes[] = "`$colunaCriado` ASC";
        if ($colunaId) $orderPartes[] = "`$colunaId` ASC";
        $order = $orderPartes ? ('ORDER BY ' . implode(', ', $orderPartes)) : '';

        try {
            $st = $pdo->prepare("
                SELECT " . implode(', ', $select) . "
                FROM `$tabela`
                WHERE `$colunaOs` = ?
                $order
            ");

            $valorBusca = in_array($colunaOs, ['numero_os', 'os_numero', 'numero'], true) ? $numeroOsBusca : $osId;
            if ($valorBusca === '') {
                continue;
            }

            $st->execute([$valorBusca]);
            $linhas = $st->fetchAll();

            foreach ($linhas as $linha) {
                $arquivo = (string)($linha['arquivo'] ?? '');
                $mime = (string)($linha['mime'] ?? '');

                if (!eh_imagem_relatorio($arquivo, $mime)) {
                    continue;
                }

                $grupo = tipo_foto_relatorio($linha);
                adicionar_foto_relatorio($fotos, $arquivo, $grupo, (string)($linha['descricao'] ?? ''), $linha['criado_em'] ?? null);
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    /* Remove duplicadas */
    $unicas = [];
    $resultado = [];
    foreach ($fotos as $foto) {
        $chave = strtolower((string)($foto['arquivo'] ?? '') . '|' . (string)($foto['grupo'] ?? ''));
        if (isset($unicas[$chave])) {
            continue;
        }
        $unicas[$chave] = true;
        $resultado[] = $foto;
    }

    return $resultado;
}

function separar_fotos_antes_depois_relatorio(array $fotos): array {
    $antes = [];
    $depois = [];
    $geral = [];

    foreach ($fotos as $foto) {
        if (($foto['grupo'] ?? '') === 'ANTES') {
            $antes[] = $foto;
        } elseif (($foto['grupo'] ?? '') === 'DEPOIS') {
            $depois[] = $foto;
        } else {
            $geral[] = $foto;
        }
    }

    if (!$antes && !$depois && count($geral) >= 2) {
        $meio = (int)ceil(count($geral) / 2);
        $antes = array_slice($geral, 0, $meio);
        $depois = array_slice($geral, $meio);
        $geral = [];
    }

    return [
        'antes' => $antes,
        'depois' => $depois,
        'geral' => $geral
    ];
}

function inicio_semana_relatorio($data) {
    $timestamp = strtotime($data);
    $diaSemana = date('N', $timestamp);
    return date('Y-m-d', strtotime('-' . ($diaSemana - 1) . ' days', $timestamp));
}

function fim_semana_relatorio($data) {
    return date('Y-m-d', strtotime(inicio_semana_relatorio($data) . ' +6 days'));
}

$filtroStatus = $_GET['status'] ?? '';
$filtroInicio = $_GET['inicio'] ?? '';
$filtroFim = $_GET['fim'] ?? '';
$filtroCategoria = (int)($_GET['categoria_id'] ?? 0);
$filtroLocal = (int)($_GET['local_id'] ?? 0);
$filtroAgente = (int)($_GET['agente_id'] ?? 0);
$filtroPrioridade = $_GET['prioridade'] ?? '';
$export = $_GET['export'] ?? '';
$modoApresentacao = ($_GET['apresentacao'] ?? '') === '1';
$imprimirAoCarregar = ($_GET['imprimir'] ?? '') === '1';
$limiteOrdensRelatorioSql = '';
$slaPrazoSql = sla_sql_prazo($pdo, 'os');

$where = [exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os')];
$params = [];

if ($filtroStatus !== 'PAUSADA') {
    $where[] = "os.status <> 'PAUSADA'";
}

if ($user['perfil'] === 'AGENTE') {
    $where[] = os_where_agente_participante('os');
    $params[] = $user['id'];
    $params[] = $user['id'];
}

if ($filtroStatus !== '') {
    $where[] = 'os.status = ?';
    $params[] = $filtroStatus;
}

if ($filtroPrioridade !== '') {
    $where[] = 'os.prioridade = ?';
    $params[] = $filtroPrioridade;
}

if ($filtroCategoria > 0) {
    $where[] = 'os.categoria_id = ?';
    $params[] = $filtroCategoria;
}

if ($filtroLocal > 0) {
    $where[] = 'os.local_id = ?';
    $params[] = $filtroLocal;
}

if ($filtroAgente > 0 && $user['perfil'] !== 'AGENTE') {
    $where[] = os_where_agente_participante('os');
    $params[] = $filtroAgente;
    $params[] = $filtroAgente;
}

if ($filtroInicio !== '') {
    $where[] = 'DATE(os.criado_em) >= ?';
    $params[] = $filtroInicio;
}

if ($filtroFim !== '') {
    $where[] = 'DATE(os.criado_em) <= ?';
    $params[] = $filtroFim;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$andWhereSql = $whereSql ? ' AND ' : ' WHERE ';

$condEssencial = "(os.prioridade IN ('CRITICA','URGENTE') OR os.titulo LIKE '[SERVICO ESSENCIAL]%')";

$total = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
", $params);

$totalEssenciais = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
    " . ($whereSql ? " AND " : " WHERE ") . "
    $condEssencial
", $params);

$totalNaoEssenciais = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
    " . ($whereSql ? " AND " : " WHERE ") . "
    NOT $condEssencial
", $params);

$abertas = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
    " . ($whereSql ? " AND " : " WHERE ") . "
    os.status IN ('ABERTA','ATRIBUIDA','EM_ANDAMENTO','AGUARDANDO_MATERIAL')
", $params);

$concluidas = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
    " . ($whereSql ? " AND " : " WHERE ") . "
    os.status IN ('CONCLUIDA_PELO_AGENTE','ENCERRADA')
", $params);

$baseTaxaConclusao = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
    " . ($whereSql ? " AND " : " WHERE ") . "
    os.status <> 'PAUSADA'
", $params);

$porStatus = fetchAllRelatorio($pdo, "
    SELECT os.status, COUNT(*) total
    FROM ordens_servico os
    $whereSql
    GROUP BY os.status
    ORDER BY total DESC
", $params);

$porCategoria = fetchAllRelatorio($pdo, "
    SELECT c.nome, COUNT(*) total
    FROM ordens_servico os
    JOIN categorias c ON c.id = os.categoria_id
    $whereSql
    GROUP BY c.nome
    ORDER BY total DESC
", $params);

$porLocal = fetchAllRelatorio($pdo, "
    SELECT l.nome, COUNT(*) total
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    $whereSql
    GROUP BY l.nome
    ORDER BY total DESC
", $params);

$porPrioridade = fetchAllRelatorio($pdo, "
    SELECT os.prioridade, COUNT(*) total
    FROM ordens_servico os
    $whereSql
    GROUP BY os.prioridade
    ORDER BY total DESC
", $params);

$porAgente = fetchAllRelatorio($pdo, "
    SELECT agente, COUNT(DISTINCT os_id) total
    FROM (
        SELECT os.id AS os_id, COALESCE(u.nome, 'Sem agente') agente
        FROM ordens_servico os
        LEFT JOIN usuarios u ON u.id = os.agente_id
        $whereSql
        UNION ALL
        SELECT os.id AS os_id, u2.nome agente
        FROM ordens_servico os
        JOIN ordem_agentes oa2 ON oa2.ordem_servico_id = os.id AND oa2.papel = 'AUXILIAR'
        JOIN usuarios u2 ON u2.id = oa2.usuario_id
        $whereSql
    ) base_agentes
    GROUP BY agente
    ORDER BY total DESC
", array_merge($params, $params));

$rankingAtraso = fetchAllRelatorio($pdo, "
    SELECT
        COALESCE(u.nome, 'Sem agente') agente,
        COUNT(*) total_atrasadas,
        SUM(CEIL(TIMESTAMPDIFF(MINUTE, $slaPrazoSql, NOW()) / 1440)) dias_atraso
    FROM ordens_servico os
    LEFT JOIN usuarios u ON u.id = os.agente_id
    $whereSql
    " . ($whereSql ? " AND " : " WHERE ") . "
    $slaPrazoSql IS NOT NULL
    AND $slaPrazoSql < NOW()
    AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')
    GROUP BY agente
    ORDER BY dias_atraso DESC
    LIMIT 20
", $params);

$ordensAtrasadas = fetchAllRelatorio($pdo, "
    SELECT
        os.*,
        l.nome AS local_nome,
        c.nome AS categoria_nome,
        COALESCE(a.nome, 'Sem agente') AS agente_nome,
        CEIL(TIMESTAMPDIFF(MINUTE, $slaPrazoSql, NOW()) / 1440) AS dias_atraso
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    JOIN categorias c ON c.id = os.categoria_id
    LEFT JOIN usuarios a ON a.id = os.agente_id
    $whereSql
    " . ($whereSql ? " AND " : " WHERE ") . "
    $slaPrazoSql IS NOT NULL
    AND $slaPrazoSql < NOW()
    AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA')
    ORDER BY dias_atraso DESC
    $limiteOrdensRelatorioSql
", $params);

$essenciais = fetchAllRelatorio($pdo, "
    SELECT 
        os.*,
        l.nome AS local_nome,
        c.nome AS categoria_nome,
        COALESCE(g.nome, 'Sem gestor') AS gestor_nome,
        COALESCE(s.nome, 'Sem solicitante') AS solicitante_nome,
        COALESCE(a.nome, 'Sem agente') AS agente_nome
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    JOIN categorias c ON c.id = os.categoria_id
    LEFT JOIN usuarios g ON g.id = os.gestor_id
    LEFT JOIN usuarios s ON s.id = os.solicitante_id
    LEFT JOIN usuarios a ON a.id = os.agente_id
    $whereSql
    " . ($whereSql ? " AND " : " WHERE ") . "
    $condEssencial
    ORDER BY os.criado_em DESC
    $limiteOrdensRelatorioSql
", $params);

$ordensRecentes = fetchAllRelatorio($pdo, "
    SELECT 
        os.*,
        l.nome AS local_nome,
        c.nome AS categoria_nome,
        COALESCE(g.nome, 'Sem gestor') AS gestor_nome,
        COALESCE(s.nome, 'Sem solicitante') AS solicitante_nome,
        COALESCE(a.nome, 'Sem agente') AS agente_nome
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    JOIN categorias c ON c.id = os.categoria_id
    LEFT JOIN usuarios g ON g.id = os.gestor_id
    LEFT JOIN usuarios s ON s.id = os.solicitante_id
    LEFT JOIN usuarios a ON a.id = os.agente_id
    $whereSql
    ORDER BY os.criado_em DESC
    $limiteOrdensRelatorioSql
", $params);

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_ordens_servico.csv');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['numero_os', 'titulo', 'local', 'categoria', 'prioridade', 'status', 'agente', 'prazo', 'abertura', 'custo_materiais']);

    foreach ($ordensRecentes as $osCsv) {
        fputcsv($out, [
            $osCsv['numero_os'],
            $osCsv['titulo'],
            $osCsv['local_nome'],
            $osCsv['categoria_nome'],
            $osCsv['prioridade'],
            $osCsv['status'],
            $osCsv['agente_nome'],
            ($osCsv['sla_vencimento'] ?? null) ?: ($osCsv['prazo'] ?? ''),
            $osCsv['criado_em'],
            $osCsv['custo_materiais'] ?? '0',
        ]);
    }

    fclose($out);
    exit;
}

$fotosPorOs = [];
$anexosPorOs = [];
$documentosPorOs = [];
$totalAnexosRelatorio = 0;
$totalDocumentosRelatorio = 0;

foreach ($ordensRecentes as $osFoto) {
    $osFotoId = (int)$osFoto['id'];
    $anexosEncontrados = buscar_anexos_os_relatorio($pdo, $osFotoId);
    if ($anexosEncontrados) {
        $anexosPorOs[$osFotoId] = $anexosEncontrados;
        $totalAnexosRelatorio += count($anexosEncontrados);

        $gruposAnexos = agrupar_anexos_os_relatorio($anexosEncontrados);
        $fotosPorOs[$osFotoId] = [
            'antes' => $gruposAnexos['antes'],
            'depois' => $gruposAnexos['depois'],
            'geral' => $gruposAnexos['geral'],
        ];
        $documentosPorOs[$osFotoId] = $gruposAnexos['documentos'];
        $totalDocumentosRelatorio += count($gruposAnexos['documentos']);
    } else {
        $fotosEncontradas = buscar_fotos_os_relatorio($pdo, (int)$osFoto['id']);

        if ($fotosEncontradas) {
            $fotosPorOs[$osFotoId] = separar_fotos_antes_depois_relatorio($fotosEncontradas);
        }
    }
}

$totalOsComFotos = 0;
$totalFotosRelatorio = 0;
foreach ($fotosPorOs as $gruposFotosKpi) {
    $totalFotosOsKpi = count($gruposFotosKpi['antes'] ?? []) + count($gruposFotosKpi['depois'] ?? []) + count($gruposFotosKpi['geral'] ?? []);
    $totalFotosRelatorio += $totalFotosOsKpi;
    if ($totalFotosOsKpi > 0) {
        $totalOsComFotos++;
    }
}

$totalGeralRelatorio = (int)($total['total'] ?? 0);
$totalConcluidasRelatorio = (int)($concluidas['total'] ?? 0);
$totalBaseConclusaoRelatorio = (int)($baseTaxaConclusao['total'] ?? 0);
$totalOrdensExibidasRelatorio = count($ordensRecentes);
$percentualConclusaoRelatorio = $totalBaseConclusaoRelatorio > 0 ? round(($totalConcluidasRelatorio / $totalBaseConclusaoRelatorio) * 100, 1) : 0;
$percentualFotosRelatorio = $totalGeralRelatorio > 0 ? round(($totalOsComFotos / $totalGeralRelatorio) * 100, 1) : 0;
$periodoRelatorio = ($filtroInicio || $filtroFim)
    ? (($filtroInicio ? date('d/m/Y', strtotime($filtroInicio)) : 'inicio') . ' ate ' . ($filtroFim ? date('d/m/Y', strtotime($filtroFim)) : 'hoje'))
    : 'Todos os periodos';

$custoMateriaisDisponivel = coluna_existe_relatorio($pdo, 'ordens_servico', 'custo_materiais');
$custoTotalRelatorio = 0.0;
if ($custoMateriaisDisponivel) {
    $custoTotalLinha = fetchOneRelatorio($pdo, "
        SELECT COALESCE(SUM(os.custo_materiais), 0) total
        FROM ordens_servico os
        $whereSql
    ", $params);
    $custoTotalRelatorio = (float)($custoTotalLinha['total'] ?? 0);
}

$condRiscoAtivo = "
    os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','PAUSADA')
    AND (
        $condEssencial
        OR ($slaPrazoSql IS NOT NULL AND $slaPrazoSql < NOW())
    )
";

$atrasadasTotalLinha = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
    $andWhereSql
    $slaPrazoSql IS NOT NULL
    AND $slaPrazoSql < NOW()
    AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','PAUSADA')
", $params);
$totalAtrasadasRelatorio = (int)($atrasadasTotalLinha['total'] ?? 0);

$riscoAtivoLinha = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
    $andWhereSql
    $condRiscoAtivo
", $params);
$totalRiscoAtivoRelatorio = (int)($riscoAtivoLinha['total'] ?? 0);

$criticasAbertasLinha = fetchOneRelatorio($pdo, "
    SELECT COUNT(*) total
    FROM ordens_servico os
    $whereSql
    $andWhereSql
    os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','PAUSADA')
    AND $condEssencial
", $params);
$totalCriticasAbertasRelatorio = (int)($criticasAbertasLinha['total'] ?? 0);

$contagemStatusRelatorio = [];
foreach ($porStatus as $statusResumoRelatorio) {
    $contagemStatusRelatorio[(string)$statusResumoRelatorio['status']] = (int)$statusResumoRelatorio['total'];
}

$totalPausadasRelatorio = (int)($contagemStatusRelatorio['PAUSADA'] ?? 0);
$totalSemaforoVerdeRelatorio = $totalConcluidasRelatorio;
$totalSemaforoVermelhoRelatorio = $totalRiscoAtivoRelatorio;
$totalSemaforoCinzaRelatorio = $totalPausadasRelatorio;
$totalSemaforoAmareloRelatorio = max(
    0,
    $totalGeralRelatorio - $totalSemaforoVerdeRelatorio - $totalSemaforoVermelhoRelatorio - $totalSemaforoCinzaRelatorio
);

$idadeBacklogRelatorio = fetchOneRelatorio($pdo, "
    SELECT
        SUM(CASE WHEN TIMESTAMPDIFF(DAY, os.criado_em, NOW()) <= 2 THEN 1 ELSE 0 END) ate_2,
        SUM(CASE WHEN TIMESTAMPDIFF(DAY, os.criado_em, NOW()) BETWEEN 3 AND 7 THEN 1 ELSE 0 END) ate_7,
        SUM(CASE WHEN TIMESTAMPDIFF(DAY, os.criado_em, NOW()) BETWEEN 8 AND 15 THEN 1 ELSE 0 END) ate_15,
        SUM(CASE WHEN TIMESTAMPDIFF(DAY, os.criado_em, NOW()) > 15 THEN 1 ELSE 0 END) acima_15
    FROM ordens_servico os
    $whereSql
    $andWhereSql
    os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','PAUSADA')
", $params);

$evolucaoRelatorio = fetchAllRelatorio($pdo, "
    SELECT
        DATE_FORMAT(os.criado_em, '%Y-%m') periodo,
        COUNT(*) abertas,
        SUM(CASE WHEN os.status IN ('CONCLUIDA_PELO_AGENTE','ENCERRADA') THEN 1 ELSE 0 END) concluidas
    FROM ordens_servico os
    $whereSql
    GROUP BY periodo
    ORDER BY periodo DESC
    LIMIT 6
", $params);
$evolucaoRelatorio = array_reverse($evolucaoRelatorio);
$maiorVolumeEvolucaoRelatorio = 1;
foreach ($evolucaoRelatorio as $linhaEvolucaoRelatorio) {
    $maiorVolumeEvolucaoRelatorio = max($maiorVolumeEvolucaoRelatorio, (int)($linhaEvolucaoRelatorio['abertas'] ?? 0));
}

$riscosPorLocalRelatorio = fetchAllRelatorio($pdo, "
    SELECT l.nome, COUNT(*) total
    FROM ordens_servico os
    JOIN locais l ON l.id = os.local_id
    $whereSql
    $andWhereSql
    $condRiscoAtivo
    GROUP BY l.nome
    ORDER BY total DESC, l.nome ASC
    LIMIT 5
", $params);

$riscosPorCategoriaRelatorio = fetchAllRelatorio($pdo, "
    SELECT c.nome, COUNT(*) total
    FROM ordens_servico os
    JOIN categorias c ON c.id = os.categoria_id
    $whereSql
    $andWhereSql
    $condRiscoAtivo
    GROUP BY c.nome
    ORDER BY total DESC, c.nome ASC
    LIMIT 5
", $params);

$idsAtrasadasRelatorio = [];
foreach ($ordensAtrasadas as $ordemAtrasadaResumo) {
    $idsAtrasadasRelatorio[(int)$ordemAtrasadaResumo['id']] = true;
}

$ordensApresentacaoAgrupadas = [
    'risco' => [
        'titulo' => 'Riscos, criticidade e SLA',
        'subtitulo' => 'OS criticas, urgentes ou vencidas que merecem decisao ou acompanhamento proximo.',
        'ordens' => [],
    ],
    'execucao' => [
        'titulo' => 'Execucao em andamento',
        'subtitulo' => 'OS abertas, atribuidas ou em execucao operacional.',
        'ordens' => [],
    ],
    'aguardando' => [
        'titulo' => 'Aguardando e pausadas',
        'subtitulo' => 'Itens dependentes de material, liberacao, definicao ou pausa operacional.',
        'ordens' => [],
    ],
    'concluidas' => [
        'titulo' => 'Concluidas e encerradas',
        'subtitulo' => 'Entregas finalizadas no periodo filtrado, com anexos quando registrados.',
        'ordens' => [],
    ],
    'outras' => [
        'titulo' => 'Outras situacoes',
        'subtitulo' => 'OS fora dos grupos principais, como reprovadas ou estados residuais.',
        'ordens' => [],
    ],
];

foreach ($ordensRecentes as $ordemApresentacao) {
    $statusApresentacao = strtoupper((string)($ordemApresentacao['status'] ?? ''));
    $prioridadeApresentacao = strtoupper((string)($ordemApresentacao['prioridade'] ?? ''));
    $idApresentacao = (int)($ordemApresentacao['id'] ?? 0);
    $ehConcluidaApresentacao = in_array($statusApresentacao, ['CONCLUIDA_PELO_AGENTE', 'ENCERRADA'], true);
    $ehRiscoApresentacao = (
        !$ehConcluidaApresentacao &&
        (
            in_array($prioridadeApresentacao, ['CRITICA', 'URGENTE'], true) ||
            comeca_com($ordemApresentacao['titulo'] ?? '', '[SERVICO ESSENCIAL]') ||
            isset($idsAtrasadasRelatorio[$idApresentacao])
        )
    );

    if ($statusApresentacao === 'PAUSADA') {
        $chaveGrupoApresentacao = 'aguardando';
    } elseif ($ehRiscoApresentacao) {
        $chaveGrupoApresentacao = 'risco';
    } elseif (in_array($statusApresentacao, ['ABERTA', 'ATRIBUIDA', 'EM_ANDAMENTO'], true)) {
        $chaveGrupoApresentacao = 'execucao';
    } elseif (in_array($statusApresentacao, ['AGUARDANDO_MATERIAL', 'PAUSADA'], true)) {
        $chaveGrupoApresentacao = 'aguardando';
    } elseif ($ehConcluidaApresentacao) {
        $chaveGrupoApresentacao = 'concluidas';
    } else {
        $chaveGrupoApresentacao = 'outras';
    }

    $ordensApresentacaoAgrupadas[$chaveGrupoApresentacao]['ordens'][] = $ordemApresentacao;
}

$pesoPrioridadeApresentacao = ['CRITICA' => 0, 'URGENTE' => 1, 'ALTA' => 2, 'MEDIA' => 3, 'BAIXA' => 4];
foreach ($ordensApresentacaoAgrupadas as &$grupoApresentacaoOrdenacao) {
    usort($grupoApresentacaoOrdenacao['ordens'], function ($a, $b) use ($idsAtrasadasRelatorio, $pesoPrioridadeApresentacao) {
        $aAtrasada = isset($idsAtrasadasRelatorio[(int)($a['id'] ?? 0)]) ? 0 : 1;
        $bAtrasada = isset($idsAtrasadasRelatorio[(int)($b['id'] ?? 0)]) ? 0 : 1;
        if ($aAtrasada !== $bAtrasada) {
            return $aAtrasada <=> $bAtrasada;
        }

        $aPeso = $pesoPrioridadeApresentacao[strtoupper((string)($a['prioridade'] ?? ''))] ?? 99;
        $bPeso = $pesoPrioridadeApresentacao[strtoupper((string)($b['prioridade'] ?? ''))] ?? 99;
        if ($aPeso !== $bPeso) {
            return $aPeso <=> $bPeso;
        }

        return strtotime((string)($b['criado_em'] ?? '')) <=> strtotime((string)($a['criado_em'] ?? ''));
    });
}
unset($grupoApresentacaoOrdenacao);

$totalBacklogAntigoRelatorio = (int)($idadeBacklogRelatorio['acima_15'] ?? 0);
$totalAguardandoApresentacao = count($ordensApresentacaoAgrupadas['aguardando']['ordens'] ?? []);

$mensagensExecutivasRelatorio = [];
if ($totalRiscoAtivoRelatorio > 0) {
    $mensagensExecutivasRelatorio[] = $totalRiscoAtivoRelatorio . ' OS estao em risco ativo por criticidade, urgencia, essencialidade ou SLA vencido.';
}
if ($percentualConclusaoRelatorio >= 80) {
    $mensagensExecutivasRelatorio[] = 'A taxa de conclusao esta em ' . $percentualConclusaoRelatorio . '%, indicando boa conversao das OS elegiveis.';
} elseif ($totalBaseConclusaoRelatorio > 0) {
    $mensagensExecutivasRelatorio[] = 'A taxa de conclusao esta em ' . $percentualConclusaoRelatorio . '%, abaixo de um patamar executivo recomendado.';
}
if ($totalBacklogAntigoRelatorio > 0) {
    $mensagensExecutivasRelatorio[] = $totalBacklogAntigoRelatorio . ' OS ativas estao abertas ha mais de 15 dias e pedem plano de destravamento.';
}
if ($totalOsComFotos > 0) {
    $mensagensExecutivasRelatorio[] = $totalOsComFotos . ' OS possuem evidencia visual/documental registrada no filtro atual.';
}
if (!$mensagensExecutivasRelatorio) {
    $mensagensExecutivasRelatorio[] = 'Nao ha pontos criticos destacados no filtro atual; manter acompanhamento operacional de rotina.';
}

$entregasExecutivasRelatorio = [
    $totalConcluidasRelatorio . ' OS concluidas ou encerradas no recorte atual.',
    $totalOsComFotos . ' OS com anexos de imagem para evidenciar execucao.',
    'Custo de materiais registrado: R$ ' . number_format($custoTotalRelatorio, 2, ',', '.'),
];

$pendenciasExecutivasRelatorio = [
    ($abertas['total'] ?? 0) . ' OS ainda em aberto, andamento ou aguardando material.',
    $totalRiscoAtivoRelatorio . ' OS classificadas como risco ativo.',
    $totalBacklogAntigoRelatorio . ' OS ativas acima de 15 dias.',
];

$decisoesExecutivasRelatorio = [];
if ($totalRiscoAtivoRelatorio > 0) {
    $decisoesExecutivasRelatorio[] = 'Priorizar as OS de risco/SLA com responsavel e prazo de regularizacao.';
}
if ($totalAguardandoApresentacao > 0) {
    $decisoesExecutivasRelatorio[] = 'Definir acao para OS aguardando material, liberacao ou pausa administrativa.';
}
if ($totalBacklogAntigoRelatorio > 0) {
    $decisoesExecutivasRelatorio[] = 'Revisar backlog acima de 15 dias e separar impedimento real de baixa prioridade.';
}
if (!$decisoesExecutivasRelatorio) {
    $decisoesExecutivasRelatorio[] = 'Manter cadencia atual e revisar novos riscos no proximo ciclo executivo.';
}

$debugFotosRelatorio = [];
if (isset($_GET['debug_fotos']) && $_GET['debug_fotos'] === '1') {
    try {
        $tabsDebug = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND (TABLE_NAME LIKE '%foto%' OR TABLE_NAME LIKE '%anexo%' OR TABLE_NAME LIKE '%arquivo%' OR TABLE_NAME LIKE '%upload%' OR TABLE_NAME LIKE '%evidencia%' OR TABLE_NAME LIKE '%registro%') ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tabsDebug as $tabDebug) {
            $colsDebug = $pdo->query("SHOW COLUMNS FROM `$tabDebug`")->fetchAll(PDO::FETCH_ASSOC);
            $debugFotosRelatorio[] = [
                'tabela' => $tabDebug,
                'colunas' => implode(', ', array_map(fn($c) => $c['Field'], $colsDebug)),
            ];
        }
    } catch (Throwable $e) {
        $debugFotosRelatorio[] = ['tabela' => 'ERRO', 'colunas' => $e->getMessage()];
    }
}

$statusDisponiveis = [
    'ABERTA',
    'ATRIBUIDA',
    'EM_ANDAMENTO',
    'AGUARDANDO_MATERIAL',
    'PAUSADA',
    'CONCLUIDA_PELO_AGENTE',
    'REPROVADA',
    'ENCERRADA'
];

$categoriasFiltro = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$locaisFiltro = $pdo->query("SELECT id, nome FROM locais ORDER BY nome")->fetchAll();
$agentesFiltro = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS') ORDER BY nome")->fetchAll();
$queryBaseRelatorio = $_GET;
unset($queryBaseRelatorio['export'], $queryBaseRelatorio['imprimir']);
$csvQuery = http_build_query(array_merge($queryBaseRelatorio, ['export' => 'csv']));
$apresentacaoQuery = http_build_query(array_merge($queryBaseRelatorio, ['apresentacao' => '1', 'imprimir' => '1']));
$modoNormalQueryArgs = $queryBaseRelatorio;
unset($modoNormalQueryArgs['apresentacao']);
$modoNormalQuery = http_build_query($modoNormalQueryArgs);
$modoNormalUrl = $modoNormalQuery !== '' ? app_path('relatorios', $modoNormalQuery) : app_path('relatorios');

$semanaCronograma = $_GET['semana_cronograma'] ?? date('Y-m-d');
$cronogramaInicio = inicio_semana_relatorio($semanaCronograma);
$cronogramaFim = fim_semana_relatorio($semanaCronograma);
$filtroAgenteCronograma = (int)($_GET['agente_cronograma'] ?? 0);

$agentesCronograma = $pdo->query("
    SELECT id, nome
    FROM usuarios
    WHERE perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS')
    AND status = 'ATIVO'
    ORDER BY nome
")->fetchAll();

$whereCronograma = ['ca.semana_inicio = ?'];
$paramsCronograma = [$cronogramaInicio];

if ($user['perfil'] === 'AGENTE') {
    $whereCronograma[] = 'ca.agente_id = ?';
    $paramsCronograma[] = $user['id'];
}

if ($filtroAgenteCronograma > 0 && $user['perfil'] !== 'AGENTE') {
    $whereCronograma[] = 'ca.agente_id = ?';
    $paramsCronograma[] = $filtroAgenteCronograma;
}

$whereCronogramaSql = 'WHERE ' . implode(' AND ', $whereCronograma);

$cronogramaResumo = fetchOneRelatorio($pdo, "
    SELECT 
        COUNT(*) total,
        SUM(CASE WHEN ca.status = 'PLANEJADO' THEN 1 ELSE 0 END) planejado,
        SUM(CASE WHEN ca.status = 'EM_ANDAMENTO' THEN 1 ELSE 0 END) em_andamento,
        SUM(CASE WHEN ca.status = 'CONCLUIDO' THEN 1 ELSE 0 END) concluido,
        SUM(CASE WHEN ca.status = 'CANCELADO' THEN 1 ELSE 0 END) cancelado
    FROM cronograma_agentes ca
    $whereCronogramaSql
", $paramsCronograma);

$cronogramaAgentes = fetchAllRelatorio($pdo, "
    SELECT
        ca.*,
        a.nome AS agente_nome,
        g.nome AS gestor_nome,
        COALESCE(l.nome, '-') AS local_nome
    FROM cronograma_agentes ca
    JOIN usuarios a ON a.id = ca.agente_id
    JOIN usuarios g ON g.id = ca.gestor_id
    LEFT JOIN locais l ON l.id = ca.local_id
    $whereCronogramaSql
    ORDER BY ca.dia_semana ASC, ca.hora_inicio ASC, a.nome ASC
", $paramsCronograma);

$diasCronograma = [
    1 => 'Segunda-feira',
    2 => 'Terca-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sabado',
    7 => 'Domingo'
];

$statusCronograma = [
    'PLANEJADO' => 'Planejado',
    'EM_ANDAMENTO' => 'Em andamento',
    'CONCLUIDO' => 'Concluido',
    'CANCELADO' => 'Cancelado'
];

render_header('Relatorios');
?>

<style>
:root {
    --relatorio-azul: #143a5a;
    --relatorio-azul-2: #1f5b85;
    --relatorio-cinza: #f4f7fa;
    --relatorio-borda: #d9e2ea;
    --relatorio-texto: #1f2933;
}

.relatorio-executivo {
    background: #fff;
}

.executive-sheet {
    border: 1px solid var(--relatorio-borda);
    border-radius: 18px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 8px 26px rgba(15, 23, 42, .08);
}

.executive-header {
    display: grid;
    grid-template-columns: 90px 1fr 190px;
    gap: 16px;
    align-items: center;
    padding: 18px 22px;
    background: linear-gradient(135deg, var(--relatorio-azul), var(--relatorio-azul-2));
    color: #fff;
}

.executive-logo-box {
    width: 74px;
    height: 74px;
    border-radius: 16px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.35);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.executive-logo-box img {
    max-width: 64px;
    max-height: 64px;
}

.executive-title h1 {
    font-size: 23px;
    margin: 0 0 6px;
    font-weight: 800;
    letter-spacing: .2px;
}

.executive-title p {
    margin: 0;
    opacity: .92;
    font-size: 13px;
}

.executive-meta {
    text-align: right;
    font-size: 12px;
    line-height: 1.5;
}

.executive-body {
    padding: 18px 22px 22px;
}

.executive-section-title {
    display: flex;
    justify-content: space-between;
    align-items: end;
    gap: 12px;
    margin: 18px 0 10px;
    padding-bottom: 8px;
    border-bottom: 2px solid var(--relatorio-borda);
}

.executive-section-title h2 {
    font-size: 17px;
    margin: 0;
    color: var(--relatorio-azul);
    font-weight: 800;
}

.executive-section-title small {
    color: #687789;
}

.kpi-executivo-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 14px;
}

.kpi-executivo {
    border: 1px solid var(--relatorio-borda);
    border-radius: 14px;
    padding: 14px;
    background: linear-gradient(180deg, #fff, var(--relatorio-cinza));
}

.kpi-executivo .numero {
    font-size: 28px;
    line-height: 1;
    font-weight: 800;
    color: var(--relatorio-azul);
}

.kpi-executivo .rotulo {
    margin-top: 7px;
    font-size: 12px;
    color: #5f6f80;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .3px;
}

.kpi-executivo .numero.numero-moeda {
    font-size: 21px;
}

.executive-cover-grid {
    display: grid;
    grid-template-columns: 1.05fr .95fr;
    gap: 14px;
    margin-bottom: 14px;
}

.executive-highlight,
.executive-panel,
.executive-insight-card,
.executive-conclusion {
    border: 1px solid var(--relatorio-borda);
    border-radius: 12px;
    background: #fff;
    padding: 14px;
}

.executive-highlight {
    background: linear-gradient(180deg, #fff, #f7fafc);
    border-left: 6px solid var(--relatorio-azul);
}

.executive-highlight h3,
.executive-panel h3,
.executive-insight-card h3,
.executive-conclusion h3 {
    color: var(--relatorio-azul);
    font-size: 14px;
    font-weight: 900;
    margin: 0 0 10px;
}

.executive-highlight ul,
.executive-conclusion ul {
    margin: 0;
    padding-left: 18px;
}

.executive-highlight li,
.executive-conclusion li {
    color: var(--relatorio-texto);
    margin-bottom: 5px;
}

.semaforo-executivo {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.semaforo-item {
    border: 1px solid var(--relatorio-borda);
    border-top: 5px solid #64748b;
    border-radius: 10px;
    padding: 10px;
    background: #fbfcfd;
}

.semaforo-item strong {
    color: var(--relatorio-texto);
    display: block;
    font-size: 24px;
    line-height: 1;
}

.semaforo-item span {
    color: #475569;
    display: block;
    font-size: 11px;
    font-weight: 900;
    margin-top: 5px;
    text-transform: uppercase;
}

.semaforo-vermelho { border-top-color: #b91c1c; }
.semaforo-amarelo { border-top-color: #ca8a04; }
.semaforo-verde { border-top-color: #15803d; }
.semaforo-cinza { border-top-color: #64748b; }

.executive-insight-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px;
    margin: 14px 0;
}

.executive-mini-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.executive-mini-list li {
    align-items: center;
    border-bottom: 1px solid #edf2f7;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding: 6px 0;
}

.executive-mini-list span {
    min-width: 0;
    overflow-wrap: anywhere;
}

.executive-mini-list li:last-child {
    border-bottom: 0;
}

.executive-mini-list b {
    color: var(--relatorio-azul);
}

.evolucao-linha {
    align-items: center;
    display: grid;
    grid-template-columns: 58px 1fr 42px;
    gap: 8px;
    margin-bottom: 8px;
}

.evolucao-barras {
    display: grid;
    gap: 3px;
}

.evolucao-barra {
    background: #e5edf5;
    border-radius: 999px;
    height: 7px;
    overflow: hidden;
}

.evolucao-barra span {
    display: block;
    height: 100%;
}

.evolucao-abertas span {
    background: #1f5b85;
}

.evolucao-concluidas span {
    background: #15803d;
}

.idade-backlog-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.idade-backlog-item {
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    padding: 8px;
    text-align: center;
}

.idade-backlog-item strong {
    color: var(--relatorio-azul);
    display: block;
    font-size: 21px;
    line-height: 1;
}

.idade-backlog-item span {
    color: #5f6f80;
    display: block;
    font-size: 11px;
    font-weight: 800;
    margin-top: 5px;
}

.executive-conclusion {
    background: #f8fafc;
    margin: 14px 0;
}

.executive-conclusion-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.executive-conclusion h4 {
    color: var(--relatorio-azul);
    font-size: 13px;
    font-weight: 900;
    margin: 0 0 8px;
}

.resumo-executivo-grid {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 14px;
}

.resumo-box {
    border: 1px solid var(--relatorio-borda);
    border-radius: 14px;
    background: #fff;
    padding: 14px;
}

.resumo-box h3 {
    font-size: 14px;
    margin: 0 0 10px;
    color: var(--relatorio-azul);
    font-weight: 800;
}

.resumo-box ul {
    margin: 0;
    padding-left: 18px;
    color: var(--relatorio-texto);
}

.resumo-box li {
    margin-bottom: 5px;
}

.apresentacao-panorama {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
    margin: 14px 0 8px;
}

.apresentacao-panorama-item {
    border: 1px solid var(--relatorio-borda);
    border-left: 5px solid var(--relatorio-azul);
    border-radius: 10px;
    background: #fff;
    padding: 10px;
}

.apresentacao-panorama-item strong {
    color: var(--relatorio-azul);
    display: block;
    font-size: 24px;
    line-height: 1;
}

.apresentacao-panorama-item span {
    display: block;
    color: var(--relatorio-texto);
    font-size: 12px;
    font-weight: 800;
    margin-top: 5px;
    text-transform: uppercase;
}

.apresentacao-panorama-item small {
    color: #657487;
    display: block;
    line-height: 1.25;
    margin-top: 4px;
}

.apresentacao-panorama-item.grupo-risco { border-left-color: #9f1239; }
.apresentacao-panorama-item.grupo-execucao { border-left-color: #0f766e; }
.apresentacao-panorama-item.grupo-aguardando { border-left-color: #b45309; }
.apresentacao-panorama-item.grupo-concluidas { border-left-color: #15803d; }
.apresentacao-panorama-item.grupo-outras { border-left-color: #475569; }

.apresentacao-grupo-header {
    align-items: center;
    background: #f7fafc;
    border: 1px solid var(--relatorio-borda);
    border-left: 6px solid var(--relatorio-azul);
    border-radius: 10px;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin: 16px 0 10px;
    padding: 10px 12px;
}

.apresentacao-grupo-header h3 {
    color: var(--relatorio-azul);
    font-size: 15px;
    font-weight: 900;
    margin: 0;
}

.apresentacao-grupo-header p {
    color: #5f6f80;
    font-size: 12px;
    margin: 3px 0 0;
}

.apresentacao-grupo-total {
    background: #fff;
    border: 1px solid var(--relatorio-borda);
    border-radius: 8px;
    color: var(--relatorio-azul);
    font-weight: 900;
    min-width: 72px;
    padding: 7px 10px;
    text-align: center;
}

.apresentacao-grupo-header.grupo-risco { border-left-color: #9f1239; }
.apresentacao-grupo-header.grupo-execucao { border-left-color: #0f766e; }
.apresentacao-grupo-header.grupo-aguardando { border-left-color: #b45309; }
.apresentacao-grupo-header.grupo-concluidas { border-left-color: #15803d; }
.apresentacao-grupo-header.grupo-outras { border-left-color: #475569; }

.relatorio-apresentacao .executive-photo-card.grupo-risco .executive-photo-head { background: linear-gradient(90deg, #7f1d1d, #9f1239); }
.relatorio-apresentacao .executive-photo-card.grupo-execucao .executive-photo-head { background: linear-gradient(90deg, #115e59, #0f766e); }
.relatorio-apresentacao .executive-photo-card.grupo-aguardando .executive-photo-head { background: linear-gradient(90deg, #92400e, #b45309); }
.relatorio-apresentacao .executive-photo-card.grupo-concluidas .executive-photo-head { background: linear-gradient(90deg, #166534, #15803d); }
.relatorio-apresentacao .executive-photo-card.grupo-outras .executive-photo-head { background: linear-gradient(90deg, #334155, #475569); }

.executive-photo-card {
    border: 1px solid var(--relatorio-borda);
    border-radius: 16px;
    margin-bottom: 18px;
    overflow: hidden;
    background: #fff;
    break-inside: avoid;
    page-break-inside: avoid;
}

.executive-photo-head {
    padding: 12px 14px;
    background: var(--relatorio-cinza);
    border-bottom: 1px solid var(--relatorio-borda);
}

.executive-photo-head strong {
    color: var(--relatorio-azul);
    font-size: 15px;
}

.executive-photo-meta {
    margin-top: 4px;
    color: #667789;
    font-size: 12px;
}

.executive-photo-body {
    padding: 14px;
}

.os-completa-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 12px;
}

.os-completa-item {
    border: 1px solid var(--relatorio-borda);
    border-radius: 10px;
    background: #fbfcfd;
    padding: 8px;
    font-size: 12px;
}

.os-completa-item b {
    display: block;
    color: var(--relatorio-azul);
    font-size: 11px;
    text-transform: uppercase;
}

.os-completa-descricao {
    border: 1px solid var(--relatorio-borda);
    border-radius: 10px;
    background: #fff;
    margin-bottom: 12px;
    padding: 10px;
    font-size: 12px;
    white-space: pre-line;
}

.executive-photo-compare {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.executive-photo-col {
    border: 1px solid var(--relatorio-borda);
    border-radius: 14px;
    padding: 10px;
    background: #fbfcfd;
}

.executive-photo-col h3 {
    font-size: 13px;
    color: var(--relatorio-azul);
    text-transform: uppercase;
    letter-spacing: .4px;
    margin: 0 0 10px;
    font-weight: 900;
}

.executive-photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.executive-photo-item img {
    width: 100%;
    height: 145px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #cfd8e3;
    background: #fff;
}

.anexos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
}

.anexo-card-img {
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 8px;
    background: #fff;
}

.anexo-card-img img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 10px;
    border: 1px solid #eee;
    background: #f8f9fa;
}

.anexo-card-img small {
    color: #6c757d;
    display: block;
    margin-top: 6px;
}

.relatorio-apresentacao .executive-photo-card {
    border-radius: 8px;
    box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
}

.relatorio-apresentacao .executive-photo-head {
    background: linear-gradient(90deg, #143a5a, #1f5b85);
    color: #fff;
}

.relatorio-apresentacao .executive-photo-head strong,
.relatorio-apresentacao .executive-photo-meta {
    color: #fff;
}

.relatorio-apresentacao .executive-photo-item img {
    height: 260px;
    object-fit: contain;
    background: #f8fafc;
}

.relatorio-apresentacao .foto-legenda {
    color: #38485a;
    font-weight: 600;
}

@media (max-width: 900px) {
    .executive-header,
    .executive-cover-grid,
    .executive-insight-grid,
    .executive-conclusion-grid,
    .resumo-executivo-grid,
    .executive-photo-compare,
    .os-completa-grid,
    .apresentacao-panorama,
    .kpi-executivo-grid {
        grid-template-columns: 1fr;
    }

    .executive-meta {
        text-align: left;
    }
}

.relatorio-card {
    border: 0;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.06);
}

.kpi {
    font-size: 28px;
    font-weight: 700;
}

.kpi-label {
    color: #666;
    font-size: 14px;
}

.table-sm td,
.table-sm th {
    vertical-align: middle;
}

.essencial-label {
    background: #dc3545;
    color: #fff;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.cronograma-dia {
    border-left: 4px solid #0d6efd;
    background: #f8f9fa;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 12px;
}

.cronograma-item {
    background: #fff;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    padding: 10px;
    margin-top: 8px;
}

.status-PLANEJADO {
    background: #6c757d;
    color: #fff;
}

.status-EM_ANDAMENTO {
    background: #0d6efd;
    color: #fff;
}

.status-CONCLUIDO {
    background: #198754;
    color: #fff;
}

.status-CANCELADO {
    background: #dc3545;
    color: #fff;
}

.fotos-relatorio-os {
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 12px;
    margin-bottom: 14px;
    page-break-inside: avoid;
}

.fotos-comparativo {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.fotos-coluna {
    border: 1px solid #dee2e6;
    border-radius: 10px;
    padding: 10px;
    background: #fafafa;
}

.fotos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
}

.foto-relatorio {
    width: 100%;
    height: 145px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #ddd;
    background: #fff;
}

.foto-legenda {
    font-size: 12px;
    color: #666;
    margin-top: 4px;
}

.anexos-documentos {
    border-top: 1px solid var(--relatorio-borda);
    margin-top: 14px;
    padding-top: 12px;
}

.anexos-documentos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 10px;
}

.anexo-documento-card {
    border: 1px solid var(--relatorio-borda);
    border-radius: 10px;
    background: #fbfcfd;
    color: var(--relatorio-texto);
    display: block;
    padding: 10px;
    text-decoration: none;
}

.anexo-documento-card strong {
    color: var(--relatorio-azul);
    display: block;
    font-size: 12px;
    margin-bottom: 4px;
}

.anexo-documento-card small {
    display: block;
}

.fotos-relatorio-os > .mb-2 {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 8px;
}

.fotos-coluna h3 {
    border-bottom: 1px solid #dee2e6;
    padding-bottom: 6px;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .fotos-comparativo {
        grid-template-columns: 1fr;
    }
}

@media print {
    @page {
        size: A4 landscape;
        margin: 4mm;
    }

    html,
    body {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        color: #000 !important;
        font-size: 9px !important;
        line-height: 1.18 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .btn,
    form,
    .no-print,
    nav,
    .navbar,
    .sidebar,
    footer,
    body.imprimir-relatorio-executivo #area-cronograma,
    body:not(.imprimir-cronograma) #area-cronograma {
        display: none !important;
        visibility: hidden !important;
    }

    body.imprimir-relatorio-executivo * {
        visibility: hidden !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo,
    body.imprimir-relatorio-executivo #relatorio-executivo * {
        visibility: visible !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo {
        display: block !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 1px solid #000 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        background: #fff !important;
        overflow: visible !important;
    }

    body.imprimir-relatorio-executivo .executive-header {
        display: grid !important;
        grid-template-columns: 18mm 1fr 55mm !important;
        gap: 4mm !important;
        align-items: center !important;
        padding: 4mm !important;
        background: #fff !important;
        color: #000 !important;
        border-bottom: 1px solid #000 !important;
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    body.imprimir-relatorio-executivo .executive-logo-box {
        width: 15mm !important;
        height: 15mm !important;
        border-radius: 2mm !important;
        border: 1px solid #000 !important;
        background: #fff !important;
    }

    body.imprimir-relatorio-executivo .executive-logo-box img {
        max-width: 13mm !important;
        max-height: 13mm !important;
    }

    body.imprimir-relatorio-executivo .executive-title h1 {
        font-size: 15px !important;
        margin: 0 0 1.5mm !important;
        line-height: 1.08 !important;
    }

    body.imprimir-relatorio-executivo .executive-title p,
    body.imprimir-relatorio-executivo .executive-meta,
    body.imprimir-relatorio-executivo small,
    body.imprimir-relatorio-executivo .text-muted,
    body.imprimir-relatorio-executivo .executive-photo-meta,
    body.imprimir-relatorio-executivo .foto-legenda {
        font-size: 8.5px !important;
        line-height: 1.15 !important;
    }

    body.imprimir-relatorio-executivo .executive-body {
        padding: 3.5mm !important;
    }

    body.imprimir-relatorio-executivo .executive-section-title {
        margin: 3mm 0 2mm !important;
        padding-bottom: 1mm !important;
        border-bottom: 1px solid #000 !important;
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    body.imprimir-relatorio-executivo .executive-section-title h2,
    body.imprimir-relatorio-executivo h2.h5,
    body.imprimir-relatorio-executivo h3.h6,
    body.imprimir-relatorio-executivo .resumo-box h3 {
        font-size: 11px !important;
        line-height: 1.1 !important;
        margin: 0 0 1.5mm !important;
        color: #000 !important;
    }

    body.imprimir-relatorio-executivo .kpi-executivo-grid {
        display: grid !important;
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 2mm !important;
        margin-bottom: 2mm !important;
    }

    body.imprimir-relatorio-executivo .kpi-executivo {
        padding: 2.5mm !important;
        border: 1px solid #000 !important;
        border-radius: 2mm !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    body.imprimir-relatorio-executivo .kpi-executivo .numero,
    body.imprimir-relatorio-executivo .kpi {
        font-size: 16px !important;
        line-height: 1 !important;
        color: #000 !important;
    }

    body.imprimir-relatorio-executivo .kpi-executivo .rotulo,
    body.imprimir-relatorio-executivo .kpi-label {
        font-size: 7.5px !important;
        margin-top: 1mm !important;
        color: #000 !important;
    }

    body.imprimir-relatorio-executivo .kpi-executivo .numero.numero-moeda {
        font-size: 12px !important;
    }

    body.imprimir-relatorio-executivo .executive-cover-grid,
    body.imprimir-relatorio-executivo .executive-insight-grid,
    body.imprimir-relatorio-executivo .executive-conclusion-grid {
        display: grid !important;
        gap: 2mm !important;
    }

    body.imprimir-relatorio-executivo .executive-cover-grid {
        grid-template-columns: 1fr 1fr !important;
        margin-bottom: 2mm !important;
    }

    body.imprimir-relatorio-executivo .executive-insight-grid,
    body.imprimir-relatorio-executivo .executive-conclusion-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        margin: 2mm 0 !important;
    }

    body.imprimir-relatorio-executivo .executive-highlight,
    body.imprimir-relatorio-executivo .executive-panel,
    body.imprimir-relatorio-executivo .executive-insight-card,
    body.imprimir-relatorio-executivo .executive-conclusion {
        border: 1px solid #000 !important;
        border-radius: 2mm !important;
        padding: 2mm !important;
        background: #fff !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    body.imprimir-relatorio-executivo .executive-highlight h3,
    body.imprimir-relatorio-executivo .executive-panel h3,
    body.imprimir-relatorio-executivo .executive-insight-card h3,
    body.imprimir-relatorio-executivo .executive-conclusion h3,
    body.imprimir-relatorio-executivo .executive-conclusion h4 {
        color: #000 !important;
        font-size: 8.5px !important;
        margin: 0 0 1mm !important;
    }

    body.imprimir-relatorio-executivo .semaforo-executivo,
    body.imprimir-relatorio-executivo .idade-backlog-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 1mm !important;
    }

    body.imprimir-relatorio-executivo .semaforo-item,
    body.imprimir-relatorio-executivo .idade-backlog-item {
        border: 1px solid #c9c9c9 !important;
        border-top-width: 1mm !important;
        border-radius: 1mm !important;
        padding: 1mm !important;
    }

    body.imprimir-relatorio-executivo .semaforo-item strong,
    body.imprimir-relatorio-executivo .idade-backlog-item strong {
        font-size: 12px !important;
    }

    body.imprimir-relatorio-executivo .semaforo-item span,
    body.imprimir-relatorio-executivo .idade-backlog-item span,
    body.imprimir-relatorio-executivo .executive-mini-list li,
    body.imprimir-relatorio-executivo .evolucao-linha {
        font-size: 6.8px !important;
    }

    body.imprimir-relatorio-executivo .resumo-executivo-grid {
        display: grid !important;
        grid-template-columns: 1.15fr .85fr !important;
        gap: 2mm !important;
        margin-bottom: 2mm !important;
    }

    body.imprimir-relatorio-executivo .resumo-box,
    body.imprimir-relatorio-executivo .relatorio-card,
    body.imprimir-relatorio-executivo .executive-photo-card,
    body.imprimir-relatorio-executivo .executive-photo-col {
        border: 1px solid #000 !important;
        border-radius: 2mm !important;
        box-shadow: none !important;
        background: #fff !important;
        padding: 2mm !important;
        margin-bottom: 2mm !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    body.imprimir-relatorio-executivo .resumo-box ul {
        margin: 0 !important;
        padding-left: 4mm !important;
    }

    body.imprimir-relatorio-executivo .resumo-box li {
        margin-bottom: .7mm !important;
    }

    /* Remove a segunda linha de KPIs, pois ela repete os indicadores do cabeçalho e economiza espaço. */
    body.imprimir-relatorio-executivo .relatorio-kpi-operacional {
        display: none !important;
        visibility: hidden !important;
    }

    body.imprimir-relatorio-executivo .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-left: -1mm !important;
        margin-right: -1mm !important;
        gap: 0 !important;
    }

    body.imprimir-relatorio-executivo .row > [class*="col-"] {
        padding-left: 1mm !important;
        padding-right: 1mm !important;
        margin-bottom: 2mm !important;
    }

    body.imprimir-relatorio-executivo .col-md-2,
    body.imprimir-relatorio-executivo .col-md-3,
    body.imprimir-relatorio-executivo .col-md-4,
    body.imprimir-relatorio-executivo .col-md-5,
    body.imprimir-relatorio-executivo .col-md-6,
    body.imprimir-relatorio-executivo .col-md-7 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }

    body.imprimir-relatorio-executivo .table-responsive {
        overflow: visible !important;
    }

    body.imprimir-relatorio-executivo table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 0 !important;
        font-size: 8px !important;
        line-height: 1.08 !important;
    }

    body.imprimir-relatorio-executivo th,
    body.imprimir-relatorio-executivo td {
        padding: 1mm 1.2mm !important;
        border: 1px solid #c9c9c9 !important;
        vertical-align: top !important;
    }

    body.imprimir-relatorio-executivo .badge,
    body.imprimir-relatorio-executivo .essencial-label {
        display: inline-block !important;
        padding: .5mm 1mm !important;
        border: 1px solid #000 !important;
        border-radius: 1mm !important;
        background: #fff !important;
        color: #000 !important;
        font-size: 7px !important;
        line-height: 1 !important;
        white-space: nowrap !important;
    }

    body.imprimir-relatorio-executivo a {
        color: #000 !important;
        text-decoration: none !important;
    }

    body.imprimir-relatorio-executivo .executive-photo-card {
        padding: 0 !important;
        margin-bottom: 2.5mm !important;
        break-inside: avoid-page !important;
        page-break-inside: avoid !important;
    }

    body.imprimir-relatorio-executivo .executive-photo-head {
        padding: 2mm !important;
        background: #f2f2f2 !important;
        border-bottom: 1px solid #000 !important;
    }

    body.imprimir-relatorio-executivo .executive-photo-head strong {
        font-size: 9.5px !important;
        color: #000 !important;
    }

    body.imprimir-relatorio-executivo .executive-photo-body {
        padding: 2mm !important;
    }

    body.imprimir-relatorio-executivo .os-completa-grid {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 1.5mm !important;
        margin-bottom: 2mm !important;
    }

    body.imprimir-relatorio-executivo .os-completa-item,
    body.imprimir-relatorio-executivo .os-completa-descricao {
        border: 1px solid #c9c9c9 !important;
        border-radius: 1mm !important;
        background: #fff !important;
        padding: 1.2mm !important;
        font-size: 8px !important;
        line-height: 1.12 !important;
        margin-bottom: 1.5mm !important;
    }

    body.imprimir-relatorio-executivo .os-completa-item b {
        color: #000 !important;
        font-size: 7.5px !important;
    }

    body.imprimir-relatorio-executivo .executive-photo-compare,
    body.imprimir-relatorio-executivo .fotos-comparativo {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 2mm !important;
    }

    body.imprimir-relatorio-executivo .executive-photos-grid,
    body.imprimir-relatorio-executivo .fotos-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1.5mm !important;
    }

    body.imprimir-relatorio-executivo .foto-relatorio,
    body.imprimir-relatorio-executivo .executive-photo-item img {
        width: 100% !important;
        height: 26mm !important;
        object-fit: cover !important;
        border-radius: 1mm !important;
        border: 1px solid #777 !important;
        background: #fff !important;
        display: block !important;
    }

    body.imprimir-relatorio-executivo .foto-legenda {
        margin-top: .6mm !important;
        max-height: 8mm !important;
        overflow: hidden !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao {
        border: none !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-header {
        grid-template-columns: 18mm 1fr 54mm !important;
        gap: 4mm !important;
        padding: 4mm !important;
        background: #143a5a !important;
        color: #fff !important;
        border-bottom: 0 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-logo-box {
        width: 15mm !important;
        height: 15mm !important;
        border-color: rgba(255,255,255,.85) !important;
        background: rgba(255,255,255,.14) !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-logo-box img {
        max-width: 13mm !important;
        max-height: 13mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-title h1 {
        color: #fff !important;
        font-size: 18px !important;
        line-height: 1.05 !important;
        margin-bottom: 1mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-title p,
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-meta {
        color: #fff !important;
        font-size: 8.5px !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-body {
        padding: 3mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .kpi-executivo-grid {
        gap: 2mm !important;
        margin: 2mm 0 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .kpi-executivo {
        border-color: #c6d4e1 !important;
        padding: 2.5mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .kpi-executivo .numero {
        color: #143a5a !important;
        font-size: 18px !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .kpi-executivo .rotulo {
        font-size: 7px !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .resumo-executivo-grid {
        gap: 2mm !important;
        margin-bottom: 2mm !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .resumo-box {
        border-color: #c6d4e1 !important;
        padding: 2.5mm !important;
        font-size: 8px !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-panorama {
        display: grid !important;
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 1.5mm !important;
        margin: 2mm 0 3mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-panorama-item {
        border: 1px solid #c6d4e1 !important;
        border-left-width: 1.5mm !important;
        border-radius: 1.5mm !important;
        padding: 1.5mm !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-panorama-item strong {
        font-size: 15px !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-panorama-item span {
        font-size: 6.8px !important;
        margin-top: .7mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-panorama-item small {
        font-size: 6.4px !important;
        line-height: 1.08 !important;
        max-height: 10mm !important;
        overflow: hidden !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .grupo-risco { border-left-color: #9f1239 !important; }
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .grupo-execucao { border-left-color: #0f766e !important; }
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .grupo-aguardando { border-left-color: #b45309 !important; }
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .grupo-concluidas { border-left-color: #15803d !important; }
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .grupo-outras { border-left-color: #475569 !important; }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-body > .row,
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-body > .relatorio-card:not(.apresentacao-os-lista) {
        display: none !important;
        visibility: hidden !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-os-title {
        break-before: auto !important;
        page-break-before: auto !important;
        break-after: avoid !important;
        page-break-after: avoid !important;
        margin-top: 0 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-os-lista {
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-os-lista > h2,
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-os-lista > p {
        display: none !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-grupo-header {
        border: 1px solid #c6d4e1 !important;
        border-left-width: 2mm !important;
        border-radius: 1.5mm !important;
        display: flex !important;
        margin: 2mm 0 1.5mm !important;
        padding: 1.8mm 2mm !important;
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-grupo-header h3 {
        font-size: 9px !important;
        margin: 0 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-grupo-header p {
        font-size: 7px !important;
        line-height: 1.1 !important;
        margin: .6mm 0 0 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-grupo-total {
        font-size: 8px !important;
        min-width: 16mm !important;
        padding: 1mm 1.5mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-os-lista .executive-photo-card {
        break-before: auto !important;
        page-break-before: auto !important;
        break-inside: auto !important;
        page-break-inside: auto !important;
        border: 1px solid #c6d4e1 !important;
        border-radius: 2mm !important;
        margin: 0 0 2mm !important;
        padding: 0 !important;
        box-shadow: none !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .apresentacao-os-lista .executive-photo-card:first-of-type {
        break-before: avoid !important;
        page-break-before: avoid !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-head {
        background: #143a5a !important;
        color: #fff !important;
        border-bottom: 0 !important;
        padding: 2mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-head strong {
        color: #fff !important;
        font-size: 10.5px !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-meta {
        color: #eef5fb !important;
        font-size: 7.5px !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-card.grupo-risco .executive-photo-head {
        background: #7f1d1d !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-card.grupo-execucao .executive-photo-head {
        background: #115e59 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-card.grupo-aguardando .executive-photo-head {
        background: #92400e !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-card.grupo-concluidas .executive-photo-head {
        background: #166534 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-card.grupo-outras .executive-photo-head {
        background: #334155 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-body {
        padding: 2mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .os-completa-grid {
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 1mm !important;
        margin-bottom: 1.2mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .os-completa-item,
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .os-completa-descricao {
        border-color: #d3dde8 !important;
        font-size: 7.5px !important;
        line-height: 1.12 !important;
        padding: 1mm !important;
        margin-bottom: 1mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .os-completa-item b {
        color: #143a5a !important;
        font-size: 7px !important;
        margin-bottom: .3mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-compare {
        gap: 2mm !important;
        margin-top: 1mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-col {
        border-color: #d3dde8 !important;
        padding: 1.5mm !important;
        break-inside: auto !important;
        page-break-inside: auto !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-col h3,
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao h3.h6 {
        color: #143a5a !important;
        font-size: 8px !important;
        letter-spacing: 0 !important;
        margin-bottom: 1mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-col .executive-photos-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 1.5mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photos-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1.5mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .foto-relatorio,
    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .executive-photo-item img {
        height: 34mm !important;
        object-fit: contain !important;
        border: 1px solid #9fb0c2 !important;
        background: #f8fafc !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .foto-legenda {
        max-height: 8mm !important;
        overflow: hidden !important;
        color: #243447 !important;
        font-size: 7px !important;
        line-height: 1.08 !important;
        font-weight: 600 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .anexos-documentos {
        border-top: 1px solid #d3dde8 !important;
        margin-top: 1.5mm !important;
        padding-top: 1mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .anexos-documentos-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 1.5mm !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .anexo-documento-card {
        border: 1px solid #d3dde8 !important;
        color: #000 !important;
        padding: 1.2mm !important;
        font-size: 7px !important;
        line-height: 1.08 !important;
    }

    body.imprimir-relatorio-executivo #relatorio-executivo.relatorio-apresentacao .anexo-documento-card strong {
        color: #143a5a !important;
        font-size: 7.5px !important;
    }

    body.imprimir-relatorio-executivo h1,
    body.imprimir-relatorio-executivo h2,
    body.imprimir-relatorio-executivo h3,
    body.imprimir-relatorio-executivo thead {
        page-break-after: avoid !important;
        break-after: avoid !important;
    }

    body.imprimir-relatorio-executivo tr,
    body.imprimir-relatorio-executivo img,
    body.imprimir-relatorio-executivo .executive-photo-item {
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    body.imprimir-cronograma * {
        visibility: hidden !important;
    }

    body.imprimir-cronograma #area-cronograma,
    body.imprimir-cronograma #area-cronograma * {
        visibility: visible !important;
    }

    body.imprimir-cronograma #area-cronograma {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 4mm !important;
        background: #fff !important;
        border: none !important;
        box-shadow: none !important;
        font-size: 9px !important;
    }

    body.imprimir-cronograma #area-cronograma .card,
    body.imprimir-cronograma #area-cronograma .cronograma-dia,
    body.imprimir-cronograma #area-cronograma .cronograma-item {
        box-shadow: none !important;
        border: 1px solid #000 !important;
        background: #fff !important;
        padding: 2mm !important;
        margin-bottom: 2mm !important;
        break-inside: avoid !important;
        page-break-inside: avoid !important;
    }

    body.imprimir-cronograma #area-cronograma .badge {
        background: #fff !important;
        color: #000 !important;
        border: 1px solid #000 !important;
    }

    body.imprimir-cronograma #area-cronograma a {
        text-decoration: none !important;
        color: #000 !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h1 class="h3 mb-0">Relatorios de Ordens de Servico</h1>

    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-success" href="<?= e(app_path('relatorios', $csvQuery)) ?>">
            Exportar CSV
        </a>

        <button type="button" class="btn btn-outline-secondary" onclick="imprimirRelatorioCompleto()">
            Exportar PDF completo
        </button>

        <?php if ($modoApresentacao): ?>
            <button type="button" class="btn btn-primary" onclick="imprimirRelatorioCompleto('Relatorio_OS_Apresentacao', 5000)">
                Imprimir apresentacao executiva
            </button>

            <a class="btn btn-outline-secondary" href="<?= e($modoNormalUrl) ?>">
                Modo normal
            </a>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= e(app_path('relatorios', $apresentacaoQuery)) ?>">
                PDF apresentacao executiva
            </a>
        <?php endif; ?>

        <button type="button" class="btn btn-outline-primary" onclick="imprimirSomenteCronograma()">
            PDF do cronograma
        </button>
    </div>
</div>

<div class="card p-3 mb-3 relatorio-card no-print">
    <form method="get" class="row g-3">
        <input type="hidden" name="semana_cronograma" value="<?= e($cronogramaInicio) ?>">
        <input type="hidden" name="agente_cronograma" value="<?= e($filtroAgenteCronograma) ?>">
        <?php if ($modoApresentacao): ?>
            <input type="hidden" name="apresentacao" value="1">
        <?php endif; ?>

        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">Todos</option>

                <?php foreach ($statusDisponiveis as $s): ?>
                    <option value="<?= e($s) ?>" <?= $filtroStatus === $s ? 'selected' : '' ?>>
                        <?= e(str_replace('_', ' ', $s)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">Prioridade</label>
            <select class="form-select" name="prioridade">
                <option value="">Todas</option>
                <?php foreach (['BAIXA','MEDIA','ALTA','CRITICA','URGENTE'] as $p): ?>
                    <option value="<?= e($p) ?>" <?= $filtroPrioridade === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Categoria</label>
            <select class="form-select" name="categoria_id">
                <option value="">Todas</option>
                <?php foreach ($categoriasFiltro as $cat): ?>
                    <option value="<?= e($cat['id']) ?>" <?= $filtroCategoria === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Local</label>
            <select class="form-select" name="local_id">
                <option value="">Todos</option>
                <?php foreach ($locaisFiltro as $loc): ?>
                    <option value="<?= e($loc['id']) ?>" <?= $filtroLocal === (int)$loc['id'] ? 'selected' : '' ?>><?= e($loc['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($user['perfil'] !== 'AGENTE'): ?>
            <div class="col-md-3">
                <label class="form-label">Agente</label>
                <select class="form-select" name="agente_id">
                    <option value="">Todos</option>
                    <?php foreach ($agentesFiltro as $agf): ?>
                        <option value="<?= e($agf['id']) ?>" <?= $filtroAgente === (int)$agf['id'] ? 'selected' : '' ?>><?= e($agf['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-md-3">
            <label class="form-label">Data inicial</label>
            <input class="form-control" type="date" name="inicio" value="<?= e($filtroInicio) ?>">
        </div>

        <div class="col-md-3">
            <label class="form-label">Data final</label>
            <input class="form-control" type="date" name="fim" value="<?= e($filtroFim) ?>">
        </div>

        <div class="col-md-3 d-flex align-items-end gap-2">
            <button class="btn btn-primary w-100">Filtrar</button>
            <a href="<?= e(app_path('relatorios')) ?>" class="btn btn-outline-secondary w-100">Limpar</a>
        </div>
    </form>
</div>

<div id="relatorio-executivo" class="relatorio-executivo executive-sheet mb-3<?= $modoApresentacao ? ' relatorio-apresentacao' : '' ?>">
    <div class="executive-header">
        <div class="executive-logo-box">
            <img src="../public/assets/imobilizado_logo.png" loading="eager" decoding="sync" alt="Logo">
        </div>
        <div class="executive-title">
            <h1><?= $modoApresentacao ? 'Apresentacao Executiva de OS' : 'Relatorio Executivo de Ordens de Servico' ?></h1>
            <p><?= $modoApresentacao ? 'Panorama para diretoria e acionistas, com riscos, execucao, entregas e anexos.' : 'Consolidacao operacional com KPIs, SLA, atrasos, servicos criticos e anexos por OS.' ?></p>
        </div>
        <div class="executive-meta">
            <div><b>Período:</b> <?= e($periodoRelatorio) ?></div>
            <div><b>Emissão (dispositivo):</b> <span class="emissao-dispositivo" data-fallback="<?= e(date('d/m/Y H:i')) ?>"><?= e(date('d/m/Y H:i')) ?></span></div>
            <div><b>Usuário:</b> <?= e($user['nome'] ?? 'Sistema') ?></div>
        </div>
    </div>

    <div class="executive-body">
        <div class="executive-section-title">
            <h2>Indicadores gerais</h2>
            <small>Visão consolidada das OS filtradas</small>
        </div>

        <div class="kpi-executivo-grid">
            <div class="kpi-executivo"><div class="numero"><?= e($totalGeralRelatorio) ?></div><div class="rotulo">Total de OS</div></div>
            <div class="kpi-executivo"><div class="numero"><?= e($totalConcluidasRelatorio) ?></div><div class="rotulo">Concluídas / encerradas</div></div>
            <div class="kpi-executivo"><div class="numero"><?= e($percentualConclusaoRelatorio) ?>%</div><div class="rotulo">Taxa de conclusão</div></div>
            <div class="kpi-executivo"><div class="numero"><?= e($modoApresentacao ? $totalAnexosRelatorio : $totalFotosRelatorio) ?></div><div class="rotulo"><?= $modoApresentacao ? 'Anexos na apresentacao' : 'Anexos de imagem' ?></div></div>
            <div class="kpi-executivo"><div class="numero numero-moeda">R$ <?= e(number_format($custoTotalRelatorio, 2, ',', '.')) ?></div><div class="rotulo">Custo materiais</div></div>
        </div>

        <div class="executive-cover-grid">
            <div class="executive-highlight">
                <h3>Mensagem principal</h3>
                <ul>
                    <?php foreach (array_slice($mensagensExecutivasRelatorio, 0, 5) as $mensagemExecutiva): ?>
                        <li><?= e($mensagemExecutiva) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="executive-panel">
                <h3>Semáforo executivo</h3>
                <div class="semaforo-executivo">
                    <div class="semaforo-item semaforo-vermelho">
                        <strong><?= e($totalSemaforoVermelhoRelatorio) ?></strong>
                        <span>Risco</span>
                    </div>
                    <div class="semaforo-item semaforo-amarelo">
                        <strong><?= e($totalSemaforoAmareloRelatorio) ?></strong>
                        <span>Atenção</span>
                    </div>
                    <div class="semaforo-item semaforo-verde">
                        <strong><?= e($totalSemaforoVerdeRelatorio) ?></strong>
                        <span>Concluídas</span>
                    </div>
                    <div class="semaforo-item semaforo-cinza">
                        <strong><?= e($totalSemaforoCinzaRelatorio) ?></strong>
                        <span>Pausadas</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="resumo-executivo-grid mb-3">
            <div class="resumo-box">
                <h3><?= $modoApresentacao ? 'Resumo para diretoria e acionistas' : 'Resumo para diretoria' ?></h3>
                <ul>
                    <li><b><?= e($abertas['total'] ?? 0) ?></b> OS permanecem em aberto, andamento ou aguardando material.</li>
                    <li><b><?= e($totalEssenciais['total'] ?? 0) ?></b> OS foram classificadas como serviços essenciais/críticos.</li>
                    <li><b><?= e($totalAtrasadasRelatorio) ?></b> OS aparecem vencidas pelo SLA no filtro atual.</li>
                    <li><b><?= e($totalOsComFotos) ?></b> OS possuem anexos de imagem, cobrindo <b><?= e($percentualFotosRelatorio) ?>%</b> do total filtrado.</li>
                    <?php if ($modoApresentacao): ?><li><b><?= e($totalDocumentosRelatorio) ?></b> anexos/documentos tambem foram listados na apresentacao.</li><?php endif; ?>
                </ul>
            </div>
            <div class="resumo-box">
                <h3><?= $modoApresentacao ? 'Leitura executiva' : 'Critérios do relatório' ?></h3>
                <?php if ($modoApresentacao): ?>
                    <ul>
                        <li>As OS foram agrupadas por impacto: risco/SLA, execucao, aguardando/pausadas, concluidas e outras.</li>
                        <li>Itens criticos, urgentes, essenciais ou vencidos aparecem primeiro para facilitar decisao.</li>
                        <li>Os anexos aparecem junto da OS como evidencia visual e documental da entrega.</li>
                        <li>OS pausadas aparecem somente quando filtradas, sem afetar a taxa de conclusao.</li>
                    </ul>
                <?php else: ?>
                    <ul>
                        <li>O prazo de SLA usa o vencimento calculado por prioridade quando a coluna existir.</li>
                        <li>Os anexos de imagem são agrupados por OS e comparados em Antes x Depois quando houver classificacao.</li>
                        <li>A tela normal exibe ate 100 OS nas listas e anexos de imagem para manter a consulta leve.</li>
                        <li>O cronograma não sai na impressão executiva.</li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="executive-insight-grid">
            <div class="executive-insight-card">
                <h3>Resumo de riscos</h3>
                <ul class="executive-mini-list">
                    <li><span>Risco ativo</span><b><?= e($totalRiscoAtivoRelatorio) ?></b></li>
                    <li><span>Criticas/urgentes abertas</span><b><?= e($totalCriticasAbertasRelatorio) ?></b></li>
                    <li><span>SLA vencido no filtro</span><b><?= e($totalAtrasadasRelatorio) ?></b></li>
                    <li><span>Backlog acima de 15 dias</span><b><?= e($totalBacklogAntigoRelatorio) ?></b></li>
                </ul>
            </div>

            <div class="executive-insight-card">
                <h3>Evolução por período</h3>
                <?php foreach ($evolucaoRelatorio as $linhaEvolucao): ?>
                    <?php
                    $totalPeriodo = (int)($linhaEvolucao['abertas'] ?? 0);
                    $concluidasPeriodo = (int)($linhaEvolucao['concluidas'] ?? 0);
                    $larguraTotal = $maiorVolumeEvolucaoRelatorio > 0 ? max(3, round(($totalPeriodo / $maiorVolumeEvolucaoRelatorio) * 100)) : 0;
                    $larguraConcluidas = $totalPeriodo > 0 ? max(3, round(($concluidasPeriodo / $totalPeriodo) * 100)) : 0;
                    $periodoRotulo = date('m/Y', strtotime(($linhaEvolucao['periodo'] ?? date('Y-m')) . '-01'));
                    ?>
                    <div class="evolucao-linha">
                        <span><?= e($periodoRotulo) ?></span>
                        <div class="evolucao-barras">
                            <div class="evolucao-barra evolucao-abertas"><span style="width: <?= e($larguraTotal) ?>%"></span></div>
                            <div class="evolucao-barra evolucao-concluidas"><span style="width: <?= e($larguraConcluidas) ?>%"></span></div>
                        </div>
                        <b><?= e($concluidasPeriodo) ?>/<?= e($totalPeriodo) ?></b>
                    </div>
                <?php endforeach; ?>
                <?php if (!$evolucaoRelatorio): ?>
                    <div class="text-muted small">Sem dados de evolução no filtro atual.</div>
                <?php endif; ?>
            </div>

            <div class="executive-insight-card">
                <h3>Idade das OS ativas</h3>
                <div class="idade-backlog-grid">
                    <div class="idade-backlog-item"><strong><?= e((int)($idadeBacklogRelatorio['ate_2'] ?? 0)) ?></strong><span>0-2 dias</span></div>
                    <div class="idade-backlog-item"><strong><?= e((int)($idadeBacklogRelatorio['ate_7'] ?? 0)) ?></strong><span>3-7 dias</span></div>
                    <div class="idade-backlog-item"><strong><?= e((int)($idadeBacklogRelatorio['ate_15'] ?? 0)) ?></strong><span>8-15 dias</span></div>
                    <div class="idade-backlog-item"><strong><?= e((int)($idadeBacklogRelatorio['acima_15'] ?? 0)) ?></strong><span>+15 dias</span></div>
                </div>
            </div>
        </div>

        <div class="executive-insight-grid">
            <div class="executive-insight-card">
                <h3>Locais com maior risco</h3>
                <ul class="executive-mini-list">
                    <?php foreach ($riscosPorLocalRelatorio as $linhaRiscoLocal): ?>
                        <li><span><?= e($linhaRiscoLocal['nome']) ?></span><b><?= e($linhaRiscoLocal['total']) ?></b></li>
                    <?php endforeach; ?>
                    <?php if (!$riscosPorLocalRelatorio): ?>
                        <li><span>Sem risco ativo no filtro</span><b>0</b></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="executive-insight-card">
                <h3>Categorias com maior risco</h3>
                <ul class="executive-mini-list">
                    <?php foreach ($riscosPorCategoriaRelatorio as $linhaRiscoCategoria): ?>
                        <li><span><?= e($linhaRiscoCategoria['nome']) ?></span><b><?= e($linhaRiscoCategoria['total']) ?></b></li>
                    <?php endforeach; ?>
                    <?php if (!$riscosPorCategoriaRelatorio): ?>
                        <li><span>Sem risco ativo no filtro</span><b>0</b></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="executive-insight-card">
                <h3>Indicadores de governança</h3>
                <ul class="executive-mini-list">
                    <li><span>Cobertura com anexos</span><b><?= e($percentualFotosRelatorio) ?>%</b></li>
                    <li><span>Base da taxa de conclusão</span><b><?= e($totalBaseConclusaoRelatorio) ?></b></li>
                    <li><span>OS exibidas no relatório</span><b><?= e($totalOrdensExibidasRelatorio) ?></b></li>
                    <li><span>Pausadas fora da performance</span><b><?= e($totalPausadasRelatorio) ?></b></li>
                </ul>
            </div>
        </div>

        <?php if ($modoApresentacao): ?>
            <div class="apresentacao-panorama">
                <?php foreach ($ordensApresentacaoAgrupadas as $chaveGrupoPanorama => $grupoPanorama): ?>
                    <div class="apresentacao-panorama-item grupo-<?= e($chaveGrupoPanorama) ?>">
                        <strong><?= e(count($grupoPanorama['ordens'])) ?></strong>
                        <span><?= e($grupoPanorama['titulo']) ?></span>
                        <small><?= e($grupoPanorama['subtitulo']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="executive-conclusion">
            <h3>Conclusão executiva e próximos passos</h3>
            <div class="executive-conclusion-grid">
                <div>
                    <h4>Entregas</h4>
                    <ul>
                        <?php foreach ($entregasExecutivasRelatorio as $itemEntregaExecutiva): ?>
                            <li><?= e($itemEntregaExecutiva) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h4>Pendências relevantes</h4>
                    <ul>
                        <?php foreach ($pendenciasExecutivasRelatorio as $itemPendenciaExecutiva): ?>
                            <li><?= e($itemPendenciaExecutiva) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h4>Decisões sugeridas</h4>
                    <ul>
                        <?php foreach ($decisoesExecutivasRelatorio as $itemDecisaoExecutiva): ?>
                            <li><?= e($itemDecisaoExecutiva) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

<div class="row g-3 mb-3 relatorio-kpi-operacional">
    <div class="col-md-2">
        <div class="card p-3 relatorio-card">
            <div class="kpi"><?= e($total['total'] ?? 0) ?></div>
            <div class="kpi-label">Total de OS</div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card p-3 relatorio-card border border-danger">
            <div class="kpi text-danger"><?= e($totalEssenciais['total'] ?? 0) ?></div>
            <div class="kpi-label">Essenciais</div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card p-3 relatorio-card">
            <div class="kpi"><?= e($totalNaoEssenciais['total'] ?? 0) ?></div>
            <div class="kpi-label">Nao essenciais</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3 relatorio-card">
            <div class="kpi"><?= e($abertas['total'] ?? 0) ?></div>
            <div class="kpi-label">Em aberto / andamento</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card p-3 relatorio-card">
            <div class="kpi"><?= e($concluidas['total'] ?? 0) ?></div>
            <div class="kpi-label">Concluidas / encerradas</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card p-3 relatorio-card">
            <h2 class="h5">Por status</h2>

            <table class="table table-sm">
                <?php foreach ($porStatus as $r): ?>
                    <tr>
                        <td><?= badge_status($r['status']) ?></td>
                        <td class="text-end"><?= e($r['total']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$porStatus): ?>
                    <tr><td colspan="2">Nenhum dado encontrado.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-3 relatorio-card">
            <h2 class="h5">Por categoria</h2>

            <table class="table table-sm">
                <?php foreach ($porCategoria as $r): ?>
                    <tr>
                        <td><?= e($r['nome']) ?></td>
                        <td class="text-end"><?= e($r['total']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$porCategoria): ?>
                    <tr><td colspan="2">Nenhum dado encontrado.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card p-3 relatorio-card">
            <h2 class="h5">Por prioridade</h2>

            <table class="table table-sm">
                <?php foreach ($porPrioridade as $r): ?>
                    <tr>
                        <td><?= badge_prioridade($r['prioridade']) ?></td>
                        <td class="text-end"><?= e($r['total']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$porPrioridade): ?>
                    <tr><td colspan="2">Nenhum dado encontrado.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card p-3 relatorio-card">
            <h2 class="h5">Por agente</h2>

            <table class="table table-sm">
                <?php foreach ($porAgente as $r): ?>
                    <tr>
                        <td><?= e($r['agente']) ?></td>
                        <td class="text-end"><?= e($r['total']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$porAgente): ?>
                    <tr><td colspan="2">Nenhum dado encontrado.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-3 relatorio-card">
            <h2 class="h5">Por local</h2>

            <table class="table table-sm">
                <?php foreach ($porLocal as $r): ?>
                    <tr>
                        <td><?= e($r['nome']) ?></td>
                        <td class="text-end"><?= e($r['total']) ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$porLocal): ?>
                    <tr><td colspan="2">Nenhum dado encontrado.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <div class="card p-3 relatorio-card border border-warning">
            <h2 class="h5">Ranking de atraso por agente</h2>

            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Agente</th>
                        <th class="text-end">OS</th>
                        <th class="text-end">Dias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankingAtraso as $r): ?>
                        <tr>
                            <td><?= e($r['agente']) ?></td>
                            <td class="text-end"><?= e($r['total_atrasadas']) ?></td>
                            <td class="text-end"><?= e($r['dias_atraso']) ?></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$rankingAtraso): ?>
                        <tr><td colspan="3" class="text-muted">Nenhuma OS atrasada no filtro atual.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card p-3 relatorio-card border border-danger">
            <h2 class="h5 text-danger">Ordens atrasadas</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>OS</th>
                            <th>Titulo</th>
                            <th>Agente</th>
                            <th class="text-end">Dias de atraso</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordensAtrasadas as $oa): ?>
                            <tr>
                                <td><?= e($oa['numero_os']) ?></td>
                                <td><?= e($oa['titulo']) ?></td>
                                <td><?= e($oa['agente_nome']) ?></td>
                                <td class="text-end"><?= e($oa['dias_atraso']) ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-danger" href="<?= e(app_path('ordem_detalhe', ['id' => $oa['id']])) ?>">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$ordensAtrasadas): ?>
                            <tr><td colspan="5" class="text-muted">Nenhuma OS atrasada no filtro atual.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card p-3 mb-3 relatorio-card border border-danger">
    <h2 class="h5 text-danger">Servicos Essenciais</h2>

    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>Numero OS</th>
                    <th>Titulo</th>
                    <th>Local</th>
                    <th>Categoria</th>
                    <th>Agente</th>
                    <th>Status</th>
                    <th>Prazo</th>
                    <th>Abertura</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($essenciais as $os): ?>
                    <tr>
                        <td><?= e($os['numero_os']) ?></td>
                        <td><?= e($os['titulo']) ?></td>
                        <td><?= e($os['local_nome']) ?></td>
                        <td><?= e($os['categoria_nome']) ?></td>
                        <td><?= e($os['agente_nome']) ?></td>
                        <td><?= badge_status($os['status']) ?></td>
                        <td><?= e($os['prazo'] ?: '-') ?></td>
                        <td><?= e($os['criado_em']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-danger" href="<?= e(app_path('ordem_detalhe', ['id' => $os['id']])) ?>">
                                Ver
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$essenciais): ?>
                    <tr>
                        <td colspan="9" class="text-center">Nenhum servico essencial encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card p-3 relatorio-card mb-3">
    <h2 class="h5"><?= $modoApresentacao ? 'Todas as Ordens de Servico filtradas' : 'Ultimas Ordens de Servico' ?></h2>

    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>Numero OS</th>
                    <th>Titulo</th>
                    <th>Tipo</th>
                    <th>Local</th>
                    <th>Categoria</th>
                    <th>Prioridade</th>
                    <th>Agente</th>
                    <th>Status</th>
                    <th>Abertura</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($ordensRecentes as $os): ?>
                    <?php
                    $ehEssencial = (
                        in_array($os['prioridade'], ['CRITICA', 'URGENTE'], true) ||
                        comeca_com($os['titulo'], '[SERVICO ESSENCIAL]')
                    );
                    ?>

                    <tr>
                        <td><?= e($os['numero_os']) ?></td>
                        <td><?= e($os['titulo']) ?></td>
                        <td>
                            <?php if ($ehEssencial): ?>
                                <span class="essencial-label">Essencial</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Normal</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($os['local_nome']) ?></td>
                        <td><?= e($os['categoria_nome']) ?></td>
                        <td><?= badge_prioridade($os['prioridade']) ?></td>
                        <td><?= e($os['agente_nome']) ?></td>
                        <td><?= badge_status($os['status']) ?></td>
                        <td><?= e($os['criado_em']) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('ordem_detalhe', ['id' => $os['id']])) ?>">
                                Ver
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$ordensRecentes): ?>
                    <tr>
                        <td colspan="10" class="text-center">Nenhuma ordem encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="executive-section-title<?= $modoApresentacao ? ' apresentacao-os-title' : '' ?>">
    <h2><?= $modoApresentacao ? 'Apêndice operacional: OS completas com anexos' : 'Anexos por ordem de servico' ?></h2>
    <small><?= $modoApresentacao ? 'Detalhamento por grupo executivo, com evidências e documentos anexados' : 'Anexos de imagem para entrega de resultado' ?></small>
</div>

<div class="card p-3 relatorio-card mb-3<?= $modoApresentacao ? ' apresentacao-os-lista' : '' ?>">
    <h2 class="h5"><?= $modoApresentacao ? 'OS completas da apresentacao' : 'Anexos das Ordens de Servico' ?></h2>
    <p class="text-muted small mb-3">
        <?= $modoApresentacao ? 'Todas as OS filtradas aparecem abaixo com dados principais, descricao e anexos quando existirem.' : 'Quando houver anexos de imagem identificados como antes e depois, o relatorio exibira a comparacao lado a lado.' ?>
    </p>

    <?php if (!$modoApresentacao && !$fotosPorOs): ?>
        <div class="text-muted">
            Nenhum anexo de imagem encontrado nas ordens filtradas.
        </div>
    <?php endif; ?>

    <?php if ($modoApresentacao && !$ordensRecentes): ?>
        <div class="text-muted">
            Nenhuma ordem encontrada para a apresentacao.
        </div>
    <?php endif; ?>

    <?php
    $gruposRenderizacaoApresentacao = $modoApresentacao
        ? $ordensApresentacaoAgrupadas
        : ['anexos' => ['titulo' => '', 'subtitulo' => '', 'ordens' => $ordensRecentes]];
    ?>

    <?php foreach ($gruposRenderizacaoApresentacao as $chaveGrupoApresentacao => $grupoApresentacao): ?>
        <?php if ($modoApresentacao && empty($grupoApresentacao['ordens'])) continue; ?>

        <?php if ($modoApresentacao): ?>
            <div class="apresentacao-grupo-header grupo-<?= e($chaveGrupoApresentacao) ?>">
                <div>
                    <h3><?= e($grupoApresentacao['titulo']) ?></h3>
                    <p><?= e($grupoApresentacao['subtitulo']) ?></p>
                </div>
                <div class="apresentacao-grupo-total">
                    <?= e(count($grupoApresentacao['ordens'])) ?> OS
                </div>
            </div>
        <?php endif; ?>

    <?php foreach ($grupoApresentacao['ordens'] as $os): ?>
        <?php
        $osIdFoto = (int)$os['id'];

        if (!$modoApresentacao && empty($fotosPorOs[$osIdFoto])) {
            continue;
        }

        $grupoFotos = $fotosPorOs[$osIdFoto] ?? ['antes' => [], 'depois' => [], 'geral' => []];
        $antesFotos = $grupoFotos['antes'] ?? [];
        $depoisFotos = $grupoFotos['depois'] ?? [];
        $geralFotos = $grupoFotos['geral'] ?? [];
        $documentosAnexos = $documentosPorOs[$osIdFoto] ?? [];
        ?>

        <div class="executive-photo-card<?= $modoApresentacao ? ' grupo-' . e($chaveGrupoApresentacao) : '' ?>">
            <div class="executive-photo-head">
                <strong>OS <?= e($os['numero_os']) ?> - <?= e($os['titulo']) ?></strong>
                <br>
                <div class="executive-photo-meta">
                    Local: <?= e($os['local_nome']) ?> |
                    Categoria: <?= e($os['categoria_nome']) ?> |
                    Agente: <?= e($os['agente_nome']) ?> |
                    Status: <?= e(str_replace('_', ' ', $os['status'])) ?>
                </div>
            </div>
            <div class="executive-photo-body">

            <?php if ($modoApresentacao): ?>
                <div class="os-completa-grid">
                    <div class="os-completa-item">
                        <b>Prioridade</b>
                        <?= e($os['prioridade'] ?? '-') ?>
                    </div>
                    <div class="os-completa-item">
                        <b>Solicitante</b>
                        <?= e($os['solicitante_nome'] ?? '-') ?>
                    </div>
                    <div class="os-completa-item">
                        <b>Gestor</b>
                        <?= e($os['gestor_nome'] ?? '-') ?>
                    </div>
                    <div class="os-completa-item">
                        <b>Abertura</b>
                        <?= e($os['criado_em'] ?? '-') ?>
                    </div>
                    <div class="os-completa-item">
                        <b>Prazo</b>
                        <?= e(($os['sla_vencimento'] ?? null) ?: ($os['prazo'] ?? '-')) ?>
                    </div>
                    <div class="os-completa-item">
                        <b>Inicio</b>
                        <?= e(($os['data_inicio'] ?? null) ?: '-') ?>
                    </div>
                    <div class="os-completa-item">
                        <b>Conclusao</b>
                        <?= e(($os['data_conclusao'] ?? null) ?: '-') ?>
                    </div>
                    <div class="os-completa-item">
                        <b>Custo materiais</b>
                        R$ <?= e(number_format((float)($os['custo_materiais'] ?? 0), 2, ',', '.')) ?>
                    </div>
                </div>

                <div class="os-completa-descricao"><b>Descricao:</b><br><?= e($os['descricao'] ?? '') ?></div>

                <?php if (!empty($os['observacao_aprovacao'])): ?>
                    <div class="os-completa-descricao"><b>Observacao da aprovacao:</b><br><?= e($os['observacao_aprovacao']) ?></div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($antesFotos || $depoisFotos): ?>
                <div class="executive-photo-compare">
                    <div class="executive-photo-col">
                        <h3 class="h6">Antes</h3>

                        <?php if (!$antesFotos): ?>
                            <div class="text-muted small">Sem anexos de antes.</div>
                        <?php endif; ?>

                        <div class="executive-photos-grid anexos-grid">
                            <?php foreach ($antesFotos as $foto): ?>
                                <?php
                                $fotoUrl = anexo_url_segura_relatorio($foto['arquivo_url'] ?? $foto['arquivo'] ?? $foto['src'] ?? '');
                                $fotoSrcs = $foto['srcs'] ?? [$fotoUrl];
                                ?>
                                <div class="executive-photo-item anexo-card-img">
                                    <a class="text-decoration-none text-dark" href="<?= e($fotoUrl) ?>" target="_blank">
                                        <img class="foto-relatorio" src="<?= e($fotoUrl) ?>" data-srcs="<?= e(implode('||', $fotoSrcs)) ?>" loading="eager" decoding="sync" onerror="trocarSrcFotoRelatorio(this)" alt="Foto antes da OS <?= e($os['numero_os']) ?>">
                                        <small class="foto-legenda"><?= e(($foto['descricao'] ?? '') ?: 'Sem descrição') ?><br>Enviado por <?= e($foto['usuario_nome'] ?? 'Sistema') ?> em <?= e($foto['criado_em'] ?? '-') ?></small>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="executive-photo-col">
                        <h3 class="h6">Depois</h3>

                        <?php if (!$depoisFotos): ?>
                            <div class="text-muted small">Sem anexos de depois.</div>
                        <?php endif; ?>

                        <div class="executive-photos-grid anexos-grid">
                            <?php foreach ($depoisFotos as $foto): ?>
                                <?php
                                $fotoUrl = anexo_url_segura_relatorio($foto['arquivo_url'] ?? $foto['arquivo'] ?? $foto['src'] ?? '');
                                $fotoSrcs = $foto['srcs'] ?? [$fotoUrl];
                                ?>
                                <div class="executive-photo-item anexo-card-img">
                                    <a class="text-decoration-none text-dark" href="<?= e($fotoUrl) ?>" target="_blank">
                                        <img class="foto-relatorio" src="<?= e($fotoUrl) ?>" data-srcs="<?= e(implode('||', $fotoSrcs)) ?>" loading="eager" decoding="sync" onerror="trocarSrcFotoRelatorio(this)" alt="Foto depois da OS <?= e($os['numero_os']) ?>">
                                        <small class="foto-legenda"><?= e(($foto['descricao'] ?? '') ?: 'Sem descrição') ?><br>Enviado por <?= e($foto['usuario_nome'] ?? 'Sistema') ?> em <?= e($foto['criado_em'] ?? '-') ?></small>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($geralFotos): ?>
                <div class="mt-3">
                    <h3 class="h6">Outros anexos de imagem</h3>

                    <div class="executive-photos-grid anexos-grid">
                        <?php foreach ($geralFotos as $foto): ?>
                            <?php
                            $fotoUrl = anexo_url_segura_relatorio($foto['arquivo_url'] ?? $foto['arquivo'] ?? $foto['src'] ?? '');
                            $fotoSrcs = $foto['srcs'] ?? [$fotoUrl];
                            ?>
                            <div class="executive-photo-item anexo-card-img">
                                <a class="text-decoration-none text-dark" href="<?= e($fotoUrl) ?>" target="_blank">
                                    <img class="foto-relatorio" src="<?= e($fotoUrl) ?>" data-srcs="<?= e(implode('||', $fotoSrcs)) ?>" loading="eager" decoding="sync" onerror="trocarSrcFotoRelatorio(this)" alt="Imagem anexada na OS <?= e($os['numero_os']) ?>">
                                    <small class="foto-legenda"><b><?= e($foto['tipo'] ?? 'OUTRO') ?></b><br><?= e(($foto['descricao'] ?? '') ?: 'Sem descrição') ?><br>Enviado por <?= e($foto['usuario_nome'] ?? 'Sistema') ?> em <?= e($foto['criado_em'] ?? '-') ?></small>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($documentosAnexos): ?>
                <div class="anexos-documentos">
                    <h3 class="h6">Documentos anexados</h3>
                    <?php foreach ($documentosAnexos as $a): ?>
                        <?php $arquivoUrl = anexo_url_segura_relatorio($a['arquivo_url'] ?? $a['arquivo'] ?? $a['src'] ?? ''); ?>
                        <div class="border rounded p-2 mb-2 anexo-documento-card">
                            <b><?= e($a['tipo'] ?: 'DOCUMENTO') ?></b>
                            por <?= e($a['usuario_nome'] ?? 'Sistema') ?> - <?= e($a['criado_em'] ?? '-') ?><br>
                            <a href="<?= e($arquivoUrl) ?>" target="_blank"><?= e($arquivoUrl) ?></a><br>
                            <?= e($a['descricao'] ?? '') ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($modoApresentacao && !$antesFotos && !$depoisFotos && !$geralFotos && !$documentosAnexos): ?>
                <div class="text-muted small">Nenhum anexo registrado para esta OS.</div>
            <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endforeach; ?>
</div>

    </div>
</div>

<div id="area-cronograma" class="card p-3 mt-3 mb-3 relatorio-card border border-primary">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h5 mb-0 text-primary">Cronograma Semanal dos Agentes</h2>
            <small class="text-muted">
                Semana de <?= e(date('d/m/Y', strtotime($cronogramaInicio))) ?> ate <?= e(date('d/m/Y', strtotime($cronogramaFim))) ?>
            </small>
        </div>

        <?php if (in_array($user['perfil'], ['TI', 'GESTOR'], true)): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('cronograma_agentes')) ?>">
                Abrir cronograma
            </a>
        <?php endif; ?>
    </div>

    <form method="get" class="row g-3 mb-3">
        <input type="hidden" name="status" value="<?= e($filtroStatus) ?>">
        <input type="hidden" name="inicio" value="<?= e($filtroInicio) ?>">
        <input type="hidden" name="fim" value="<?= e($filtroFim) ?>">
        <input type="hidden" name="prioridade" value="<?= e($filtroPrioridade) ?>">
        <input type="hidden" name="categoria_id" value="<?= e($filtroCategoria) ?>">
        <input type="hidden" name="local_id" value="<?= e($filtroLocal) ?>">
        <input type="hidden" name="agente_id" value="<?= e($filtroAgente) ?>">

        <div class="col-md-4">
            <label class="form-label">Semana do cronograma</label>
            <input 
                class="form-control" 
                type="date" 
                name="semana_cronograma" 
                value="<?= e($cronogramaInicio) ?>"
            >
        </div>

        <?php if ($user['perfil'] !== 'AGENTE'): ?>
            <div class="col-md-5">
                <label class="form-label">Agente</label>
                <select class="form-select" name="agente_cronograma">
                    <option value="">Todos os agentes</option>

                    <?php foreach ($agentesCronograma as $ag): ?>
                        <option value="<?= e($ag['id']) ?>" <?= $filtroAgenteCronograma === (int)$ag['id'] ? 'selected' : '' ?>>
                            <?= e($ag['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100">
                Filtrar cronograma
            </button>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-md-2">
            <div class="card p-3 relatorio-card">
                <div class="kpi"><?= e($cronogramaResumo['total'] ?? 0) ?></div>
                <div class="kpi-label">Atividades</div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card p-3 relatorio-card">
                <div class="kpi"><?= e($cronogramaResumo['planejado'] ?? 0) ?></div>
                <div class="kpi-label">Planejadas</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 relatorio-card">
                <div class="kpi"><?= e($cronogramaResumo['em_andamento'] ?? 0) ?></div>
                <div class="kpi-label">Em andamento</div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card p-3 relatorio-card">
                <div class="kpi"><?= e($cronogramaResumo['concluido'] ?? 0) ?></div>
                <div class="kpi-label">Concluidas</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3 relatorio-card">
                <div class="kpi"><?= e($cronogramaResumo['cancelado'] ?? 0) ?></div>
                <div class="kpi-label">Canceladas</div>
            </div>
        </div>
    </div>

    <?php foreach ($diasCronograma as $diaNumero => $diaNome): ?>
        <div class="cronograma-dia">
            <h3 class="h6 mb-2"><?= e($diaNome) ?></h3>

            <?php
            $temAtividadeCronograma = false;

            foreach ($cronogramaAgentes as $c):
                if ((int)$c['dia_semana'] !== $diaNumero) {
                    continue;
                }

                $temAtividadeCronograma = true;
            ?>
                <div class="cronograma-item">
                    <div>
                        <strong><?= e($c['titulo']) ?></strong>
                        <br>

                        <small>
                            <?= e(substr($c['hora_inicio'], 0, 5)) ?> ate <?= e(substr($c['hora_fim'], 0, 5)) ?>
                            |
                            Agente: <?= e($c['agente_nome']) ?>
                            |
                            Local: <?= e($c['local_nome']) ?>
                        </small>

                        <?php if (!empty($c['descricao'])): ?>
                            <div class="mt-2">
                                <?= nl2br(e($c['descricao'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-2">
                            <span class="badge status-<?= e($c['status']) ?>">
                                <?= e($statusCronograma[$c['status']] ?? $c['status']) ?>
                            </span>

                            <small class="text-muted">
                                Criado por <?= e($c['gestor_nome']) ?> em <?= e($c['criado_em']) ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$temAtividadeCronograma): ?>
                <div class="text-muted small">
                    Nenhuma atividade cadastrada para este dia.
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<script>
function atualizarEmissaoDispositivo() {
    const elementos = document.querySelectorAll('.emissao-dispositivo');
    if (!elementos.length) {
        return;
    }

    const agora = new Date();
    const dataHora = new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(agora);

    elementos.forEach(function(el) {
        el.textContent = dataHora;
        el.setAttribute('data-device-time', agora.toISOString());
    });
}

function trocarSrcFotoRelatorio(img) {
    const lista = (img.getAttribute('data-srcs') || '').split('||').filter(Boolean);
    const atual = img.getAttribute('src');
    let indice = lista.indexOf(atual);

    if (indice < 0) {
        indice = 0;
    }

    const proximo = lista[indice + 1];

    if (proximo) {
        img.setAttribute('src', proximo);
        return;
    }

    img.onerror = null;
    img.style.display = 'none';
}

function imprimirRelatorioCompleto(tituloImpressao, tempoEsperaImagem) {
    document.body.classList.remove('imprimir-cronograma', 'imprimir-relatorio-executivo');
    document.body.classList.add('imprimir-relatorio-executivo');
    atualizarEmissaoDispositivo();

    const tituloOriginal = document.title;
    document.title = tituloImpressao || 'Relatorio_Executivo_OS';
    const tempoEspera = tempoEsperaImagem || 2000;

    // Aguarda os anexos de imagem carregarem antes de abrir a janela de impressão.
    const imagens = Array.from(document.querySelectorAll('#relatorio-executivo img'));
    const promessas = imagens.map(function(img) {
        img.loading = 'eager';

        const decodificar = function() {
            if (img.decode && img.naturalWidth > 0) {
                return img.decode().catch(function() {});
            }

            return Promise.resolve();
        };

        if (img.complete) {
            return decodificar();
        }

        return new Promise(function(resolve) {
            img.onload = resolve;
            img.onerror = resolve;
            setTimeout(resolve, tempoEspera);
        }).then(decodificar);
    });

    Promise.all(promessas).then(function() {
        window.print();

        setTimeout(function() {
            document.body.classList.remove('imprimir-relatorio-executivo');
            document.title = tituloOriginal;
        }, 1000);
    });
}

function imprimirSomenteCronograma() {
    document.body.classList.remove('imprimir-relatorio-executivo');
    document.body.classList.add('imprimir-cronograma');
    atualizarEmissaoDispositivo();

    const tituloOriginal = document.title;
    document.title = 'Cronograma_Semanal_Agentes';

    window.print();

    setTimeout(function() {
        document.body.classList.remove('imprimir-cronograma');
        document.title = tituloOriginal;
    }, 1000);
}

atualizarEmissaoDispositivo();
window.addEventListener('load', atualizarEmissaoDispositivo);

<?php if ($modoApresentacao && $imprimirAoCarregar): ?>
window.addEventListener('load', function() {
    if (window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.delete('imprimir');
        window.history.replaceState({}, '', url.toString());
    }

    setTimeout(function() {
        imprimirRelatorioCompleto('Relatorio_OS_Apresentacao', 5000);
    }, 700);
});
<?php endif; ?>
</script>

<?php render_footer(); ?>
