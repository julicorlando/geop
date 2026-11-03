<?php
declare(strict_types=1);

require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/gestao_integrada.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/limpeza.php';
require_once __DIR__ . '/exclusao_ordens.php';

function ambiente_schema_ready(PDO $pdo): bool
{
    try {
        $st = $pdo->query("SHOW TABLES LIKE 'ambientes_qr'");
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function ambiente_require_schema(PDO $pdo): void
{
    if (!ambiente_schema_ready($pdo)) {
        throw new RuntimeException('O banco do módulo de Ambientes ainda não foi atualizado. Importe sql/ATUALIZACAO_MODULO_AMBIENTES_QR.sql.');
    }
}

function ambiente_generate_token(): string
{
    return bin2hex(random_bytes(24));
}

function ambiente_public_url(array $ambiente): string
{
    $token = (string)($ambiente['token_publico'] ?? '');
    $base = rtrim(app_base_path(), '/');
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || $forwardedProto === 'https';
    $prefix = $host !== '' ? (($https ? 'https' : 'http') . '://' . $host) : '';
    return $prefix . $base . '/ambiente/' . rawurlencode($token);
}

function ambiente_get_by_token(PDO $pdo, string $token, bool $onlyActive = true): ?array
{
    if (!preg_match('/^[a-f0-9]{48}$/', $token)) return null;
    $sql = "SELECT aq.*, l.nome AS local_nome, l.descricao AS local_descricao,g.nome AS plano_grupo_nome
            FROM ambientes_qr aq
            JOIN locais l ON l.id=aq.local_id
            LEFT JOIN limpeza_plano_grupos g ON g.id=aq.plano_grupo_id
            WHERE aq.token_publico=?" . ($onlyActive ? " AND aq.ativo=1" : '') . " LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$token]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function ambiente_get(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare("SELECT aq.*,l.nome AS local_nome,g.nome AS plano_grupo_nome FROM ambientes_qr aq JOIN locais l ON l.id=aq.local_id LEFT JOIN limpeza_plano_grupos g ON g.id=aq.plano_grupo_id WHERE aq.id=? LIMIT 1");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function ambiente_actions(PDO $pdo, int $ambienteId, bool $onlyActive = true): array
{
    $sql = "SELECT aa.*, aal.obrigatoria, aal.ordem_exibicao
            FROM ambiente_acao_locais aal
            JOIN ambiente_acoes aa ON aa.id=aal.acao_id
            WHERE aal.ambiente_id=?";
    if ($onlyActive) $sql .= " AND aal.ativo=1 AND aa.ativo=1";
    $sql .= " ORDER BY aal.ordem_exibicao,aa.nome";
    $st = $pdo->prepare($sql);
    $st->execute([$ambienteId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ambiente_open_os(PDO $pdo, int $localId): array
{
    $ordemAtivaSql = exclusao_sql_nao_excluida($pdo, 'ordens_servico');
    $st = $pdo->prepare("SELECT id,numero_os,titulo,prioridade,status
        FROM ordens_servico
        WHERE local_id=? AND $ordemAtivaSql AND status IN ('ABERTA','ATRIBUIDA','EM_ANDAMENTO','PAUSADA','AGUARDANDO_MATERIAL','REPROVADA')
        ORDER BY FIELD(prioridade,'CRITICA','ALTA','MEDIA','BAIXA'),criado_em DESC");
    $st->execute([$localId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ambiente_status_label(string $status): string
{
    return [
        'RASCUNHO' => 'Rascunho',
        'AGUARDANDO_VALIDACAO' => 'Aguardando validação',
        'VALIDADA' => 'Validada',
        'REPROVADA' => 'Reprovada',
        'CORRECAO_SOLICITADA' => 'Correção solicitada',
        'SOLICITADO_FECHAMENTO' => 'Fechamento da OS solicitado',
        'CANCELADA' => 'Cancelada',
    ][$status] ?? str_replace('_', ' ', $status);
}

function ambiente_status_class(string $status): string
{
    return [
        'AGUARDANDO_VALIDACAO' => 'warning',
        'VALIDADA' => 'success',
        'SOLICITADO_FECHAMENTO' => 'primary',
        'REPROVADA' => 'danger',
        'CORRECAO_SOLICITADA' => 'danger',
        'CANCELADA' => 'secondary',
        'RASCUNHO' => 'secondary',
    ][$status] ?? 'secondary';
}

function ambiente_record_items(PDO $pdo, int $registroId): array
{
    $items=[];
    $st = $pdo->prepare("SELECT ri.*,aa.nome,aa.categoria,'ACAO' AS origem FROM ambiente_registro_itens ri JOIN ambiente_acoes aa ON aa.id=ri.acao_id WHERE ri.registro_id=? ORDER BY aa.nome");
    $st->execute([$registroId]);
    foreach($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) $items[]=$row;
    if (limpeza_schema_ready($pdo)) {
        foreach(limpeza_task_record_items($pdo,$registroId) as $row){
            $row['categoria']='PLANO DE LIMPEZA';
            $row['origem']='PLANO';
            $items[]=$row;
        }
    }
    return $items;
}

function ambiente_plan_tasks(PDO $pdo, int $ambienteId, bool $onlyActive = true): array
{
    $st=$pdo->prepare("SELECT t.*,g.nome AS grupo_nome FROM ambientes_qr aq JOIN limpeza_plano_grupos g ON g.id=aq.plano_grupo_id JOIN limpeza_plano_tarefas t ON t.grupo_id=g.id WHERE aq.id=?" . ($onlyActive ? " AND g.ativo=1 AND t.ativo=1" : "") . " ORDER BY t.ordem,t.descricao");
    $st->execute([$ambienteId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ambiente_record_photos(PDO $pdo, int $registroId, bool $publicOnly = false): array
{
    $sql = "SELECT arf.*,u.nome AS usuario_nome FROM ambiente_registro_fotos arf JOIN usuarios u ON u.id=arf.usuario_id WHERE arf.registro_id=?";
    if ($publicOnly) $sql .= " AND arf.publica=1";
    $sql .= " ORDER BY arf.criado_em";
    $st = $pdo->prepare($sql);
    $st->execute([$registroId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ambiente_public_records(PDO $pdo, int $ambienteId, int $limit): array
{
    $limit = max(1, min(50, $limit));
    $st = $pdo->prepare("SELECT ar.*,u.nome AS asg_nome,eu.nome AS executor_usuario_nome,m.nome_colaborador AS membro_nome,s.nome AS supervisor_nome,os.numero_os,os.titulo AS os_titulo,
        (SELECT GROUP_CONCAT(rr.nome_responsavel ORDER BY rr.responsavel_principal DESC,rr.nome_responsavel SEPARATOR ', ') FROM ambiente_registro_responsaveis rr WHERE rr.registro_id=ar.id) AS equipe_nomes,
        (SELECT GROUP_CONCAT(DISTINCT aa.nome ORDER BY aa.nome SEPARATOR ' • ') FROM ambiente_registro_itens ri JOIN ambiente_acoes aa ON aa.id=ri.acao_id WHERE ri.registro_id=ar.id AND ri.concluida=1) AS acoes_genericas,
        (SELECT GROUP_CONCAT(DISTINCT pt.descricao ORDER BY pt.ordem,pt.descricao SEPARATOR ' • ') FROM ambiente_registro_plano_itens rpi JOIN limpeza_plano_tarefas pt ON pt.id=rpi.tarefa_id WHERE rpi.registro_id=ar.id AND rpi.concluida=1) AS tarefas_plano
        FROM ambiente_registros ar
        JOIN usuarios u ON u.id=ar.asg_id
        LEFT JOIN usuarios eu ON eu.id=ar.executor_usuario_id
        LEFT JOIN limpeza_organograma_nos m ON m.id=ar.executor_membro_id
        LEFT JOIN usuarios s ON s.id=ar.supervisor_id
        LEFT JOIN ordens_servico os ON os.id=ar.ordem_servico_id AND " . exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os') . "
        WHERE ar.ambiente_id=? AND ar.status IN ('VALIDADA','SOLICITADO_FECHAMENTO')
        ORDER BY ar.validado_em DESC,ar.finalizado_em DESC LIMIT ?");
    $st->bindValue(1, $ambienteId, PDO::PARAM_INT);
    $st->bindValue(2, $limit, PDO::PARAM_INT);
    $st->execute();
    $rows=$st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach($rows as &$row){
        $row['acoes']=limpeza_merge_action_names($row['acoes_genericas']??'', $row['tarefas_plano']??'');
        $row['executor_exibicao']=limpeza_record_executor($row);
    }
    unset($row);
    return $rows;
}

function ambiente_log_access(PDO $pdo, int $ambienteId): void
{
    try {
        $secret = (string)(env('APP_KEY', 'geop') ?: 'geop');
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $pdo->prepare("INSERT INTO ambiente_qr_acessos(ambiente_id,usuario_id,ip_hash,user_agent_hash) VALUES(?,?,?,?)")
            ->execute([$ambienteId, (int)(current_user()['id'] ?? 0) ?: null, hash_hmac('sha256',$ip,$secret), hash_hmac('sha256',$ua,$secret)]);
        $pdo->prepare("UPDATE ambientes_qr SET ultimo_acesso_em=NOW() WHERE id=?")->execute([$ambienteId]);
    } catch (Throwable $e) {
        error_log('[GEOP ambiente acesso] ' . $e->getMessage());
    }
}

function ambiente_normalize_uploads(array $files): array
{
    if (!isset($files['name'])) return [];
    if (!is_array($files['name'])) return [$files];
    $normalized = [];
    foreach ($files['name'] as $i => $name) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $normalized;
}

function ambiente_store_photos(PDO $pdo, int $registroId, int $usuarioId, string $origem, string $tipo, array $files, bool $publica = false): int
{
    $items = ambiente_normalize_uploads($files);
    if (!$items) return 0;
    if (count($items) > 8) throw new RuntimeException('Envie no máximo 8 fotos por lançamento.');
    $directory = dirname(__DIR__) . '/public/uploads/ambientes';
    $stored = 0;
    foreach ($items as $file) {
        $saved = upload_store($file, $directory, 'amb_' . $registroId . '_');
        $relative = 'uploads/ambientes/' . $saved['name'];
        $pdo->prepare("INSERT INTO ambiente_registro_fotos(registro_id,usuario_id,origem,tipo,arquivo_url,publica) VALUES(?,?,?,?,?,?)")
            ->execute([$registroId,$usuarioId,$origem,$tipo,$relative,$publica ? 1 : 0]);
        $stored++;
    }
    return $stored;
}

function ambiente_notify_profiles(PDO $pdo, array $profiles, string $tipo, string $titulo, string $mensagem, string $url, string $keyPrefix): void
{
    if (!$profiles) return;
    $marks = implode(',', array_fill(0, count($profiles), '?'));
    try {
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE status='ATIVO' AND perfil IN ($marks)");
        $st->execute($profiles);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $uid) {
            gestao_notificar($pdo,(int)$uid,$tipo,$titulo,$mensagem,$url,$keyPrefix.'-'.(int)$uid);
        }
    } catch (Throwable $e) {
        error_log('[GEOP ambiente notificação] ' . $e->getMessage());
    }
}

function ambiente_copy_photos_to_os(PDO $pdo, int $registroId, int $osId, int $usuarioId): int
{
    $st = $pdo->prepare("SELECT arquivo_url,descricao FROM ambiente_registro_fotos WHERE registro_id=? AND tipo IN ('DEPOIS','INSPECAO')");
    $st->execute([$registroId]);
    $count = 0;
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $foto) {
        $exists = $pdo->prepare("SELECT 1 FROM anexos_os WHERE ordem_servico_id=? AND arquivo_url=? LIMIT 1");
        $exists->execute([$osId,$foto['arquivo_url']]);
        if ($exists->fetchColumn()) continue;
        $pdo->prepare("INSERT INTO anexos_os(ordem_servico_id,usuario_id,tipo,arquivo_url,descricao) VALUES(?,?,?,?,?)")
            ->execute([$osId,$usuarioId,'FOTO_DEPOIS',$foto['arquivo_url'],'Evidência da validação do ambiente. '.trim((string)$foto['descricao'])]);
        $count++;
    }
    return $count;
}

function ambiente_qr_current_state(array $ambiente, ?array $lastRecord): array
{
    if (!$lastRecord) return ['SEM_REGISTRO','Sem limpeza validada','secondary'];
    $frequency = max(1, (int)$ambiente['frequencia_minutos']);
    $reference = strtotime((string)($lastRecord['validado_em'] ?: $lastRecord['finalizado_em']));
    if (!$reference) return ['VALIDADO','Serviço validado','success'];
    $elapsed = (time() - $reference) / 60;
    if ($elapsed > $frequency) return ['ATRASADO','Limpeza fora da frequência prevista','danger'];
    return ['HIGIENIZADO','Ambiente higienizado','success'];
}
