<?php
declare(strict_types=1);
require_once __DIR__ . '/exclusao_ordens.php';

require_once __DIR__ . '/upload.php';

function ordem_categoria_eh_limpeza(PDO $pdo, int $categoriaId): bool
{
    if ($categoriaId <= 0) return false;
    $st = $pdo->prepare("SELECT nome FROM categorias WHERE id=? LIMIT 1");
    $st->execute([$categoriaId]);
    $nome = mb_strtolower(trim((string)$st->fetchColumn()), 'UTF-8');
    $nome = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome) ?: $nome;
    return trim(strtolower($nome)) === 'limpeza';
}

function ordem_tipo_categoria(PDO $pdo, int $categoriaId): string
{
    return ordem_categoria_eh_limpeza($pdo, $categoriaId) ? 'OL' : 'OS';
}

function gerar_numero_ol_unificada(PDO $pdo): string
{
    $year = (int)date('Y');

    // Incremento atômico sem transação explícita. Isso elimina de vez a
    // exceção "There is no active transaction" na abertura de OL.
    $stTabela = $pdo->query("SHOW TABLES LIKE 'ol_numero_sequences'");
    if (!$stTabela->fetchColumn()) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ol_numero_sequences (
            ano INT NOT NULL PRIMARY KEY,
            ultimo_numero INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $st = $pdo->prepare("INSERT INTO ol_numero_sequences (ano, ultimo_numero)
        VALUES (?, LAST_INSERT_ID(1))
        ON DUPLICATE KEY UPDATE ultimo_numero = LAST_INSERT_ID(ultimo_numero + 1)");
    $st->execute([$year]);

    $next = (int)$pdo->query("SELECT LAST_INSERT_ID()")->fetchColumn();
    if ($next <= 0) {
        $q = $pdo->prepare("SELECT ultimo_numero FROM ol_numero_sequences WHERE ano=? LIMIT 1");
        $q->execute([$year]);
        $next = (int)$q->fetchColumn();
    }
    if ($next <= 0) {
        throw new RuntimeException('Não foi possível gerar o número sequencial da OL.');
    }

    return sprintf('OL-%04d-%07d', $year, $next);
}

function gerar_numero_ordem_categoria(PDO $pdo, int $categoriaId): string
{
    return ordem_categoria_eh_limpeza($pdo,$categoriaId) ? gerar_numero_ol_unificada($pdo) : gerar_numero_os($pdo);
}

function ordem_checklist_catalogo(PDO $pdo): array
{
    try {
        $st = $pdo->query("SHOW TABLES LIKE 'limpeza_plano_tarefas'");
        if (!$st->fetchColumn()) return [];
        $sql = "SELECT t.id,t.descricao,g.nome AS grupo_nome
                FROM limpeza_plano_tarefas t
                LEFT JOIN limpeza_plano_grupos g ON g.id=t.grupo_id
                WHERE COALESCE(t.ativo,1)=1
                ORDER BY COALESCE(g.ordem,999),g.nome,t.ordem,t.descricao";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function ordem_salvar_checklist(PDO $pdo, int $ordemId, array $tarefasIds, array $itensLivres = []): void
{
    $pdo->prepare('DELETE FROM ordem_checklist_itens WHERE ordem_servico_id=?')->execute([$ordemId]);
    $ins = $pdo->prepare('INSERT INTO ordem_checklist_itens(ordem_servico_id,tarefa_plano_id,descricao,obrigatorio,concluido,ordem) VALUES(?,?,?,?,0,?)');
    $ordem = 1;
    $ids = array_values(array_unique(array_filter(array_map('intval',$tarefasIds), static fn($v)=>$v>0)));
    if ($ids) {
        $ph = implode(',',array_fill(0,count($ids),'?'));
        $st = $pdo->prepare("SELECT id,descricao FROM limpeza_plano_tarefas WHERE id IN ($ph)");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $t) {
            $ins->execute([$ordemId,(int)$t['id'],(string)$t['descricao'],1,$ordem++]);
        }
    }
    foreach ($itensLivres as $descricao) {
        $descricao = trim((string)$descricao);
        if ($descricao === '') continue;
        $ins->execute([$ordemId,null,mb_substr($descricao,0,255),1,$ordem++]);
    }
}

function ordem_checklist(PDO $pdo, int $ordemId): array
{
    try {
        $st=$pdo->prepare('SELECT * FROM ordem_checklist_itens WHERE ordem_servico_id=? ORDER BY ordem,id');
        $st->execute([$ordemId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch(Throwable $e){ return []; }
}

function ordem_normalizar_uploads(array $files): array
{
    if (!isset($files['name'])) return [];
    if (!is_array($files['name'])) return (($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) ? [] : [$files];
    $out=[];
    foreach($files['name'] as $i=>$name){
        $err=$files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if($err===UPLOAD_ERR_NO_FILE) continue;
        $out[]=['name'=>$name,'type'=>$files['type'][$i]??'','tmp_name'=>$files['tmp_name'][$i]??'','error'=>$err,'size'=>$files['size'][$i]??0];
    }
    return $out;
}

function ordem_salvar_fotos_abertura(PDO $pdo, int $ordemId, int $usuarioId, array $files): int
{
    $items=ordem_normalizar_uploads($files);
    if(count($items)>10) throw new RuntimeException('Envie no máximo 10 fotos na abertura.');
    if(!$items) return 0;
    $dir=dirname(__DIR__).'/public/uploads';
    if(!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) throw new RuntimeException('Não foi possível criar a pasta de uploads.');
    $ins=$pdo->prepare("INSERT INTO anexos_os(ordem_servico_id,usuario_id,tipo,arquivo_url,descricao) VALUES(?,?,'FOTO_ANTES',?,?)");
    $n=0;
    foreach($items as $file){
        $saved=upload_store($file,$dir,'os_'.$ordemId.'_');
        $ins->execute([$ordemId,$usuarioId,'uploads/'.$saved['name'],'Foto anexada na abertura da ordem.']);
        $n++;
    }
    return $n;
}



function ordem_limpeza_membros(PDO $pdo): array
{
    try {
        $st = $pdo->query("SELECT n.id,n.usuario_id,n.nome_colaborador,n.cargo,n.setor,n.turno,u.perfil AS usuario_perfil
                           FROM limpeza_organograma_nos n
                           LEFT JOIN usuarios u ON u.id=n.usuario_id
                           WHERE n.ativo=1
                           ORDER BY n.ordem,n.nome_colaborador");
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function ordem_limpeza_eh_supervisor(array $membro): bool
{
    $cargo = mb_strtolower((string)($membro['cargo'] ?? ''), 'UTF-8');
    $perfil = strtoupper((string)($membro['usuario_perfil'] ?? ''));
    return str_contains($cargo, 'supervisor') || $perfil === 'SUPERVISOR_ASG';
}

function ordem_limpeza_eh_asg(array $membro): bool
{
    if (ordem_limpeza_eh_supervisor($membro)) return false;
    $texto = mb_strtolower(trim((string)($membro['cargo'] ?? '') . ' ' . (string)($membro['setor'] ?? '')), 'UTF-8');
    if ($texto === '') return true;
    foreach (['asg','auxiliar de serviços gerais','auxiliar de servicos gerais','serviços gerais','servicos gerais','limpeza','higienização','higienizacao'] as $termo) {
        if (str_contains($texto, $termo)) return true;
    }
    return false;
}

function ordem_salvar_equipe_limpeza(PDO $pdo, int $ordemId, array $membrosIds, ?int $principalId, ?int $supervisorId): void
{
    $pdo->prepare('DELETE FROM ordem_limpeza_participantes WHERE ordem_servico_id=?')->execute([$ordemId]);
    $membrosIds = array_values(array_unique(array_filter(array_map('intval', $membrosIds), static fn($v)=>$v>0)));
    if ($principalId && !in_array($principalId, $membrosIds, true)) $membrosIds[] = $principalId;
    if ($supervisorId && in_array($supervisorId, $membrosIds, true)) {
        $membrosIds = array_values(array_filter($membrosIds, static fn($v)=>$v!==$supervisorId));
    }
    $todos = array_values(array_unique(array_filter(array_merge($membrosIds, $supervisorId ? [$supervisorId] : []))));
    if ($todos) {
        $ph = implode(',', array_fill(0, count($todos), '?'));
        $st = $pdo->prepare("SELECT id FROM limpeza_organograma_nos WHERE ativo=1 AND id IN ($ph)");
        $st->execute($todos);
        $validos = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
        sort($validos); $esperados=$todos; sort($esperados);
        if ($validos !== $esperados) throw new RuntimeException('Um dos colaboradores selecionados não está ativo no organograma da limpeza.');
    }
    $ins = $pdo->prepare('INSERT INTO ordem_limpeza_participantes(ordem_servico_id,membro_id,papel,principal) VALUES(?,?,?,?)');
    foreach ($membrosIds as $mid) $ins->execute([$ordemId,$mid,'ASG',$principalId===$mid?1:0]);
    if ($supervisorId) $ins->execute([$ordemId,$supervisorId,'SUPERVISOR',0]);
}

function ordem_equipe_limpeza(PDO $pdo, int $ordemId): array
{
    try {
        $st=$pdo->prepare("SELECT p.*,n.nome_colaborador,n.cargo,n.usuario_id FROM ordem_limpeza_participantes p JOIN limpeza_organograma_nos n ON n.id=p.membro_id WHERE p.ordem_servico_id=? ORDER BY (p.papel='SUPERVISOR'),p.principal DESC,n.nome_colaborador");
        $st->execute([$ordemId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch(Throwable $e){ return []; }
}

function ordem_migrar_ol_legada(PDO $pdo, int $olId, int $usuarioId): int
{
    $perfil = strtoupper((string)(current_user()['perfil'] ?? ''));
    if ($perfil !== 'TI') throw new RuntimeException('Somente o TI pode migrar OL legada.');
    $olAtivaSql=exclusao_sql_nao_excluida($pdo,'limpeza_ordens');
    $st=$pdo->prepare("SELECT * FROM limpeza_ordens WHERE id=? AND $olAtivaSql LIMIT 1");
    $st->execute([$olId]);
    $ol=$st->fetch(PDO::FETCH_ASSOC);
    if(!$ol) throw new RuntimeException('OL legada não encontrada.');

    $check=$pdo->prepare("SELECT ordem_servico_id FROM ol_unificacao_migracoes WHERE ordem_limpeza_id=? LIMIT 1");
    $check->execute([$olId]);
    if($existing=(int)$check->fetchColumn()) return $existing;

    $cat=(int)$pdo->query("SELECT id FROM categorias WHERE LOWER(TRIM(nome))='limpeza' ORDER BY id LIMIT 1")->fetchColumn();
    if($cat<=0) throw new RuntimeException('Cadastre/ative a categoria Limpeza antes de migrar.');
    $local=(int)($ol['local_id'] ?? 0);
    if($local<=0) $local=(int)$pdo->query('SELECT id FROM locais WHERE ativo=1 ORDER BY id LIMIT 1')->fetchColumn();
    if($local<=0) throw new RuntimeException('Nenhum local ativo disponível para receber a OL.');

    $statusMap=['ABERTA'=>'ABERTA','ATRIBUIDA'=>'ATRIBUIDA','EM_ANDAMENTO'=>'EM_ANDAMENTO','AGUARDANDO_VALIDACAO'=>'CONCLUIDA_PELO_AGENTE','CORRECAO_SOLICITADA'=>'REPROVADA','ENCERRADA'=>'ENCERRADA','CANCELADA'=>'ENCERRADA'];
    $status=$statusMap[(string)$ol['status']] ?? 'ABERTA';
    $solicitante=(int)($ol['solicitante_id'] ?: $ol['criado_por'] ?: $usuarioId);
    $gestor=(int)($ol['criado_por'] ?: $usuarioId);
    $numero=trim((string)$ol['numero_ol']);
    if($numero==='') $numero=gerar_numero_ol_unificada($pdo);

    $pdo->beginTransaction();
    try{
        $ins=$pdo->prepare("INSERT INTO ordens_servico(numero_os,titulo,descricao,local_id,categoria_id,prioridade,status,solicitante_id,gestor_id,agente_id,prazo,origem,tipo_ordem,criado_em,data_inicio,data_conclusao,data_encerramento,aprovado_por,observacao_aprovacao)
            VALUES(?,?,?,?,?,?,?,?,?,?,?,'OL_LEGADA','OL',?,?,?,?,?,?)");
        $prazo=$ol['prazo_em'] ?? null;
        $ins->execute([$numero,$ol['titulo'],(string)($ol['descricao']??''),$local,$cat,$ol['prioridade']??'MEDIA',$status,$solicitante,$gestor,null,$prazo,$ol['criado_em']??date('Y-m-d H:i:s'),$ol['iniciado_em']??null,$ol['concluido_em']??null,$ol['encerrado_em']??null,$ol['validado_por']??null,$ol['parecer_validacao']??null]);
        $osId=(int)$pdo->lastInsertId();

        $it=$pdo->prepare('SELECT tarefa_plano_id,descricao,obrigatorio,status_item,ordem FROM limpeza_ordem_itens WHERE ordem_limpeza_id=? ORDER BY ordem,id');
        $it->execute([$olId]);
        $cii=$pdo->prepare('INSERT INTO ordem_checklist_itens(ordem_servico_id,tarefa_plano_id,descricao,obrigatorio,concluido,ordem) VALUES(?,?,?,?,?,?)');
        foreach($it->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r) $cii->execute([$osId,$r['tarefa_plano_id']?:null,$r['descricao'],(int)($r['obrigatorio']??1),((string)($r['status_item']??'PENDENTE')==='CONCLUIDO'?1:0),(int)($r['ordem']??0)]);

        $ft=$pdo->prepare('SELECT tipo,arquivo_url,descricao FROM limpeza_ordem_fotos WHERE ordem_limpeza_id=? ORDER BY id');
        $ft->execute([$olId]);
        $afi=$pdo->prepare('INSERT INTO anexos_os(ordem_servico_id,usuario_id,tipo,arquivo_url,descricao) VALUES(?,?,?,?,?)');
        foreach($ft->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r){
            $tipo=strtoupper((string)$r['tipo']);
            $tipoOs=in_array($tipo,['ANTES','PROBLEMA'],true)?'FOTO_ANTES':(in_array($tipo,['DEPOIS','INSPECAO'],true)?'FOTO_DEPOIS':'OUTRO');
            $afi->execute([$osId,$usuarioId,$tipoOs,$r['arquivo_url'],$r['descricao']??'Imagem migrada da OL legada.']);
        }

        try {
            $pt=$pdo->prepare('SELECT membro_id,planejado,executou,responsavel_principal FROM limpeza_ordem_participantes WHERE ordem_limpeza_id=? ORDER BY id');
            $pt->execute([$olId]);
            $pi=$pdo->prepare("INSERT IGNORE INTO ordem_limpeza_participantes(ordem_servico_id,membro_id,papel,principal) VALUES(?,?,?,?)");
            foreach($pt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r){
                $pi->execute([$osId,(int)$r['membro_id'],'ASG',(int)($r['responsavel_principal']??0)]);
            }
            if (!empty($ol['supervisor_membro_id'])) {
                $pi->execute([$osId,(int)$ol['supervisor_membro_id'],'SUPERVISOR',0]);
            }
        } catch(Throwable $ignore) {}

        $obs='OL legada '.$numero.' migrada para o ambiente unificado de Ordens pelo TI. O prefixo OL foi preservado por ser categoria Limpeza.';
        $pdo->prepare('INSERT INTO historico_os(ordem_servico_id,usuario_id,status_anterior,status_novo,descricao) VALUES(?,?,?,?,?)')->execute([$osId,$usuarioId,null,$status,$obs]);
        $pdo->prepare('INSERT INTO ol_unificacao_migracoes(ordem_limpeza_id,ordem_servico_id,migrado_por) VALUES(?,?,?)')->execute([$olId,$osId,$usuarioId]);
        $pdo->commit();
        return $osId;
    } catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
