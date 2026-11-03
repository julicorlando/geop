<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/cftv.php';
require_role(['TI']);
ensure_cftv_schema($pdo);
$user=current_user();

if($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $action=(string)($_POST['action']??'');
    if($action==='salvar') {
        $id=(int)($_POST['id']??0);
        $tipo=in_array($_POST['tipo']??'', ['LOJA_MALL','PRACA_ALIMENTACAO'],true)?(string)$_POST['tipo']:'LOJA_MALL';
        $nome=trim((string)($_POST['nome']??''));
        if($nome==='') $_SESSION['mensagem_erro']='Informe o nome da loja.';
        elseif($id>0) {
            $pdo->prepare("UPDATE cftv_destinos_acesso SET tipo=?,nome=? WHERE id=?")->execute([$tipo,$nome,$id]);
            auditoria_log($pdo,'cftv_destino_acesso',$id,'EDITAR_DESTINO',null,null,$tipo.' - '.$nome,$user['id']);
            $_SESSION['mensagem_sucesso']='Loja atualizada.';
        } else {
            $pdo->prepare("INSERT INTO cftv_destinos_acesso(tipo,nome,criado_por)
                VALUES(?,?,?) ON DUPLICATE KEY UPDATE ativo=1")->execute([$tipo,$nome,$user['id']]);
            $id=(int)$pdo->lastInsertId();
            auditoria_log($pdo,'cftv_destino_acesso',$id,'CRIAR_DESTINO',null,null,$tipo.' - '.$nome,$user['id']);
            $_SESSION['mensagem_sucesso']='Loja cadastrada.';
        }
    }
    if($action==='alternar') {
        $id=(int)($_POST['id']??0);
        $pdo->prepare("UPDATE cftv_destinos_acesso SET ativo=1-ativo WHERE id=?")->execute([$id]);
        auditoria_log($pdo,'cftv_destino_acesso',$id,'ALTERAR_STATUS_DESTINO',null,null,'Status alternado',$user['id']);
        $_SESSION['mensagem_sucesso']='Disponibilidade atualizada.';
    }
    redirect('cftv_destinos');
}

$busca=trim((string)($_GET['busca']??''));
$st=$pdo->prepare("SELECT d.*,u.nome criado_por_nome FROM cftv_destinos_acesso d
 LEFT JOIN usuarios u ON u.id=d.criado_por WHERE d.nome LIKE ? ORDER BY d.tipo,d.ativo DESC,d.nome");
$st->execute(['%'.$busca.'%']);
$destinos=$st->fetchAll(PDO::FETCH_ASSOC);
$mall=array_values(array_filter($destinos,fn($d)=>$d['tipo']==='LOJA_MALL'));
$praca=array_values(array_filter($destinos,fn($d)=>$d['tipo']==='PRACA_ALIMENTACAO'));
$edit=null;
if(isset($_GET['edit'])) {
    $st=$pdo->prepare("SELECT * FROM cftv_destinos_acesso WHERE id=? LIMIT 1");
    $st->execute([(int)$_GET['edit']]);$edit=$st->fetch(PDO::FETCH_ASSOC)?:null;
}
render_header('Lojas do controle de acesso');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h1 class="h3 mb-0">Lojas do controle de acesso</h1><small class="text-muted">Cadastro exclusivo do TI para o formulário público.</small></div><a class="btn btn-outline-primary" href="<?=e(app_path('cftv',['view'=>'liberacoes']))?>">Voltar às liberações</a></div>
<div class="row g-3">
 <div class="col-lg-4"><div class="card p-3"><h2 class="h5"><?=$edit?'Editar loja':'Cadastrar loja'?></h2><form method="post" class="row g-2"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="salvar"><input type="hidden" name="id" value="<?=e($edit['id']??'')?>"><div class="col-12"><label class="form-label">Localização</label><select class="form-select" name="tipo"><option value="LOJA_MALL" <?=($edit['tipo']??'')==='LOJA_MALL'?'selected':''?>>Loja do Mall</option><option value="PRACA_ALIMENTACAO" <?=($edit['tipo']??'')==='PRACA_ALIMENTACAO'?'selected':''?>>Loja da Praça de Alimentação</option></select></div><div class="col-12"><label class="form-label">Nome da loja</label><input class="form-control" name="nome" value="<?=e($edit['nome']??'')?>" required></div><div class="col-12"><button class="btn btn-primary w-100">Salvar loja</button><?php if($edit): ?><a class="btn btn-outline-secondary w-100 mt-2" href="<?=e(app_path('cftv_destinos'))?>">Cancelar edição</a><?php endif ?></div></form></div></div>
 <div class="col-lg-8"><div class="card p-3 mb-3"><form class="row g-2"><div class="col-9"><input class="form-control" name="busca" value="<?=e($busca)?>" placeholder="Pesquisar loja"></div><div class="col-3"><button class="btn btn-dark w-100">Pesquisar</button></div></form></div>
 <?php foreach([['Lojas do Mall',$mall],['Lojas da Praça de Alimentação',$praca]] as [$titulo,$lista]): ?><div class="card p-3 mb-3"><h2 class="h5"><?=$titulo?> <span class="badge text-bg-secondary"><?=count($lista)?></span></h2><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Loja</th><th>Status</th><th>Cadastrada por</th><th></th></tr></thead><tbody><?php foreach($lista as $d): ?><tr><td><strong><?=e($d['nome'])?></strong></td><td><span class="badge text-bg-<?=$d['ativo']?'success':'secondary'?>"><?=$d['ativo']?'Ativa':'Inativa'?></span></td><td><?=e($d['criado_por_nome']?:'TI')?></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?=e(app_path('cftv_destinos',['edit'=>$d['id']]))?>">Editar</a> <form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="alternar"><input type="hidden" name="id" value="<?=e($d['id'])?>"><button class="btn btn-sm btn-outline-<?=$d['ativo']?'danger':'success'?>"><?=$d['ativo']?'Desativar':'Ativar'?></button></form></td></tr><?php endforeach; if(!$lista): ?><tr><td colspan="4" class="text-center text-muted">Nenhuma loja cadastrada.</td></tr><?php endif ?></tbody></table></div></div><?php endforeach ?>
 </div>
</div>
<?php render_footer(); ?>
