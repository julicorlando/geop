<?php
/**
 * GEOP - configuração do patch de exclusão
 *
 * AJUSTE ESTE ARQUIVO ANTES DE USAR.
 */
declare(strict_types=1);

/*
 * 1) Carregue o bootstrap real do GEOP.
 * Exemplo:
 * require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
 *
 * Ele deve iniciar sessão e disponibilizar PDO em $pdo.
 */
// require_once dirname(__DIR__, 2) . '/app/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
 * 2) Configure as tabelas/colunas do projeto real.
 */
const GEOP_ORDENS_TABLE = 'ordens';
const GEOP_ORDENS_PK = 'id';
const GEOP_USERS_TABLE = 'usuarios';
const GEOP_USERS_PK = 'id';

/*
 * 3) Ajuste as chaves de sessão conforme o GEOP real.
 */
function geopCurrentUserId(): int {
    return (int)($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0);
}

function geopCurrentUserRole(): string {
    $role = $_SESSION['perfil'] ?? $_SESSION['role'] ?? '';
    return strtoupper(trim((string)$role));
}

function geopIsTI(): bool {
    return in_array(geopCurrentUserRole(), ['TI', 'ADMIN', 'ADMINISTRADOR'], true);
}

function geopIsGestor(): bool {
    return in_array(geopCurrentUserRole(), ['GESTOR', 'GESTOR_SINDICO', 'GESTOR SÍNDICO'], true);
}

function geopRequireLogin(): void {
    if (geopCurrentUserId() <= 0) {
        http_response_code(401);
        exit('Sessão inválida.');
    }
}

function geopCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function geopCheckCsrf(): void {
    $token = (string)($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $token)) {
        http_response_code(419);
        exit('CSRF inválido.');
    }
}

/*
 * O GEOP real deve fornecer $pdo.
 */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    /*
     * NÃO coloque credenciais aqui em produção.
     * Integre com o bootstrap/config do GEOP.
     */
    throw new RuntimeException(
        'Patch não configurado: carregue o bootstrap do GEOP e disponibilize uma instância PDO em $pdo.'
    );
}
