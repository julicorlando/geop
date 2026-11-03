<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';

require_role(['TI', 'GESTOR']);
check_csrf();
ensure_operational_schema($pdo);

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'salvar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $unidade = trim($_POST['unidade'] ?? '');
        $quantidade = (float)str_replace(',', '.', $_POST['quantidade_atual'] ?? '0');
        $minimo = (float)str_replace(',', '.', $_POST['estoque_minimo'] ?? '0');
        $custo = (float)str_replace(',', '.', $_POST['custo_unitario'] ?? '0');

        if ($nome !== '') {
            if ($id > 0) {
                $antes = $pdo->prepare("SELECT * FROM estoque_materiais WHERE id = ?");
                $antes->execute([$id]);
                $materialAntes = $antes->fetch() ?: [];

                $st = $pdo->prepare("
                    UPDATE estoque_materiais
                    SET nome = ?, unidade = ?, quantidade_atual = ?, estoque_minimo = ?, custo_unitario = ?, ativo = ?, atualizado_em = NOW()
                    WHERE id = ?
                ");
                $st->execute([$nome, $unidade, $quantidade, $minimo, $custo, isset($_POST['ativo']) ? 1 : 0, $id]);

                auditoria_campos($pdo, 'estoque_materiais', $id, 'EDITAR_ESTOQUE', $materialAntes, [
                    'nome' => $nome,
                    'unidade' => $unidade,
                    'quantidade_atual' => $quantidade,
                    'estoque_minimo' => $minimo,
                    'custo_unitario' => $custo,
                    'ativo' => isset($_POST['ativo']) ? 1 : 0,
                ], ['nome', 'unidade', 'quantidade_atual', 'estoque_minimo', 'custo_unitario', 'ativo'], $user['id']);
            } else {
                $st = $pdo->prepare("
                    INSERT INTO estoque_materiais (nome, unidade, quantidade_atual, estoque_minimo, custo_unitario, ativo)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $st->execute([$nome, $unidade, $quantidade, $minimo, $custo]);
                $id = (int)$pdo->lastInsertId();
                auditoria_log($pdo, 'estoque_materiais', $id, 'CRIAR_ESTOQUE', null, null, $nome, $user['id']);
            }
        }

        redirect('estoque');
    }

    if ($action === 'movimentar') {
        $id = (int)($_POST['material_id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'ENTRADA';
        $quantidade = (float)str_replace(',', '.', $_POST['quantidade'] ?? '0');
        $obs = trim($_POST['observacao'] ?? '');

        if ($id > 0 && $quantidade > 0 && in_array($tipo, ['ENTRADA', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO'], true)) {
            $sinal = $tipo === 'AJUSTE_NEGATIVO' ? -1 : 1;
            $pdo->prepare("UPDATE estoque_materiais SET quantidade_atual = quantidade_atual + ?, atualizado_em = NOW() WHERE id = ?")
                ->execute([$quantidade * $sinal, $id]);

            $st = $pdo->prepare("
                INSERT INTO movimentacoes_estoque (material_id, usuario_id, tipo, quantidade, observacao)
                VALUES (?, ?, ?, ?, ?)
            ");
            $st->execute([$id, $user['id'], $tipo, $quantidade, $obs]);

            auditoria_log($pdo, 'estoque_materiais', $id, 'MOVIMENTAR_ESTOQUE', 'quantidade_atual', null, ($sinal * $quantidade), $user['id']);
        }

        redirect('estoque');
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare("SELECT * FROM estoque_materiais WHERE id = ?");
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch();
}

$materiais = $pdo->query("SELECT * FROM estoque_materiais ORDER BY ativo DESC, nome")->fetchAll();
$movimentos = $pdo->query("
    SELECT m.*, e.nome AS material_nome, u.nome AS usuario_nome
    FROM movimentacoes_estoque m
    JOIN estoque_materiais e ON e.id = m.material_id
    LEFT JOIN usuarios u ON u.id = m.usuario_id
    ORDER BY m.criado_em DESC
    LIMIT 30
")->fetchAll();

render_header('Estoque');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Estoque e materiais</h1>
    <a class="btn btn-outline-secondary" href="<?= e(app_path('relatorios')) ?>">Relatorios</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card p-3">
            <h2 class="h5"><?= $edit ? 'Editar material' : 'Novo material' ?></h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="salvar">
                <input type="hidden" name="id" value="<?= e($edit['id'] ?? '') ?>">

                <div class="col-12">
                    <label class="form-label">Nome</label>
                    <input class="form-control" name="nome" value="<?= e($edit['nome'] ?? '') ?>" required>
                </div>

                <div class="col-4">
                    <label class="form-label">Unidade</label>
                    <input class="form-control" name="unidade" value="<?= e($edit['unidade'] ?? '') ?>" placeholder="un, m, kg">
                </div>

                <div class="col-8">
                    <label class="form-label">Quantidade atual</label>
                    <input class="form-control" name="quantidade_atual" type="number" step="0.01" value="<?= e($edit['quantidade_atual'] ?? '0') ?>">
                </div>

                <div class="col-6">
                    <label class="form-label">Estoque minimo</label>
                    <input class="form-control" name="estoque_minimo" type="number" step="0.01" value="<?= e($edit['estoque_minimo'] ?? '0') ?>">
                </div>

                <div class="col-6">
                    <label class="form-label">Custo unitario</label>
                    <input class="form-control" name="custo_unitario" type="number" step="0.01" value="<?= e($edit['custo_unitario'] ?? '0') ?>">
                </div>

                <?php if ($edit): ?>
                    <div class="col-12 form-check ms-2">
                        <input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?= !empty($edit['ativo']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="ativo">Ativo</label>
                    </div>
                <?php endif; ?>

                <div class="col-12">
                    <button class="btn btn-primary w-100">Salvar</button>
                </div>
            </form>
        </div>

        <div class="card p-3 mt-3">
            <h2 class="h5">Movimentar estoque</h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="movimentar">

                <div class="col-12">
                    <select class="form-select" name="material_id" required>
                        <option value="">Material</option>
                        <?php foreach ($materiais as $m): ?>
                            <option value="<?= e($m['id']) ?>"><?= e($m['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6">
                    <select class="form-select" name="tipo">
                        <option value="ENTRADA">Entrada</option>
                        <option value="AJUSTE_POSITIVO">Ajuste +</option>
                        <option value="AJUSTE_NEGATIVO">Ajuste -</option>
                    </select>
                </div>

                <div class="col-6">
                    <input class="form-control" name="quantidade" type="number" step="0.01" placeholder="Qtd" required>
                </div>

                <div class="col-12">
                    <input class="form-control" name="observacao" placeholder="Observacao">
                </div>

                <div class="col-12">
                    <button class="btn btn-dark w-100">Registrar movimento</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-3 mb-3">
            <h2 class="h5">Materiais</h2>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Atual</th>
                            <th>Minimo</th>
                            <th>Custo</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiais as $m): ?>
                            <?php $baixo = (float)$m['quantidade_atual'] <= (float)$m['estoque_minimo']; ?>
                            <tr>
                                <td><?= e($m['nome']) ?></td>
                                <td><?= e($m['quantidade_atual']) ?> <?= e($m['unidade']) ?></td>
                                <td><?= e($m['estoque_minimo']) ?></td>
                                <td>R$ <?= e(number_format((float)$m['custo_unitario'], 2, ',', '.')) ?></td>
                                <td>
                                    <span class="badge text-bg-<?= $baixo ? 'danger' : 'success' ?>">
                                        <?= $baixo ? 'Baixo' : 'Ok' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('estoque', ['edit' => $m['id']])) ?>">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-3">
            <h2 class="h5">Ultimos movimentos</h2>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Material</th>
                            <th>Tipo</th>
                            <th>Qtd</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimentos as $mov): ?>
                            <tr>
                                <td><?= e($mov['criado_em']) ?></td>
                                <td><?= e($mov['material_nome']) ?></td>
                                <td><?= e($mov['tipo']) ?></td>
                                <td><?= e($mov['quantidade']) ?></td>
                                <td><?= e($mov['usuario_nome'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>
