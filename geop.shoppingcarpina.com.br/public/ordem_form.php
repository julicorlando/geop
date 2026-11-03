<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';
require_once __DIR__ . '/../includes/conhecimento.php';
require_once __DIR__ . '/../includes/ordens_unificadas.php';
require_once __DIR__ . '/../includes/geop_v8.php';

require_login();
check_csrf();
ensure_operational_schema($pdo);
gestao_ensure_schema($pdo);
geop_v8_boot($pdo);

$user = current_user();
$perfil = strtoupper((string)($user['perfil'] ?? ''));

if ($perfil === 'AGENTE') {
    http_response_code(403);
    render_header('Acesso negado');
    ?>
    <div class="alert alert-danger">
        <strong>Acesso negado.</strong> Agente nao pode abrir ordem de servico.
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(app_path('index')) ?>">Voltar</a>
    <?php
    render_footer();
    exit;
}

$mensagemErro = '';
$duplicidadesDetectadas = [];
$analiseInteligente = null;

$locais = $pdo->query("SELECT * FROM locais WHERE ativo=1 ORDER BY nome")->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias WHERE ativo=1 ORDER BY nome")->fetchAll();
$checklistLimpeza = ordem_checklist_catalogo($pdo);
$artigosConhecimento = conhecimento_buscar($pdo);
$agentes = $pdo->query("SELECT * FROM usuarios WHERE perfil='AGENTE' AND status='ATIVO' ORDER BY nome")->fetchAll();
$gestores = $pdo->query("SELECT * FROM usuarios WHERE perfil='GESTOR' AND status='ATIVO' ORDER BY nome")->fetchAll();
$membrosLimpeza = ordem_limpeza_membros($pdo);
$asgsLimpeza = array_values(array_filter($membrosLimpeza, 'ordem_limpeza_eh_asg'));
$supervisoresLimpeza = array_values(array_filter($membrosLimpeza, 'ordem_limpeza_eh_supervisor'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $localId = (int)($_POST['local_id'] ?? 0);
        $categoriaId = (int)($_POST['categoria_id'] ?? 0);

        if ($titulo === '' || $descricao === '' || $localId <= 0 || $categoriaId <= 0) {
            throw new RuntimeException('Preencha todos os campos obrigatorios.');
        }

        $analiseInteligente = geop_v8_ai_assist($pdo, $titulo, $descricao, $localId);
        geop_v8_record_ai_analysis($pdo, null, (int)$user['id'], 'ABERTURA_OS', $analiseInteligente, $titulo . ' ' . $descricao);
        $duplicidadesDetectadas = geop_v8_find_duplicates($pdo, $titulo, $descricao, $localId, $categoriaId, 5);
        $limiteDuplicidade = (float)geop_v8_config($pdo, 'os_duplicate_threshold', 0.55);
        $melhorDuplicidade = $duplicidadesDetectadas[0] ?? null;
        if ($melhorDuplicidade && (float)$melhorDuplicidade['similaridade'] >= $limiteDuplicidade && empty($_POST['ignorar_duplicidade'])) {
            throw new RuntimeException('Possível OS duplicada encontrada. Revise os chamados abaixo. Se for uma demanda diferente, marque “Abrir mesmo assim”.');
        }

        $tipoOrdem = ordem_tipo_categoria($pdo, $categoriaId);
        $numero = gerar_numero_ordem_categoria($pdo, $categoriaId);
        $agente = !empty($_POST['agente_id']) ? (int)$_POST['agente_id'] : null;
        $gestorId = (int)($user['id'] ?? 0);
        $abrirParaGestor = !empty($_POST['abrir_para_gestor']);

        if ($perfil === 'TI' && $abrirParaGestor) {
            if (!empty($_POST['gestor_responsavel_id'])) {
                $gestorId = (int)$_POST['gestor_responsavel_id'];
            } elseif (!empty($gestores[0]['id'])) {
                $gestorId = (int)$gestores[0]['id'];
            }
            $agente = null;
        }

        if ($gestorId <= 0) {
            $gestorId = (int)($user['id'] ?? 0);
        }

        $prioridade = $_POST['prioridade'] ?? 'MEDIA';
        $status = $agente ? 'ATRIBUIDA' : 'ABERTA';
        $slaHoras = sla_horas_por_prioridade($prioridade);
        $prazo = prazo_sla_para_banco($_POST['prazo'] ?? '', $prioridade);

        $columns = [
            'numero_os','titulo','descricao','local_id','categoria_id','prioridade','status',
            'solicitante_id','gestor_id','agente_id','prazo'
        ];
        $values = [
            $numero,
            $titulo,
            $descricao,
            $localId,
            $categoriaId,
            $prioridade,
            $status,
            (int)$user['id'],
            $gestorId,
            $agente,
            $prazo,
        ];

        if (app_column_exists($pdo, 'ordens_servico', 'tipo_ordem')) {
            $columns[] = 'tipo_ordem';
            $values[] = $tipoOrdem;
        }

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
            $values[] = 'MANUAL';
        }

        if (geop_v8_column_exists($pdo, 'ordens_servico', 'duplicidade_ignorada')) {
            $columns[] = 'duplicidade_ignorada';
            $values[] = !empty($_POST['ignorar_duplicidade']) ? 1 : 0;
        }
        if (geop_v8_column_exists($pdo, 'ordens_servico', 'duplicidade_referencia_id')) {
            $columns[] = 'duplicidade_referencia_id';
            $values[] = !empty($_POST['ignorar_duplicidade']) && !empty($melhorDuplicidade['id']) ? (int)$melhorDuplicidade['id'] : null;
        }

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $stmt = $pdo->prepare("INSERT INTO ordens_servico (" . implode(',', $columns) . ") VALUES ($placeholders)");

        $stmt->execute($values);
        $inseriu = true;

        if (!$inseriu) {
            throw $ultimoErro ?: new RuntimeException('Nao foi possivel gerar um numero unico para a OS.');
        }

        $id = (int)$pdo->lastInsertId();
        geop_v8_checklist_instantiate($pdo, $id, $categoriaId);

        if ($tipoOrdem === 'OL') {
            $equipeAsg = $_POST['equipe_asg'] ?? [];
            $principalAsg = !empty($_POST['responsavel_principal_membro_id']) ? (int)$_POST['responsavel_principal_membro_id'] : null;
            $supervisorMembro = !empty($_POST['supervisor_membro_id']) ? (int)$_POST['supervisor_membro_id'] : null;
            if (!$equipeAsg) throw new RuntimeException('Selecione pelo menos um ASG para a Ordem de Limpeza.');
            if (!$principalAsg) throw new RuntimeException('Defina o responsável principal da equipe de limpeza.');
            if (!$supervisorMembro) throw new RuntimeException('Selecione o supervisor da Ordem de Limpeza.');
            ordem_salvar_equipe_limpeza($pdo, $id, $equipeAsg, $principalAsg, $supervisorMembro);
            ordem_salvar_checklist($pdo, $id, $_POST['checklist_tarefas'] ?? [], $_POST['checklist_livre'] ?? []);
            if (!empty($_FILES['fotos_abertura'])) {
                ordem_salvar_fotos_abertura($pdo, $id, (int)$user['id'], $_FILES['fotos_abertura']);
            }
            foreach (ordem_equipe_limpeza($pdo, $id) as $membroEquipe) {
                $uid = (int)($membroEquipe['usuario_id'] ?? 0);
                if ($uid > 0 && $uid !== (int)$user['id']) {
                    gestao_notificar($pdo,$uid,'NOVA_OL','Nova OL atribuída',
                        $numero.' — '.$titulo,app_path('ordem_detalhe',['id'=>$id]),'nova-ol-'.$id.'-'.$uid);
                }
            }
        }
        $descricaoHistoricoCriacao = $tipoOrdem . ' criada. SLA: ' . $slaHoras . ' horas.';
        if ($abrirParaGestor && $perfil === 'TI' && !$agente) {
            $descricaoHistoricoCriacao .= ' Aberta pelo TI para o gestor definir o agente.';
        }

        registrar_historico($pdo, $id, (int)$user['id'], null, $status, $descricaoHistoricoCriacao);
        gestao_registrar_tempo_status($pdo,$id,'',$status,(int)$user['id']);
        auditoria_log($pdo, 'ordem_servico', $id, 'CRIAR', null, null, $descricaoHistoricoCriacao, (int)$user['id']);

        $osNotificacao = [
            'id' => $id,
            'numero_os' => $numero,
            'titulo' => $titulo,
            'status' => $status,
            'prioridade' => $prioridade,
            'prazo' => $prazo,
            'sla_vencimento' => $prazo,
        ];

        if ($agente) {
            whatsapp_queue_user($pdo, $agente, mensagem_whatsapp_os('Nova '.$tipoOrdem.' atribuida para voce', $osNotificacao), 'Nova '.$tipoOrdem);
            gestao_notificar($pdo,$agente,'OS_ATRIBUIDA','Nova '.$tipoOrdem.' atribuída',
                $numero.' — '.$titulo,app_path('ordem_detalhe',['id'=>$id]),'os-atribuida-'.$id.'-'.$agente);
        }
        if ($gestorId && $gestorId !== (int)$user['id']) {
            gestao_notificar($pdo,$gestorId,'NOVA_OS','Nova '.$tipoOrdem.' sob sua gestão',
                $numero.' — '.$titulo,app_path('ordem_detalhe',['id'=>$id]),'nova-os-'.$id.'-'.$gestorId);
        }

        redirect('ordem_detalhe?id=' . $id);
    } catch (Throwable $e) {
        $mensagemErro = $e->getMessage();
        error_log('[GEOP salvar ordem] ' . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    }
}

render_header('Nova Ordem');
?>
<h1 class="h3 mb-1" id="tituloNovaOrdem">Nova Ordem de Serviço</h1>
<p class="text-muted mb-3" id="subtituloNovaOrdem">Ao escolher a categoria Limpeza, o sistema criará automaticamente uma OL com checklist e fotos.</p>

<?php if ($mensagemErro): ?>
    <div class="alert alert-danger">
        <strong>Erro ao salvar OS:</strong> <?= e($mensagemErro) ?>
    </div>
<?php endif; ?>

<?php if ($duplicidadesDetectadas): ?>
<div class="alert alert-warning">
    <strong>Possíveis OS duplicadas</strong>
    <div class="mt-2">
        <?php foreach ($duplicidadesDetectadas as $dup): ?>
            <div class="border rounded bg-white p-2 mb-2">
                <a href="<?= e(app_path('ordem_detalhe', ['id'=>$dup['id']])) ?>" target="_blank"><strong><?= e($dup['numero_os']) ?></strong> — <?= e($dup['titulo']) ?></a>
                <div class="small text-muted">Status <?= e($dup['status']) ?> · similaridade <?= e(number_format((float)$dup['similaridade']*100,0)) ?>%</div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="card p-3">
    <form method="post" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <?php if ($duplicidadesDetectadas): ?>
        <div class="col-12">
            <div class="form-check form-switch border border-warning rounded p-3 ps-5 bg-warning-subtle">
                <input class="form-check-input" type="checkbox" name="ignorar_duplicidade" value="1" id="ignorarDuplicidade" <?= !empty($_POST['ignorar_duplicidade']) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="ignorarDuplicidade">Abrir mesmo assim — confirmei que esta é uma demanda diferente</label>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-md-8">
            <label class="form-label required">Titulo</label>
            <input class="form-control" name="titulo" value="<?= e($_POST['titulo'] ?? $_GET['titulo'] ?? '') ?>" required>
        </div>

        <div class="col-md-4">
            <label class="form-label required">Prioridade</label>
            <?php $prioridadeSelecionada = $_POST['prioridade'] ?? 'MEDIA'; ?>
            <select class="form-select" name="prioridade" required>
                <?php foreach (['BAIXA','MEDIA','ALTA','CRITICA'] as $p): ?>
                    <option value="<?= e($p) ?>" <?= $prioridadeSelecionada === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Se o prazo ficar vazio, o SLA sera calculado automaticamente.</div>
        </div>

        <div class="col-md-6">
            <label class="form-label required">Local</label>
            <select class="form-select" name="local_id" id="local_id" required>
                <option value="">Selecione</option>
                <?php foreach ($locais as $l): ?>
                    <option value="<?= e($l['id']) ?>" <?= (string)($_POST['local_id'] ?? '') === (string)$l['id'] ? 'selected' : '' ?>><?= e($l['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label required">Categoria</label>
            <select class="form-select" name="categoria_id" id="categoria_id" required>
                <option value="">Selecione</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= e($c['id']) ?>" data-limpeza="<?= mb_strtolower(trim((string)$c['nome']),'UTF-8') === 'limpeza' ? '1' : '0' ?>" <?= (string)($_POST['categoria_id'] ?? $_GET['categoria_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <section class="border rounded p-3 bg-primary-subtle" id="assistente-v8">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <strong>Assistente V8</strong><span class="badge text-bg-primary">Sugestões</span>
                </div>
                <div class="small text-muted mt-1">Analisa título/descrição e sugere prioridade, categoria, materiais e duplicidades. Nada é aplicado sem sua confirmação.</div>
                <div id="assistente-v8-resultado" class="mt-2 small">Preencha o título e a descrição para receber sugestões.</div>
            </section>
        </div>

        <div class="col-12">
            <section class="border rounded p-3 bg-light" id="sugestoes-conhecimento">
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <strong>Soluções sugeridas</strong>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('conhecimento')) ?>" target="_blank">Abrir base completa</a>
                </div>
                <div class="small text-muted mt-1" id="sugestoes-conhecimento-vazio">Selecione uma categoria ou descreva o problema para consultar soluções existentes.</div>
                <div class="row g-2 mt-1" id="sugestoes-conhecimento-lista"></div>
            </section>
        </div>

        <?php if ($perfil === 'TI'): ?>
            <div class="col-12">
                <div class="form-check form-switch p-3 rounded border bg-light">
                    <input class="form-check-input" type="checkbox" role="switch" id="abrir_para_gestor" name="abrir_para_gestor" value="1" <?= !empty($_POST['abrir_para_gestor']) ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="abrir_para_gestor">Abrir OS para o gestor definir o agente</label>
                    <div class="form-text">Quando marcado, a OS fica ABERTA, sem agente, e o gestor recebe alerta lateral ate atribuir o responsavel.</div>
                </div>
            </div>

            <div class="col-md-6" id="campo_gestor_responsavel">
                <label class="form-label">Gestor responsavel</label>
                <select class="form-select" name="gestor_responsavel_id" id="gestor_responsavel_id">
                    <option value="">Selecione o gestor</option>
                    <?php foreach ($gestores as $g): ?>
                        <option value="<?= e($g['id']) ?>" <?= (string)($_POST['gestor_responsavel_id'] ?? '') === (string)$g['id'] ? 'selected' : '' ?>><?= e($g['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <?php if (in_array($perfil, ['TI','GESTOR','COORDENADOR_OPERACOES_OBRAS'], true)): ?>
            <div class="col-md-6" id="blocoAgenteOs">
                <label class="form-label">Agente responsavel</label>
                <select class="form-select" name="agente_id" id="agente_id_select">
                    <option value="">Definir depois</option>
                    <?php foreach ($agentes as $a): ?>
                        <option value="<?= e($a['id']) ?>" <?= (string)($_POST['agente_id'] ?? '') === (string)$a['id'] ? 'selected' : '' ?>><?= e($a['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                <small id="agente_recomendacao" class="text-muted">A distribuição considera especialidade, disponibilidade e carga atual.</small>
            </div>
        <?php else: ?>
            <input type="hidden" name="agente_id" value="">
        <?php endif; ?>

        <div class="col-md-6">
            <label class="form-label">Prazo / SLA</label>
            <input class="form-control" type="datetime-local" name="prazo" value="<?= e($_POST['prazo'] ?? '') ?>">
        </div>


        <div class="col-12 d-none" id="blocoOlLimpeza">
            <div class="card border-success-subtle p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div>
                        <h2 class="h5 mb-0">Checklist da Ordem de Limpeza</h2>
                        <small class="text-muted">Selecione o que deverá ser executado nesta OL.</small>
                    </div>
                    <span class="badge text-bg-success">A numeração será OL</span>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label required">Equipe ASG</label>
                        <div class="row g-2">
                            <?php $asgSelecionados=array_map('intval',$_POST['equipe_asg']??[]); foreach ($asgsLimpeza as $m): ?>
                                <div class="col-md-6 col-xl-4">
                                    <label class="form-check border rounded p-2 h-100">
                                        <input class="form-check-input asg-check" type="checkbox" name="equipe_asg[]" value="<?= (int)$m['id'] ?>" <?= in_array((int)$m['id'],$asgSelecionados,true)?'checked':'' ?>>
                                        <span class="form-check-label ms-1"><strong><?= e($m['nome_colaborador']) ?></strong><small class="d-block text-muted"><?= e($m['cargo']) ?><?= !empty($m['turno'])?' · '.e($m['turno']):'' ?></small></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (!$asgsLimpeza): ?><div class="alert alert-warning py-2 mt-2 mb-0">Nenhum ASG ativo foi encontrado no Organograma da Limpeza.</div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Responsável principal</label>
                        <select class="form-select" name="responsavel_principal_membro_id" id="responsavel_principal_membro_id">
                            <option value="">Selecione entre os ASGs marcados</option>
                            <?php foreach ($asgsLimpeza as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= (string)($_POST['responsavel_principal_membro_id']??'')===(string)$m['id']?'selected':'' ?>><?= e($m['nome_colaborador']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Supervisor da limpeza</label>
                        <select class="form-select" name="supervisor_membro_id" id="supervisor_membro_id">
                            <option value="">Selecione o supervisor</option>
                            <?php foreach ($supervisoresLimpeza as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= (string)($_POST['supervisor_membro_id']??'')===(string)$m['id']?'selected':'' ?>><?= e($m['nome_colaborador']) ?> — <?= e($m['cargo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if ($checklistLimpeza): ?>
                    <div class="row g-2">
                    <?php foreach ($checklistLimpeza as $item): ?>
                        <div class="col-md-6">
                            <label class="form-check border rounded p-2 h-100">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="checklist_tarefas[]" value="<?= (int)$item['id'] ?>" <?= in_array((int)$item['id'], array_map('intval', $_POST['checklist_tarefas'] ?? []), true) ? 'checked' : '' ?>>
                                <span><strong><?= e($item['descricao']) ?></strong><?php if (!empty($item['grupo_nome'])): ?><br><small class="text-muted"><?= e($item['grupo_nome']) ?></small><?php endif; ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning py-2">Nenhuma tarefa do plano de limpeza foi encontrada. Você pode adicionar itens personalizados abaixo.</div>
                <?php endif; ?>
                <label class="form-label mt-3">Itens adicionais</label>
                <input class="form-control mb-2" name="checklist_livre[]" placeholder="Ex.: Higienizar corrimãos">
                <input class="form-control mb-2" name="checklist_livre[]" placeholder="Outro item opcional">
                <label class="form-label mt-2">Fotos na abertura da OL</label>
                <input class="form-control" type="file" name="fotos_abertura[]" accept="image/jpeg,image/png,image/webp" multiple>
                <div class="form-text">Até 10 fotos. Elas serão registradas como fotos de antes na própria ordem.</div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label required">Descricao</label>
            <textarea class="form-control" name="descricao" rows="5" required><?= e($_POST['descricao'] ?? $_GET['descricao'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
            <button class="btn btn-primary" id="btnSalvarOrdem">Salvar OS</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_path('ordens')) ?>">Cancelar</a>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chk = document.getElementById('abrir_para_gestor');
    const agente = document.getElementById('agente_id_select');
    const gestor = document.getElementById('gestor_responsavel_id');
    if (!chk || !agente) return;

    const sync = function () {
        if (chk.checked) {
            agente.value = '';
            agente.setAttribute('disabled', 'disabled');
            if (gestor) gestor.setAttribute('required', 'required');
        } else {
            agente.removeAttribute('disabled');
            if (gestor) gestor.removeAttribute('required');
        }
    };

    chk.addEventListener('change', sync);
    sync();
});

document.addEventListener('DOMContentLoaded', function () {
    const artigos = <?= json_encode(array_map(static function ($a) {
        return [
            'id' => (int)$a['id'],
            'categoria_id' => (int)($a['categoria_id'] ?? 0),
            'titulo' => $a['titulo'],
            'problema' => $a['problema'] ?? '',
            'solucao' => $a['solucao'] ?? $a['conteudo'] ?? '',
            'palavras' => $a['palavras_chave'] ?? '',
            'url' => app_path('conhecimento', ['q' => $a['titulo']]),
        ];
    }, $artigosConhecimento), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const categoria = document.getElementById('categoria_id');
    const titulo = document.querySelector('[name="titulo"]');
    const descricao = document.querySelector('[name="descricao"]');
    const lista = document.getElementById('sugestoes-conhecimento-lista');
    const vazio = document.getElementById('sugestoes-conhecimento-vazio');
    if (!categoria || !lista) return;
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const atualizar = function () {
        const categoriaId = parseInt(categoria.value || '0', 10);
        const texto = ((titulo?.value || '') + ' ' + (descricao?.value || '')).toLocaleLowerCase('pt-BR');
        const termos = texto.split(/[^\p{L}\p{N}]+/u).filter(t => t.length >= 4);
        const encontrados = artigos.map(a => {
            const base = (a.titulo + ' ' + a.problema + ' ' + a.palavras).toLocaleLowerCase('pt-BR');
            let score = a.categoria_id === categoriaId ? 10 : (a.categoria_id === 0 ? 1 : -20);
            termos.forEach(t => { if (base.includes(t)) score += 2; });
            return {...a, score};
        }).filter(a => a.score > 0).sort((a,b) => b.score-a.score).slice(0,3);
        vazio.style.display = encontrados.length ? 'none' : 'block';
        lista.innerHTML = encontrados.map(a => '<div class="col-md-4"><div class="card h-100 p-2"><strong>'+escapeHtml(a.titulo)+'</strong><small class="mt-1">'+escapeHtml(a.problema).slice(0,140)+'</small><a class="btn btn-sm btn-outline-success mt-2" target="_blank" href="'+escapeHtml(a.url)+'">Ver solução</a></div></div>').join('');
    };
    categoria.addEventListener('change', atualizar);
    titulo?.addEventListener('input', atualizar);
    descricao?.addEventListener('input', atualizar);
    atualizar();
});

document.addEventListener('DOMContentLoaded', function () {
    const categoria = document.getElementById('categoria_id');
    const bloco = document.getElementById('blocoOlLimpeza');
    const titulo = document.getElementById('tituloNovaOrdem');
    const subtitulo = document.getElementById('subtituloNovaOrdem');
    const botao = document.getElementById('btnSalvarOrdem');
    if (!categoria || !bloco) return;
    const syncTipo = () => {
        const opt = categoria.options[categoria.selectedIndex];
        const limpeza = opt && opt.dataset.limpeza === '1';
        bloco.classList.toggle('d-none', !limpeza);
        const blocoAgente = document.getElementById('blocoAgenteOs');
        const agenteSelect = document.getElementById('agente_id_select');
        if (blocoAgente) blocoAgente.classList.toggle('d-none', limpeza);
        if (agenteSelect) { if (limpeza) agenteSelect.value=''; agenteSelect.disabled = limpeza; }
        ['responsavel_principal_membro_id','supervisor_membro_id'].forEach(id => { const el=document.getElementById(id); if(el) el.required=limpeza; });
        document.querySelectorAll('.asg-check').forEach(el => { el.disabled=!limpeza; });
        if (titulo) titulo.textContent = limpeza ? 'Nova Ordem de Limpeza' : 'Nova Ordem de Serviço';
        if (subtitulo) subtitulo.textContent = limpeza ? 'Categoria Limpeza identificada: a ordem receberá prefixo OL e checklist.' : 'Selecione a categoria e informe os dados da ordem.';
        if (botao) botao.textContent = limpeza ? 'Salvar OL' : 'Salvar OS';
    };
    categoria.addEventListener('change', syncTipo);
    syncTipo();
});

document.addEventListener('DOMContentLoaded', function () {
    const categoria = document.getElementById('categoria_id');
    const local = document.getElementById('local_id');
    const agente = document.getElementById('agente_id_select');
    const aviso = document.getElementById('agente_recomendacao');
    if (!categoria || !local || !agente) return;
    const recomendar = async () => {
        if (!categoria.value || !local.value) return;
        try {
            const url = <?= json_encode(app_path('gestao_api')) ?> + '?action=recomendar_agentes&categoria_id=' + encodeURIComponent(categoria.value) + '&local_id=' + encodeURIComponent(local.value);
            const data = await fetch(url, {headers:{'Accept':'application/json'}}).then(r => r.json());
            const ids = new Map((data.agentes || []).map((a, i) => [String(a.id), i]));
            [...agente.options].sort((a,b) => (ids.get(a.value) ?? 999) - (ids.get(b.value) ?? 999)).forEach(o => agente.appendChild(o));
            const melhor = data.agentes?.[0];
            if (melhor && aviso) aviso.textContent = 'Recomendado: ' + melhor.nome + ' (' + melhor.carga + '/' + melhor.capacidade_maxima + ' OS).';
        } catch (_) {}
    };
    categoria.addEventListener('change', recomendar);
    local.addEventListener('change', recomendar);
    recomendar();
});
</script>
<script>
(function(){
 const titulo=document.querySelector('[name="titulo"]');
 const descricao=document.querySelector('[name="descricao"]');
 const local=document.getElementById('local_id');
 const categoria=document.getElementById('categoria_id');
 const prioridade=document.querySelector('[name="prioridade"]');
 const box=document.getElementById('assistente-v8-resultado');
 if(!titulo||!descricao||!box)return;
 let timer=null;
 async function analisar(){
   const texto=(titulo.value+' '+descricao.value).trim();
   if(texto.length<8){box.textContent='Preencha o título e a descrição para receber sugestões.';return;}
   box.innerHTML='<span class="text-muted">Analisando…</span>';
   const q=new URLSearchParams({titulo:titulo.value,descricao:descricao.value,local_id:local?.value||'0',categoria_id:categoria?.value||'0'});
   try{
     const res=await fetch('<?= e(app_path('api_os_inteligencia')) ?>?'+q.toString(),{headers:{'Accept':'application/json'}});
     const data=await res.json(); if(!data.ok||!data.resultado){box.textContent='Sem sugestões no momento.';return;}
     const r=data.resultado; let html='';
     html+=`<div class="mb-2"><strong>Prioridade sugerida:</strong> <button type="button" class="btn btn-sm btn-outline-primary ms-1" id="aplicarPrioridadeV8">${r.prioridade_sugerida}</button></div>`;
     if(r.categorias_sugeridas?.length) html+=`<div class="mb-2"><strong>Categorias:</strong> ${r.categorias_sugeridas.map(c=>c.nome).join(', ')}</div>`;
     if(r.materiais_sugeridos?.length) html+=`<div class="mb-2"><strong>Materiais possivelmente relacionados:</strong> ${r.materiais_sugeridos.map(m=>m.nome).join(', ')}</div>`;
     if(r.duplicidades?.length) html+=`<div class="text-warning-emphasis"><strong>Possíveis duplicidades:</strong> ${r.duplicidades.map(d=>`${d.numero_os} (${Math.round(d.similaridade*100)}%)`).join(', ')}</div>`;
     box.innerHTML=html||'Nenhuma sugestão relevante.';
     document.getElementById('aplicarPrioridadeV8')?.addEventListener('click',()=>{prioridade.value=r.prioridade_sugerida;});
   }catch(e){box.textContent='Não foi possível consultar as sugestões agora.';}
 }
 function schedule(){clearTimeout(timer);timer=setTimeout(analisar,650);}
 titulo.addEventListener('input',schedule);descricao.addEventListener('input',schedule);local?.addEventListener('change',schedule);categoria?.addEventListener('change',schedule);
})();
</script>

<?php render_footer(); ?>
