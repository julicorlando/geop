<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/conhecimento.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';
require_login();
gestao_ensure_schema($pdo);

$gerenciar = conhecimento_pode_gerenciar();
$acao = (string)($_POST['acao'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if ($acao === 'avaliar') {
        $artigoId = max(0,(int)($_POST['artigo_id'] ?? 0));
        $util = (int)($_POST['util'] ?? 0) === 1 ? 1 : 0;
        $comentario = trim((string)($_POST['comentario'] ?? ''));
        if ($artigoId>0) {
            $pdo->prepare("INSERT INTO conhecimento_avaliacoes(artigo_id,usuario_id,util,comentario,criado_em)
                VALUES(?,?,?,?,NOW()) ON DUPLICATE KEY UPDATE util=VALUES(util),comentario=VALUES(comentario),atualizado_em=NOW()")
                ->execute([$artigoId,(int)current_user()['id'],$util,substr($comentario,0,500)]);
        }
        header('Location: '.app_path('conhecimento')); exit;
    }
    if (!$gerenciar) { http_response_code(403); throw new RuntimeException('Acesso negado.'); }
    $id = max(0, (int)($_POST['id'] ?? 0));
    if ($acao === 'salvar') {
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $problema = trim((string)($_POST['problema'] ?? ''));
        $solucao = trim((string)($_POST['solucao'] ?? ''));
        if ($titulo === '' || $problema === '' || $solucao === '') throw new RuntimeException('Informe título, problema e solução.');
        $userId = (int)(current_user()['id'] ?? 0);
        $origemOsId = max(0, (int)($_POST['origem_os_id'] ?? 0)) ?: null;
        $dados = [
            $titulo, (int)($_POST['categoria_id'] ?? 0) ?: null, $problema,
            trim((string)($_POST['sintomas'] ?? '')), trim((string)($_POST['causa'] ?? '')),
            $solucao, trim((string)($_POST['palavras_chave'] ?? '')),
            trim((string)($_POST['versao'] ?? '1.0')) ?: '1.0',
            $userId,
            in_array($_POST['status'] ?? '', ['RASCUNHO','PUBLICADO','INATIVO'], true) ? $_POST['status'] : 'RASCUNHO',
            $origemOsId,
        ];
        if ($id) {
            $dados[] = $id;
            $pdo->prepare("UPDATE conhecimento_artigos SET titulo=?,categoria_id=?,problema=?,sintomas=?,causa=?,solucao=?,
                palavras_chave=?,versao=?,responsavel_id=?,status=?,origem_os_id=?,updated_at=NOW() WHERE id=?")->execute($dados);
            auditar($pdo, 'CONHECIMENTO_ATUALIZAR', 'conhecimento_artigos', $id, [], ['titulo'=>$titulo,'status'=>$dados[9]]);
        } else {
            $pdo->prepare("INSERT INTO conhecimento_artigos
                (titulo,categoria_id,problema,sintomas,causa,solucao,palavras_chave,versao,responsavel_id,status,origem_os_id,conteudo)
                VALUES(?,?,?,?,?,?,?,?,?,?,?,?)")->execute(array_merge($dados, [$solucao]));
            $id = (int)$pdo->lastInsertId();
            auditar($pdo, 'CONHECIMENTO_CRIAR', 'conhecimento_artigos', $id, [], ['titulo'=>$titulo,'status'=>$dados[9]]);
        }
        header('Location: ' . app_path('conhecimento')); exit;
    }
    if ($acao === 'status' && $id) {
        $statusNovo = ($_POST['status'] ?? '') === 'PUBLICADO' ? 'PUBLICADO' : 'INATIVO';
        $pdo->prepare("UPDATE conhecimento_artigos SET status=?,updated_at=NOW() WHERE id=?")->execute([$statusNovo,$id]);
        auditar($pdo, 'CONHECIMENTO_STATUS', 'conhecimento_artigos', $id, [], ['status'=>$statusNovo]);
        header('Location: ' . app_path('conhecimento')); exit;
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$categoriaId = max(0, (int)($_GET['categoria_id'] ?? 0));
$artigos = conhecimento_buscar($pdo, $q, $categoriaId ?: null, $gerenciar);
$categorias = $pdo->query("SELECT id,nome FROM categorias WHERE ativo=1 ORDER BY nome")->fetchAll();
$edit = null;
if ($gerenciar && !empty($_GET['editar'])) {
    $st=$pdo->prepare("SELECT * FROM conhecimento_artigos WHERE id=?"); $st->execute([(int)$_GET['editar']]); $edit=$st->fetch() ?: null;
}
if ($gerenciar && !$edit && !empty($_GET['nova_os_id'])) {
    $st=$pdo->prepare("SELECT id,titulo,descricao,categoria_id,observacao_aprovacao,solicitacao_encerramento_obs,numero_os
        FROM ordens_servico WHERE id=? AND status='ENCERRADA' AND " . exclusao_sql_nao_excluida($pdo, 'ordens_servico') . "");
    $st->execute([(int)$_GET['nova_os_id']]);
    if ($origem=$st->fetch()) {
        $edit = [
            'id'=>0, 'titulo'=>'Solução histórica — '.$origem['titulo'],
            'categoria_id'=>$origem['categoria_id'], 'problema'=>$origem['descricao'],
            'sintomas'=>'', 'causa'=>'',
            'solucao'=>$origem['observacao_aprovacao'] ?: $origem['solicitacao_encerramento_obs'] ?: '',
            'palavras_chave'=>$origem['titulo'].', '.$origem['numero_os'],
            'versao'=>'1.0', 'status'=>'RASCUNHO', 'origem_os_id'=>$origem['id'],
        ];
    }
}
render_header('Base de conhecimento');
?>
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3">Base de conhecimento</h1></div>
<div class="card p-3 mb-3">
 <form class="row g-2">
  <div class="col-md-7"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Pesquisar problema, solução ou palavra-chave"></div>
  <div class="col-md-3"><select class="form-select" name="categoria_id"><option value="">Todas as categorias</option><?php foreach($categorias as $c): ?><option value="<?=e($c['id'])?>" <?=$categoriaId===$c['id']?'selected':''?>><?=e($c['nome'])?></option><?php endforeach ?></select></div>
  <div class="col-md-2"><button class="btn btn-dark w-100">Pesquisar</button></div>
 </form>
</div>
<?php if($gerenciar): ?>
<details class="card p-3 mb-3" <?= $edit?'open':'' ?>><summary class="fw-bold"><?= $edit?'Editar artigo':'Cadastrar solução' ?></summary>
 <form method="post" class="row g-3 mt-1">
  <input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="acao" value="salvar"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>"><input type="hidden" name="origem_os_id" value="<?=e($edit['origem_os_id']??'')?>">
  <div class="col-md-8"><label class="form-label">Título</label><input class="form-control" name="titulo" required value="<?=e($edit['titulo']??'')?>"></div>
  <div class="col-md-4"><label class="form-label">Categoria</label><select class="form-select" name="categoria_id"><option value="">Geral</option><?php foreach($categorias as $c): ?><option value="<?=e($c['id'])?>" <?=($edit['categoria_id']??'')==$c['id']?'selected':''?>><?=e($c['nome'])?></option><?php endforeach ?></select></div>
  <div class="col-md-6"><label class="form-label">Problema</label><textarea class="form-control" name="problema" required rows="3"><?=e($edit['problema']??'')?></textarea></div>
  <div class="col-md-6"><label class="form-label">Sintomas</label><textarea class="form-control" name="sintomas" rows="3"><?=e($edit['sintomas']??'')?></textarea></div>
  <div class="col-md-6"><label class="form-label">Causa provável</label><textarea class="form-control" name="causa" rows="3"><?=e($edit['causa']??'')?></textarea></div>
  <div class="col-md-6"><label class="form-label">Solução/procedimento</label><textarea class="form-control" name="solucao" required rows="3"><?=e($edit['solucao']??'')?></textarea></div>
  <div class="col-md-6"><label class="form-label">Palavras-chave</label><input class="form-control" name="palavras_chave" value="<?=e($edit['palavras_chave']??'')?>" placeholder="bomba, vazamento, elétrica"></div>
  <div class="col-md-2"><label class="form-label">Versão</label><input class="form-control" name="versao" value="<?=e($edit['versao']??'1.0')?>"></div>
  <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach(['RASCUNHO','PUBLICADO','INATIVO'] as $s): ?><option <?=($edit['status']??'RASCUNHO')===$s?'selected':''?>><?=e($s)?></option><?php endforeach ?></select></div>
  <div class="col-12"><button class="btn btn-primary">Salvar artigo</button></div>
 </form>
</details>
<?php endif ?>
<div class="row g-3">
<?php foreach($artigos as $a): ?><div class="col-lg-6"><article class="card p-3 h-100">
 <div class="d-flex justify-content-between gap-2"><h2 class="h5"><?=e($a['titulo'])?></h2><span class="badge text-bg-<?=$a['status']==='PUBLICADO'?'success':'secondary'?>"><?=e($a['status'])?></span></div>
 <small class="text-muted mb-2"><?=e($a['categoria_nome']?:'Solução geral')?> · versão <?=e($a['versao'])?></small>
 <?php if(!empty($a['origem_os_id'])): ?><div class="small mb-2"><a href="<?=e(app_path('ordem_detalhe',['id'=>$a['origem_os_id']]))?>">Dados extraídos da <?=e($a['origem_numero_os'] ?: 'OS')?></a></div><?php endif ?>
 <p><strong>Problema:</strong><br><?=nl2br(e($a['problema']))?></p>
 <?php if($a['causa']): ?><p><strong>Causa provável:</strong><br><?=nl2br(e($a['causa']))?></p><?php endif ?>
 <div class="alert alert-success mb-2"><strong>Solução:</strong><br><?=nl2br(e($a['solucao']))?></div>
 <div class="d-flex flex-wrap gap-2">
  <?php if(can_create_os()): ?><a class="btn btn-sm btn-primary" href="<?=e(app_path('ordem_form',[
      'categoria_id'=>$a['categoria_id'],
      'titulo'=>$a['problema'] ?: $a['titulo'],
      'descricao'=>"Referência da base de conhecimento: ".$a['titulo']."\n\nProblema observado: "
  ]))?>">Abrir OS com esta solução</a><?php endif ?>
  <?php if($gerenciar): ?><a class="btn btn-sm btn-outline-primary" href="<?=e(app_path('conhecimento',['editar'=>$a['id']]))?>">Editar</a><?php endif ?>
 </div>
 <form method="post" class="border-top mt-3 pt-2">
  <input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="acao" value="avaliar"><input type="hidden" name="artigo_id" value="<?=e($a['id'])?>">
  <span class="small text-muted me-2">Esta solução foi útil?</span>
  <button class="btn btn-sm btn-outline-success" name="util" value="1">👍 Sim</button>
  <button class="btn btn-sm btn-outline-danger" name="util" value="0">👎 Não</button>
 </form>
</article></div><?php endforeach ?>
<?php if(!$artigos): ?><div class="col-12"><div class="alert alert-light border">Nenhum artigo encontrado.</div></div><?php endif ?>
</div>
<?php render_footer(); ?>
