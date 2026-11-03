<?php
declare(strict_types=1);
require_once __DIR__ . '/exclusao_ordens.php';

function gestao_column_exists(PDO $pdo, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) return false;
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $st->execute([$column]);
        if ($st->fetch(PDO::FETCH_ASSOC)) return true;
    } catch (Throwable $e) {}
    try {
        $pdo->query("SELECT `$column` FROM `$table` LIMIT 0");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function gestao_add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table . $column) || gestao_column_exists($pdo, $table, $column)) return;
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    } catch (Throwable $e) {
        error_log('[GEOP gestão schema] ' . $e->getMessage());
    }
}

function gestao_ensure_schema(PDO $pdo): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $queries = [
        "CREATE TABLE IF NOT EXISTS notificacoes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL,
            tipo VARCHAR(60) NOT NULL, titulo VARCHAR(180) NOT NULL, mensagem TEXT NULL,
            url VARCHAR(255) NULL, chave VARCHAR(190) NULL, lida_em DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_notificacao_chave_usuario(usuario_id,chave),
            KEY idx_notificacao_usuario(usuario_id,lida_em,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS usuario_especialidades (
            usuario_id INT NOT NULL, especialidade VARCHAR(100) NOT NULL, nivel TINYINT UNSIGNED DEFAULT 1,
            PRIMARY KEY(usuario_id,especialidade)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS estoque_reservas (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ordem_servico_id INT NOT NULL, material_id INT NOT NULL, usuario_id INT NULL,
            quantidade DECIMAL(12,2) NOT NULL, custo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(30) NOT NULL DEFAULT 'RESERVADA', justificativa TEXT NULL,
            aprovado_por INT NULL, aprovado_em DATETIME NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, atualizado_em DATETIME NULL,
            KEY idx_reserva_os(ordem_servico_id,status), KEY idx_reserva_material(material_id,status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS equipe_estado (
            usuario_id INT NOT NULL PRIMARY KEY, estado VARCHAR(30) NOT NULL DEFAULT 'DISPONIVEL',
            capacidade_maxima SMALLINT NOT NULL DEFAULT 5, observacao VARCHAR(255) NULL,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS escalas_servico (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, usuario_id INT NOT NULL,
            funcao VARCHAR(80) NOT NULL DEFAULT 'Agente de Manutenção',
            posto VARCHAR(100) NOT NULL DEFAULT 'Manutenção', data_servico DATE NOT NULL,
            tipo VARCHAR(20) NOT NULL DEFAULT 'TRABALHO', jornada_horas SMALLINT NOT NULL DEFAULT 12,
            hora_inicio TIME NULL, hora_fim TIME NULL, intervalo_inicio TIME NULL, intervalo_fim TIME NULL,
            criado_por INT NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_escala_usuario_data(usuario_id,data_servico),
            KEY idx_escala_mes(data_servico,posto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS feriados_operacionais (
            id INT AUTO_INCREMENT PRIMARY KEY, data DATE NOT NULL, nome VARCHAR(160) NOT NULL,
            local_id INT NULL, ativo TINYINT(1) NOT NULL DEFAULT 1, UNIQUE KEY uq_feriado(data,local_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS ativos_medidores (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ativo_id BIGINT UNSIGNED NOT NULL,
            tipo VARCHAR(50) NOT NULL, valor_atual DECIMAL(18,2) NOT NULL DEFAULT 0,
            limite_proxima_os DECIMAL(18,2) NULL, incremento_ciclo DECIMAL(18,2) NULL,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_medidor_ativo(ativo_id,tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS ativo_falhas (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ativo_id BIGINT UNSIGNED NOT NULL,
            ordem_servico_id INT NOT NULL, categoria_id INT NULL, resumo VARCHAR(255) NULL,
            registrada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_falha_ativo(ativo_id,registrada_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS conhecimento_avaliacoes (
            artigo_id BIGINT UNSIGNED NOT NULL, usuario_id INT NOT NULL, util TINYINT(1) NOT NULL,
            comentario VARCHAR(500) NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL, PRIMARY KEY(artigo_id,usuario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS os_status_tempos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL,
            status VARCHAR(40) NOT NULL, iniciado_em DATETIME NOT NULL, finalizado_em DATETIME NULL,
            segundos BIGINT UNSIGNED NULL, usuario_id INT NULL,
            KEY idx_status_tempo_os(ordem_servico_id,iniciado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS sla_escalonamentos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ordem_servico_id INT NOT NULL,
            nivel VARCHAR(30) NOT NULL, destinatario_id INT NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_sla_nivel(ordem_servico_id,nivel,destinatario_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($queries as $sql) {
        try { $pdo->exec($sql); } catch (Throwable $e) { error_log('[GEOP gestão schema] ' . $e->getMessage()); }
    }

    gestao_add_column($pdo, 'notificacoes', 'chave', 'VARCHAR(190) NULL');
    gestao_add_column($pdo, 'notificacoes', 'lida_em', 'DATETIME NULL');
    gestao_add_column($pdo, 'notificacoes', 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    gestao_add_column($pdo, 'manutencoes_preventivas', 'tipo_gatilho', "VARCHAR(20) NOT NULL DEFAULT 'CALENDARIO'");
    gestao_add_column($pdo, 'manutencoes_preventivas', 'ativo_id', 'BIGINT UNSIGNED NULL');
    gestao_add_column($pdo, 'manutencoes_preventivas', 'medidor_tipo', 'VARCHAR(50) NULL');
    gestao_add_column($pdo, 'manutencoes_preventivas', 'medidor_limite', 'DECIMAL(18,2) NULL');
    gestao_add_column($pdo, 'ordens_servico', 'reabertura_contagem', 'INT NOT NULL DEFAULT 0');
    gestao_add_column($pdo, 'escalas_servico', 'funcao', "VARCHAR(80) NOT NULL DEFAULT 'Agente de Manutenção'");
    try { $pdo->exec("CREATE UNIQUE INDEX uq_notificacao_chave_usuario ON notificacoes(usuario_id,chave)"); } catch (Throwable $e) {}
    try { $pdo->exec("CREATE UNIQUE INDEX uq_medidor_ativo ON ativos_medidores(ativo_id,tipo)"); } catch (Throwable $e) {}
}

function gestao_notificar(PDO $pdo, int $usuarioId, string $tipo, string $titulo, string $mensagem, string $url = '', string $chave = ''): void
{
    if ($usuarioId <= 0) return;
    gestao_ensure_schema($pdo);
    $chave = $chave !== '' ? substr($chave, 0, 190) : null;
    try {
        $st = $pdo->prepare("INSERT INTO notificacoes(usuario_id,tipo,titulo,mensagem,url,chave,created_at)
            VALUES(?,?,?,?,?,?,NOW())
            ON DUPLICATE KEY UPDATE titulo=VALUES(titulo),mensagem=VALUES(mensagem),url=VALUES(url)");
        $st->execute([$usuarioId,$tipo,substr($titulo,0,180),$mensagem,$url,$chave]);
    } catch (Throwable $e) {
        error_log('[GEOP notificação] ' . $e->getMessage());
    }
}

function gestao_perfis_gestao(): array
{
    return ['GESTOR','GESTOR_SINDICO','DIRETOR_EXECUTIVO','COORDENADOR_OPERACOES_OBRAS'];
}

function gestao_perfis_ti(): array
{
    return ['TI'];
}

function gestao_perfis_cftv(): array
{
    return ['CFTV','CONTROLE_ACESSO'];
}

function gestao_usuarios_perfis(PDO $pdo, array $perfis): array
{
    $perfis = array_values(array_unique(array_filter(array_map(
        static fn($p): string => strtoupper(trim((string)$p)),
        $perfis
    ))));
    if (!$perfis) return [];

    try {
        $ph = implode(',', array_fill(0, count($perfis), '?'));
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE status='ATIVO' AND perfil IN ($ph) ORDER BY id");
        $st->execute($perfis);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
    } catch (Throwable $e) {
        error_log('[GEOP perfis notificação] '.$e->getMessage());
        return [];
    }
}

function gestao_notificar_perfis(PDO $pdo, array $perfis, string $tipo, string $titulo, string $mensagem, string $url = '', string $chave = '', array $ignorarIds = []): void
{
    $ignorarIds = array_map('intval', $ignorarIds);
    foreach (gestao_usuarios_perfis($pdo, $perfis) as $uid) {
        if (in_array($uid, $ignorarIds, true)) continue;
        $chaveUsuario = $chave !== '' ? $chave.'-'.$uid : '';
        gestao_notificar($pdo, $uid, $tipo, $titulo, $mensagem, $url, $chaveUsuario);
    }
}

function gestao_usuarios_gestao(PDO $pdo): array
{
    // Importante: TI não recebe automaticamente alertas operacionais/financeiros
    // apenas por ser administrador do sistema. Alertas de TI devem ser enviados
    // explicitamente ao perfil TI.
    return gestao_usuarios_perfis($pdo, gestao_perfis_gestao());
}

function gestao_notificacao_perfis_restritos(string $tipo): ?array
{
    $tipo = strtoupper(trim($tipo));

    // CFTV / Controle de Acesso: evento já aprovado e pronto para operação.
    if (in_array($tipo, ['ACESSO_APROVADO','LIBERACAO_APROVADA','CFTV_ALERTA'], true)) {
        return gestao_perfis_cftv();
    }

    // Aprovação da solicitação é decisão de gestão, não operação do CFTV.
    if ($tipo === 'ACESSO_PENDENTE') {
        return gestao_perfis_gestao();
    }

    // Alertas de gestão / financeiro / estoque.
    if (in_array($tipo, [
        'ESTOQUE_MINIMO',
        'MATERIAL_APROVACAO',
        'COMPRA_APROVACAO',
        'APROVACOES_PENDENTES',
        'OS_REABERTA',
    ], true)) {
        return gestao_perfis_gestao();
    }

    // Solicitação de exclusão pode ser analisada pela gestão ou TI.
    if ($tipo === 'EXCLUSAO_ORDEM_PENDENTE') {
        return array_values(array_unique(array_merge(gestao_perfis_gestao(), gestao_perfis_ti())));
    }

    // Depois de aprovada, a exclusão entra na fila administrativa do TI.
    if ($tipo === 'EXCLUSAO_ORDEM_APROVADA') {
        return gestao_perfis_ti();
    }

    // Segurança, saúde do sistema, backup e integridade são pertinentes ao TI.
    foreach (['SISTEMA_','SEGURANCA_','BACKUP_','INTEGRIDADE_','TI_'] as $prefixo) {
        if (str_starts_with($tipo, $prefixo)) return gestao_perfis_ti();
    }

    // null = notificação pessoal/contextual; respeita o usuario_id gravado.
    return null;
}

function gestao_notificacao_pertinente_perfil(string $tipo, string $perfil): bool
{
    $restritos = gestao_notificacao_perfis_restritos($tipo);
    if ($restritos === null) return true;
    return in_array(strtoupper(trim($perfil)), $restritos, true);
}

function gestao_usuario_perfil(PDO $pdo, int $usuarioId): string
{
    if ($usuarioId <= 0) return '';
    try {
        $st = $pdo->prepare("SELECT perfil FROM usuarios WHERE id=? LIMIT 1");
        $st->execute([$usuarioId]);
        return strtoupper(trim((string)$st->fetchColumn()));
    } catch (Throwable $e) {
        return '';
    }
}

function gestao_notificacoes_resumo_usuario(PDO $pdo, int $usuarioId): array
{
    gestao_ensure_schema($pdo);
    $perfil = gestao_usuario_perfil($pdo, $usuarioId);

    try {
        $st = $pdo->prepare("SELECT id,tipo,lida_em FROM notificacoes WHERE usuario_id=? ORDER BY id DESC LIMIT 500");
        $st->execute([$usuarioId]);

        $maxId = 0;
        $unread = 0;
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            if (!gestao_notificacao_pertinente_perfil((string)($row['tipo'] ?? ''), $perfil)) continue;
            $maxId = max($maxId, (int)($row['id'] ?? 0));
            if (empty($row['lida_em'])) $unread++;
        }
        return ['max_id'=>$maxId,'unread'=>$unread];
    } catch (Throwable $e) {
        return ['max_id'=>0,'unread'=>0];
    }
}

function gestao_registrar_tempo_status(PDO $pdo, int $osId, string $statusAnterior, string $statusNovo, ?int $usuarioId): void
{
    gestao_ensure_schema($pdo);
    try {
        $pdo->prepare("UPDATE os_status_tempos SET finalizado_em=NOW(),
            segundos=TIMESTAMPDIFF(SECOND,iniciado_em,NOW())
            WHERE ordem_servico_id=? AND finalizado_em IS NULL")->execute([$osId]);
        $pdo->prepare("INSERT INTO os_status_tempos(ordem_servico_id,status,iniciado_em,usuario_id)
            VALUES(?,?,NOW(),?)")->execute([$osId,$statusNovo,$usuarioId]);
    } catch (Throwable $e) {
        error_log('[GEOP tempo status] ' . $e->getMessage());
    }
}

function gestao_recomendar_agentes(PDO $pdo, int $categoriaId, int $localId, int $limite = 5): array
{
    gestao_ensure_schema($pdo);
    try {
        $ordemAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico','os');
        $st = $pdo->prepare("SELECT u.id,u.nome,COALESCE(ee.estado,'DISPONIVEL') estado,
            COALESCE(ee.capacidade_maxima,5) capacidade_maxima,
            COUNT(DISTINCT os.id) carga,
            SUM(CASE WHEN c.id IS NOT NULL THEN COALESCE(ue.nivel,1) ELSE 0 END) especialidade_score
            FROM usuarios u
            LEFT JOIN equipe_estado ee ON ee.usuario_id=u.id
            LEFT JOIN usuario_especialidades ue ON ue.usuario_id=u.id
            LEFT JOIN categorias c ON c.id=? AND LOWER(ue.especialidade)=LOWER(c.nome)
            LEFT JOIN ordens_servico os ON os.agente_id=u.id
              AND $ordemAtivaSql
              AND os.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','CANCELADA')
            WHERE u.status='ATIVO' AND u.perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS')
              AND COALESCE(ee.estado,'DISPONIVEL') NOT IN ('AUSENTE','FERIAS','AFASTADO')
            GROUP BY u.id,u.nome,ee.estado,ee.capacidade_maxima
            HAVING carga < capacidade_maxima
            ORDER BY especialidade_score DESC,
              CASE estado WHEN 'DISPONIVEL' THEN 0 WHEN 'EM_ATENDIMENTO' THEN 1 ELSE 2 END,
              carga ASC,u.nome LIMIT ?");
        $st->bindValue(1,$categoriaId,PDO::PARAM_INT);
        $st->bindValue(2,max(1,$limite),PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function gestao_movimentar_material_os(PDO $pdo, int $osId, string $nome, float $quantidade, ?int $usuarioId, bool $somenteReservar = false): array
{
    gestao_ensure_schema($pdo);
    if ($osId<=0 || $quantidade<=0 || trim($nome)==='') return ['status'=>'INVALIDO','custo'=>0.0];
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT * FROM estoque_materiais WHERE nome=? AND ativo=1 LIMIT 1 FOR UPDATE");
        $st->execute([trim($nome)]);
        $material = $st->fetch(PDO::FETCH_ASSOC);
        if (!$material) {
            $pdo->rollBack();
            return ['status'=>'NAO_CADASTRADO','custo'=>0.0];
        }
        $st = $pdo->prepare("SELECT COALESCE(SUM(quantidade),0) FROM estoque_reservas
            WHERE material_id=? AND status='RESERVADA'");
        $st->execute([(int)$material['id']]);
        $reservado = (float)$st->fetchColumn();
        $disponivel = (float)$material['quantidade_atual'] - $reservado;
        $custo = (float)$material['custo_unitario'] * $quantidade;

        if ($disponivel < $quantidade) {
            $justificativa = 'Saldo disponível: '.$disponivel.'. Solicitado: '.$quantidade.'.';
            $pdo->prepare("INSERT INTO estoque_reservas
                (ordem_servico_id,material_id,usuario_id,quantidade,custo_unitario,status,justificativa)
                VALUES(?,?,?,?,?,'APROVACAO_NEGATIVO',?)")
                ->execute([$osId,$material['id'],$usuarioId,$quantidade,$material['custo_unitario'],$justificativa]);
            $reservaId = (int)$pdo->lastInsertId();
            try {
                $pdo->prepare("INSERT INTO aprovacoes(tipo,entidade,entidade_id,solicitante_id,status,observacao)
                    VALUES('ESTOQUE_NEGATIVO','estoque_reserva',?,?,'PENDENTE',?)")
                    ->execute([$reservaId,$usuarioId,$justificativa]);
            } catch (Throwable $e) {}
            $pdo->commit();
            foreach (gestao_usuarios_gestao($pdo) as $uid) {
                gestao_notificar($pdo,$uid,'MATERIAL_APROVACAO','Aprovação de estoque negativo',
                    $nome.' para a OS #'.$osId.'. '.$justificativa,app_path('gestao',['tab'=>'estoque']),
                    'estoque-aprovacao-'.$reservaId.'-'.$uid);
            }
            return ['status'=>'AGUARDANDO_APROVACAO','custo'=>0.0,'reserva_id'=>$reservaId,'disponivel'=>$disponivel];
        }

        $status = $somenteReservar ? 'RESERVADA' : 'CONSUMIDA';
        $pdo->prepare("INSERT INTO estoque_reservas
            (ordem_servico_id,material_id,usuario_id,quantidade,custo_unitario,status)
            VALUES(?,?,?,?,?,?)")->execute([$osId,$material['id'],$usuarioId,$quantidade,$material['custo_unitario'],$status]);
        if (!$somenteReservar) {
            $pdo->prepare("UPDATE estoque_materiais SET quantidade_atual=quantidade_atual-?,atualizado_em=NOW() WHERE id=?")
                ->execute([$quantidade,$material['id']]);
            $pdo->prepare("INSERT INTO movimentacoes_estoque
                (material_id,ordem_servico_id,usuario_id,tipo,quantidade,custo_unitario,observacao)
                VALUES(?,?,?,'SAIDA_OS',?,?,?)")
                ->execute([$material['id'],$osId,$usuarioId,$quantidade,$material['custo_unitario'],'Consumo integrado na OS']);
            if (gestao_column_exists($pdo,'ordens_servico','custo_materiais')) {
                $pdo->prepare("UPDATE ordens_servico SET custo_materiais=COALESCE(custo_materiais,0)+? WHERE id=?")
                    ->execute([$custo,$osId]);
            }
        }
        $pdo->commit();
        return ['status'=>$status,'custo'=>$somenteReservar?0.0:$custo,'reserva_id'=>(int)$pdo->lastInsertId()];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function gestao_gerar_artigo_os(PDO $pdo, int $osId, int $responsavelId): int
{
    gestao_ensure_schema($pdo);
    try {
        $ordemAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico','os');
        $st = $pdo->prepare("SELECT os.*,c.nome categoria_nome FROM ordens_servico os
            LEFT JOIN categorias c ON c.id=os.categoria_id WHERE os.id=? AND $ordemAtivaSql AND os.status='ENCERRADA' LIMIT 1");
        $st->execute([$osId]);
        $os = $st->fetch(PDO::FETCH_ASSOC);
        if (!$os) return 0;
        $solucao = trim((string)($os['observacao_aprovacao'] ?? ''));
        if ($solucao === '') $solucao = trim((string)($os['solicitacao_encerramento_obs'] ?? ''));
        if (strlen($solucao)<10) return 0;
        $st = $pdo->prepare("SELECT id FROM conhecimento_artigos WHERE origem_os_id=? LIMIT 1");
        $st->execute([$osId]);
        $existente = (int)$st->fetchColumn();
        if ($existente) return $existente;
        $titulo = 'Solução histórica — '.($os['titulo'] ?? $os['numero_os']);
        $pdo->prepare("INSERT INTO conhecimento_artigos
            (titulo,categoria_id,conteudo,problema,solucao,palavras_chave,origem_os_id,
             versao,responsavel_id,status,created_at,updated_at)
            VALUES(?,?,?,?,?,?,?,'1.0',?,'PUBLICADO',NOW(),NOW())")
            ->execute([$titulo,$os['categoria_id'],$solucao,$os['descricao'],$solucao,
                implode(', ',array_filter([$os['categoria_nome']??'',$os['titulo']??'',$os['numero_os']??''])),$osId,$responsavelId]);
        return (int)$pdo->lastInsertId();
    } catch (Throwable $e) {
        error_log('[GEOP conhecimento automático] '.$e->getMessage());
        return 0;
    }
}

function gestao_processar_automacoes(PDO $pdo): void
{
    gestao_ensure_schema($pdo);
    $last = (int)($_SESSION['gestao_automacoes_em'] ?? 0);
    if ($last > time() - 60) return;
    $_SESSION['gestao_automacoes_em'] = time();
    $gestores = gestao_usuarios_gestao($pdo);

    try {
        $baixos = $pdo->query("SELECT id,nome,quantidade_atual,estoque_minimo FROM estoque_materiais
            WHERE ativo=1 AND quantidade_atual<=estoque_minimo")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($baixos as $m) foreach ($gestores as $uid) {
            gestao_notificar($pdo,$uid,'ESTOQUE_MINIMO','Estoque mínimo',
                $m['nome'].' está com '.$m['quantidade_atual'].' (mínimo '.$m['estoque_minimo'].').',
                app_path('estoque'),'estoque-minimo-'.$m['id']);
        }

        $ordemAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico');
        $sla = $pdo->query("SELECT id,numero_os,titulo,gestor_id,agente_id,prioridade,
            COALESCE(sla_vencimento,prazo) vencimento
            FROM ordens_servico WHERE $ordemAtivaSql
              AND status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','CANCELADA')
              AND COALESCE(sla_vencimento,prazo) IS NOT NULL
              AND COALESCE(sla_vencimento,prazo)<=DATE_ADD(NOW(),INTERVAL 6 HOUR)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sla as $os) {
            $vencida = strtotime((string)$os['vencimento']) < time();
            $nivel = $vencida ? 'VENCIDA' : 'PROXIMA';
            $destinos = array_unique(array_filter([(int)$os['gestor_id'],(int)$os['agente_id']]));
            if ($vencida || empty($os['agente_id']) || $os['prioridade']==='CRITICA') $destinos = array_unique(array_merge($destinos,$gestores));
            foreach ($destinos as $uid) {
                $pdo->prepare("INSERT IGNORE INTO sla_escalonamentos(ordem_servico_id,nivel,destinatario_id)
                    VALUES(?,?,?)")->execute([(int)$os['id'],$nivel,(int)$uid]);
                gestao_notificar($pdo,$uid,'SLA_'.$nivel,
                    $vencida ? 'SLA vencido' : 'SLA próximo do vencimento',
                    $os['numero_os'].' — '.$os['titulo'],app_path('ordem_detalhe',['id'=>$os['id']]),
                    'sla-'.strtolower($nivel).'-'.$os['id'].'-'.$uid);
            }
        }

        $preventivas = $pdo->query("SELECT id,titulo,proxima_execucao,criado_por,agente_id
            FROM manutencoes_preventivas WHERE ativo=1
              AND proxima_execucao BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE,INTERVAL 7 DAY)")
            ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($preventivas as $p) foreach (array_unique(array_filter([(int)$p['criado_por'],(int)$p['agente_id']])) as $uid) {
            gestao_notificar($pdo,$uid,'PREVENTIVA_PROXIMA','Preventiva próxima',
                $p['titulo'].' vence em '.$p['proxima_execucao'],app_path('preventivas'),
                'preventiva-'.$p['id'].'-'.$p['proxima_execucao']);
        }

        if (function_exists('geop_v8_notify_operations')) {
            geop_v8_notify_operations($pdo);
        }
    } catch (Throwable $e) {
        error_log('[GEOP automações] ' . $e->getMessage());
    }
}

function gestao_notificacoes_usuario(PDO $pdo, int $usuarioId, int $limite = 20): array
{
    gestao_ensure_schema($pdo);
    $limite = max(1, min(200, $limite));
    $perfil = gestao_usuario_perfil($pdo, $usuarioId);

    try {
        // Busca uma janela maior para que o filtro de pertinência não reduza
        // artificialmente a quantidade exibida.
        $fetchLimit = min(1000, max($limite * 8, 100));
        $st = $pdo->prepare("SELECT * FROM notificacoes WHERE usuario_id=?
            ORDER BY (lida_em IS NULL) DESC,created_at DESC,id DESC LIMIT ?");
        $st->bindValue(1,$usuarioId,PDO::PARAM_INT);
        $st->bindValue(2,$fetchLimit,PDO::PARAM_INT);
        $st->execute();

        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            if (!gestao_notificacao_pertinente_perfil((string)($row['tipo'] ?? ''), $perfil)) continue;
            $out[] = $row;
            if (count($out) >= $limite) break;
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

