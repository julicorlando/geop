<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/geop_v8.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function is_logged_in() {
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

function require_login() {
    global $pdo;
    if (!is_logged_in()) {
        header('Location: ' . app_path('login'));
        exit;
    }

    geop_v8_boot($pdo);
    if (!geop_v8_session_guard($pdo)) {
        $uid = (int)(current_user()['id'] ?? 0);
        geop_v8_security_event($pdo, $uid ?: null, 'SESSAO_EXPIRADA', 'Sessão encerrada por inatividade.');
        logout_user();
        header('Location: ' . app_path('login', ['expirada' => 1]));
        exit;
    }

    if ((!empty($_SESSION['senha_troca_obrigatoria']) || password_change_required()) && !password_policy_exempt_page()) {
        header('Location: ' . app_path('mudar_senha', ['obrigatoria' => 1]));
        exit;
    }
}

function require_role($roles) {
    require_login();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array(current_user()['perfil'], $roles, true)) {
        http_response_code(403);
        die('Acesso negado.');
    }
}

function usuario_pode(string $permissao): bool {
    global $pdo;
    if (!is_logged_in()) return false;
    if ((current_user()['perfil'] ?? '') === 'TI') return true;
    static $cache = [];
    $perfil = (string) (current_user()['perfil'] ?? '');
    $key = $perfil . ':' . $permissao;
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $st = $pdo->prepare("SELECT 1
            FROM perfis pf
            JOIN perfil_permissoes pp ON pp.perfil_id = pf.id
            JOIN permissoes p ON p.id = pp.permissao_id
            WHERE pf.codigo = ? AND pf.ativo = 1 AND p.codigo = ? LIMIT 1");
        $st->execute([$perfil, $permissao]);
        return $cache[$key] = (bool) $st->fetchColumn();
    } catch (Throwable $e) {
        error_log('[GEOP RBAC] ' . $e->getMessage());
        return $cache[$key] = false;
    }
}

function exigir_permissao(string $permissao): void {
    require_login();
    if (!usuario_pode($permissao)) {
        http_response_code(403);
        throw new RuntimeException('Você não tem permissão para realizar esta operação.');
    }
}

function can_manage_users() {
    return is_logged_in() && current_user()['perfil'] === 'TI';
}

function can_manage_catalogs() {
    return is_logged_in() && current_user()['perfil'] === 'TI';
}

function can_manage_os() {
    return is_logged_in() && in_array(current_user()['perfil'], ['TI','GESTOR','GESTOR_SINDICO'], true);
}

function can_create_os() {
    return is_logged_in() && in_array(current_user()['perfil'], ['TI','GESTOR','COORDENADOR_OPERACOES_OBRAS'], true);
}

function can_execute_os() {
    return is_logged_in() && in_array(current_user()['perfil'], ['AGENTE','COORDENADOR_OPERACOES_OBRAS'], true);
}

function can_access_reports() {
    return is_logged_in() && current_user()['perfil'] !== 'CFTV';
}

function app_user_profiles(): array {
    return [
        'TI' => 'TI',
        'GESTOR' => 'Gestor',
        'GESTOR_SINDICO' => 'Gestor síndico',
        'AGENTE' => 'Agente',
        'CFTV' => 'CFTV',
        'CONTROLE_ACESSO' => 'Controle de acesso',
        'DIRETOR_EXECUTIVO' => 'Diretor executivo',
        'COORDENADOR_OPERACOES_OBRAS' => 'Coordenador de operacoes e obras',
        'ASG' => 'Auxiliar de Serviços Gerais',
        'SUPERVISOR_ASG' => 'Supervisor de Serviços Gerais',
    ];
}

function profile_label(?string $perfil): string {
    $perfis = app_user_profiles();
    return $perfis[$perfil ?? ''] ?? (string)$perfil;
}

function auth_column_exists(PDO $pdo, string $table, string $column): bool {
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $st->execute([$column]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function ensure_password_policy_schema(): void {
    global $pdo;
    static $done = false;
    if ($done) return;
    $done = true;

    try {
        if (!auth_column_exists($pdo, 'usuarios', 'senha_alterada_em')) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN senha_alterada_em DATETIME NULL");
        }

        if (!auth_column_exists($pdo, 'usuarios', 'forcar_troca_senha')) {
            $pdo->exec("ALTER TABLE usuarios ADD COLUMN forcar_troca_senha TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (Throwable $e) {
        // Se a hospedagem negar ALTER, o login continua funcionando sem quebrar o sistema.
    }
}

function password_policy_exempt_page(): bool {
    $script = basename(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''));
    return in_array($script, ['mudar_senha.php', 'logout.php', 'login.php'], true);
}

function password_change_required(?array $user = null): bool {
    global $pdo;
    ensure_password_policy_schema();

    if (!auth_column_exists($pdo, 'usuarios', 'senha_alterada_em') || !auth_column_exists($pdo, 'usuarios', 'forcar_troca_senha')) {
        return false;
    }

    $user = $user ?: current_user();
    if (!$user || empty($user['id'])) {
        return false;
    }

    try {
        $st = $pdo->prepare("SELECT senha_alterada_em, forcar_troca_senha FROM usuarios WHERE id = ? LIMIT 1");
        $st->execute([(int)$user['id']]);
        $policy = $st->fetch();

        if ($policy) {
            $user['senha_alterada_em'] = $policy['senha_alterada_em'];
            $user['forcar_troca_senha'] = $policy['forcar_troca_senha'];

            if (is_logged_in() && (int)(current_user()['id'] ?? 0) === (int)$user['id']) {
                $_SESSION['user']['senha_alterada_em'] = $policy['senha_alterada_em'];
                $_SESSION['user']['forcar_troca_senha'] = $policy['forcar_troca_senha'];
            }
        }
    } catch (Throwable $e) {
        // Se nao conseguir consultar, usa os dados ja carregados na sessao.
    }

    if ((int)($user['forcar_troca_senha'] ?? 1) === 1) {
        return true;
    }

    $alteradaEm = trim((string)($user['senha_alterada_em'] ?? ''));
    if ($alteradaEm === '') {
        return true;
    }

    $timestamp = strtotime($alteradaEm);
    if (!$timestamp) {
        return true;
    }

    return $timestamp <= strtotime('-90 days');
}

function ensure_gestor_token_table() {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS gestor_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token VARCHAR(64) NOT NULL,
        target VARCHAR(50) NOT NULL,
        valido_ate DATETIME NOT NULL,
        criado_por INT NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function generate_gestor_token(string $target, int $geradoPor, int $minutes = 15): string {
    global $pdo;
    ensure_gestor_token_table();
    $token = bin2hex(random_bytes(8));
    $validoAte = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
    $st = $pdo->prepare("INSERT INTO gestor_tokens (token, target, valido_ate, criado_por) VALUES (?, ?, ?, ?)");
    $st->execute([$token, $target, $validoAte, $geradoPor]);
    return $token;
}

function validate_gestor_token(string $token, string $target): bool {
    global $pdo;
    ensure_gestor_token_table();
    $st = $pdo->prepare("SELECT id FROM gestor_tokens WHERE token = ? AND target = ? AND valido_ate >= NOW() LIMIT 1");
    $st->execute([$token, $target]);
    return (bool)$st->fetchColumn();
}

function gestor_reports_access_allowed(): bool {
    return !empty($_SESSION['gestor_reports_access']) && ($_SESSION['gestor_reports_access'] + 600) >= time();
}

function mark_gestor_reports_access(): void {
    $_SESSION['gestor_reports_access'] = time();
}

/**
 * Controle de franquia de relatórios do Gestor.
 * O Gestor pode emitir até 10 relatórios sem token. Ao validar um token do TI,
 * a franquia é renovada para mais 10 emissões. Consultas/filtros não consomem a franquia.
 */
function ensure_gestor_report_quota_table(): void {
    global $pdo;
    $pdo->exec("CREATE TABLE IF NOT EXISTS gestor_relatorio_controle (
        usuario_id INT NOT NULL PRIMARY KEY,
        relatorios_sem_token INT NOT NULL DEFAULT 0,
        ultimo_token_em DATETIME NULL,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_gestor_relatorio_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gestor_relatorio_emissoes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        tipo VARCHAR(40) NOT NULL DEFAULT 'RELATORIO',
        sem_token TINYINT(1) NOT NULL DEFAULT 1,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_gestor_relatorio_emissoes_usuario_data (usuario_id, criado_em),
        CONSTRAINT fk_gestor_relatorio_emissao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function gestor_report_quota_status(int $usuarioId): array {
    global $pdo;
    ensure_gestor_report_quota_table();
    $st = $pdo->prepare("SELECT relatorios_sem_token, ultimo_token_em FROM gestor_relatorio_controle WHERE usuario_id=?");
    $st->execute([$usuarioId]);
    $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['relatorios_sem_token' => 0, 'ultimo_token_em' => null];
    $usados = max(0, (int)($row['relatorios_sem_token'] ?? 0));
    return [
        'limite' => 10,
        'usados' => min(10, $usados),
        'restantes' => max(0, 10 - $usados),
        'ultimo_token_em' => $row['ultimo_token_em'] ?? null,
    ];
}

function gestor_report_register_generation(int $usuarioId, string $tipo = 'RELATORIO'): array {
    global $pdo;
    ensure_gestor_report_quota_table();

    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT IGNORE INTO gestor_relatorio_controle(usuario_id, relatorios_sem_token) VALUES(?,0)")
            ->execute([$usuarioId]);
        $st = $pdo->prepare("SELECT relatorios_sem_token FROM gestor_relatorio_controle WHERE usuario_id=? FOR UPDATE");
        $st->execute([$usuarioId]);
        $usados = (int)$st->fetchColumn();

        if ($usados >= 10) {
            $pdo->rollBack();
            return ['allowed' => false, 'limite' => 10, 'usados' => 10, 'restantes' => 0, 'requires_token' => true];
        }

        $novo = $usados + 1;
        $pdo->prepare("UPDATE gestor_relatorio_controle SET relatorios_sem_token=? WHERE usuario_id=?")
            ->execute([$novo, $usuarioId]);
        $pdo->prepare("INSERT INTO gestor_relatorio_emissoes(usuario_id,tipo,sem_token) VALUES(?,?,1)")
            ->execute([$usuarioId, mb_substr($tipo, 0, 40)]);
        $pdo->commit();

        return ['allowed' => true, 'limite' => 10, 'usados' => $novo, 'restantes' => max(0, 10 - $novo), 'requires_token' => false];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function gestor_report_reset_quota(int $usuarioId): void {
    global $pdo;
    ensure_gestor_report_quota_table();
    $pdo->prepare("INSERT INTO gestor_relatorio_controle(usuario_id,relatorios_sem_token,ultimo_token_em)
        VALUES(?,0,NOW())
        ON DUPLICATE KEY UPDATE relatorios_sem_token=0, ultimo_token_em=NOW()")
        ->execute([$usuarioId]);
}

function login_user($email, $senha, &$motivo = '') {
    global $pdo;
    ensure_password_policy_schema();

    $email = mb_strtolower(trim((string)$email), 'UTF-8');
    $senha = (string)$senha;
    geop_v8_boot($pdo);

    if ($email === '' || $senha === '') {
        $motivo = 'Informe e-mail e senha.';
        return false;
    }

    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $lock = geop_v8_login_blocked($pdo, $email, $ip);
    if (!empty($lock['blocked'])) {
        $motivo = 'Muitas tentativas de acesso. Tente novamente em alguns minutos.';
        geop_v8_security_event($pdo, null, 'LOGIN_BLOQUEADO', $email);
        return false;
    }

    $stmt = $pdo->prepare("SELECT u.*, s.nome AS setor_nome FROM usuarios u LEFT JOIN setores s ON s.id = u.setor_id WHERE LOWER(u.email) = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $motivo = 'E-mail, senha ou status do usuário inválido.';
        geop_v8_login_attempt($pdo, $email, null, false, 'USUARIO_NAO_ENCONTRADO');
        return false;
    }

    if ($user['status'] !== 'ATIVO') {
        $motivo = 'Usuario inativo.';
        geop_v8_login_attempt($pdo, $email, (int)$user['id'], false, 'USUARIO_INATIVO');
        return false;
    }

    $senhaOk = password_verify($senha, $user['senha_hash']);

    if (!$senhaOk) {
        $motivo = 'Senha incorreta.';
        geop_v8_login_attempt($pdo, $email, (int)$user['id'], false, 'SENHA_INCORRETA');
        auditar($pdo, 'LOGIN_FALHA', 'usuarios', (int) $user['id'], [], ['email' => $email]);
        return false;
    }

    $twoFactorRequired = !empty($user['totp_enabled']);
    $forceTi2fa = (bool)geop_v8_config($pdo, '2fa_ti_required', false) && (($user['perfil'] ?? '') === 'TI');
    if ($forceTi2fa && empty($user['totp_enabled'])) {
        $motivo = 'Sua conta TI precisa ativar o 2FA. Entre em contato com outro TI ou desative temporariamente a exigência no banco.';
        geop_v8_login_attempt($pdo, $email, (int)$user['id'], false, '2FA_TI_NAO_CONFIGURADO');
        return false;
    }

    if ($twoFactorRequired) {
        $_SESSION['2fa_pending_user'] = ['id'=>(int)$user['id'],'email'=>(string)$user['email'],'perfil'=>(string)$user['perfil']];
        $_SESSION['2fa_pending_at'] = time();
        $motivo = '2FA_REQUIRED';
        geop_v8_security_event($pdo, (int)$user['id'], 'LOGIN_2FA_PENDENTE');
        return false;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $trocaObrigatoria = password_change_required($user);

    unset($user['senha_hash']);
    $_SESSION['user'] = $user;
    $_SESSION['senha_troca_obrigatoria'] = $trocaObrigatoria ? 1 : 0;
    $_SESSION['geop_last_activity'] = time();
    geop_v8_login_attempt($pdo, $email, (int)$user['id'], true, 'OK');
    geop_v8_security_event($pdo, (int)$user['id'], 'LOGIN_SUCESSO');
    auditar($pdo, 'LOGIN_SUCESSO', 'usuarios', (int) $user['id']);
    return true;
}

function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function check_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf'] ?? '';
        if (!is_string($token) || !hash_equals((string) ($_SESSION['csrf'] ?? ''), $token)) {
            http_response_code(419);
            throw new RuntimeException('Sua sessão expirou. Atualize a página e tente novamente.');
        }
    }
}
