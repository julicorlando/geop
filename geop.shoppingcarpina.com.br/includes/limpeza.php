<?php
declare(strict_types=1);

function limpeza_allowed_profiles(): array
{
    return ['TI','GESTOR','GESTOR_SINDICO','SUPERVISOR_ASG'];
}

function limpeza_access_allowed(): bool
{
    if (!is_logged_in()) return false;
    return in_array((string)(current_user()['perfil'] ?? ''), limpeza_allowed_profiles(), true)
        && usuario_pode('limpeza.acessar');
}

function limpeza_require_access(): void
{
    require_login();
    if (!limpeza_access_allowed()) {
        http_response_code(403);
        die('Acesso ao módulo Limpeza restrito ao Gestor, Supervisor de Limpeza e TI.');
    }
}

function limpeza_schema_ready(PDO $pdo): bool
{
    try {
        foreach (['limpeza_organograma_nos','limpeza_plano_grupos','limpeza_plano_tarefas','limpeza_cronogramas','limpeza_cronograma_itens','limpeza_cronograma_responsaveis','ambiente_registro_plano_itens','ambiente_registro_responsaveis'] as $table) {
            $st = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
            if (!$st->fetchColumn()) return false;
        }
        $requiredColumns = [
            'ambientes_qr' => ['plano_grupo_id'],
            'ambiente_registros' => ['registrado_por','executor_membro_id','executor_usuario_id','executor_nome','origem_lancamento'],
        ];
        $columnSt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
        foreach ($requiredColumns as $table => $columns) {
            foreach ($columns as $column) {
                $columnSt->execute([$table,$column]);
                if (!(int)$columnSt->fetchColumn()) return false;
            }
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function limpeza_require_schema(PDO $pdo): void
{
    if (!limpeza_schema_ready($pdo)) {
        throw new RuntimeException('O banco do módulo Limpeza V3 ainda não foi atualizado. Importe sql/ATUALIZACAO_MODULO_LIMPEZA_V3.sql.');
    }
}

function limpeza_month_name(int $month): string
{
    return [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'][$month] ?? 'Mês';
}

function limpeza_weekday_short(int $year, int $month, int $day): string
{
    $w = (int)date('w', strtotime(sprintf('%04d-%02d-%02d',$year,$month,$day)));
    return ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][$w] ?? '';
}

function limpeza_groups(PDO $pdo, bool $onlyActive = true): array
{
    $sql = "SELECT * FROM limpeza_plano_grupos" . ($onlyActive ? " WHERE ativo=1" : "") . " ORDER BY ordem,nome";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function limpeza_tasks(PDO $pdo, ?int $groupId = null, bool $onlyActive = true): array
{
    $params = [];
    $where = [];
    if ($groupId !== null && $groupId > 0) { $where[] = 't.grupo_id=?'; $params[] = $groupId; }
    if ($onlyActive) { $where[] = 't.ativo=1'; $where[] = 'g.ativo=1'; }
    $sql = "SELECT t.*,g.nome AS grupo_nome,g.ordem AS grupo_ordem FROM limpeza_plano_tarefas t JOIN limpeza_plano_grupos g ON g.id=t.grupo_id";
    if ($where) $sql .= ' WHERE '.implode(' AND ',$where);
    $sql .= ' ORDER BY g.ordem,g.nome,t.ordem,t.descricao';
    $st = $pdo->prepare($sql); $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function limpeza_members(PDO $pdo, bool $onlyActive = true): array
{
    $sql = "SELECT n.*,u.email AS usuario_email,u.perfil AS usuario_perfil FROM limpeza_organograma_nos n LEFT JOIN usuarios u ON u.id=n.usuario_id";
    if ($onlyActive) $sql .= " WHERE n.ativo=1";
    $sql .= " ORDER BY n.ordem,n.cargo,n.nome_colaborador";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function limpeza_member(PDO $pdo, int $id): ?array
{
    $st=$pdo->prepare("SELECT n.*,u.email AS usuario_email,u.perfil AS usuario_perfil FROM limpeza_organograma_nos n LEFT JOIN usuarios u ON u.id=n.usuario_id WHERE n.id=? LIMIT 1");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function limpeza_build_tree(array $nodes): array
{
    $byParent=[];
    foreach($nodes as $node){
        $parent=(int)($node['parent_id']??0);
        $byParent[$parent][]=$node;
    }
    $walk=function(int $parent,array $seen=[]) use (&$walk,&$byParent): array {
        $result=[];
        foreach($byParent[$parent]??[] as $node){
            $id=(int)$node['id'];
            if(isset($seen[$id])) continue;
            $next=$seen; $next[$id]=true;
            $node['children']=$walk($id,$next);
            $result[]=$node;
        }
        return $result;
    };
    return $walk(0);
}

function limpeza_descendant_ids(array $nodes, int $rootId): array
{
    $children=[];
    foreach($nodes as $n) $children[(int)($n['parent_id']??0)][]=(int)$n['id'];
    $result=[]; $queue=$children[$rootId]??[];
    while($queue){
        $id=array_shift($queue);
        if(in_array($id,$result,true)) continue;
        $result[]=$id;
        foreach($children[$id]??[] as $child) $queue[]=$child;
    }
    return $result;
}

function limpeza_get_or_create_schedule(PDO $pdo, int $year, int $month, int $userId): array
{
    $st=$pdo->prepare("SELECT * FROM limpeza_cronogramas WHERE ano=? AND mes=? LIMIT 1");
    $st->execute([$year,$month]);
    $row=$st->fetch(PDO::FETCH_ASSOC);
    if($row) return $row;
    $name='Plano de Limpeza - '.limpeza_month_name($month).'/'.$year;
    $pdo->prepare("INSERT INTO limpeza_cronogramas(ano,mes,nome,status,criado_por) VALUES(?,?,?,'RASCUNHO',?)")
        ->execute([$year,$month,$name,$userId]);
    $st->execute([$year,$month]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
}

function limpeza_schedule_map(PDO $pdo, int $scheduleId): array
{
    $st=$pdo->prepare("SELECT ci.* FROM limpeza_cronograma_itens ci WHERE ci.cronograma_id=?");
    $st->execute([$scheduleId]);
    $rows=$st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $responsibles=[];
    if($rows){
        $teamSt=$pdo->prepare("SELECT cr.cronograma_item_id,cr.membro_id,cr.responsavel_principal,n.nome_colaborador
            FROM limpeza_cronograma_responsaveis cr
            JOIN limpeza_organograma_nos n ON n.id=cr.membro_id
            JOIN limpeza_cronograma_itens ci ON ci.id=cr.cronograma_item_id
            WHERE ci.cronograma_id=?
            ORDER BY cr.responsavel_principal DESC,n.nome_colaborador");
        $teamSt->execute([$scheduleId]);
        foreach($teamSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $member){
            $responsibles[(int)$member['cronograma_item_id']][]=$member;
        }
    }

    $map=[];
    foreach($rows as $row){
        $team=$responsibles[(int)$row['id']] ?? [];
        if(!$team && !empty($row['responsavel_membro_id'])){
            $legacy=limpeza_member($pdo,(int)$row['responsavel_membro_id']);
            if($legacy){
                $team[]=[
                    'cronograma_item_id'=>(int)$row['id'],
                    'membro_id'=>(int)$legacy['id'],
                    'responsavel_principal'=>1,
                    'nome_colaborador'=>$legacy['nome_colaborador'],
                ];
            }
        }
        $row['responsaveis']=$team;
        $row['responsavel_ids']=array_map('intval',array_column($team,'membro_id'));
        $row['responsavel_nomes']=implode(', ',array_column($team,'nome_colaborador'));
        $row['responsavel_principal_id']=0;
        foreach($team as $member){
            if((int)$member['responsavel_principal']===1){
                $row['responsavel_principal_id']=(int)$member['membro_id'];
                break;
            }
        }
        if(!$row['responsavel_principal_id'] && $row['responsavel_ids']) $row['responsavel_principal_id']=$row['responsavel_ids'][0];
        $map[(int)$row['tarefa_id']][(int)$row['dia']]=$row;
    }
    return $map;
}

function limpeza_schedule_team_for_task(array $taskCells): array
{
    foreach($taskCells as $cell){
        if(!empty($cell['responsavel_ids'])){
            return [
                'ids'=>array_map('intval',$cell['responsavel_ids']),
                'principal_id'=>(int)($cell['responsavel_principal_id'] ?? 0),
                'nomes'=>(string)($cell['responsavel_nomes'] ?? ''),
            ];
        }
    }
    return ['ids'=>[],'principal_id'=>0,'nomes'=>''];
}

function limpeza_record_responsibles(PDO $pdo, int $recordId): array
{
    $st=$pdo->prepare("SELECT rr.*,n.nome_colaborador,u.nome AS usuario_nome
        FROM ambiente_registro_responsaveis rr
        LEFT JOIN limpeza_organograma_nos n ON n.id=rr.membro_id
        LEFT JOIN usuarios u ON u.id=rr.usuario_id
        WHERE rr.registro_id=?
        ORDER BY rr.responsavel_principal DESC,COALESCE(n.nome_colaborador,rr.nome_responsavel,u.nome)");
    $st->execute([$recordId]);
    $rows=$st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach($rows as &$row){
        $row['nome_exibicao']=trim((string)($row['nome_responsavel'] ?: ($row['nome_colaborador'] ?? '') ?: ($row['usuario_nome'] ?? '')));
    }
    unset($row);
    return $rows;
}

function limpeza_record_team_names(PDO $pdo, int $recordId): string
{
    $names=[];
    foreach(limpeza_record_responsibles($pdo,$recordId) as $member){
        if($member['nome_exibicao']!=='') $names[]=$member['nome_exibicao'].((int)$member['responsavel_principal']===1?' (principal)':'');
    }
    return implode(', ',$names);
}

function limpeza_task_record_items(PDO $pdo, int $recordId): array
{
    $st=$pdo->prepare("SELECT rpi.*,t.descricao AS nome,t.periodicidade,t.observacao,g.nome AS grupo_nome
        FROM ambiente_registro_plano_itens rpi
        JOIN limpeza_plano_tarefas t ON t.id=rpi.tarefa_id
        JOIN limpeza_plano_grupos g ON g.id=t.grupo_id
        WHERE rpi.registro_id=? ORDER BY g.ordem,t.ordem,t.descricao");
    $st->execute([$recordId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function limpeza_record_executor(array $record): string
{
    foreach (['equipe_nomes','responsaveis_nomes','executor_nome','membro_nome','executor_usuario_nome','asg_nome'] as $key) {
        $value=trim((string)($record[$key]??''));
        if($value!=='') return $value;
    }
    return 'Responsável não informado';
}

function limpeza_origin_for_profile(string $profile): string
{
    return in_array($profile,['TI','GESTOR','GESTOR_SINDICO'],true) ? 'GESTOR' : 'SUPERVISOR';
}

function limpeza_merge_action_names(?string $generic, ?string $plan): string
{
    $parts=[];
    foreach([$generic,$plan] as $value){
        $value=trim((string)$value);
        if($value!=='') $parts[]=$value;
    }
    return implode(' • ',$parts);
}
