<?php
declare(strict_types=1);
require_once __DIR__ . '/exclusao_ordens.php';

require_once __DIR__ . '/limpeza.php';
require_once __DIR__ . '/ambientes_qr.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/gestao_integrada.php';
require_once __DIR__ . '/audit.php';

function ol_access_allowed(): bool
{
    return limpeza_access_allowed() && usuario_pode('ol.acessar');
}

function ol_require_access(): void
{
    limpeza_require_access();
    if (!ol_access_allowed()) {
        http_response_code(403);
        throw new RuntimeException('Você não tem permissão para acessar as Ordens de Limpeza.');
    }
}

/**
 * Verifica uma tabela no banco realmente usado pela conexão PDO atual.
 * A V6.2 chamava app_table_exists(), função que não fazia parte do pacote,
 * fazendo a interface concluir incorretamente que a migração não existia.
 */
function ol_table_exists(PDO $pdo, string $table): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        return false;
    }

    try {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME) = LOWER(?)'
        );
        $st->execute([$table]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) {
        try {
            $st = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
            return (bool)$st->fetchColumn();
        } catch (Throwable $ignored) {
            return false;
        }
    }
}

function ol_current_database(PDO $pdo): string
{
    try {
        return (string)($pdo->query('SELECT DATABASE()')->fetchColumn() ?: 'não identificado');
    } catch (Throwable $e) {
        return 'não identificado';
    }
}

/**
 * Ativa a estrutura OS -> OL usando a mesma conexão do GEOP.
 * Somente deve ser chamada após a checagem de perfil TI no controlador.
 */
function ol_activate_os_migration_schema(PDO $pdo): void
{
    if (!ol_table_exists($pdo, 'limpeza_ordens')) {
        throw new RuntimeException('O módulo OL ainda não está instalado neste banco. Importe primeiro o banco do módulo OL V5/V6.');
    }
    if (!ol_table_exists($pdo, 'ordens_servico')) {
        throw new RuntimeException('A tabela ordens_servico não foi encontrada no banco atual.');
    }
    if (!ol_table_exists($pdo, 'usuarios')) {
        throw new RuntimeException('A tabela usuarios não foi encontrada no banco atual.');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS limpeza_ordem_migracoes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ordem_servico_id INT NOT NULL,
        ordem_limpeza_id BIGINT UNSIGNED NOT NULL,
        migrado_por INT NOT NULL,
        status_os_anterior VARCHAR(40) NOT NULL,
        os_encerrada TINYINT(1) NOT NULL DEFAULT 1,
        observacao TEXT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_migracao_os (ordem_servico_id),
        UNIQUE KEY uq_migracao_ol (ordem_limpeza_id),
        KEY idx_migracao_usuario_data (migrado_por,criado_em),
        CONSTRAINT fk_migracao_os FOREIGN KEY (ordem_servico_id) REFERENCES ordens_servico(id) ON DELETE RESTRICT,
        CONSTRAINT fk_migracao_ol FOREIGN KEY (ordem_limpeza_id) REFERENCES limpeza_ordens(id) ON DELETE CASCADE,
        CONSTRAINT fk_migracao_usuario FOREIGN KEY (migrado_por) REFERENCES usuarios(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    if (ol_table_exists($pdo, 'permissoes') && ol_table_exists($pdo, 'perfis') && ol_table_exists($pdo, 'perfil_permissoes')) {
        $pdo->exec("INSERT IGNORE INTO permissoes(codigo,nome,descricao) VALUES
            ('ol.migrar_os','Migrar OS para OL','Converter uma Ordem de Serviço em Ordem de Limpeza')");

        $pdo->exec("DELETE pp FROM perfil_permissoes pp
            JOIN perfis pf ON pf.id=pp.perfil_id
            JOIN permissoes pm ON pm.id=pp.permissao_id
            WHERE pm.codigo='ol.migrar_os' AND pf.codigo <> 'TI'");

        $pdo->exec("INSERT IGNORE INTO perfil_permissoes(perfil_id,permissao_id)
            SELECT pf.id,pm.id FROM perfis pf CROSS JOIN permissoes pm
            WHERE pf.codigo='TI' AND pm.codigo='ol.migrar_os'");
    }

    if (ol_table_exists($pdo, 'schema_migrations')) {
        $st = $pdo->prepare('INSERT IGNORE INTO schema_migrations(version,applied_at) VALUES(?,NOW())');
        $st->execute(['011_correcao_verificacao_migracao_os_ol_v6_3']);
    }

    if (!ol_table_exists($pdo, 'limpeza_ordem_migracoes')) {
        throw new RuntimeException('A tabela de migração não pôde ser criada. Verifique se o usuário do banco possui permissão CREATE.');
    }
}

function ol_schema_ready(PDO $pdo): bool
{
    try {
        foreach (['limpeza_ordens_sequences','limpeza_ordens','limpeza_ordem_participantes','limpeza_ordem_itens','limpeza_ordem_fotos','limpeza_ordem_historico','limpeza_ordem_migracoes'] as $table) {
            $st = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
            if (!$st->fetchColumn()) return false;
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function ol_require_schema(PDO $pdo): void
{
    if (!ol_schema_ready($pdo)) {
        throw new RuntimeException('O banco do módulo OL ainda não foi atualizado. Importe sql/ATUALIZACAO_MIGRACAO_OS_OL_V6_2.sql.');
    }
}

function ol_statuses(): array
{
    return [
        'ABERTA' => 'Aberta',
        'ATRIBUIDA' => 'Atribuída',
        'EM_ANDAMENTO' => 'Em andamento',
        'AGUARDANDO_VALIDACAO' => 'Aguardando validação',
        'CORRECAO_SOLICITADA' => 'Correção solicitada',
        'ENCERRADA' => 'Encerrada',
        'CANCELADA' => 'Cancelada',
    ];
}

function ol_status_label(string $status): string
{
    return ol_statuses()[$status] ?? str_replace('_', ' ', $status);
}

function ol_status_class(string $status): string
{
    return [
        'ABERTA' => 'info',
        'ATRIBUIDA' => 'primary',
        'EM_ANDAMENTO' => 'warning',
        'AGUARDANDO_VALIDACAO' => 'warning',
        'CORRECAO_SOLICITADA' => 'danger',
        'ENCERRADA' => 'success',
        'CANCELADA' => 'secondary',
    ][$status] ?? 'secondary';
}

function ol_types(): array
{
    return [
        'ROTINA' => 'Rotina programada',
        'EMERGENCIAL' => 'Limpeza emergencial',
        'EVENTO' => 'Evento ou ação especial',
        'POS_OBRA' => 'Pós-obra ou manutenção',
        'REFORCO' => 'Reforço de equipe',
        'OUTRA' => 'Outra necessidade',
    ];
}

function ol_priorities(): array
{
    return ['BAIXA' => 'Baixa', 'MEDIA' => 'Média', 'ALTA' => 'Alta', 'CRITICA' => 'Crítica'];
}

function ol_generate_number(PDO $pdo): string
{
    $year = (int)date('Y');
    $started = !$pdo->inTransaction();
    if ($started) $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO limpeza_ordens_sequences(ano,ultimo_numero) VALUES(?,0) ON DUPLICATE KEY UPDATE ultimo_numero=ultimo_numero')->execute([$year]);
        $st = $pdo->prepare('SELECT ultimo_numero FROM limpeza_ordens_sequences WHERE ano=? FOR UPDATE');
        $st->execute([$year]);
        $next = (int)$st->fetchColumn() + 1;
        $pdo->prepare('UPDATE limpeza_ordens_sequences SET ultimo_numero=? WHERE ano=?')->execute([$next,$year]);
        if ($started) $pdo->commit();
        return sprintf('OL-%04d-%06d', $year, $next);
    } catch (Throwable $e) {
        if ($started && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function ol_get(PDO $pdo, int $id): ?array
{
    $olAtivaSql = exclusao_sql_nao_excluida($pdo,'limpeza_ordens','ol');
    $st = $pdo->prepare("SELECT ol.*,aq.nome_publico AS ambiente_nome,aq.token_publico AS ambiente_token,l.nome AS local_nome,
        sc.nome_colaborador AS supervisor_membro_nome,su.nome AS supervisor_usuario_nome,
        criador.nome AS criado_por_nome,solicitante.nome AS solicitante_nome,validador.nome AS validado_por_nome,
        mig.ordem_servico_id AS os_origem_id,mig.status_os_anterior,mig.os_encerrada,mig.observacao AS migracao_observacao,mig.criado_em AS migrado_em,
        osorig.numero_os AS os_origem_numero,osorig.titulo AS os_origem_titulo,osorig.status AS os_origem_status,
        migrador.nome AS migrado_por_nome,
        (SELECT GROUP_CONCAT(CASE WHEN p.responsavel_principal=1 THEN CONCAT(n.nome_colaborador,' (principal)') ELSE n.nome_colaborador END ORDER BY p.responsavel_principal DESC,n.nome_colaborador SEPARATOR ', ')
         FROM limpeza_ordem_participantes p JOIN limpeza_organograma_nos n ON n.id=p.membro_id WHERE p.ordem_limpeza_id=ol.id AND p.planejado=1) AS equipe_planejada,
        (SELECT GROUP_CONCAT(CASE WHEN p.responsavel_principal=1 THEN CONCAT(n.nome_colaborador,' (principal)') ELSE n.nome_colaborador END ORDER BY p.responsavel_principal DESC,n.nome_colaborador SEPARATOR ', ')
         FROM limpeza_ordem_participantes p JOIN limpeza_organograma_nos n ON n.id=p.membro_id WHERE p.ordem_limpeza_id=ol.id AND p.executou=1) AS equipe_executora
        FROM limpeza_ordens ol
        LEFT JOIN ambientes_qr aq ON aq.id=ol.ambiente_id
        LEFT JOIN locais l ON l.id=ol.local_id
        LEFT JOIN limpeza_organograma_nos sc ON sc.id=ol.supervisor_membro_id
        LEFT JOIN usuarios su ON su.id=sc.usuario_id
        LEFT JOIN usuarios criador ON criador.id=ol.criado_por
        LEFT JOIN usuarios solicitante ON solicitante.id=ol.solicitante_id
        LEFT JOIN usuarios validador ON validador.id=ol.validado_por
        LEFT JOIN limpeza_ordem_migracoes mig ON mig.ordem_limpeza_id=ol.id
        LEFT JOIN ordens_servico osorig ON osorig.id=mig.ordem_servico_id
        LEFT JOIN usuarios migrador ON migrador.id=mig.migrado_por
        WHERE ol.id=? AND $olAtivaSql LIMIT 1");
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function ol_participants(PDO $pdo, int $olId): array
{
    $st = $pdo->prepare("SELECT p.*,n.nome_colaborador,n.cargo,n.turno,n.setor,n.usuario_id,u.nome AS usuario_nome,u.email AS usuario_email
        FROM limpeza_ordem_participantes p
        JOIN limpeza_organograma_nos n ON n.id=p.membro_id
        LEFT JOIN usuarios u ON u.id=n.usuario_id
        WHERE p.ordem_limpeza_id=? ORDER BY p.responsavel_principal DESC,p.executou DESC,n.nome_colaborador");
    $st->execute([$olId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ol_items(PDO $pdo, int $olId): array
{
    $st = $pdo->prepare("SELECT i.*,t.descricao AS tarefa_plano_nome,g.nome AS grupo_nome
        FROM limpeza_ordem_itens i
        LEFT JOIN limpeza_plano_tarefas t ON t.id=i.tarefa_plano_id
        LEFT JOIN limpeza_plano_grupos g ON g.id=t.grupo_id
        WHERE i.ordem_limpeza_id=? ORDER BY i.ordem,i.id");
    $st->execute([$olId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ol_photos(PDO $pdo, int $olId): array
{
    $st = $pdo->prepare("SELECT f.*,u.nome AS usuario_nome FROM limpeza_ordem_fotos f LEFT JOIN usuarios u ON u.id=f.usuario_id WHERE f.ordem_limpeza_id=? ORDER BY f.criado_em,f.id");
    $st->execute([$olId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ol_history(PDO $pdo, int $olId): array
{
    $st = $pdo->prepare("SELECT h.*,u.nome AS usuario_nome FROM limpeza_ordem_historico h LEFT JOIN usuarios u ON u.id=h.usuario_id WHERE h.ordem_limpeza_id=? ORDER BY h.criado_em DESC,h.id DESC");
    $st->execute([$olId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function ol_migration_by_os(PDO $pdo, int $osId): ?array
{
    $st=$pdo->prepare("SELECT m.*,ol.numero_ol,ol.titulo AS ol_titulo,ol.status AS ol_status,u.nome AS migrado_por_nome
        FROM limpeza_ordem_migracoes m
        JOIN limpeza_ordens ol ON ol.id=m.ordem_limpeza_id
        LEFT JOIN usuarios u ON u.id=m.migrado_por
        WHERE m.ordem_servico_id=? LIMIT 1");
    $st->execute([$osId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function ol_source_os(PDO $pdo, int $osId, bool $lock=false): ?array
{
    $osAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico','os');
    $sql="SELECT os.*,l.nome AS local_nome,c.nome AS categoria_nome,s.nome AS solicitante_nome,g.nome AS gestor_nome
        FROM ordens_servico os
        JOIN locais l ON l.id=os.local_id
        JOIN categorias c ON c.id=os.categoria_id
        LEFT JOIN usuarios s ON s.id=os.solicitante_id
        LEFT JOIN usuarios g ON g.id=os.gestor_id
        WHERE os.id=? AND $osAtivaSql LIMIT 1" . ($lock ? ' FOR UPDATE' : '');
    $st=$pdo->prepare($sql);
    $st->execute([$osId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function ol_copy_os_images(PDO $pdo, int $osId, int $olId, int $userId): int
{
    $st=$pdo->prepare("SELECT tipo,arquivo_url,descricao FROM anexos_os WHERE ordem_servico_id=? ORDER BY id");
    $st->execute([$osId]);
    $insert=$pdo->prepare("INSERT INTO limpeza_ordem_fotos(ordem_limpeza_id,usuario_id,origem,tipo,arquivo_url,descricao,publica) VALUES(?,?,'GESTAO',?,?,?,0)");
    $count=0;
    foreach($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row){
        $ext=strtolower(pathinfo((string)$row['arquivo_url'],PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png','webp','gif'],true)) continue;
        $sourceType=strtoupper((string)($row['tipo'] ?? ''));
        $type=$sourceType==='FOTO_ANTES'?'ANTES':($sourceType==='FOTO_DEPOIS'?'DEPOIS':'DURANTE');
        $description=trim((string)($row['descricao'] ?? ''));
        $insert->execute([$olId,$userId,$type,(string)$row['arquivo_url'],$description!==''?$description:'Imagem migrada da OS de origem.']);
        $count++;
    }
    return $count;
}

function ol_add_history(PDO $pdo, int $olId, ?int $userId, ?string $oldStatus, string $newStatus, string $action, string $description): void
{
    $pdo->prepare('INSERT INTO limpeza_ordem_historico(ordem_limpeza_id,usuario_id,status_anterior,status_novo,acao,descricao) VALUES(?,?,?,?,?,?)')
        ->execute([$olId,$userId,$oldStatus,$newStatus,substr($action,0,80),$description]);
}

function ol_normalize_uploads(array $files): array
{
    if (!isset($files['name'])) return [];
    if (!is_array($files['name'])) return (($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) ? [] : [$files];
    $result = [];
    foreach ($files['name'] as $index => $name) {
        $error = $files['error'][$index] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) continue;
        $result[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $error,
            'size' => $files['size'][$index] ?? 0,
        ];
    }
    return $result;
}

function ol_store_photos(PDO $pdo, int $olId, int $userId, string $origin, string $type, array $files, bool $public = false): int
{
    $items = ol_normalize_uploads($files);
    if (!$items) return 0;
    if (count($items) > 10) throw new RuntimeException('Envie no máximo 10 fotos por operação.');
    $directory = dirname(__DIR__) . '/public/uploads/ol';
    $st = $pdo->prepare('INSERT INTO limpeza_ordem_fotos(ordem_limpeza_id,usuario_id,origem,tipo,arquivo_url,publica) VALUES(?,?,?,?,?,?)');
    $stored = 0;
    foreach ($items as $file) {
        $saved = upload_store($file, $directory, 'ol_' . $olId . '_');
        $st->execute([$olId,$userId,$origin,$type,'uploads/ol/' . $saved['name'],$public ? 1 : 0]);
        $stored++;
    }
    return $stored;
}

function ol_replace_team(PDO $pdo, int $olId, array $memberIds, int $principalId): void
{
    $memberIds = array_values(array_unique(array_filter(array_map('intval',$memberIds), static fn(int $id): bool => $id > 0)));
    if (!$memberIds) throw new RuntimeException('Selecione pelo menos um integrante da equipe de limpeza.');
    if (!in_array($principalId,$memberIds,true)) $principalId = $memberIds[0];

    $placeholders = implode(',',array_fill(0,count($memberIds),'?'));
    $verify = $pdo->prepare("SELECT id FROM limpeza_organograma_nos WHERE ativo=1 AND id IN ($placeholders)");
    $verify->execute($memberIds);
    $valid = array_map('intval',$verify->fetchAll(PDO::FETCH_COLUMN) ?: []);
    sort($valid); $expected=$memberIds; sort($expected);
    if ($valid !== $expected) throw new RuntimeException('Um dos integrantes selecionados não está ativo no organograma.');

    $existingSt = $pdo->prepare('SELECT membro_id,executou FROM limpeza_ordem_participantes WHERE ordem_limpeza_id=?');
    $existingSt->execute([$olId]);
    $existing=[];
    foreach($existingSt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) $existing[(int)$row['membro_id']] = (int)$row['executou'];

    $deleteParams=array_merge([$olId],$memberIds);
    $pdo->prepare("DELETE FROM limpeza_ordem_participantes WHERE ordem_limpeza_id=? AND planejado=1 AND membro_id NOT IN ($placeholders)")->execute($deleteParams);
    $upsert=$pdo->prepare("INSERT INTO limpeza_ordem_participantes(ordem_limpeza_id,membro_id,planejado,executou,responsavel_principal,papel)
        VALUES(?,?,1,0,?,?) ON DUPLICATE KEY UPDATE planejado=1,responsavel_principal=VALUES(responsavel_principal),papel=VALUES(papel)");
    foreach($memberIds as $memberId){
        $isPrincipal=$memberId===$principalId;
        $upsert->execute([$olId,$memberId,$isPrincipal?1:0,$isPrincipal?'RESPONSAVEL':'EXECUTOR']);
    }
}

function ol_set_actual_team(PDO $pdo, int $olId, array $memberIds, int $principalId): void
{
    $memberIds = array_values(array_unique(array_filter(array_map('intval',$memberIds), static fn(int $id): bool => $id > 0)));
    if (!$memberIds) throw new RuntimeException('Informe quem realmente executou a limpeza.');
    if (!in_array($principalId,$memberIds,true)) $principalId=$memberIds[0];

    $placeholders=implode(',',array_fill(0,count($memberIds),'?'));
    $verify=$pdo->prepare("SELECT id FROM limpeza_organograma_nos WHERE ativo=1 AND id IN ($placeholders)");
    $verify->execute($memberIds);
    $valid=array_map('intval',$verify->fetchAll(PDO::FETCH_COLUMN) ?: []); sort($valid); $expected=$memberIds; sort($expected);
    if($valid!==$expected) throw new RuntimeException('Um dos executores não está ativo no organograma.');

    $pdo->prepare('UPDATE limpeza_ordem_participantes SET executou=0,responsavel_principal=0 WHERE ordem_limpeza_id=?')->execute([$olId]);
    $upsert=$pdo->prepare("INSERT INTO limpeza_ordem_participantes(ordem_limpeza_id,membro_id,planejado,executou,responsavel_principal,papel)
        VALUES(?,?,0,1,?,?) ON DUPLICATE KEY UPDATE executou=1,responsavel_principal=VALUES(responsavel_principal),papel=VALUES(papel)");
    foreach($memberIds as $memberId){
        $isPrincipal=$memberId===$principalId;
        $upsert->execute([$olId,$memberId,$isPrincipal?1:0,$isPrincipal?'RESPONSAVEL':'EXECUTOR']);
    }
}

function ol_user_executed(PDO $pdo, int $olId, int $userId): bool
{
    $st=$pdo->prepare("SELECT 1 FROM limpeza_ordem_participantes p JOIN limpeza_organograma_nos n ON n.id=p.membro_id WHERE p.ordem_limpeza_id=? AND p.executou=1 AND n.usuario_id=? LIMIT 1");
    $st->execute([$olId,$userId]);
    return (bool)$st->fetchColumn();
}

function ol_notify_profiles(PDO $pdo, array $profiles, string $type, string $title, string $message, string $url, string $key): void
{
    if (!$profiles) return;
    $placeholders=implode(',',array_fill(0,count($profiles),'?'));
    $st=$pdo->prepare("SELECT id FROM usuarios WHERE status='ATIVO' AND perfil IN ($placeholders)");
    $st->execute($profiles);
    foreach(array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN) ?: []) as $userId){
        gestao_notificar($pdo,$userId,$type,$title,$message,$url,$key.'-'.$userId);
    }
}

function ol_notify_team(PDO $pdo, int $olId, string $type, string $title, string $message, string $url, string $key): void
{
    $st=$pdo->prepare("SELECT DISTINCT n.usuario_id FROM limpeza_ordem_participantes p JOIN limpeza_organograma_nos n ON n.id=p.membro_id WHERE p.ordem_limpeza_id=? AND n.usuario_id IS NOT NULL");
    $st->execute([$olId]);
    foreach(array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN) ?: []) as $userId){
        gestao_notificar($pdo,$userId,$type,$title,$message,$url,$key.'-'.$userId);
    }
}

function ol_sync_environment_record(PDO $pdo, array $ol, int $validatorUserId, bool $publicPhotos): ?int
{
    if (empty($ol['ambiente_id'])) return null;
    if (!ambiente_schema_ready($pdo) || !limpeza_schema_ready($pdo)) return null;

    $existing=$pdo->prepare('SELECT id FROM ambiente_registros WHERE ol_id=? LIMIT 1');
    $existing->execute([(int)$ol['id']]);
    $existingId=(int)($existing->fetchColumn() ?: 0);
    if($existingId) return $existingId;

    $participants=ol_participants($pdo,(int)$ol['id']);
    $executors=array_values(array_filter($participants,static fn(array $p): bool => (int)$p['executou']===1));
    if(!$executors) $executors=array_values(array_filter($participants,static fn(array $p): bool => (int)$p['planejado']===1));
    if(!$executors) return null;
    $principal=null;
    foreach($executors as $participant){ if((int)$participant['responsavel_principal']===1){$principal=$participant;break;} }
    $principal=$principal ?: $executors[0];
    $legacyUserId=(int)($principal['usuario_id'] ?: $ol['criado_por'] ?: $validatorUserId);
    $teamNames=implode(', ',array_column($executors,'nome_colaborador'));
    $items=ol_items($pdo,(int)$ol['id']);
    $itemNames=[];
    foreach($items as $item){ if($item['status_item']==='CONCLUIDO') $itemNames[]=$item['descricao']; }

    $pdo->prepare("INSERT INTO ambiente_registros(
        ambiente_id,ordem_servico_id,asg_id,registrado_por,executor_membro_id,executor_usuario_id,executor_nome,origem_lancamento,ol_id,
        status,iniciado_em,finalizado_em,observacao_asg,problema_encontrado,insumos_repostos,supervisor_id,validado_em,observacao_supervisor
    ) VALUES(?,NULL,?,?,?,?,?,'OL',?,'VALIDADA',?,?,?,?,?,?,NOW(),?)")
        ->execute([
            (int)$ol['ambiente_id'],$legacyUserId,$validatorUserId,(int)$principal['membro_id'],!empty($principal['usuario_id'])?(int)$principal['usuario_id']:null,
            (string)$principal['nome_colaborador'],(int)$ol['id'],$ol['iniciado_em'] ?: (!empty($ol['data_programada']) ? $ol['data_programada'].' 00:00:00' : ($ol['concluido_em'] ?: date('Y-m-d H:i:s'))),
            $ol['concluido_em'] ?: date('Y-m-d H:i:s'),$ol['observacao_execucao'] ?: ('Execução da '.$ol['numero_ol'].' por '.$teamNames),
            $ol['problema_encontrado'] ?: null,$ol['insumos_utilizados'] ?: null,$validatorUserId,
            $ol['parecer_validacao'] ?: ('Ordem de Limpeza '.$ol['numero_ol'].' validada e encerrada.')
        ]);
    $recordId=(int)$pdo->lastInsertId();

    $responsibleSt=$pdo->prepare('INSERT INTO ambiente_registro_responsaveis(registro_id,membro_id,usuario_id,nome_responsavel,responsavel_principal) VALUES(?,?,?,?,?)');
    foreach($executors as $participant){
        $responsibleSt->execute([$recordId,(int)$participant['membro_id'],!empty($participant['usuario_id'])?(int)$participant['usuario_id']:null,$participant['nome_colaborador'],(int)$participant['responsavel_principal']]);
    }

    $planSt=$pdo->prepare('INSERT IGNORE INTO ambiente_registro_plano_itens(registro_id,tarefa_id,cronograma_item_id,concluida,observacao) VALUES(?,?,?,?,?)');
    $scheduleFind=$pdo->prepare("SELECT ci.id FROM limpeza_cronogramas c JOIN limpeza_cronograma_itens ci ON ci.cronograma_id=c.id WHERE c.ano=? AND c.mes=? AND ci.dia=? AND ci.tarefa_id=? LIMIT 1");
    $scheduleFinish=$pdo->prepare("UPDATE limpeza_cronograma_itens SET status='CONCLUIDO',registro_id=?,responsavel_membro_id=COALESCE(responsavel_membro_id,?) WHERE id=?");
    $recordDate=new DateTimeImmutable((string)($ol['concluido_em'] ?: ($ol['data_programada'] ?: date('Y-m-d'))));
    $findAction=$pdo->prepare('SELECT id FROM ambiente_acoes WHERE nome=? LIMIT 1');
    $createAction=$pdo->prepare("INSERT INTO ambiente_acoes(nome,descricao,categoria,exige_foto,exige_observacao,ativo,criado_por) VALUES(?,?,'OL',0,0,1,?)");
    $linkAction=$pdo->prepare('INSERT IGNORE INTO ambiente_acao_locais(ambiente_id,acao_id,obrigatoria,ordem_exibicao,ativo) VALUES(?,?,0,999,1)');
    $recordAction=$pdo->prepare('INSERT IGNORE INTO ambiente_registro_itens(registro_id,acao_id,concluida,observacao) VALUES(?,?,1,?)');
    foreach($items as $item){
        if($item['status_item']!=='CONCLUIDO') continue;
        if(!empty($item['tarefa_plano_id'])){
            $scheduleFind->execute([(int)$recordDate->format('Y'),(int)$recordDate->format('n'),(int)$recordDate->format('j'),(int)$item['tarefa_plano_id']]);
            $scheduleItemId=(int)($scheduleFind->fetchColumn() ?: 0) ?: null;
            $planSt->execute([$recordId,(int)$item['tarefa_plano_id'],$scheduleItemId,1,$item['observacao_execucao'] ?: null]);
            if($scheduleItemId) $scheduleFinish->execute([$recordId,(int)$principal['membro_id'],$scheduleItemId]);
            continue;
        }
        $actionName=mb_substr(trim((string)$item['descricao']),0,160);
        $findAction->execute([$actionName]);
        $actionId=(int)($findAction->fetchColumn() ?: 0);
        if(!$actionId){
            $createAction->execute([$actionName,'Atividade criada a partir da '.$ol['numero_ol'],$validatorUserId]);
            $actionId=(int)$pdo->lastInsertId();
        }
        $linkAction->execute([(int)$ol['ambiente_id'],$actionId]);
        $recordAction->execute([$recordId,$actionId,$item['observacao_execucao'] ?: null]);
    }

    $photos=ol_photos($pdo,(int)$ol['id']);
    $photoSt=$pdo->prepare('INSERT INTO ambiente_registro_fotos(registro_id,usuario_id,origem,tipo,arquivo_url,descricao,publica) VALUES(?,?,?,?,?,?,?)');
    foreach($photos as $photo){
        $photoSt->execute([$recordId,(int)($photo['usuario_id'] ?: $validatorUserId),'OL',$photo['tipo'],$photo['arquivo_url'],$photo['descricao'],$publicPhotos?1:0]);
    }
    $pdo->prepare("INSERT INTO ambiente_validacoes(registro_id,supervisor_id,resultado,observacao,validado_em) VALUES(?,?,'APROVADA',?,NOW())")
        ->execute([$recordId,$validatorUserId,$ol['parecer_validacao'] ?: ('OL '.$ol['numero_ol'].' aprovada.')]);
    $pdo->prepare('UPDATE limpeza_ordens SET ambiente_registro_id=? WHERE id=?')->execute([$recordId,(int)$ol['id']]);
    return $recordId;
}
