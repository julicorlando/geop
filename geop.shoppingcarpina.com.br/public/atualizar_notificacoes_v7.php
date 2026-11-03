<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/operacional.php';
require_once __DIR__ . '/../includes/cftv.php';

require_role('TI');
check_csrf();

function v71_identificador_valido(string $valor): bool
{
    return (bool) preg_match('/^[A-Za-z0-9_]+$/', $valor);
}

function v71_tabela_existe(PDO $pdo, string $tabela): bool
{
    if (!v71_identificador_valido($tabela)) {
        return false;
    }
    $st = $pdo->prepare('SHOW TABLES LIKE ?');
    $st->execute([$tabela]);
    return (bool) $st->fetchColumn();
}

function v71_coluna_existe(PDO $pdo, string $tabela, string $coluna): bool
{
    if (!v71_identificador_valido($tabela) || !v71_identificador_valido($coluna)) {
        return false;
    }
    $st = $pdo->prepare("SHOW COLUMNS FROM `{$tabela}` LIKE ?");
    $st->execute([$coluna]);
    return (bool) $st->fetch(PDO::FETCH_ASSOC);
}

function v71_indice_existe(PDO $pdo, string $tabela, string $indice): bool
{
    if (!v71_identificador_valido($tabela) || !v71_identificador_valido($indice)) {
        return false;
    }
    $st = $pdo->query("SHOW INDEX FROM `{$tabela}`");
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $linha) {
        if (strcasecmp((string) ($linha['Key_name'] ?? ''), $indice) === 0) {
            return true;
        }
    }
    return false;
}

function v71_status(PDO $pdo): array
{
    $db = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $solicitacoes = v71_tabela_existe($pdo, 'cftv_solicitacoes_acesso');
    $liberacoes = v71_tabela_existe($pdo, 'cftv_liberacoes');

    return [
        'banco' => $db,
        'tabela_solicitacoes' => $solicitacoes,
        'tabela_liberacoes' => $liberacoes,
        'email_solicitacoes' => $solicitacoes && v71_coluna_existe($pdo, 'cftv_solicitacoes_acesso', 'email'),
        'email_liberacoes' => $liberacoes && v71_coluna_existe($pdo, 'cftv_liberacoes', 'email'),
        'indice_solicitacoes' => $solicitacoes && v71_indice_existe($pdo, 'cftv_solicitacoes_acesso', 'idx_solicitacao_email'),
        'indice_liberacoes' => $liberacoes && v71_indice_existe($pdo, 'cftv_liberacoes', 'idx_email'),
    ];
}

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Cria as tabelas CFTV que ainda não existirem usando a conexão real do GEOP.
        ensure_cftv_schema($pdo);

        if (!v71_tabela_existe($pdo, 'cftv_solicitacoes_acesso')) {
            throw new RuntimeException('A tabela cftv_solicitacoes_acesso não existe e não pôde ser criada.');
        }
        if (!v71_tabela_existe($pdo, 'cftv_liberacoes')) {
            throw new RuntimeException('A tabela cftv_liberacoes não existe e não pôde ser criada.');
        }

        if (!v71_coluna_existe($pdo, 'cftv_solicitacoes_acesso', 'email')) {
            $pdo->exec("ALTER TABLE cftv_solicitacoes_acesso ADD COLUMN email VARCHAR(190) NULL AFTER telefone");
        }
        if (!v71_coluna_existe($pdo, 'cftv_liberacoes', 'email')) {
            $pdo->exec("ALTER TABLE cftv_liberacoes ADD COLUMN email VARCHAR(190) NULL AFTER cpf");
        }
        if (!v71_indice_existe($pdo, 'cftv_solicitacoes_acesso', 'idx_solicitacao_email')) {
            $pdo->exec("CREATE INDEX idx_solicitacao_email ON cftv_solicitacoes_acesso(email)");
        }
        if (!v71_indice_existe($pdo, 'cftv_liberacoes', 'idx_email')) {
            $pdo->exec("CREATE INDEX idx_email ON cftv_liberacoes(email)");
        }

        $mensagem = 'Atualização V7 ativada com sucesso no banco usado pelo GEOP.';
    } catch (Throwable $e) {
        $erro = 'Não foi possível atualizar o banco: ' . $e->getMessage();
    }
}

$status = v71_status($pdo);
$ativo = $status['email_solicitacoes'] && $status['email_liberacoes'];

render_header('Atualização V7 — Notificações de liberações');
?>
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Atualização V7 — Liberações</h1>
        <p class="text-muted mb-0">Instalador compatível com cPanel, sem acesso ao information_schema.</p>
    </div>
    <a href="cftv?view=liberacoes" class="btn btn-outline-secondary">Voltar para liberações</a>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-success"><?= e($mensagem) ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-danger"><?= e($erro) ?></div>
<?php endif; ?>

<div class="card p-3 mb-3">
    <h2 class="h5">Banco conectado</h2>
    <p class="mb-0"><code><?= e($status['banco'] ?: 'não identificado') ?></code></p>
</div>

<div class="card p-3 mb-3">
    <h2 class="h5">Diagnóstico</h2>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Item</th><th>Situação</th></tr></thead>
            <tbody>
                <?php
                $itens = [
                    'Tabela de solicitações' => $status['tabela_solicitacoes'],
                    'Tabela de liberações' => $status['tabela_liberacoes'],
                    'E-mail nas solicitações' => $status['email_solicitacoes'],
                    'E-mail nas liberações' => $status['email_liberacoes'],
                    'Índice de e-mail nas solicitações' => $status['indice_solicitacoes'],
                    'Índice de e-mail nas liberações' => $status['indice_liberacoes'],
                ];
                foreach ($itens as $rotulo => $ok):
                ?>
                    <tr>
                        <td><?= e($rotulo) ?></td>
                        <td><span class="badge <?= $ok ? 'text-bg-success' : 'text-bg-warning' ?>"><?= $ok ? 'OK' : 'Pendente' ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($ativo): ?>
    <div class="alert alert-success">
        <strong>V7 ativada.</strong> O banco já possui os campos obrigatórios para o e-mail do solicitante.
    </div>
<?php else: ?>
    <form method="post" class="card p-3">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <h2 class="h5">Ativar no banco atual</h2>
        <p>O instalador adicionará somente as colunas e índices que estiverem faltando.</p>
        <button class="btn btn-primary" type="submit">Ativar atualização V7</button>
    </form>
<?php endif; ?>
<?php render_footer(); ?>
