<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';
require_once __DIR__ . '/../includes/exclusao_ordens.php';


$id = (int)($_GET['id'] ?? 0);
$token = strtolower(preg_replace('/[^a-f0-9]/', '', (string)($_GET['t'] ?? ($_GET['token'] ?? ''))));
$mensagemSucesso = '';
$mensagemErro = '';

function public_table_exists(PDO $pdo, string $table): bool {
    if (function_exists('app_table_exists')) {
        try { return app_table_exists($pdo, $table); } catch (Throwable $e) {}
    }

    try {
        $st = $pdo->prepare("SHOW TABLES LIKE ?");
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function public_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $st->execute([$column]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function public_ensure_support(PDO $pdo): void {
    try {
        if (!public_table_exists($pdo, 'anexos_os')) {
            $pdo->exec("
                CREATE TABLE anexos_os (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ordem_servico_id INT NOT NULL,
                    usuario_id INT NULL,
                    tipo VARCHAR(50) DEFAULT 'FOTO_DEPOIS',
                    arquivo_url VARCHAR(255) NOT NULL,
                    descricao TEXT NULL,
                    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_anexos_os_ordem (ordem_servico_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    } catch (Throwable $e) {}

    $cols = [
        'solicitacao_encerramento' => "TINYINT(1) NOT NULL DEFAULT 0",
        'solicitacao_encerramento_data' => "DATETIME NULL",
        'solicitacao_encerramento_obs' => "TEXT NULL",
    ];

    foreach ($cols as $col => $def) {
        try {
            if (!public_column_exists($pdo, 'ordens_servico', $col)) {
                $pdo->exec("ALTER TABLE ordens_servico ADD COLUMN `$col` $def");
            }
        } catch (Throwable $e) {}
    }

    // Suporte para agente principal e agente auxiliar na OS pública.
    // Se a tabela ainda não existir, cria sem quebrar o campo antigo ordens_servico.agente_id.
    try {
        if (!public_table_exists($pdo, 'ordem_agentes')) {
            $pdo->exec("
                CREATE TABLE ordem_agentes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ordem_servico_id INT NOT NULL,
                    usuario_id INT NOT NULL,
                    papel VARCHAR(20) NOT NULL DEFAULT 'AUXILIAR',
                    criado_por INT NULL,
                    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_ordem_usuario (ordem_servico_id, usuario_id),
                    INDEX idx_ordem (ordem_servico_id),
                    INDEX idx_usuario (usuario_id),
                    INDEX idx_papel (papel)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            "
            );
        }

        if (public_table_exists($pdo, 'ordem_agentes') && public_column_exists($pdo, 'ordem_agentes', 'papel')) {
            $pdo->exec("
                INSERT IGNORE INTO ordem_agentes (ordem_servico_id, usuario_id, papel)
                SELECT id, agente_id, 'PRINCIPAL'
                FROM ordens_servico
                WHERE agente_id IS NOT NULL
            "
            );
        }
    } catch (Throwable $e) {}
}

function public_load_os(PDO $pdo, int $id) {
    $ordemAtivaSql = exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os');
    // Carrega a OS já com agente principal e auxiliares da mesma forma usada em ordem_detalhe.php.
    // O LEFT JOIN não duplica a OS porque o GROUP_CONCAT agrega os auxiliares em uma única linha.
    $sql = "
        SELECT
            os.*,
            l.nome AS local_nome,
            c.nome AS categoria_nome,
            g.nome AS gestor_nome,
            a.nome AS agente_nome,
            GROUP_CONCAT(DISTINCT CASE WHEN oa.papel = 'PRINCIPAL' THEN up.nome END ORDER BY up.nome SEPARATOR ', ') AS agentes_principais_nomes,
            GROUP_CONCAT(DISTINCT CASE WHEN oa.papel = 'AUXILIAR' THEN up.nome END ORDER BY up.nome SEPARATOR ', ') AS agentes_auxiliares_nomes
        FROM ordens_servico os
        JOIN locais l ON l.id = os.local_id
        JOIN categorias c ON c.id = os.categoria_id
        JOIN usuarios g ON g.id = os.gestor_id
        LEFT JOIN usuarios a ON a.id = os.agente_id
        LEFT JOIN ordem_agentes oa ON oa.ordem_servico_id = os.id
        LEFT JOIN usuarios up ON up.id = oa.usuario_id
        WHERE os.id = ? AND $ordemAtivaSql
        GROUP BY os.id
        LIMIT 1
    ";

    try {
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        return $st->fetch();
    } catch (Throwable $e) {
        // Fallback caso a tabela ordem_agentes ainda não exista no banco de produção.
        $st = $pdo->prepare("
            SELECT
                os.*,
                l.nome AS local_nome,
                c.nome AS categoria_nome,
                g.nome AS gestor_nome,
                a.nome AS agente_nome
            FROM ordens_servico os
            JOIN locais l ON l.id = os.local_id
            JOIN categorias c ON c.id = os.categoria_id
            JOIN usuarios g ON g.id = os.gestor_id
            LEFT JOIN usuarios a ON a.id = os.agente_id
            WHERE os.id = ? AND $ordemAtivaSql
            LIMIT 1
        ");
        $st->execute([$id]);
        return $st->fetch();
    }
}


function public_load_agentes_os(PDO $pdo, int $id, array $os = []): array {
    $principalId = (int)($os['agente_id'] ?? 0);
    $principalTabela = trim((string)($os['agentes_principais_nomes'] ?? ''));
    $auxiliaresTabela = trim((string)($os['agentes_auxiliares_nomes'] ?? ''));

    $dados = [
        'principal' => $principalTabela !== '' ? $principalTabela : trim((string)($os['agente_nome'] ?? '')),
        'auxiliares' => $auxiliaresTabela !== '' ? array_values(array_filter(array_map('trim', explode(',', $auxiliaresTabela)))) : [],
    ];

    if (!public_table_exists($pdo, 'ordem_agentes')) {
        return $dados;
    }

    try {
        $temPapel = public_column_exists($pdo, 'ordem_agentes', 'papel');
        $temPrincipal = public_column_exists($pdo, 'ordem_agentes', 'principal');

        if ($temPapel) {
            $sql = "
                SELECT oa.usuario_id, oa.papel, u.nome
                FROM ordem_agentes oa
                JOIN usuarios u ON u.id = oa.usuario_id
                WHERE oa.ordem_servico_id = ?
                ORDER BY
                    CASE
                        WHEN oa.papel = 'PRINCIPAL' THEN 0
                        WHEN oa.usuario_id = ? THEN 0
                        ELSE 1
                    END,
                    u.nome ASC
            ";
            $st = $pdo->prepare($sql);
            $st->execute([$id, $principalId]);
        } elseif ($temPrincipal) {
            // Compatibilidade caso a tabela tenha sido criada com coluna principal TINYINT.
            $sql = "
                SELECT oa.usuario_id,
                       CASE WHEN oa.principal = 1 THEN 'PRINCIPAL' ELSE 'AUXILIAR' END AS papel,
                       u.nome
                FROM ordem_agentes oa
                JOIN usuarios u ON u.id = oa.usuario_id
                WHERE oa.ordem_servico_id = ?
                ORDER BY
                    CASE
                        WHEN oa.principal = 1 THEN 0
                        WHEN oa.usuario_id = ? THEN 0
                        ELSE 1
                    END,
                    u.nome ASC
            ";
            $st = $pdo->prepare($sql);
            $st->execute([$id, $principalId]);
        } else {
            // Compatibilidade mínima: qualquer agente na tabela diferente do principal vira auxiliar.
            $sql = "
                SELECT oa.usuario_id, u.nome
                FROM ordem_agentes oa
                JOIN usuarios u ON u.id = oa.usuario_id
                WHERE oa.ordem_servico_id = ?
                ORDER BY u.nome ASC
            ";
            $st = $pdo->prepare($sql);
            $st->execute([$id]);
        }

        $linhas = $st->fetchAll();

        foreach ($linhas as $linha) {
            $uid = (int)($linha['usuario_id'] ?? 0);
            $nome = trim((string)($linha['nome'] ?? ''));
            $papel = strtoupper(trim((string)($linha['papel'] ?? '')));
            if ($nome === '') continue;

            if ($papel === 'PRINCIPAL' || ($principalId > 0 && $uid === $principalId)) {
                $dados['principal'] = $nome;
                continue;
            }

            if (!in_array($nome, $dados['auxiliares'], true) && $nome !== $dados['principal']) {
                $dados['auxiliares'][] = $nome;
            }
        }
    } catch (Throwable $e) {}

    return $dados;
}

function public_file_url($arquivoUrl): string {
    $arquivoUrl = trim(str_replace('\\', '/', (string)$arquivoUrl));
    if ($arquivoUrl === '') return '';

    if (preg_match('/^https?:\/\//i', $arquivoUrl)) return $arquivoUrl;

    $arquivoUrl = preg_replace('/\?.*$/', '', $arquivoUrl);

    if (strpos($arquivoUrl, '/uploads/') !== false) {
        $arquivoUrl = substr($arquivoUrl, strpos($arquivoUrl, '/uploads/') + 1);
    }

    if (preg_match('/^[A-Za-z]:\//', $arquivoUrl) || strpos($arquivoUrl, '/home/') === 0 || strpos($arquivoUrl, __DIR__) === 0) {
        $arquivoUrl = 'uploads/' . basename($arquivoUrl);
    }

    $arquivoUrl = ltrim($arquivoUrl, '/');
    if (strpos($arquivoUrl, 'uploads/') !== 0) {
        $arquivoUrl = 'uploads/' . basename($arquivoUrl);
    }

    return app_path($arquivoUrl);
}

function public_is_image($arquivoUrl): bool {
    $ext = strtolower(pathinfo((string)$arquivoUrl, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
}

function public_registrar_historico(PDO $pdo, int $osId, string $status, string $descricao): void {
    if (!public_table_exists($pdo, 'historico_os')) return;

    try {
        $st = $pdo->prepare("
            INSERT INTO historico_os
            (ordem_servico_id, usuario_id, status_anterior, status_novo, descricao)
            VALUES (?, NULL, ?, ?, ?)
        ");
        $st->execute([$osId, $status, $status, $descricao]);
    } catch (Throwable $e) {
        try {
            $st = $pdo->prepare("
                INSERT INTO historico_os
                (ordem_servico_id, status_anterior, status_novo, descricao)
                VALUES (?, ?, ?, ?)
            ");
            $st->execute([$osId, $status, $status, $descricao]);
        } catch (Throwable $e2) {}
    }
}

function public_upload_files_array(string $field): array {
    if (empty($_FILES[$field])) return [];

    $files = $_FILES[$field];
    $normalized = [];

    if (is_array($files['name'])) {
        foreach ($files['name'] as $i => $name) {
            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$i] ?? 0,
            ];
        }
    } else {
        $normalized[] = $files;
    }

    return $normalized;
}

public_ensure_support($pdo);
$os = $id > 0 ? public_load_os($pdo, $id) : null;

if (!$os || $token === '' || !hash_equals(public_os_token($os), $token)) {
    http_response_code(404);
    $title = 'OS não encontrada';
    ?>
    <!doctype html>
    <html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> - Shopping Carpina</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="<?= e(app_path('assets/style.css')) ?>">
    </head>
    <body>
        <main class="container py-5">
            <div class="card p-4 text-center">
                <h1 class="h4 mb-2"><?= e($title) ?></h1>
                <p class="text-muted mb-0">O link informado não existe ou não está mais válido.</p>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Link público validado pelo token da própria OS. Não usamos CSRF por sessão aqui,
    // pois links públicos costumam falhar quando o navegador bloqueia cookie, há cache
    // ou troca de domínio/subdomínio. A segurança permanece no token assinado da OS.
    if (($os['status'] ?? '') === 'ENCERRADA') {
        $mensagemErro = 'Esta OS já está encerrada e não permite novas ações pelo link público.';
    } else {
        $acao = (string)($_POST['acao'] ?? '');

        if ($acao === 'enviar_fotos') {
            $descricao = trim((string)($_POST['descricao_foto'] ?? 'Foto enviada pelo link público.'));
            if ($descricao === '') $descricao = 'Foto enviada pelo link público.';

            $permitidos = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $maxBytes = 10 * 1024 * 1024;
            $arquivos = array_merge(public_upload_files_array('fotos'), public_upload_files_array('foto'), public_upload_files_array('arquivo'));
            $enviadas = 0;

            foreach ($arquivos as $arquivo) {
                if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
                if (($arquivo['size'] ?? 0) > $maxBytes) {
                    $mensagemErro = 'Uma das fotos excede o limite de 10MB.';
                    continue;
                }

                $ext = strtolower(pathinfo((string)$arquivo['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $permitidos, true)) {
                    $mensagemErro = 'Envie apenas imagens JPG, PNG, WEBP ou GIF.';
                    continue;
                }

                if (!@getimagesize($arquivo['tmp_name'])) {
                    $mensagemErro = 'Um dos arquivos enviados não é uma imagem válida.';
                    continue;
                }

                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $nome = 'os_publica_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadDir . '/' . $nome;

                if (move_uploaded_file($arquivo['tmp_name'], $dest)) {
                    $url = 'uploads/' . $nome;
                    $st = $pdo->prepare("
                        INSERT INTO anexos_os
                        (ordem_servico_id, usuario_id, tipo, arquivo_url, descricao)
                        VALUES (?, NULL, ?, ?, ?)
                    ");
                    $st->execute([$id, 'FOTO_DEPOIS', $url, $descricao]);
                    $enviadas++;
                }
            }

            if ($enviadas > 0) {
                public_registrar_historico($pdo, $id, (string)($os['status'] ?? ''), $enviadas . ' foto(s) enviada(s) pelo link público.');
                $mensagemSucesso = $enviadas . ' foto(s) anexada(s) com sucesso.';
                $os = public_load_os($pdo, $id);
            } elseif ($mensagemErro === '') {
                $mensagemErro = 'Selecione pelo menos uma foto válida.';
            }
        }

        if ($acao === 'solicitar_encerramento') {
            $obs = trim((string)($_POST['observacao_encerramento'] ?? ''));

            if ($obs === '') {
                $mensagemErro = 'Informe uma observação descrevendo o serviço realizado.';
            } else {
                $st = $pdo->prepare("
                    UPDATE ordens_servico
                    SET solicitacao_encerramento = 1,
                        solicitacao_encerramento_data = NOW(),
                        solicitacao_encerramento_obs = ?
                    WHERE id = ?
                    LIMIT 1
                ");
                $st->execute([$obs, $id]);

                public_registrar_historico($pdo, $id, (string)($os['status'] ?? ''), 'Solicitação de encerramento enviada pelo link público: ' . $obs);
                $mensagemSucesso = 'Solicitação de encerramento enviada. O gestor/TI fará a conferência final.';
                $os = public_load_os($pdo, $id);
            }
        }
    }
}

$historico = [];
if (public_table_exists($pdo, 'historico_os')) {
    $stHistorico = $pdo->prepare("
        SELECT h.*, u.nome AS usuario_nome
        FROM historico_os h
        LEFT JOIN usuarios u ON u.id = h.usuario_id
        WHERE h.ordem_servico_id = ?
        ORDER BY h.criado_em DESC
    ");
    $stHistorico->execute([$id]);
    $historico = $stHistorico->fetchAll();
}

$anexos = [];
if (public_table_exists($pdo, 'anexos_os')) {
    $stAnexos = $pdo->prepare("
        SELECT *
        FROM anexos_os
        WHERE ordem_servico_id = ?
        ORDER BY criado_em DESC, id DESC
    ");
    $stAnexos->execute([$id]);
    $anexos = $stAnexos->fetchAll();
}

$prazo = ($os['sla_vencimento'] ?? null) ?: ($os['prazo'] ?? null);
$agentesOs = public_load_agentes_os($pdo, $id, is_array($os) ? $os : []);
$jaSolicitouEncerramento = !empty($os['solicitacao_encerramento']);
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($os['numero_os'] ?? ('OS #' . $id)) ?> - Shopping Carpina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="<?= e(app_path('assets/icon.svg')) ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(app_path('assets/style.css')) ?>">
    <style>
        body{background:#f6f7fb;}
        .public-hero{background:linear-gradient(135deg,#111827,#1f2937);color:#fff!important;border-radius:24px;padding:24px;box-shadow:0 18px 45px rgba(17,24,39,.18)}
        .public-hero,
        .public-hero *{color:#fff!important;}
        .public-hero .text-muted{color:rgba(255,255,255,.78)!important;}
        .public-hero h1,
        .public-hero .h3,
        .public-hero .h5,
        .public-hero .fw-normal{color:#fff!important;text-shadow:0 1px 2px rgba(0,0,0,.35);}
        .public-hero h1{font-weight:800!important;letter-spacing:-.02em;}
        .card{border:0;border-radius:20px;box-shadow:0 12px 30px rgba(15,23,42,.08)}
        .action-card{border:1px solid rgba(15,23,42,.08)}.anexo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}.anexo-card{border:1px solid #e5e7eb;border-radius:16px;padding:10px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,.05);transition:.2s;overflow:hidden}.anexo-card:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.08)}.os-photo{width:100%;height:190px;object-fit:cover;border-radius:12px;border:1px solid #ddd;background:#fff}.anexo-preview{height:190px;display:flex;align-items:center;justify-content:center;flex-direction:column;border:1px dashed #ccc;border-radius:12px;background:#fafafa;text-align:center;padding:15px}.anexo-icone{font-size:50px;line-height:1;margin-bottom:8px}.nome-anexo{margin-top:10px;font-size:13px;font-weight:600;word-break:break-word}.anexo-data{font-size:11px;color:#777;margin-top:4px}.anexo-descricao{font-size:12px;color:#666;margin-top:6px}.btn-anexo{margin-top:10px}.timeline-item{border-left:3px solid #e5e7eb;padding-left:14px;position:relative}.timeline-item:before{content:"";position:absolute;left:-7px;top:4px;width:11px;height:11px;border-radius:50%;background:#0d6efd}@media print{.no-print{display:none!important}.card,.public-hero{box-shadow:none}.anexo-card{break-inside:avoid;page-break-inside:avoid;box-shadow:none}.os-photo{height:150px}}
    </style>
</head>
<body>
<main class="container py-4">
    <div class="public-hero mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="text-muted small">Shopping Carpina • Ordem de Serviço Pública</div>
                <h1 class="h3 mb-1"><?= e($os['numero_os'] ?? ('OS #' . $id)) ?></h1>
                <div class="h5 fw-normal mb-0"><?= e($os['titulo'] ?? '-') ?></div>
                <div class="small mt-2" style="color:rgba(255,255,255,.82)!important;">
                    Executante: <?= e($agentesOs['principal'] ?: '-') ?>
                    <?php if (!empty($agentesOs['auxiliares'])): ?>
                        • Auxiliar: <?= e(implode(', ', $agentesOs['auxiliares'])) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <?= badge_status($os['status'] ?? '') ?>
                <?= badge_prioridade($os['prioridade'] ?? '') ?>
                <?= badge_sla($os) ?>
            </div>
        </div>
    </div>

    <?php if ($mensagemSucesso): ?><div class="alert alert-success no-print"><?= e($mensagemSucesso) ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger no-print"><?= e($mensagemErro) ?></div><?php endif; ?>

    <?php if ($jaSolicitouEncerramento): ?>
        <div class="alert alert-warning no-print">
            <strong>Encerramento solicitado.</strong>
            Solicitação enviada em <?= e($os['solicitacao_encerramento_data'] ?? '-') ?>.
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card p-3 mb-3">
                <h2 class="h5">Descrição</h2>
                <p class="mb-0"><?= nl2br(e($os['descricao'] ?? '-')) ?></p>
            </div>

            <div class="card p-3 mb-3">
                <h2 class="h5">Dados da OS</h2>
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">Local</div><div><?= e($os['local_nome'] ?? '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Categoria</div><div><?= e($os['categoria_nome'] ?? '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Gestor</div><div><?= e($os['gestor_nome'] ?? '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Agente principal / executante</div><div><?= e($agentesOs['principal'] ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Agente auxiliar</div><div><?= e(!empty($agentesOs['auxiliares']) ? implode(', ', $agentesOs['auxiliares']) : '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Prazo/SLA</div><div><?= e($prazo ?: '-') ?></div></div>
                    <div class="col-md-6"><div class="text-muted small">Criada em</div><div><?= e($os['criado_em'] ?? '-') ?></div></div>
                </div>
            </div>

            <div class="card p-3 mb-3 no-print action-card">
                <h2 class="h5">Anexar fotos do serviço</h2>
                <p class="text-muted small">Envie evidências do atendimento. Formatos aceitos: JPG, PNG, WEBP ou GIF até 10MB por foto.</p>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="enviar_fotos">
                    <div class="mb-3">
                        <label class="form-label">Fotos</label>
                        <input class="form-control" type="file" name="fotos[]" accept="image/*" multiple required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observação</label>
                        <textarea class="form-control" name="descricao_foto" rows="3" placeholder="Ex.: Foto após execução do serviço"></textarea>
                    </div>
                    <button class="btn btn-primary">Enviar foto(s)</button>
                </form>
            </div>

            <div class="card p-3 mb-3 no-print action-card">
                <h2 class="h5">Solicitar encerramento</h2>
                <p class="text-muted small">A solicitação não encerra automaticamente. O gestor/TI fará a aprovação final.</p>
                <form method="post">
                    <input type="hidden" name="acao" value="solicitar_encerramento">
                    <div class="mb-3">
                        <label class="form-label">Serviço realizado / justificativa</label>
                        <textarea class="form-control" name="observacao_encerramento" rows="4" required placeholder="Descreva o que foi feito para solicitar o encerramento."><?= e($os['solicitacao_encerramento_obs'] ?? '') ?></textarea>
                    </div>
                    <button class="btn btn-success" <?= $jaSolicitouEncerramento ? 'disabled' : '' ?>>
                        <?= $jaSolicitouEncerramento ? 'Encerramento já solicitado' : 'Solicitar encerramento' ?>
                    </button>
                </form>
            </div>

            <?php if ($anexos): ?>
                <div class="card p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div><h2 class="h5 mb-0">Anexos da Ordem de Serviço</h2><small class="text-muted">Arquivos enviados nesta OS</small></div>
                        <span class="badge bg-primary"><?= count($anexos) ?> arquivo(s)</span>
                    </div>
                    <div class="anexo-grid">
                        <?php foreach ($anexos as $anexo): ?>
                            <?php
                            $arquivo = public_file_url($anexo['arquivo_url'] ?? '');
                            if ($arquivo === '') continue;
                            $nomeArquivo = basename(parse_url($arquivo, PHP_URL_PATH) ?: $arquivo);
                            $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
                            $isImage = public_is_image($arquivo);
                            $isPdf = $ext === 'pdf';
                            ?>
                            <div class="anexo-card">
                                <?php if ($isImage): ?>
                                    <a href="<?= e($arquivo) ?>" target="_blank" rel="noopener"><img class="os-photo" src="<?= e($arquivo) ?>" alt="<?= e($anexo['descricao'] ?? 'Imagem da OS') ?>" loading="lazy"></a>
                                <?php elseif ($isPdf): ?>
                                    <div class="anexo-preview"><div class="anexo-icone">📄</div><div class="small text-muted">Documento PDF</div><a class="btn btn-outline-danger btn-sm btn-anexo" href="<?= e($arquivo) ?>" target="_blank" rel="noopener">Abrir PDF</a></div>
                                <?php else: ?>
                                    <div class="anexo-preview"><div class="anexo-icone">📎</div><div class="small text-muted">Arquivo anexado</div><a class="btn btn-outline-primary btn-sm btn-anexo" href="<?= e($arquivo) ?>" target="_blank" rel="noopener">Baixar arquivo</a></div>
                                <?php endif; ?>
                                <div class="nome-anexo"><?= e($nomeArquivo) ?></div>
                                <?php if (!empty($anexo['tipo'])): ?><div class="anexo-data">Tipo: <?= e($anexo['tipo']) ?></div><?php endif; ?>
                                <?php if (!empty($anexo['descricao'])): ?><div class="anexo-descricao"><?= e($anexo['descricao']) ?></div><?php endif; ?>
                                <?php if (!empty($anexo['criado_em'])): ?><div class="anexo-data"><?= e($anexo['criado_em']) ?></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card p-3">
                <h2 class="h5">Histórico</h2>
                <?php if ($historico): ?>
                    <div class="vstack gap-3">
                        <?php foreach ($historico as $item): ?>
                            <div class="timeline-item">
                                <div class="fw-semibold"><?= e($item['descricao'] ?? '-') ?></div>
                                <div class="small text-muted">
                                    <?= e($item['criado_em'] ?? '-') ?>
                                    <?php if (!empty($item['usuario_nome'])): ?> por <?= e($item['usuario_nome']) ?><?php else: ?> por acesso público<?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Nenhum histórico registrado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
</body>
</html>
