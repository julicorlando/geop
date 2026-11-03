<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/operacional.php';

// Compatibilidade com a nomenclatura real do auth.php atual.
if (function_exists('exigir_permissao')) {
    exigir_permissao('perfis.gerenciar');
} elseif (function_exists('require_permission')) {
    require_permission('perfis.gerenciar');
} else {
    require_role('TI');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
}

$erro = '';
$msg = '';

function perfil_codigo_normalizar(string $nome): string
{
    $texto = trim($nome);
    if ($texto === '') {
        return '';
    }

    if (function_exists('iconv')) {
        $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if (is_string($convertido) && $convertido !== '') {
            $texto = $convertido;
        }
    }

    $texto = strtoupper($texto);
    $texto = preg_replace('/[^A-Z0-9]+/', '_', $texto) ?? '';
    $texto = preg_replace('/_+/', '_', $texto) ?? '';

    return trim($texto, '_');
}

function perfil_coluna_existe(PDO $pdo, string $tabela, string $coluna): bool
{
    static $cache = [];
    $chave = $tabela . '.' . $coluna;

    if (array_key_exists($chave, $cache)) {
        return $cache[$chave];
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $tabela) || !preg_match('/^[A-Za-z0-9_]+$/', $coluna)) {
        return $cache[$chave] = false;
    }

    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$tabela}` LIKE ?");
        $stmt->execute([$coluna]);
        return $cache[$chave] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        error_log('[GEOP PERFIS] Falha ao verificar coluna: ' . $e->getMessage());
        return $cache[$chave] = false;
    }
}

function perfil_audit(PDO $pdo, int $id, string $acao, $anterior = null, $novo = null): void
{
    $usuario = current_user();
    $usuarioId = (int) ($usuario['id'] ?? 0);

    try {
        if (function_exists('auditoria_log')) {
            auditoria_log($pdo, 'perfil', $id, $acao, null, $anterior, $novo, $usuarioId);
        } elseif (function_exists('auditar')) {
            $antes = is_array($anterior) ? $anterior : ['valor' => $anterior];
            $depois = is_array($novo) ? $novo : ['valor' => $novo];
            auditar($pdo, 'PERFIL_' . $acao, 'perfis', $id, $antes, $depois);
        }
    } catch (Throwable $e) {
        error_log('[GEOP PERFIS AUDITORIA] ' . $e->getMessage());
    }
}

function perfil_eh_ti(array $perfil): bool
{
    return strtoupper(trim((string) ($perfil['codigo'] ?? ''))) === 'TI';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $acao = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);

        if ($acao === 'save') {
            $nome = trim((string) ($_POST['nome'] ?? ''));
            $descricao = trim((string) ($_POST['descricao'] ?? ''));

            if ($nome === '' || mb_strlen($nome, 'UTF-8') > 120) {
                throw new RuntimeException('Nome inválido.');
            }

            $anterior = null;

            if ($id > 0) {
                $stmt = $pdo->prepare('SELECT * FROM perfis WHERE id = ? FOR UPDATE');
                $stmt->execute([$id]);
                $anterior = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$anterior) {
                    throw new RuntimeException('Perfil inexistente.');
                }

                $campos = ['nome = ?', 'descricao = ?'];
                $valores = [$nome, $descricao !== '' ? $descricao : null];

                if (perfil_coluna_existe($pdo, 'perfis', 'atualizado_em')) {
                    $campos[] = 'atualizado_em = NOW()';
                }

                $valores[] = $id;
                $sql = 'UPDATE perfis SET ' . implode(', ', $campos) . ' WHERE id = ?';
                $pdo->prepare($sql)->execute($valores);
            } else {
                $codigo = perfil_codigo_normalizar($nome);
                if ($codigo === '') {
                    throw new RuntimeException('Não foi possível gerar o código do perfil.');
                }

                $stmt = $pdo->prepare('SELECT id FROM perfis WHERE codigo = ? LIMIT 1');
                $stmt->execute([$codigo]);
                if ($stmt->fetchColumn()) {
                    throw new RuntimeException('Já existe um perfil com esse código. Escolha outro nome.');
                }

                $colunas = ['codigo', 'nome', 'descricao'];
                $marcadores = ['?', '?', '?'];
                $valores = [$codigo, $nome, $descricao !== '' ? $descricao : null];

                if (perfil_coluna_existe($pdo, 'perfis', 'criado_por')) {
                    $colunas[] = 'criado_por';
                    $marcadores[] = '?';
                    $valores[] = (int) (current_user()['id'] ?? 0);
                }

                $sql = 'INSERT INTO perfis (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', $marcadores) . ')';
                $pdo->prepare($sql)->execute($valores);
                $id = (int) $pdo->lastInsertId();
            }

            $permissoes = array_values(array_unique(array_filter(
                array_map('intval', (array) ($_POST['permissoes'] ?? [])),
                static fn (int $valor): bool => $valor > 0
            )));

            // Impede que o próprio usuário perca acidentalmente a administração.
            $stmt = $pdo->prepare('SELECT perfil_id FROM usuarios WHERE id = ? LIMIT 1');
            $stmt->execute([(int) (current_user()['id'] ?? 0)]);
            $perfilAtualId = (int) $stmt->fetchColumn();

            if ($perfilAtualId === $id) {
                $stmt = $pdo->prepare("SELECT id FROM permissoes WHERE codigo = 'perfis.gerenciar' LIMIT 1");
                $stmt->execute();
                $permissaoAdministrar = (int) $stmt->fetchColumn();

                if (
                    $permissaoAdministrar > 0
                    && !in_array($permissaoAdministrar, $permissoes, true)
                    && empty($_POST['confirm_self'])
                ) {
                    throw new RuntimeException('Confirme a remoção da sua própria permissão administrativa.');
                }
            }

            $pdo->prepare('DELETE FROM perfil_permissoes WHERE perfil_id = ?')->execute([$id]);
            $inserir = $pdo->prepare('INSERT INTO perfil_permissoes (perfil_id, permissao_id) VALUES (?, ?)');

            foreach ($permissoes as $permissaoId) {
                $inserir->execute([$id, $permissaoId]);
            }

            perfil_audit(
                $pdo,
                $id,
                $anterior ? 'EDITAR' : 'CRIAR',
                $anterior,
                ['nome' => $nome, 'permissoes' => $permissoes]
            );

            $msg = 'Perfil salvo com sucesso.';
        } elseif ($acao === 'toggle') {
            $stmt = $pdo->prepare('SELECT * FROM perfis WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$perfil) {
                throw new RuntimeException('Perfil inexistente.');
            }

            $novoAtivo = empty($perfil['ativo']);

            if (!$novoAtivo && perfil_eh_ti($perfil)) {
                throw new RuntimeException('O perfil TI é administrativo e não pode ser desativado.');
            }

            $pdo->prepare('UPDATE perfis SET ativo = ? WHERE id = ?')->execute([$novoAtivo ? 1 : 0, $id]);
            perfil_audit($pdo, $id, $novoAtivo ? 'ATIVAR' : 'DESATIVAR', $perfil, ['ativo' => $novoAtivo]);
            $msg = 'Status atualizado.';
        } elseif ($acao === 'delete') {
            $substituto = (int) ($_POST['replacement'] ?? 0);

            $stmt = $pdo->prepare('SELECT * FROM perfis WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$perfil) {
                throw new RuntimeException('Perfil inexistente.');
            }

            if (perfil_eh_ti($perfil)) {
                throw new RuntimeException('O perfil TI é administrativo e não pode ser excluído.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE perfil_id = ?');
            $stmt->execute([$id]);
            $totalUsuarios = (int) $stmt->fetchColumn();

            if ($totalUsuarios > 0 && $substituto <= 0) {
                throw new RuntimeException('Selecione um perfil substituto para os usuários vinculados.');
            }

            if ($totalUsuarios > 0) {
                $stmt = $pdo->prepare('SELECT codigo FROM perfis WHERE id = ? AND id <> ? AND ativo = 1 LIMIT 1');
                $stmt->execute([$substituto, $id]);
                $codigoSubstituto = $stmt->fetchColumn();

                if (!$codigoSubstituto) {
                    throw new RuntimeException('Perfil substituto inválido.');
                }

                if (perfil_coluna_existe($pdo, 'usuarios', 'perfil')) {
                    $pdo->prepare('UPDATE usuarios SET perfil_id = ?, perfil = ? WHERE perfil_id = ?')
                        ->execute([$substituto, $codigoSubstituto, $id]);
                } else {
                    $pdo->prepare('UPDATE usuarios SET perfil_id = ? WHERE perfil_id = ?')
                        ->execute([$substituto, $id]);
                }
            }

            $pdo->prepare('DELETE FROM perfil_permissoes WHERE perfil_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM perfis WHERE id = ?')->execute([$id]);

            perfil_audit($pdo, $id, 'EXCLUIR', $perfil, ['substituto' => $substituto]);
            $msg = 'Perfil excluído.';
        } else {
            throw new RuntimeException('Ação inválida.');
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('[GEOP PERFIS] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());

        if ($e instanceof PDOException) {
            $erro = 'Não foi possível salvar. Verifique se já existe um perfil com o mesmo código ou se o banco está atualizado.';
        } else {
            $erro = $e->getMessage();
        }
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare(
    'SELECT p.*, (SELECT COUNT(*) FROM usuarios u WHERE u.perfil_id = p.id) AS users
     FROM perfis p
     WHERE p.nome LIKE ? OR p.codigo LIKE ?
     ORDER BY p.nome
     LIMIT ' . $limit . ' OFFSET ' . $offset
);
$stmt->execute(['%' . $q . '%', '%' . $q . '%']);
$profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$perms = $pdo->query('SELECT * FROM permissoes ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$all = $pdo->query('SELECT id, codigo, nome FROM perfis WHERE ativo = 1 ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);

$edit = null;
$selected = [];
$editId = (int) ($_GET['edit'] ?? 0);

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM perfis WHERE id = ?');
    $stmt->execute([$editId]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($edit) {
        $stmt = $pdo->prepare('SELECT permissao_id FROM perfil_permissoes WHERE perfil_id = ?');
        $stmt->execute([$editId]);
        $selected = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}

render_header('Perfis');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Perfis e permissões</h1>
        <p class="text-muted mb-0">O perfil TI é o perfil administrativo protegido do sistema.</p>
    </div>
    <?php if ($edit): ?>
        <a class="btn btn-outline-secondary" href="perfis.php">Novo perfil</a>
    <?php endif; ?>
</div>

<?php if ($erro): ?>
    <div class="alert alert-danger"><?= e($erro) ?></div>
<?php endif; ?>

<?php if ($msg): ?>
    <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <form method="post" class="card p-3">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">

            <label class="form-label" for="perfil-nome">Nome do perfil</label>
            <input
                id="perfil-nome"
                class="form-control mb-3"
                name="nome"
                required
                maxlength="120"
                value="<?= e($edit['nome'] ?? '') ?>"
            >

            <?php if ($edit): ?>
                <div class="mb-3">
                    <span class="form-label d-block">Código</span>
                    <code><?= e($edit['codigo'] ?? '') ?></code>
                    <?php if (perfil_eh_ti($edit)): ?>
                        <span class="badge text-bg-primary ms-2">Administrativo</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <label class="form-label" for="perfil-descricao">Descrição</label>
            <textarea
                id="perfil-descricao"
                class="form-control mb-3"
                name="descricao"
                maxlength="255"
                rows="3"
            ><?= e($edit['descricao'] ?? '') ?></textarea>

            <h2 class="h6">Matriz de permissões</h2>
            <div class="border rounded p-2" style="max-height: 430px; overflow-y: auto;">
                <?php foreach ($perms as $permissao): ?>
                    <label class="form-check mb-1">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="permissoes[]"
                            value="<?= (int) $permissao['id'] ?>"
                            <?= in_array((int) $permissao['id'], $selected, true) ? 'checked' : '' ?>
                        >
                        <span class="form-check-label">
                            <?= e($permissao['nome'] ?? $permissao['codigo'] ?? '') ?>
                            <?php if (!empty($permissao['codigo'])): ?>
                                <small class="text-muted d-block"><?= e($permissao['codigo']) ?></small>
                            <?php endif; ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="confirm_self" value="1">
                <span class="form-check-label">Confirmo eventual perda da minha própria permissão administrativa.</span>
            </label>

            <button class="btn btn-primary mt-3" type="submit">Salvar perfil</button>
        </form>
    </div>

    <div class="col-lg-7">
        <form method="get" class="mb-3">
            <div class="input-group">
                <input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Pesquisar por nome ou código">
                <button class="btn btn-outline-primary" type="submit">Pesquisar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Perfil</th>
                        <th>Usuários</th>
                        <th style="min-width: 270px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$profiles): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">Nenhum perfil encontrado.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($profiles as $perfil): ?>
                        <?php $ehTi = perfil_eh_ti($perfil); ?>
                        <tr>
                            <td>
                                <strong><?= e($perfil['nome']) ?></strong>
                                <?php if ($ehTi): ?>
                                    <span class="badge text-bg-primary ms-1">Administrativo</span>
                                <?php endif; ?>
                                <br>
                                <small class="text-muted">
                                    <?= e($perfil['codigo'] ?? '') ?> · <?= !empty($perfil['ativo']) ? 'Ativo' : 'Inativo' ?>
                                </small>
                            </td>
                            <td><?= (int) $perfil['users'] ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int) $perfil['id'] ?>">Editar</a>

                                <?php if (!$ehTi): ?>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?= (int) $perfil['id'] ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">
                                            <?= !empty($perfil['ativo']) ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>

                                    <form method="post" class="d-flex gap-1 mt-2" onsubmit="return confirm('Excluir este perfil? Esta ação não poderá ser desfeita.');">
                                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $perfil['id'] ?>">

                                        <select class="form-select form-select-sm" name="replacement">
                                            <option value="">Perfil substituto</option>
                                            <?php foreach ($all as $substituto): ?>
                                                <?php if ((int) $substituto['id'] !== (int) $perfil['id']): ?>
                                                    <option value="<?= (int) $substituto['id'] ?>">
                                                        <?= e($substituto['nome']) ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>

                                        <button class="btn btn-sm btn-outline-danger" type="submit">Excluir</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge text-bg-light border ms-1">Protegido</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($page > 1 || count($profiles) === $limit): ?>
            <nav aria-label="Paginação">
                <ul class="pagination">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?q=<?= urlencode($q) ?>&page=<?= max(1, $page - 1) ?>">Anterior</a>
                    </li>
                    <li class="page-item disabled"><span class="page-link">Página <?= $page ?></span></li>
                    <li class="page-item <?= count($profiles) < $limit ? 'disabled' : '' ?>">
                        <a class="page-link" href="?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>">Próxima</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php render_footer(); ?>