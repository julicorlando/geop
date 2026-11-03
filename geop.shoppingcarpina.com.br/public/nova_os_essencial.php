<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';

require_role(['TI', 'GESTOR', 'COORDENADOR_OPERACOES_OBRAS']);
check_csrf();
ensure_operational_schema($pdo);

$user = current_user();

$locais = $pdo->query("SELECT * FROM locais WHERE ativo = 1 ORDER BY nome")->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome")->fetchAll();
$agentes = $pdo->query("SELECT * FROM usuarios WHERE perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS') AND status = 'ATIVO' ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero = gerar_numero_os($pdo);
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $local_id = (int)($_POST['local_id'] ?? 0);
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $agente = !empty($_POST['agente_id']) ? (int)$_POST['agente_id'] : null;
    $prioridade = 'CRITICA';
    $slaHoras = sla_horas_por_prioridade($prioridade);
    $prazo = prazo_sla_para_banco($_POST['prazo'] ?? '', $prioridade);

    $valorPrevisto = trim($_POST['valor_previsto_orcamento'] ?? '');
    $valorPrevisto = $valorPrevisto !== '' ? str_replace(',', '.', $valorPrevisto) : null;
    $semestrePrevisto = !empty($_POST['semestre_previsto']) ? (int)$_POST['semestre_previsto'] : null;
    $anoPrevisto = !empty($_POST['ano_previsto']) ? (int)$_POST['ano_previsto'] : null;
    $status = $agente ? 'ATRIBUIDA' : 'ABERTA';

    $tituloFinal = '[SERVICO ESSENCIAL] ' . $titulo;
    $descricaoFinal = "SERVICO ESSENCIAL\n\n";
    $descricaoFinal .= "Este servico foi aberto como essencial e deve ter prioridade maxima no atendimento.\n\n";
    $descricaoFinal .= "Valor previsto do orcamento: R$ " . ($valorPrevisto !== null ? number_format((float)$valorPrevisto, 2, ',', '.') : 'Nao informado') . "\n";
    $descricaoFinal .= "Semestre/Ano previsto: " . ($semestrePrevisto ? $semestrePrevisto . " semestre" : 'Nao informado') . " / " . ($anoPrevisto ?: 'Nao informado') . "\n\n";
    $descricaoFinal .= "Descricao do servico:\n" . $descricao;

    $columns = [
        'numero_os','titulo','descricao','local_id','categoria_id','prioridade','status',
        'solicitante_id','gestor_id','agente_id','prazo','valor_previsto_orcamento',
        'semestre_previsto','ano_previsto'
    ];
    $values = [
        $numero,$tituloFinal,$descricaoFinal,$local_id,$categoria_id,$prioridade,$status,
        $user['id'],$user['id'],$agente,$prazo,$valorPrevisto,$semestrePrevisto,$anoPrevisto
    ];

    if (app_column_exists($pdo, 'ordens_servico', 'sla_horas')) {
        $columns[] = 'sla_horas';
        $values[] = $slaHoras;
    }

    if (app_column_exists($pdo, 'ordens_servico', 'sla_vencimento')) {
        $columns[] = 'sla_vencimento';
        $values[] = $prazo;
    }

    if (app_column_exists($pdo, 'ordens_servico', 'origem')) {
        $columns[] = 'origem';
        $values[] = 'ESSENCIAL';
    }

    $stmt = $pdo->prepare("INSERT INTO ordens_servico (" . implode(',', $columns) . ") VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")");
    $stmt->execute($values);
    $id = (int)$pdo->lastInsertId();

    registrar_historico($pdo, $id, $user['id'], null, $status, 'OS essencial criada. SLA: ' . $slaHoras . ' horas.');
    auditoria_log($pdo, 'ordem_servico', $id, 'CRIAR_ESSENCIAL', null, null, 'OS essencial criada com status ' . $status, $user['id']);

    $osNotificacao = [
        'id' => $id,
        'numero_os' => $numero,
        'titulo' => $tituloFinal,
        'status' => $status,
        'prioridade' => $prioridade,
        'prazo' => $prazo,
        'sla_vencimento' => $prazo,
    ];

    if ($agente) {
        whatsapp_queue_user($pdo, $agente, mensagem_whatsapp_os('Servico essencial atribuido para voce', $osNotificacao), 'Servico essencial');
    }

    redirect('ordem_detalhe?id=' . $id);
}

render_header('Novo Servico Essencial');
?>

<h1 class="h3 mb-3">Novo Servico Essencial</h1>

<div class="alert alert-danger">
    Esta abertura sera registrada como <strong>Servico Essencial</strong>, com prioridade <strong>CRITICA</strong>.
</div>

<div class="card p-3">
    <form method="post" class="row g-3">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="col-md-8">
            <label class="form-label required">Titulo do servico essencial</label>
            <input class="form-control" name="titulo" placeholder="Ex: Falha eletrica em area critica" required>
        </div>

        <div class="col-md-4">
            <label class="form-label">Prioridade</label>
            <input class="form-control" value="CRITICA" disabled>
            <div class="form-text">SLA padrao: <?= e(sla_horas_por_prioridade('CRITICA')) ?> horas.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label required">Local</label>
            <select class="form-select" name="local_id" required>
                <option value="">Selecione o local</option>
                <?php foreach ($locais as $l): ?>
                    <option value="<?= e($l['id']) ?>"><?= e($l['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label required">Categoria</label>
            <select class="form-select" name="categoria_id" required>
                <option value="">Selecione a categoria</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= e($c['id']) ?>"><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Agente responsavel</label>
            <select class="form-select" name="agente_id">
                <option value="">Definir depois</option>
                <?php foreach ($agentes as $a): ?>
                    <option value="<?= e($a['id']) ?>"><?= e($a['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Prazo limite</label>
            <input class="form-control" type="datetime-local" name="prazo">
        </div>

        <div class="col-md-4">
            <label class="form-label">Valor previsto do orcamento</label>
            <input class="form-control" type="text" name="valor_previsto_orcamento" placeholder="Ex: 1500.00">
        </div>

        <div class="col-md-4">
            <label class="form-label">Semestre previsto</label>
            <select class="form-select" name="semestre_previsto">
                <option value="">Selecione</option>
                <option value="1">1 semestre</option>
                <option value="2">2 semestre</option>
            </select>
        </div>

        <div class="col-md-4">
            <label class="form-label">Ano previsto</label>
            <input class="form-control" type="number" name="ano_previsto" min="2024" max="2100" value="<?= date('Y') ?>">
        </div>

        <div class="col-12">
            <label class="form-label required">Descricao do problema essencial</label>
            <textarea class="form-control" name="descricao" rows="6" placeholder="Descreva o servico essencial, impacto, urgencia e local exato." required></textarea>
        </div>

        <div class="col-12">
            <button class="btn btn-danger">Abrir Servico Essencial</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_path('ordens')) ?>">Cancelar</a>
        </div>
    </form>
</div>

<?php render_footer(); ?>
