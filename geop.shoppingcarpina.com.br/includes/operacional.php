<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/exclusao_ordens.php';

function app_table_exists(PDO $pdo, string $table): bool {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;

    try {
        $st = $pdo->prepare("SHOW TABLES LIKE ?");
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function app_column_exists(PDO $pdo, string $table, string $column): bool {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) return false;

    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $st->execute([$column]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function app_exec_safe(PDO $pdo, string $sql): void {
    try {
        $pdo->exec($sql);
    } catch (Throwable $e) {
        // Hospedagens compartilhadas podem negar ALTER/CREATE. A tela continua funcional.
    }
}

function ensure_operational_schema(PDO $pdo): void {
    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS auditoria_eventos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entidade VARCHAR(80) NOT NULL,
            entidade_id INT NULL,
            acao VARCHAR(80) NOT NULL,
            campo VARCHAR(120) NULL,
            valor_anterior TEXT NULL,
            valor_novo TEXT NULL,
            usuario_id INT NULL,
            ip VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_entidade (entidade, entidade_id),
            INDEX idx_usuario (usuario_id),
            INDEX idx_criado_em (criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS estoque_materiais (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(160) NOT NULL,
            unidade VARCHAR(30) NULL,
            quantidade_atual DECIMAL(12,2) NOT NULL DEFAULT 0,
            estoque_minimo DECIMAL(12,2) NOT NULL DEFAULT 0,
            custo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL,
            UNIQUE KEY uq_estoque_nome (nome)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS movimentacoes_estoque (
            id INT AUTO_INCREMENT PRIMARY KEY,
            material_id INT NOT NULL,
            ordem_servico_id INT NULL,
            usuario_id INT NULL,
            tipo VARCHAR(30) NOT NULL,
            quantidade DECIMAL(12,2) NOT NULL,
            custo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
            observacao TEXT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_material (material_id),
            INDEX idx_os (ordem_servico_id),
            INDEX idx_tipo (tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");



    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS ordem_agentes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ordem_servico_id INT NOT NULL,
            usuario_id INT NOT NULL,
            papel ENUM('PRINCIPAL','AUXILIAR') NOT NULL DEFAULT 'AUXILIAR',
            criado_por INT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_ordem_usuario (ordem_servico_id, usuario_id),
            INDEX idx_ordem (ordem_servico_id),
            INDEX idx_usuario (usuario_id),
            INDEX idx_papel (papel)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS token_reabertura_solicitacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ordem_servico_id INT NOT NULL,
            solicitado_por INT NULL,
            motivo TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
            atendido_por INT NULL,
            atendido_em DATETIME NULL,
            resolvida_em DATETIME NULL,
            resolvida_por INT NULL,
            token_id INT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_ordem_servico_id (ordem_servico_id),
            INDEX idx_criado_em (criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");



    app_exec_safe($pdo, "
        INSERT IGNORE INTO ordem_agentes (ordem_servico_id, usuario_id, papel)
        SELECT id, agente_id, 'PRINCIPAL'
        FROM ordens_servico
        WHERE agente_id IS NOT NULL
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS manutencoes_preventivas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            titulo VARCHAR(180) NOT NULL,
            descricao TEXT NULL,
            local_id INT NOT NULL,
            categoria_id INT NOT NULL,
            agente_id INT NULL,
            prioridade VARCHAR(20) NOT NULL DEFAULT 'MEDIA',
            intervalo_dias INT NOT NULL DEFAULT 30,
            proxima_execucao DATE NOT NULL,
            ultimo_gerado_em DATETIME NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_por INT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL,
            INDEX idx_proxima_execucao (proxima_execucao),
            INDEX idx_ativo (ativo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $columns = [
        'sla_horas' => "ALTER TABLE ordens_servico ADD COLUMN sla_horas INT NULL",
        'sla_vencimento' => "ALTER TABLE ordens_servico ADD COLUMN sla_vencimento DATETIME NULL",
        'origem' => "ALTER TABLE ordens_servico ADD COLUMN origem VARCHAR(30) NOT NULL DEFAULT 'MANUAL'",
        'preventiva_id' => "ALTER TABLE ordens_servico ADD COLUMN preventiva_id INT NULL",
        'custo_materiais' => "ALTER TABLE ordens_servico ADD COLUMN custo_materiais DECIMAL(12,2) NOT NULL DEFAULT 0",
    ];

    foreach ($columns as $column => $sql) {
        if (!app_column_exists($pdo, 'ordens_servico', $column)) {
            app_exec_safe($pdo, $sql);
        }
    }
}


function os_sync_agentes(PDO $pdo, int $ordemId, ?int $principalId, array $auxiliaresIds = [], ?int $usuarioId = null): void {
    if (!app_table_exists($pdo, 'ordem_agentes')) {
        ensure_operational_schema($pdo);
    }

    $ids = [];
    if ($principalId && $principalId > 0) {
        $ids[$principalId] = 'PRINCIPAL';
    }

    foreach ($auxiliaresIds as $auxId) {
        $auxId = (int)$auxId;
        if ($auxId <= 0 || ($principalId && $auxId === (int)$principalId)) {
            continue;
        }
        $ids[$auxId] = 'AUXILIAR';
    }

    $pdo->prepare("DELETE FROM ordem_agentes WHERE ordem_servico_id = ?")->execute([$ordemId]);

    if (!$ids) {
        return;
    }

    $st = $pdo->prepare("INSERT INTO ordem_agentes (ordem_servico_id, usuario_id, papel, criado_por) VALUES (?, ?, ?, ?)");
    foreach ($ids as $uid => $papel) {
        $st->execute([$ordemId, (int)$uid, $papel, $usuarioId]);
    }
}

function os_agente_participa(PDO $pdo, int $ordemId, int $usuarioId): bool {
    if ($usuarioId <= 0 || $ordemId <= 0) return false;

    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM ordem_agentes WHERE ordem_servico_id = ? AND usuario_id = ?");
        $st->execute([$ordemId, $usuarioId]);
        if ((int)$st->fetchColumn() > 0) return true;
    } catch (Throwable $e) {}

    try {
        $ordemAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico');
        $st = $pdo->prepare("SELECT COUNT(*) FROM ordens_servico WHERE id = ? AND agente_id = ? AND $ordemAtivaSql");
        $st->execute([$ordemId, $usuarioId]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function os_auxiliares_ids(PDO $pdo, int $ordemId): array {
    try {
        $st = $pdo->prepare("SELECT usuario_id FROM ordem_agentes WHERE ordem_servico_id = ? AND papel = 'AUXILIAR' ORDER BY id ASC");
        $st->execute([$ordemId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    } catch (Throwable $e) {
        return [];
    }
}

function os_agentes_nomes(PDO $pdo, int $ordemId): array {
    try {
        $st = $pdo->prepare("
            SELECT oa.usuario_id, oa.papel, u.nome
            FROM ordem_agentes oa
            JOIN usuarios u ON u.id = oa.usuario_id
            WHERE oa.ordem_servico_id = ?
            ORDER BY CASE WHEN oa.papel = 'PRINCIPAL' THEN 0 ELSE 1 END, u.nome
        ");
        $st->execute([$ordemId]);
        return $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function os_where_agente_participante(string $alias = 'os'): string {
    return "($alias.agente_id = ? OR EXISTS (SELECT 1 FROM ordem_agentes oa_vis WHERE oa_vis.ordem_servico_id = $alias.id AND oa_vis.usuario_id = ?))";
}

function sla_horas_por_prioridade(string $prioridade): int {
    $map = [
        'BAIXA' => 72,
        'MEDIA' => 48,
        'ALTA' => 24,
        'CRITICA' => 4,
        'URGENTE' => 4,
    ];

    return $map[strtoupper($prioridade)] ?? 48;
}

function calcular_vencimento_sla(string $prioridade, ?string $base = null): string {
    $horas = sla_horas_por_prioridade($prioridade);
    $timestamp = $base ? strtotime($base) : time();
    if (!$timestamp) $timestamp = time();

    return date('Y-m-d H:i:s', strtotime('+' . $horas . ' hours', $timestamp));
}

function prazo_sla_para_banco(?string $prazo, string $prioridade, ?string $base = null): string {
    $prazo = trim((string)$prazo);
    if ($prazo !== '') {
        return str_replace('T', ' ', $prazo);
    }

    return calcular_vencimento_sla($prioridade, $base);
}

function sla_info(array $os): array {
    $prazo = $os['sla_vencimento'] ?? ($os['prazo'] ?? null);
    $status = $os['status'] ?? '';

    if (!$prazo || in_array($status, ['ENCERRADA', 'CONCLUIDA_PELO_AGENTE', 'REPROVADA'], true)) {
        return ['estado' => 'sem_alerta', 'label' => $prazo ?: 'Sem prazo', 'classe' => 'secondary', 'horas' => 0];
    }

    $due = strtotime((string)$prazo);
    if (!$due) {
        return ['estado' => 'sem_alerta', 'label' => 'Prazo invalido', 'classe' => 'secondary', 'horas' => 0];
    }

    $diffHours = (int)floor(($due - time()) / 3600);

    if ($diffHours < 0) {
        $diasAtraso = max(1, (int)ceil((time() - $due) / 86400));
        $labelAtraso = $diasAtraso === 1 ? '1 dia de atraso' : $diasAtraso . ' dias de atraso';
        return [
            'estado' => 'atrasada',
            'label' => $labelAtraso,
            'classe' => 'danger',
            'horas' => $diffHours,
            'dias' => $diasAtraso,
        ];
    }

    if ($diffHours <= 6) {
        return ['estado' => 'vence_logo', 'label' => 'Vence em ' . $diffHours . 'h', 'classe' => 'warning', 'horas' => $diffHours];
    }

    return ['estado' => 'ok', 'label' => 'No prazo', 'classe' => 'success', 'horas' => $diffHours];
}

function badge_sla(array $os): string {
    $info = sla_info($os);
    return '<span class="badge text-bg-' . e($info['classe']) . '">' . e($info['label']) . '</span>';
}

function sla_sql_prazo(PDO $pdo, string $alias = 'os'): string {
    $alias = preg_replace('/[^A-Za-z0-9_]/', '', $alias);
    if ($alias === '') $alias = 'os';

    if (app_column_exists($pdo, 'ordens_servico', 'sla_vencimento')) {
        return "COALESCE($alias.sla_vencimento, $alias.prazo)";
    }

    return "$alias.prazo";
}

function telefone_whatsapp($telefone): string {
    $digits = preg_replace('/\D+/', '', (string)$telefone);
    if ($digits === '') return '';

    if (strlen($digits) <= 11) {
        $digits = '55' . $digits;
    }

    return $digits;
}

function app_base_url(): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if ($host === '') return '';

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';

    return rtrim($scheme . '://' . $host . app_base_path(), '/');
}

function os_url(int $id): string {
    return app_url('ordem_detalhe', ['id' => $id]);
}

function public_os_secret(): string {
    $envSecret = getenv('APP_PUBLIC_SHARE_SECRET') ?: getenv('APP_KEY');
    if ($envSecret) {
        return (string)$envSecret;
    }

    global $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS;
    return hash('sha256', implode('|', [
        __DIR__,
        (string)($DB_HOST ?? ''),
        (string)($DB_NAME ?? ''),
        (string)($DB_USER ?? ''),
        (string)($DB_PASS ?? ''),
    ]));
}

function public_os_token(array $os): string {
    $id = (int)($os['id'] ?? 0);
    $numero = (string)($os['numero_os'] ?? '');

    return substr(hash_hmac('sha256', $id . '|' . $numero, public_os_secret()), 0, 32);
}

function public_os_url(array $os): string {
    return app_url('os-publica', [
        'id' => (int)($os['id'] ?? 0),
        't' => public_os_token($os),
    ]);
}

function whatsapp_link($telefone, string $mensagem): string {
    $numero = telefone_whatsapp($telefone);
    if ($numero === '') return '';

    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($mensagem);
}

function mensagem_whatsapp_os(string $evento, array $os): string {
    $linhas = [
        'Shopping Carpina OS',
        $evento,
        'OS: ' . ($os['numero_os'] ?? ('#' . ($os['id'] ?? ''))),
        'Titulo: ' . ($os['titulo'] ?? '-'),
        'Status: ' . str_replace('_', ' ', (string)($os['status'] ?? '-')),
        'Prioridade: ' . ($os['prioridade'] ?? '-'),
        'Prazo/SLA: ' . (($os['sla_vencimento'] ?? null) ?: (($os['prazo'] ?? null) ?: '-')),
    ];

    if (!empty($os['id'])) {
        $linhas[] = 'Visualizar OS: ' . public_os_url($os);
    }

    return implode("\n", $linhas);
}

function whatsapp_queue_add($telefone, string $nome, string $mensagem, string $label = 'Enviar WhatsApp'): void {
    $link = whatsapp_link($telefone, $mensagem);
    if ($link === '') return;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $_SESSION['whatsapp_queue'][] = [
        'nome' => $nome,
        'label' => $label,
        'link' => $link,
        'mensagem' => $mensagem,
    ];
}

function whatsapp_queue_user(PDO $pdo, $usuarioId, string $mensagem, string $label = 'Enviar WhatsApp'): void {
    if (!$usuarioId) return;

    try {
        $st = $pdo->prepare("SELECT nome, telefone FROM usuarios WHERE id = ? LIMIT 1");
        $st->execute([(int)$usuarioId]);
        $usuario = $st->fetch();
    } catch (Throwable $e) {
        $usuario = null;
    }

    if ($usuario && !empty($usuario['telefone'])) {
        whatsapp_queue_add($usuario['telefone'], $usuario['nome'], $mensagem, $label);
    }
}

function render_whatsapp_queue(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $items = $_SESSION['whatsapp_queue'] ?? [];
    unset($_SESSION['whatsapp_queue']);

    if (!$items) return;
    ?>
    <div class="alert alert-success no-print">
        <div class="fw-bold mb-2">Notificacoes WhatsApp prontas para envio pelo dispositivo</div>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($items as $item): ?>
                <a class="btn btn-success btn-sm" target="_blank" rel="noopener" href="<?= e($item['link']) ?>">
                    <?= e($item['label']) ?> - <?= e($item['nome']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="small mt-2">O sistema prepara a mensagem e abre o WhatsApp do aparelho. O envio final continua sob controle do usuario.</div>
    </div>
    <?php
}

function auditoria_log(PDO $pdo, string $entidade, $entidadeId, string $acao, ?string $campo, $anterior, $novo, $usuarioId = null): void {
    try {
        $st = $pdo->prepare("
            INSERT INTO auditoria_eventos
            (entidade, entidade_id, acao, campo, valor_anterior, valor_novo, usuario_id, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            $entidade,
            $entidadeId !== null ? (int)$entidadeId : null,
            $acao,
            $campo,
            $anterior !== null ? (string)$anterior : null,
            $novo !== null ? (string)$novo : null,
            $usuarioId !== null ? (int)$usuarioId : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);
    } catch (Throwable $e) {
        // Auditoria nao deve quebrar o fluxo principal.
    }
}

function auditoria_campos(PDO $pdo, string $entidade, $entidadeId, string $acao, array $antes, array $depois, array $campos, $usuarioId = null): void {
    foreach ($campos as $campo) {
        $valorAntes = $antes[$campo] ?? null;
        $valorDepois = $depois[$campo] ?? null;

        if ((string)$valorAntes !== (string)$valorDepois) {
            auditoria_log($pdo, $entidade, $entidadeId, $acao, $campo, $valorAntes, $valorDepois, $usuarioId);
        }
    }
}

function consumir_estoque_por_material(PDO $pdo, int $osId, string $nomeMaterial, float $quantidade, ?string $unidade, $usuarioId = null): float {
    if ($nomeMaterial === '' || $quantidade <= 0 || !app_table_exists($pdo, 'estoque_materiais')) {
        return 0.0;
    }

    try {
        $st = $pdo->prepare("SELECT * FROM estoque_materiais WHERE nome = ? AND ativo = 1 LIMIT 1");
        $st->execute([$nomeMaterial]);
        $material = $st->fetch();

        if (!$material) return 0.0;

        $custoUnitario = (float)$material['custo_unitario'];
        $custoTotal = $custoUnitario * $quantidade;

        $pdo->prepare("
            UPDATE estoque_materiais
            SET quantidade_atual = quantidade_atual - ?, atualizado_em = NOW()
            WHERE id = ?
        ")->execute([$quantidade, $material['id']]);

        $pdo->prepare("
            INSERT INTO movimentacoes_estoque
            (material_id, ordem_servico_id, usuario_id, tipo, quantidade, custo_unitario, observacao)
            VALUES (?, ?, ?, 'SAIDA_OS', ?, ?, ?)
        ")->execute([
            $material['id'],
            $osId,
            $usuarioId,
            $quantidade,
            $custoUnitario,
            'Consumo registrado na OS',
        ]);

        if (app_column_exists($pdo, 'ordens_servico', 'custo_materiais')) {
            $pdo->prepare("UPDATE ordens_servico SET custo_materiais = COALESCE(custo_materiais, 0) + ? WHERE id = ?")
                ->execute([$custoTotal, $osId]);
        }

        return $custoTotal;
    } catch (Throwable $e) {
        return 0.0;
    }
}

function estoque_alertas(PDO $pdo): array {
    try {
        return $pdo->query("
            SELECT *
            FROM estoque_materiais
            WHERE ativo = 1
            AND quantidade_atual <= estoque_minimo
            ORDER BY nome
        ")->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}


function os_alertas_atribuicao_gestor(PDO $pdo, int $gestorId): array {
    if ($gestorId <= 0) return [];

    try {
        $ordemAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico','os');
        // Alerta robusto: pega OS do gestor que ainda nao tem agente definido.
        // Considera NULL, 0 ou vazio para evitar diferencas de banco/importacao.
        $st = $pdo->prepare("
            SELECT os.id, os.numero_os, os.titulo, os.prioridade, os.criado_em, l.nome AS local_nome
            FROM ordens_servico os
            LEFT JOIN locais l ON l.id = os.local_id
            WHERE os.gestor_id = ?
              AND $ordemAtivaSql
              AND (os.agente_id IS NULL OR os.agente_id = 0 OR os.agente_id = '')
              AND UPPER(COALESCE(os.status,'')) NOT IN ('ENCERRADA','CANCELADA','CONCLUIDA','CONCLUIDA_PELO_AGENTE')
            ORDER BY os.criado_em DESC
            LIMIT 10
        ");
        $st->execute([$gestorId]);
        return $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function os_alertas_token_ti(PDO $pdo): array {
    if (!app_table_exists($pdo, 'token_reabertura_solicitacoes')) {
        ensure_operational_schema($pdo);
    }

    try {
        $ordemAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico','os');
        $st = $pdo->query("
            SELECT
                s.id,
                s.ordem_servico_id,
                s.motivo,
                s.criado_em,
                os.numero_os,
                os.titulo,
                u.nome AS solicitado_por_nome
            FROM token_reabertura_solicitacoes s
            JOIN ordens_servico os ON os.id = s.ordem_servico_id
            LEFT JOIN usuarios u ON u.id = s.solicitado_por
            WHERE s.status = 'PENDENTE'
              AND $ordemAtivaSql
              AND NOT EXISTS (
                  SELECT 1
                  FROM tokens_reabertura_os t
                  WHERE (t.solicitacao_id = s.id
                     OR (t.solicitacao_id IS NULL
                         AND t.ordem_servico_id = s.ordem_servico_id
                         AND t.criado_em >= s.criado_em))
              )
            ORDER BY s.criado_em DESC
            LIMIT 8
        ");
        return $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

function token_reabertura_tem_solicitacao_pendente(PDO $pdo, int $ordemId): bool {
    if ($ordemId <= 0) return false;
    if (!app_table_exists($pdo, 'token_reabertura_solicitacoes')) {
        ensure_operational_schema($pdo);
    }

    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM token_reabertura_solicitacoes WHERE ordem_servico_id = ? AND status = 'PENDENTE'");
        $st->execute([$ordemId]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}
