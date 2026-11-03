<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/ambientes_qr.php';

limpeza_require_access();
check_csrf();
ambiente_require_schema($pdo);
limpeza_require_schema($pdo);
if (!usuario_pode('ambientes.visualizar')) {
    http_response_code(403);
    die('Acesso negado.');
}

$user = current_user();
$canManage = usuario_pode('ambientes.gerenciar');
$canQr = usuario_pode('ambientes.gerar_qr');
$success = $_SESSION['ambiente_success'] ?? '';
$error = $_SESSION['ambiente_error'] ?? '';
unset($_SESSION['ambiente_success'], $_SESSION['ambiente_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'save_action') {
            exigir_permissao('ambientes.gerenciar');
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim((string)($_POST['nome'] ?? ''));
            $descricao = trim((string)($_POST['descricao'] ?? ''));
            $categoria = strtoupper(trim((string)($_POST['categoria'] ?? 'LIMPEZA')));
            if ($nome === '') throw new RuntimeException('Informe o nome da ação.');
            if ($id > 0) {
                $pdo->prepare("UPDATE ambiente_acoes SET nome=?,descricao=?,categoria=?,exige_foto=?,exige_observacao=?,ativo=? WHERE id=?")
                    ->execute([$nome,$descricao,$categoria,(int)!empty($_POST['exige_foto']),(int)!empty($_POST['exige_observacao']),(int)!empty($_POST['ativo']),$id]);
                auditar($pdo,'ATUALIZAR_ACAO_AMBIENTE','ambiente_acoes',$id,[],['nome'=>$nome]);
            } else {
                $pdo->prepare("INSERT INTO ambiente_acoes(nome,descricao,categoria,exige_foto,exige_observacao,ativo,criado_por) VALUES(?,?,?,?,?,?,?)")
                    ->execute([$nome,$descricao,$categoria,(int)!empty($_POST['exige_foto']),(int)!empty($_POST['exige_observacao']),1,(int)$user['id']]);
                $id = (int)$pdo->lastInsertId();
                auditar($pdo,'CRIAR_ACAO_AMBIENTE','ambiente_acoes',$id,[],['nome'=>$nome]);
            }
            $_SESSION['ambiente_success'] = 'Ação salva com sucesso.';
        } elseif ($action === 'save_environment') {
            exigir_permissao('ambientes.gerenciar');
            $id = (int)($_POST['id'] ?? 0);
            $localId = (int)($_POST['local_id'] ?? 0);
            $nome = trim((string)($_POST['nome_publico'] ?? ''));
            $descricao = trim((string)($_POST['descricao_publica'] ?? ''));
            $frequency = max(5, min(10080, (int)($_POST['frequencia_minutos'] ?? 60)));
            $historyLimit = max(1, min(50, (int)($_POST['historico_limite'] ?? 10)));
            $selectedActions = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['acoes'] ?? [])))));
            $planGroupId = (int)($_POST['plano_grupo_id'] ?? 0) ?: null;
            if ($localId <= 0 || $nome === '') throw new RuntimeException('Selecione o local e informe o nome público.');
            if (!$selectedActions && $planGroupId === null) throw new RuntimeException('Selecione uma área do plano de limpeza ou pelo menos uma ação avulsa.');

            $pdo->beginTransaction();
            if ($id > 0) {
                $pdo->prepare("UPDATE ambientes_qr SET local_id=?,plano_grupo_id=?,nome_publico=?,descricao_publica=?,ativo=?,exibir_historico=?,exibir_fotos=?,historico_limite=?,frequencia_minutos=?,exige_foto_asg=?,exige_foto_supervisor=? WHERE id=?")
                    ->execute([$localId,$planGroupId,$nome,$descricao,(int)!empty($_POST['ativo']),(int)!empty($_POST['exibir_historico']),(int)!empty($_POST['exibir_fotos']),$historyLimit,$frequency,(int)!empty($_POST['exige_foto_asg']),(int)!empty($_POST['exige_foto_supervisor']),$id]);
            } else {
                $pdo->prepare("INSERT INTO ambientes_qr(local_id,plano_grupo_id,nome_publico,descricao_publica,token_publico,ativo,exibir_historico,exibir_fotos,historico_limite,frequencia_minutos,exige_foto_asg,exige_foto_supervisor,gerado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$localId,$planGroupId,$nome,$descricao,ambiente_generate_token(),1,(int)!empty($_POST['exibir_historico']),(int)!empty($_POST['exibir_fotos']),$historyLimit,$frequency,(int)!empty($_POST['exige_foto_asg']),(int)!empty($_POST['exige_foto_supervisor']),(int)$user['id']]);
                $id = (int)$pdo->lastInsertId();
            }
            $pdo->prepare("UPDATE ambiente_acao_locais SET ativo=0 WHERE ambiente_id=?")->execute([$id]);
            $link = $pdo->prepare("INSERT INTO ambiente_acao_locais(ambiente_id,acao_id,obrigatoria,ordem_exibicao,ativo) VALUES(?,?,0,?,1) ON DUPLICATE KEY UPDATE ativo=1,ordem_exibicao=VALUES(ordem_exibicao)");
            foreach ($selectedActions as $order => $actionId) $link->execute([$id,$actionId,$order+1]);
            $pdo->commit();
            auditar($pdo,'SALVAR_AMBIENTE_QR','ambientes_qr',$id,[],['nome'=>$nome,'local_id'=>$localId,'plano_grupo_id'=>$planGroupId]);
            $_SESSION['ambiente_success'] = 'Ambiente e ações configurados com sucesso.';
        } elseif ($action === 'rotate_token') {
            exigir_permissao('ambientes.gerar_qr');
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) throw new RuntimeException('Ambiente inválido.');
            $pdo->prepare("UPDATE ambientes_qr SET token_publico=?,atualizado_em=NOW() WHERE id=?")->execute([ambiente_generate_token(),$id]);
            auditar($pdo,'ROTACIONAR_QR_AMBIENTE','ambientes_qr',$id);
            $_SESSION['ambiente_success'] = 'Novo QR Code gerado. O código anterior deixou de funcionar.';
        } elseif ($action === 'toggle_environment') {
            exigir_permissao('ambientes.gerenciar');
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE ambientes_qr SET ativo=IF(ativo=1,0,1) WHERE id=?")->execute([$id]);
            auditar($pdo,'ALTERAR_STATUS_AMBIENTE','ambientes_qr',$id);
            $_SESSION['ambiente_success'] = 'Status do ambiente alterado.';
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['ambiente_error'] = $e->getMessage();
    }
    redirect('ambientes' . (!empty($_POST['id']) ? '?edit='.(int)$_POST['id'] : ''));
}

$editId = (int)($_GET['edit'] ?? 0);
$editing = $editId > 0 ? ambiente_get($pdo,$editId) : null;
$editingActionIds = $editing ? array_map('intval', array_column(ambiente_actions($pdo,$editId,false),'id')) : [];
$locations = $pdo->query("SELECT id,nome FROM locais WHERE ativo=1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
$actions = $pdo->query("SELECT * FROM ambiente_acoes ORDER BY ativo DESC,categoria,nome")->fetchAll(PDO::FETCH_ASSOC);
$planGroups = limpeza_groups($pdo,false);
$environments = $pdo->query("SELECT aq.*,l.nome AS local_nome,pg.nome AS plano_grupo_nome,
    (SELECT MAX(ar.validado_em) FROM ambiente_registros ar WHERE ar.ambiente_id=aq.id AND ar.status IN ('VALIDADA','SOLICITADO_FECHAMENTO')) AS ultima_validacao,
    (SELECT COUNT(*) FROM ambiente_registros ar WHERE ar.ambiente_id=aq.id AND ar.status='AGUARDANDO_VALIDACAO') AS pendentes,
    (SELECT COUNT(*) FROM ambiente_qr_acessos ac WHERE ac.ambiente_id=aq.id) AS acessos
    FROM ambientes_qr aq JOIN locais l ON l.id=aq.local_id LEFT JOIN limpeza_plano_grupos pg ON pg.id=aq.plano_grupo_id ORDER BY aq.ativo DESC,aq.nome_publico")->fetchAll(PDO::FETCH_ASSOC);

render_header('Ambientes e QR Codes');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <div><h1 class="h3 mb-1">Ambientes da limpeza e QR Codes</h1><p class="text-muted mb-0">Vincule cada ambiente a uma área do cronograma e registre quem executou a limpeza.</p></div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza'))?>">Painel</a>
    <a class="btn btn-primary" href="<?=e(app_path('ambiente_registro'))?>">Registrar limpeza</a>
    <?php if (usuario_pode('ambientes.validar')): ?><a class="btn btn-outline-primary" href="<?=e(app_path('ambiente_validar'))?>">Validações</a><?php endif ?>
    <?php if (usuario_pode('ambientes.relatorios')): ?><a class="btn btn-outline-secondary" href="<?=e(app_path('ambiente_relatorios'))?>">Relatórios</a><?php endif ?>
  </div>
</div>
<?php if ($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif ?>
<?php if ($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif ?>

<div class="row g-4">
  <?php if ($canManage): ?>
  <div class="col-xl-4">
    <div class="card shadow-sm sticky-xl-top" style="top:90px">
      <div class="card-body">
        <h2 class="h5"><?= $editing ? 'Editar ambiente' : 'Novo ambiente' ?></h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_environment"><input type="hidden" name="id" value="<?=e($editing['id']??0)?>">
          <div class="mb-3"><label class="form-label">Local cadastrado</label><select class="form-select" name="local_id" required><option value="">Selecione</option><?php foreach($locations as $l): ?><option value="<?=e($l['id'])?>" <?=((int)($editing['local_id']??0)===(int)$l['id'])?'selected':''?>><?=e($l['nome'])?></option><?php endforeach ?></select></div>
          <div class="mb-3"><label class="form-label">Área do plano de limpeza</label><select class="form-select" name="plano_grupo_id"><option value="">Sem área vinculada</option><?php foreach($planGroups as $group): ?><option value="<?=e($group['id'])?>" <?=((int)($editing['plano_grupo_id']??0)===(int)$group['id'])?'selected':''?>><?=e($group['nome'])?><?=!$group['ativo']?' · inativa':''?></option><?php endforeach ?></select><div class="form-text">As atividades desta área aparecerão automaticamente no lançamento da limpeza.</div></div>
          <div class="mb-3"><label class="form-label">Nome público</label><input class="form-control" name="nome_publico" maxlength="160" required value="<?=e($editing['nome_publico']??'')?>"></div>
          <div class="mb-3"><label class="form-label">Descrição pública</label><textarea class="form-control" name="descricao_publica" rows="2"><?=e($editing['descricao_publica']??'')?></textarea></div>
          <div class="row g-2"><div class="col-7"><label class="form-label">Frequência (minutos)</label><input class="form-control" type="number" min="5" max="10080" name="frequencia_minutos" value="<?=e($editing['frequencia_minutos']??60)?>"></div><div class="col-5"><label class="form-label">Histórico</label><input class="form-control" type="number" min="1" max="50" name="historico_limite" value="<?=e($editing['historico_limite']??10)?>"></div></div>
          <div class="mt-3"><label class="form-label">Ações disponíveis</label><div class="border rounded p-2" style="max-height:260px;overflow:auto"><?php foreach($actions as $a): if(!$a['ativo'] && !in_array((int)$a['id'],$editingActionIds,true)) continue; ?><label class="d-flex gap-2 align-items-start py-1"><input class="form-check-input mt-1" type="checkbox" name="acoes[]" value="<?=e($a['id'])?>" <?=in_array((int)$a['id'],$editingActionIds,true)?'checked':''?>><span><strong><?=e($a['nome'])?></strong><small class="d-block text-muted"><?=e($a['categoria'])?><?=!$a['ativo']?' · inativa':''?></small></span></label><?php endforeach ?></div></div>
          <div class="row mt-3"><div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="exibir_historico" id="hist" <?=!$editing||!empty($editing['exibir_historico'])?'checked':''?>><label class="form-check-label" for="hist">Histórico público</label></div><div class="form-check"><input class="form-check-input" type="checkbox" name="exibir_fotos" id="pubf" <?=!empty($editing['exibir_fotos'])?'checked':''?>><label class="form-check-label" for="pubf">Fotos públicas</label></div></div><div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="exige_foto_asg" id="fexec" <?=!empty($editing['exige_foto_asg'])?'checked':''?>><label class="form-check-label" for="fexec">Foto da execução</label></div><div class="form-check"><input class="form-check-input" type="checkbox" name="exige_foto_supervisor" id="fsup" <?=!$editing||!empty($editing['exige_foto_supervisor'])?'checked':''?>><label class="form-check-label" for="fsup">Foto supervisor</label></div></div></div>
          <?php if($editing): ?><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="ativo" id="ativo" <?=!empty($editing['ativo'])?'checked':''?>><label class="form-check-label" for="ativo">Ambiente ativo</label></div><?php endif ?>
          <button class="btn btn-primary w-100 mt-3">Salvar ambiente</button><?php if($editing): ?><a class="btn btn-link w-100" href="<?=e(app_path('ambientes'))?>">Cancelar edição</a><?php endif ?>
        </form>
      </div>
    </div>
  </div>
  <?php endif ?>
  <div class="<?= $canManage ? 'col-xl-8' : 'col-12' ?>">
    <div class="row g-3">
    <?php foreach($environments as $amb): $url=ambiente_public_url($amb); ?>
      <div class="col-lg-6"><div class="card h-100 shadow-sm <?=!$amb['ativo']?'opacity-75':''?>"><div class="card-body">
        <div class="d-flex justify-content-between gap-2"><div><h2 class="h5 mb-1"><?=e($amb['nome_publico'])?></h2><div class="text-muted small"><?=e($amb['local_nome'])?></div><?php if($amb['plano_grupo_nome']): ?><span class="badge text-bg-light mt-1"><?=e($amb['plano_grupo_nome'])?></span><?php endif ?></div><span class="badge text-bg-<?=$amb['ativo']?'success':'secondary'?>"><?=$amb['ativo']?'Ativo':'Inativo'?></span></div>
        <div class="row g-2 text-center my-3"><div class="col-4"><div class="border rounded p-2"><strong class="d-block"><?=e($amb['pendentes'])?></strong><small>Pendentes</small></div></div><div class="col-4"><div class="border rounded p-2"><strong class="d-block"><?=e($amb['acessos'])?></strong><small>Leituras</small></div></div><div class="col-4"><div class="border rounded p-2"><strong class="d-block"><?=e($amb['frequencia_minutos'])?></strong><small>Minutos</small></div></div></div>
        <div class="small mb-3"><strong>Última validação:</strong> <?= $amb['ultima_validacao'] ? e(date('d/m/Y H:i',strtotime($amb['ultima_validacao']))) : 'nenhuma' ?></div>
        <div id="qr-<?=e($amb['id'])?>" class="d-flex justify-content-center bg-white rounded p-2 mb-2"></div>
        <input class="form-control form-control-sm mb-3" readonly value="<?=e($url)?>" onclick="this.select()">
        <div class="d-flex flex-wrap gap-2">
          <a class="btn btn-sm btn-success" href="<?=e(app_path('ambiente_registro',['t'=>$amb['token_publico']]))?>">Registrar limpeza</a><a class="btn btn-sm btn-primary" target="_blank" rel="noopener" href="<?=e($url)?>">Abrir público</a>
          <?php if($canQr): ?><a class="btn btn-sm btn-outline-dark" target="_blank" href="<?=e(app_path('ambiente_qr_imprimir',['id'=>$amb['id']]))?>">Imprimir QR</a><?php endif ?>
          <?php if($canManage): ?><a class="btn btn-sm btn-outline-secondary" href="<?=e(app_path('ambientes',['edit'=>$amb['id']]))?>">Editar</a><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle_environment"><input type="hidden" name="id" value="<?=e($amb['id'])?>"><button class="btn btn-sm btn-outline-warning"><?=$amb['ativo']?'Desativar':'Ativar'?></button></form><?php endif ?>
          <?php if($canQr): ?><form method="post" class="d-inline" onsubmit="return confirm('O QR atual deixará de funcionar. Gerar um novo?')"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="rotate_token"><input type="hidden" name="id" value="<?=e($amb['id'])?>"><button class="btn btn-sm btn-outline-danger">Trocar QR</button></form><?php endif ?>
        </div>
      </div></div></div>
    <?php endforeach ?>
    <?php if(!$environments): ?><div class="col-12"><div class="alert alert-info">Nenhum ambiente com QR Code foi cadastrado.</div></div><?php endif ?>
    </div>
  </div>
</div>

<?php if($canManage): ?>
<div class="card shadow-sm mt-4"><div class="card-body"><h2 class="h5">Catálogo de ações</h2><p class="text-muted">Cadastre ações avulsas adicionais. As tarefas principais vêm do cronograma de limpeza.</p>
<form method="post" class="row g-2 align-items-end"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save_action"><div class="col-md-3"><label class="form-label">Nome</label><input class="form-control" name="nome" required></div><div class="col-md-3"><label class="form-label">Descrição</label><input class="form-control" name="descricao"></div><div class="col-md-2"><label class="form-label">Categoria</label><input class="form-control" name="categoria" value="LIMPEZA"></div><div class="col-md-2"><label class="form-check"><input class="form-check-input" type="checkbox" name="exige_foto"> Exige foto</label><label class="form-check"><input class="form-check-input" type="checkbox" name="exige_observacao"> Exige observação</label></div><div class="col-md-2"><button class="btn btn-success w-100">Adicionar</button></div></form>
<div class="table-responsive mt-3"><table class="table table-sm align-middle"><thead><tr><th>Ação</th><th>Categoria</th><th>Regras</th><th>Status</th></tr></thead><tbody><?php foreach($actions as $a): ?><tr><td><strong><?=e($a['nome'])?></strong><br><small><?=e($a['descricao'])?></small></td><td><?=e($a['categoria'])?></td><td><?= $a['exige_foto']?'Foto ':'' ?><?= $a['exige_observacao']?'Observação':'' ?></td><td><span class="badge text-bg-<?=$a['ativo']?'success':'secondary'?>"><?=$a['ativo']?'Ativa':'Inativa'?></span></td></tr><?php endforeach ?></tbody></table></div>
</div></div>
<?php endif ?>
<script src="<?=e(app_path('assets/vendor/qrcode.min.js',['v'=>'20260803']))?>"></script>
<script><?php foreach($environments as $amb): ?>new QRCode(document.getElementById('qr-<?= (int)$amb['id']?>'),{text:<?=json_encode(ambiente_public_url($amb),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>,width:150,height:150,correctLevel:QRCode.CorrectLevel.H});<?php endforeach ?></script>
<?php render_footer(); ?>
