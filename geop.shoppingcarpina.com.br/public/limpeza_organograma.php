<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/limpeza.php';
require_once __DIR__ . '/../includes/audit.php';

limpeza_require_access();
limpeza_require_schema($pdo);
exigir_permissao('limpeza.organograma');
check_csrf();
$user=current_user();
$success=$_SESSION['limpeza_org_success']??'';
$error=$_SESSION['limpeza_org_error']??'';
unset($_SESSION['limpeza_org_success'],$_SESSION['limpeza_org_error']);

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=(string)($_POST['action']??'');
    try{
        if($action==='save'){
            $id=(int)($_POST['id']??0);
            $name=trim((string)($_POST['nome_colaborador']??''));
            $role=trim((string)($_POST['cargo']??''));
            $parent=(int)($_POST['parent_id']??0)?:null;
            $linked=(int)($_POST['usuario_id']??0)?:null;
            $sector=trim((string)($_POST['setor']??''));
            $shift=trim((string)($_POST['turno']??''));
            $contact=trim((string)($_POST['contato']??''));
            $note=trim((string)($_POST['observacao']??''));
            $order=max(0,min(999,(int)($_POST['ordem']??0)));
            $active=!empty($_POST['ativo'])?1:0;
            if($name===''||$role==='') throw new RuntimeException('Informe o nome do colaborador e o cargo.');
            if($linked!==null){
                $dup=$pdo->prepare("SELECT id FROM limpeza_organograma_nos WHERE usuario_id=? AND id<>? LIMIT 1");
                $dup->execute([$linked,$id]);
                if($dup->fetchColumn()) throw new RuntimeException('Este usuário do GEOP já está vinculado a outro integrante do organograma.');
            }
            $all=limpeza_members($pdo,false);
            if($id>0 && $parent!==null){
                if($parent===$id || in_array($parent,limpeza_descendant_ids($all,$id),true)) throw new RuntimeException('A hierarquia selecionada criaria um ciclo no organograma.');
            }
            if($id>0){
                $pdo->prepare("UPDATE limpeza_organograma_nos SET parent_id=?,usuario_id=?,nome_colaborador=?,cargo=?,setor=?,turno=?,contato=?,observacao=?,ordem=?,ativo=? WHERE id=?")
                    ->execute([$parent,$linked,$name,$role,$sector,$shift,$contact,$note,$order,$active,$id]);
                auditar($pdo,'ATUALIZAR_ORGANOGRAMA_LIMPEZA','limpeza_organograma_nos',$id,[],['nome'=>$name,'cargo'=>$role]);
            }else{
                $pdo->prepare("INSERT INTO limpeza_organograma_nos(parent_id,usuario_id,nome_colaborador,cargo,setor,turno,contato,observacao,ordem,ativo,criado_por) VALUES(?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([$parent,$linked,$name,$role,$sector,$shift,$contact,$note,$order,1,(int)$user['id']]);
                $id=(int)$pdo->lastInsertId();
                auditar($pdo,'CRIAR_ORGANOGRAMA_LIMPEZA','limpeza_organograma_nos',$id,[],['nome'=>$name,'cargo'=>$role]);
            }
            $_SESSION['limpeza_org_success']='Organograma atualizado com sucesso.';
        }elseif($action==='toggle'){
            $id=(int)($_POST['id']??0);
            $pdo->prepare("UPDATE limpeza_organograma_nos SET ativo=IF(ativo=1,0,1) WHERE id=?")->execute([$id]);
            auditar($pdo,'ALTERAR_STATUS_ORGANOGRAMA_LIMPEZA','limpeza_organograma_nos',$id);
            $_SESSION['limpeza_org_success']='Status do integrante alterado.';
        }elseif($action==='delete'){
            $id=(int)($_POST['id']??0);
            $used=$pdo->prepare("SELECT
                (SELECT COUNT(*) FROM ambiente_registro_responsaveis WHERE membro_id=?) +
                (SELECT COUNT(*) FROM limpeza_cronograma_responsaveis WHERE membro_id=?) +
                (SELECT COUNT(*) FROM ambiente_registros WHERE executor_membro_id=?)");
            $used->execute([$id,$id,$id]);
            if((int)$used->fetchColumn()>0) throw new RuntimeException('Este integrante já participa de cronogramas ou limpezas registradas. Desative-o em vez de excluir.');
            $pdo->prepare("UPDATE limpeza_organograma_nos SET parent_id=NULL WHERE parent_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM limpeza_organograma_nos WHERE id=?")->execute([$id]);
            auditar($pdo,'EXCLUIR_ORGANOGRAMA_LIMPEZA','limpeza_organograma_nos',$id);
            $_SESSION['limpeza_org_success']='Integrante removido.';
        }
    }catch(Throwable $e){$_SESSION['limpeza_org_error']=$e->getMessage();}
    redirect('limpeza_organograma');
}

$members=limpeza_members($pdo,false);
$tree=limpeza_build_tree(array_values(array_filter($members,fn($m)=>(int)$m['ativo']===1)));
$editId=(int)($_GET['edit']??0);
$editing=$editId?limpeza_member($pdo,$editId):null;
$users=$pdo->query("SELECT id,nome,email,perfil FROM usuarios WHERE status='ATIVO' ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC)?:[];

function render_limpeza_org_tree(array $nodes): void{
    if(!$nodes) return;
    echo '<div class="limpeza-org-level">';
    foreach($nodes as $node){
        echo '<div class="limpeza-org-branch"><div class="card shadow-sm limpeza-org-card"><div class="card-body p-3">';
        echo '<div class="d-flex justify-content-between gap-2"><div><strong>'.e($node['nome_colaborador']).'</strong><div class="text-primary">'.e($node['cargo']).'</div></div><a class="btn btn-sm btn-outline-secondary align-self-start" href="'.e(app_path('limpeza_organograma',['edit'=>$node['id']])).'">Editar</a></div>';
        $details=array_filter([$node['setor']??'', $node['turno']??'', $node['contato']??'']);
        if($details) echo '<small class="text-muted d-block mt-2">'.e(implode(' · ',$details)).'</small>';
        if(!empty($node['observacao'])) echo '<small class="d-block mt-1">'.e($node['observacao']).'</small>';
        echo '</div></div>';
        if(!empty($node['children'])) render_limpeza_org_tree($node['children']);
        echo '</div>';
    }
    echo '</div>';
}

render_header('Organograma da Limpeza');
?>
<style>
.limpeza-org-level{display:flex;gap:1rem;align-items:flex-start;justify-content:center;flex-wrap:wrap;margin-top:1rem}.limpeza-org-branch{min-width:230px;max-width:100%;text-align:center}.limpeza-org-card{border-top:4px solid var(--bs-primary);text-align:left}.limpeza-org-branch>.limpeza-org-level{border-top:2px solid #d7dde4;padding-top:1rem;position:relative}.limpeza-org-branch>.limpeza-org-level:before{content:"";position:absolute;top:-18px;left:50%;width:2px;height:18px;background:#d7dde4}.limpeza-team-table td{vertical-align:middle}
</style>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4"><div><h1 class="h3 mb-1">Organograma e equipe da limpeza</h1><p class="text-muted mb-0">Cadastre colaboradores, cargos, responsáveis superiores e turnos.</p></div><a class="btn btn-outline-secondary" href="<?=e(app_path('limpeza'))?>">Painel da limpeza</a></div>
<?php if($success): ?><div class="alert alert-success"><?=e($success)?></div><?php endif ?><?php if($error): ?><div class="alert alert-danger"><?=e($error)?></div><?php endif ?>
<div class="row g-4">
  <div class="col-xl-4"><div class="card shadow-sm sticky-xl-top" style="top:90px"><div class="card-body"><h2 class="h5"><?=$editing?'Editar integrante':'Adicionar integrante'?></h2>
    <form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e($editing['id']??0)?>">
      <div class="mb-3"><label class="form-label">Nome do colaborador</label><input class="form-control" name="nome_colaborador" maxlength="160" required value="<?=e($editing['nome_colaborador']??'')?>"></div>
      <div class="mb-3"><label class="form-label">Cargo/Função</label><input class="form-control" name="cargo" maxlength="120" required value="<?=e($editing['cargo']??'')?>" placeholder="Ex.: ASG, Líder, Supervisor"></div>
      <div class="mb-3"><label class="form-label">Superior imediato</label><select class="form-select" name="parent_id"><option value="">Topo do organograma</option><?php foreach($members as $m): if($editing && (int)$m['id']===(int)$editing['id']) continue; ?><option value="<?=e($m['id'])?>" <?=((int)($editing['parent_id']??0)===(int)$m['id'])?'selected':''?>><?=e($m['nome_colaborador'].' — '.$m['cargo'])?></option><?php endforeach ?></select></div>
      <div class="mb-3"><label class="form-label">Vincular a usuário do GEOP <span class="text-muted">(opcional)</span></label><select class="form-select" name="usuario_id"><option value="">Sem login próprio</option><?php foreach($users as $u): ?><option value="<?=e($u['id'])?>" <?=((int)($editing['usuario_id']??0)===(int)$u['id'])?'selected':''?>><?=e($u['nome'].' · '.$u['perfil'])?></option><?php endforeach ?></select></div>
      <div class="row g-2"><div class="col-6"><label class="form-label">Setor</label><input class="form-control" name="setor" value="<?=e($editing['setor']??'')?>"></div><div class="col-6"><label class="form-label">Turno</label><input class="form-control" name="turno" value="<?=e($editing['turno']??'')?>"></div></div>
      <div class="mt-3"><label class="form-label">Contato</label><input class="form-control" name="contato" value="<?=e($editing['contato']??'')?>"></div>
      <div class="mt-3"><label class="form-label">Observação</label><textarea class="form-control" name="observacao" rows="2"><?=e($editing['observacao']??'')?></textarea></div>
      <div class="row g-2 mt-1"><div class="col-6"><label class="form-label">Ordem</label><input class="form-control" type="number" min="0" max="999" name="ordem" value="<?=e($editing['ordem']??0)?>"></div><?php if($editing): ?><div class="col-6 d-flex align-items-end"><label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="ativo" <?=!empty($editing['ativo'])?'checked':''?>> Ativo</label></div><?php endif ?></div>
      <button class="btn btn-primary w-100 mt-3">Salvar</button><?php if($editing): ?><a class="btn btn-link w-100" href="<?=e(app_path('limpeza_organograma'))?>">Cancelar edição</a><?php endif ?>
    </form>
  </div></div></div>
  <div class="col-xl-8"><div class="card shadow-sm"><div class="card-body"><h2 class="h5">Visualização do organograma</h2><?php if($tree): render_limpeza_org_tree($tree); else: ?><div class="alert alert-info mt-3">Cadastre a equipe para montar o organograma.</div><?php endif ?></div></div></div>
</div>
<div class="card shadow-sm mt-4"><div class="card-body"><h2 class="h5">Lista da equipe</h2><div class="table-responsive"><table class="table limpeza-team-table"><thead><tr><th>Colaborador</th><th>Cargo</th><th>Superior</th><th>Turno</th><th>Usuário GEOP</th><th>Status</th><th></th></tr></thead><tbody><?php $byId=[];foreach($members as $m)$byId[(int)$m['id']]=$m; foreach($members as $m): ?><tr><td><strong><?=e($m['nome_colaborador'])?></strong><br><small><?=e($m['contato'])?></small></td><td><?=e($m['cargo'])?></td><td><?=e($byId[(int)($m['parent_id']??0)]['nome_colaborador']??'—')?></td><td><?=e($m['turno']?:'—')?></td><td><?=e($m['usuario_email']?:'Sem login')?></td><td><span class="badge text-bg-<?=$m['ativo']?'success':'secondary'?>"><?=$m['ativo']?'Ativo':'Inativo'?></span></td><td class="text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="<?=e(app_path('limpeza_organograma',['edit'=>$m['id']]))?>">Editar</a> <form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=e($m['id'])?>"><button class="btn btn-sm btn-outline-warning"><?=$m['ativo']?'Desativar':'Ativar'?></button></form></td></tr><?php endforeach ?><?php if(!$members): ?><tr><td colspan="7" class="text-center text-muted py-4">Nenhum integrante cadastrado.</td></tr><?php endif ?></tbody></table></div></div></div>
<?php render_footer(); ?>
