<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/cftv.php';

require_role('TI');
check_csrf();
ensure_cftv_user_profile($pdo);
ensure_password_policy_schema();
$senhaPolicyDisponivel = auth_column_exists($pdo, 'usuarios', 'senha_alterada_em') && auth_column_exists($pdo, 'usuarios', 'forcar_troca_senha');
$mensagem = $_SESSION['usuarios_msg'] ?? '';
$erro = $_SESSION['usuarios_erro'] ?? '';
unset($_SESSION['usuarios_msg'], $_SESSION['usuarios_erro']);

$perfis = app_user_profiles();
$perfilPadrao = array_key_exists($_GET['perfil'] ?? '', $perfis) ? $_GET['perfil'] : '';
$setores = $pdo->query("SELECT * FROM setores WHERE ativo = 1 ORDER BY nome")->fetchAll();

function usuarios_flash(string $tipo, string $mensagem): void {
    $_SESSION['usuarios_' . $tipo] = $mensagem;
}

function usuarios_referencias(PDO $pdo, int $usuarioId): int {
    $referencias = [
        'ordens_servico' => ['solicitante_id', 'gestor_id', 'agente_id', 'aprovado_por'],
        'historico_os' => ['usuario_id'],
        'anexos_os' => ['usuario_id'],
        'auditoria_eventos' => ['usuario_id'],
        'movimentacoes_estoque' => ['usuario_id'],
        'manutencoes_preventivas' => ['agente_id', 'criado_por'],
        'tokens_reabertura_os' => ['gerado_por', 'usado_por'],
        'gestor_tokens' => ['criado_por'],
        'cronograma_agentes' => ['agente_id', 'gestor_id'],
        'cftv_relatorios' => ['operador_id'],
        'cftv_tokens_edicao' => ['gerado_por', 'usado_por'],
        'cftv_liberacoes' => ['criado_por', 'atualizado_por', 'cancelado_por'],
        'cftv_liberacao_confirmacoes' => ['confirmado_por'],
        'usuario_convites' => ['usuario_id', 'gerado_por'],
    ];

    $total = 0;

    foreach ($referencias as $tabela => $colunas) {
        if (!app_table_exists($pdo, $tabela)) {
            continue;
        }

        $where = [];
        $params = [];
        foreach ($colunas as $coluna) {
            if (app_column_exists($pdo, $tabela, $coluna)) {
                $where[] = "`$coluna` = ?";
                $params[] = $usuarioId;
            }
        }

        if (!$where) {
            continue;
        }

        $st = $pdo->prepare("SELECT COUNT(*) FROM `$tabela` WHERE " . implode(' OR ', $where));
        $st->execute($params);
        $total += (int)$st->fetchColumn();
    }

    if (app_column_exists($pdo, 'usuarios', 'criado_por')) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE criado_por = ? AND id <> ?");
        $st->execute([$usuarioId, $usuarioId]);
        $total += (int)$st->fetchColumn();
    }

    return $total;
}

$edit = null;
if (isset($_GET['edit'])) {
    $s = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);
    $edit = $s->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'salvar';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'obrigar_troca_senha') {
        if (!$senhaPolicyDisponivel) {
            usuarios_flash('erro', 'Politica de senha nao disponivel no banco.');
        } elseif ($id <= 0) {
            usuarios_flash('erro', 'Usuario invalido para obrigar troca de senha.');
        } else {
            $stUsuario = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id = ? LIMIT 1");
            $stUsuario->execute([$id]);
            $usuarioSenha = $stUsuario->fetch();

            if (!$usuarioSenha) {
                usuarios_flash('erro', 'Usuario nao encontrado.');
            } else {
                $st = $pdo->prepare("UPDATE usuarios SET senha_alterada_em = NULL, forcar_troca_senha = 1 WHERE id = ?");
                $st->execute([$id]);

                if ($id === (int)(current_user()['id'] ?? 0)) {
                    $_SESSION['user']['senha_alterada_em'] = null;
                    $_SESSION['user']['forcar_troca_senha'] = 1;
                    $_SESSION['senha_troca_obrigatoria'] = 1;
                }

                usuarios_flash('msg', 'Usuario marcado para troca obrigatoria de senha no proximo acesso.');
            }
        }

        header('Location: ' . app_path('usuarios'));
        exit;
    }

    if ($action === 'excluir_usuario') {
        $usuarioAtualId = (int)(current_user()['id'] ?? 0);

        if ($id <= 0) {
            usuarios_flash('erro', 'Usuario invalido para exclusao.');
        } elseif ($id === $usuarioAtualId) {
            usuarios_flash('erro', 'Voce nao pode excluir o proprio usuario logado.');
        } else {
            $stUsuario = $pdo->prepare("SELECT id, nome FROM usuarios WHERE id = ? LIMIT 1");
            $stUsuario->execute([$id]);
            $usuarioExcluido = $stUsuario->fetch();

            if (!$usuarioExcluido) {
                usuarios_flash('erro', 'Usuario nao encontrado.');
            } elseif (usuarios_referencias($pdo, $id) > 0) {
                $st = $pdo->prepare("UPDATE usuarios SET status = 'INATIVO' WHERE id = ?");
                $st->execute([$id]);
                usuarios_flash('msg', 'Usuario possui historico no sistema e foi inativado.');
            } else {
                $st = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $st->execute([$id]);
                usuarios_flash('msg', 'Usuario excluido com sucesso.');
            }
        }

        header('Location: ' . app_path('usuarios'));
        exit;
    }

    $senha = (string)($_POST['senha'] ?? '');
    $senhaInformada = trim($senha) !== '';
    $perfilInformado = (string)($_POST['perfil'] ?? '');
    $perfil = array_key_exists($perfilInformado, $perfis)
        ? $perfilInformado
        : (string)($edit['perfil'] ?? 'AGENTE');
    $setorId = $_POST['setor_id'] ?: null;
    $status = in_array($_POST['status'] ?? '', ['ATIVO', 'INATIVO'], true) ? $_POST['status'] : 'ATIVO';
    $senhaProvisoria = $senhaPolicyDisponivel && (isset($_POST['senha_provisoria']) || ($id > 0 && $senhaInformada));

    if ($id) {
        $set = [
            'nome = ?',
            'email = ?',
            'telefone = ?',
            'cargo = ?',
            'perfil = ?',
            'setor_id = ?',
            'status = ?',
        ];
        $params = [
            $_POST['nome'],
            $_POST['email'],
            $_POST['telefone'],
            $_POST['cargo'],
            $perfil,
            $setorId,
            $status,
        ];

        if ($senhaInformada) {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $set[] = 'senha_hash = ?';
            $params[] = $hash;
        }

        if ($senhaPolicyDisponivel) {
            if ($senhaProvisoria) {
                $set[] = 'senha_alterada_em = NULL';
                $set[] = 'forcar_troca_senha = 1';
            } elseif ($senhaInformada) {
                $set[] = 'senha_alterada_em = NOW()';
                $set[] = 'forcar_troca_senha = 0';
            } else {
                $set[] = 'senha_alterada_em = COALESCE(senha_alterada_em, NOW())';
                $set[] = 'forcar_troca_senha = 0';
            }
        }

        $params[] = $id;
        $st = $pdo->prepare("UPDATE usuarios SET " . implode(', ', $set) . " WHERE id = ?");
        $st->execute($params);
        if ($senhaInformada && $senhaPolicyDisponivel) {
            usuarios_flash('msg', 'Usuario atualizado. A nova senha ficou provisoria e exigira troca no proximo acesso.');
        } else {
            usuarios_flash('msg', 'Usuario atualizado com sucesso.');
        }
    } else {
        if (!$senhaInformada) throw new RuntimeException('Informe uma senha provisória forte.');
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $colunas = ['nome', 'email', 'telefone', 'cargo', 'perfil', 'setor_id', 'senha_hash', 'status', 'criado_por'];
        $placeholders = ['?', '?', '?', '?', '?', '?', '?', '?', '?'];
        $params = [$_POST['nome'], $_POST['email'], $_POST['telefone'], $_POST['cargo'], $perfil, $setorId, $hash, $status, current_user()['id']];

        if ($senhaPolicyDisponivel) {
            $colunas[] = 'senha_alterada_em';
            $placeholders[] = $senhaProvisoria ? 'NULL' : 'NOW()';
            $colunas[] = 'forcar_troca_senha';
            $placeholders[] = '?';
            $params[] = $senhaProvisoria ? 1 : 0;
        }

        $st = $pdo->prepare("INSERT INTO usuarios (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $placeholders) . ")");
        $st->execute($params);
        usuarios_flash('msg', 'Usuario cadastrado com sucesso.');
    }

    header('Location: ' . app_path('usuarios'));
    exit;
}

$senhaProvisoriaMarcada = true;
if ($edit && $senhaPolicyDisponivel) {
    $senhaProvisoriaMarcada = (int)($edit['forcar_troca_senha'] ?? 0) === 1 || trim((string)($edit['senha_alterada_em'] ?? '')) === '';
}

$users = $pdo->query("
    SELECT u.*, s.nome setor_nome
    FROM usuarios u
    LEFT JOIN setores s ON s.id = u.setor_id
    ORDER BY u.nome
")->fetchAll();

render_header('Usuarios');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Usuarios <small class="text-muted">somente TI</small></h1>
    <a class="btn btn-outline-primary" href="<?= e(app_path('usuario_convite')) ?>">Convidar usuario</a>
</div>

<?php if ($mensagem): ?>
    <div class="alert alert-success"><?= e($mensagem) ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-danger"><?= e($erro) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h2 class="h5"><?= $edit ? 'Editar' : 'Novo' ?> usuario</h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="salvar">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">

                <div class="col-12">
                    <label class="form-label">Nome</label>
                    <input class="form-control" name="nome" value="<?= e($edit['nome'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">E-mail</label>
                    <input class="form-control" type="email" name="email" value="<?= e($edit['email'] ?? '') ?>" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Telefone</label>
                    <input class="form-control" name="telefone" value="<?= e($edit['telefone'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Cargo</label>
                    <input class="form-control" name="cargo" value="<?= e($edit['cargo'] ?? '') ?>">
                </div>
                <div class="col-6">
                    <label class="form-label">Perfil</label>
                    <select class="form-select" name="perfil">
                        <?php foreach ($perfis as $perfil => $label): ?>
                            <option value="<?= e($perfil) ?>" <?= (($edit['perfil'] ?? $perfilPadrao) === $perfil) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option <?= ($edit['status'] ?? 'ATIVO') === 'ATIVO' ? 'selected' : '' ?>>ATIVO</option>
                        <option <?= ($edit['status'] ?? '') === 'INATIVO' ? 'selected' : '' ?>>INATIVO</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Setor</label>
                    <select class="form-select" name="setor_id">
                        <option value="">Sem setor</option>
                        <?php foreach ($setores as $s): ?>
                            <option value="<?= e($s['id']) ?>" <?= (int)($edit['setor_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Senha <?= $edit ? '(preencha para redefinir)' : 'inicial' ?></label>
                    <input class="form-control" type="password" name="senha" placeholder="<?= $edit ? 'Nova senha provisoria se preenchida' : '123456 se vazio' ?>">
                </div>
                <?php if ($senhaPolicyDisponivel): ?>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="senha_provisoria" name="senha_provisoria" <?= $senhaProvisoriaMarcada ? 'checked' : '' ?>>
                            <label class="form-check-label" for="senha_provisoria">
                                Senha provisoria / exigir troca no proximo acesso
                            </label>
                        </div>
                        <div class="form-text">Ao redefinir a senha de um usuario existente, ela sempre vira provisoria.</div>
                    </div>
                <?php endif; ?>
                <div class="col-12">
                    <button class="btn btn-primary w-100">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-3">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Setor</th><th>Status</th><th>Senha</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <?php
                                $senhaStatus = '-';
                                $senhaClasse = 'bg-secondary';
                                $trocaObrigatoria = false;
                                if ($senhaPolicyDisponivel) {
                                    $trocaObrigatoria = (int)($u['forcar_troca_senha'] ?? 0) === 1 || trim((string)($u['senha_alterada_em'] ?? '')) === '';
                                    $senhaVencida = !$trocaObrigatoria && strtotime((string)($u['senha_alterada_em'] ?? '')) <= strtotime('-90 days');
                                    if ($trocaObrigatoria) {
                                        $senhaStatus = 'Provisoria';
                                        $senhaClasse = 'bg-warning text-dark';
                                    } elseif ($senhaVencida) {
                                        $senhaStatus = 'Vencida';
                                        $senhaClasse = 'bg-danger';
                                    } else {
                                        $senhaStatus = 'Em dia';
                                        $senhaClasse = 'bg-success';
                                    }
                                }
                            ?>
                            <tr>
                                <td><?= e($u['nome']) ?></td>
                                <td><?= e($u['email']) ?></td>
                                <td><?= e(profile_label($u['perfil'])) ?></td>
                                <td><?= e($u['setor_nome'] ?: '-') ?></td>
                                <td><?= e($u['status']) ?></td>
                                <td><span class="badge <?= e($senhaClasse) ?>"><?= e($senhaStatus) ?></span></td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('usuarios', ['edit' => $u['id']])) ?>">Editar</a>
                                        <?php if ($senhaPolicyDisponivel): ?>
                                            <form method="post" onsubmit="return confirm('Obrigar este usuario a trocar a senha no proximo acesso?');">
                                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="obrigar_troca_senha">
                                                <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                                                <button class="btn btn-sm btn-outline-warning">Obrigar troca</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-warning" disabled title="Politica de senha indisponivel">Obrigar troca</button>
                                        <?php endif; ?>
                                        <?php if ((int)$u['id'] !== (int)(current_user()['id'] ?? 0)): ?>
                                            <form method="post" onsubmit="return confirm('Deseja excluir este usuario? Se houver historico, ele sera apenas inativado.');">
                                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="excluir_usuario">
                                                <input type="hidden" name="id" value="<?= e($u['id']) ?>">
                                                <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>
