<?php
declare(strict_types=1);

/**
 * GEOP V7.5 - Fluxo seguro de exclusão de ordens.
 *
 * Regras:
 * - Gestor / Gestor Síndico solicita exclusão.
 * - O solicitante não pode aprovar a própria solicitação.
 * - Outro Gestor / Gestor Síndico ou TI pode aprovar/reprovar.
 * - Apenas TI executa a exclusão aprovada.
 * - TI pode excluir diretamente, sem aprovação prévia.
 * - A exclusão é lógica (lixeira) e pode ser restaurada pelo TI.
 */

function exclusao_table_exists(PDO $pdo, string $table): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;

    // 1) Teste direto: funciona mesmo quando information_schema/SHOW preparado
    // é limitado pela hospedagem compartilhada/cPanel.
    try {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 0");
        return true;
    } catch (Throwable $e) {
        // Continua para os fallbacks abaixo.
    }

    // 2) SHOW TABLES usando quote() em vez de placeholder no SHOW.
    try {
        $st = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
        if ($st && $st->fetchColumn() !== false) return true;
    } catch (Throwable $e) {
        // Continua para o último fallback.
    }

    // 3) information_schema como último recurso.
    try {
        $st = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND LOWER(TABLE_NAME)=LOWER(?)'
        );
        $st->execute([$table]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function exclusao_column_exists(PDO $pdo, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) return false;
    if (!exclusao_table_exists($pdo, $table)) return false;

    // Teste direto da coluna: não depende de SHOW COLUMNS preparado.
    try {
        $pdo->query("SELECT `{$column}` FROM `{$table}` LIMIT 0");
        return true;
    } catch (Throwable $e) {
        // fallback
    }

    try {
        $st = $pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $pdo->quote($column));
        return $st && $st->fetchColumn() !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function exclusao_index_exists(PDO $pdo, string $table, string $index): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $index)) return false;
    if (!exclusao_table_exists($pdo, $table)) return false;
    try {
        $st = $pdo->query('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ' . $pdo->quote($index));
        return $st && $st->fetchColumn() !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function exclusao_ensure_column(PDO $pdo, string $table, string $column, string $definition, array &$resultado): void
{
    if (!exclusao_column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        $resultado[] = "{$table}.{$column}: criada";
    } else {
        $resultado[] = "{$table}.{$column}: já existe";
    }
}

function exclusao_ensure_index(PDO $pdo, string $table, string $index, string $columns, array &$resultado): void
{
    if (!exclusao_index_exists($pdo, $table, $index)) {
        $pdo->exec("CREATE INDEX `{$index}` ON `{$table}` ({$columns})");
        $resultado[] = "Índice {$index}: criado";
    } else {
        $resultado[] = "Índice {$index}: já existe";
    }
}

function exclusao_instalar_schema(PDO $pdo): array
{
    $resultado = [];

    /*
     * 1) Cria a tabela no formato V7.5 quando ela ainda não existe.
     * Se ela já existir no formato antigo, o bloco seguinte reconcilia as colunas.
     */
    $pdo->exec("CREATE TABLE IF NOT EXISTS ordem_solicitacoes_exclusao (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        entidade ENUM('ORDEM_SERVICO','LIMPEZA_ORDEM') NULL,
        ordem_id BIGINT UNSIGNED NOT NULL,
        numero_ordem VARCHAR(40) NULL,
        titulo_ordem VARCHAR(190) NULL,
        solicitado_por INT NOT NULL,
        motivo TEXT NOT NULL,
        origem ENUM('SOLICITACAO_GESTOR','DIRETA_TI') NOT NULL DEFAULT 'SOLICITACAO_GESTOR',
        status ENUM('PENDENTE','APROVADA','REPROVADA','EXECUTADA','CANCELADA') NOT NULL DEFAULT 'PENDENTE',
        analisado_por INT NULL,
        analisado_em DATETIME NULL,
        parecer TEXT NULL,
        executado_por INT NULL,
        executado_em DATETIME NULL,
        restaurado_por INT NULL,
        restaurado_em DATETIME NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $resultado[] = 'Tabela ordem_solicitacoes_exclusao: OK';

    /*
     * 2) Reconcilia a tabela criada por versões anteriores do patch.
     * Não remove colunas antigas; apenas cria o formato que a V7.5 usa e migra os valores.
     */
    $solCols = [
        'entidade' => "ENUM('ORDEM_SERVICO','LIMPEZA_ORDEM') NULL",
        'numero_ordem' => 'VARCHAR(40) NULL',
        'titulo_ordem' => 'VARCHAR(190) NULL',
        'origem' => "ENUM('SOLICITACAO_GESTOR','DIRETA_TI') NOT NULL DEFAULT 'SOLICITACAO_GESTOR'",
        'parecer' => 'TEXT NULL',
        'executado_por' => 'INT NULL',
        'executado_em' => 'DATETIME NULL',
        'restaurado_por' => 'INT NULL',
        'restaurado_em' => 'DATETIME NULL',
    ];
    foreach ($solCols as $column => $definition) {
        exclusao_ensure_column($pdo, 'ordem_solicitacoes_exclusao', $column, $definition, $resultado);
    }

    // Migração compatível com a tabela antiga, se essas colunas existirem.
    if (exclusao_column_exists($pdo, 'ordem_solicitacoes_exclusao', 'ordem_tipo')) {
        $pdo->exec("UPDATE ordem_solicitacoes_exclusao
            SET entidade = CASE UPPER(TRIM(ordem_tipo))
                WHEN 'OS' THEN 'ORDEM_SERVICO'
                WHEN 'OL' THEN 'LIMPEZA_ORDEM'
                ELSE entidade
            END
            WHERE entidade IS NULL");
        $resultado[] = 'Compatibilidade ordem_tipo → entidade: OK';
    }
    if (exclusao_column_exists($pdo, 'ordem_solicitacoes_exclusao', 'observacao_analise')) {
        $pdo->exec("UPDATE ordem_solicitacoes_exclusao
            SET parecer = COALESCE(parecer, observacao_analise)
            WHERE observacao_analise IS NOT NULL");
        $resultado[] = 'Compatibilidade observacao_analise → parecer: OK';
    }
    if (exclusao_column_exists($pdo, 'ordem_solicitacoes_exclusao', 'excluido_por')) {
        $pdo->exec("UPDATE ordem_solicitacoes_exclusao
            SET executado_por = COALESCE(executado_por, excluido_por)
            WHERE excluido_por IS NOT NULL");
        $resultado[] = 'Compatibilidade excluido_por → executado_por: OK';
    }
    if (exclusao_column_exists($pdo, 'ordem_solicitacoes_exclusao', 'excluido_em')) {
        $pdo->exec("UPDATE ordem_solicitacoes_exclusao
            SET executado_em = COALESCE(executado_em, excluido_em)
            WHERE excluido_em IS NOT NULL");
        $resultado[] = 'Compatibilidade excluido_em → executado_em: OK';
    }

    // Índices esperados pela V7.5.
    exclusao_ensure_index($pdo, 'ordem_solicitacoes_exclusao', 'idx_ose_entidade_ordem', '`entidade`,`ordem_id`', $resultado);
    exclusao_ensure_index($pdo, 'ordem_solicitacoes_exclusao', 'idx_ose_status_data', '`status`,`criado_em`', $resultado);
    exclusao_ensure_index($pdo, 'ordem_solicitacoes_exclusao', 'idx_ose_solicitante', '`solicitado_por`,`criado_em`', $resultado);
    exclusao_ensure_index($pdo, 'ordem_solicitacoes_exclusao', 'idx_ose_analisado_por_v75', '`analisado_por`', $resultado);
    exclusao_ensure_index($pdo, 'ordem_solicitacoes_exclusao', 'idx_ose_executado_por', '`executado_por`', $resultado);

    /*
     * 3) Campos de lixeira nas tabelas reais confirmadas no dump.
     */
    foreach ([
        'ordens_servico' => [
            'excluida_em' => 'DATETIME NULL',
            'excluida_por' => 'INT NULL',
            'exclusao_solicitacao_id' => 'BIGINT UNSIGNED NULL',
        ],
        'limpeza_ordens' => [
            'excluida_em' => 'DATETIME NULL',
            'excluida_por' => 'INT NULL',
            'exclusao_solicitacao_id' => 'BIGINT UNSIGNED NULL',
        ],
    ] as $table => $columns) {
        if (!exclusao_table_exists($pdo, $table)) {
            throw new RuntimeException("Tabela obrigatória não encontrada na conexão atual: {$table}");
        }
        foreach ($columns as $column => $definition) {
            exclusao_ensure_column($pdo, $table, $column, $definition, $resultado);
        }
    }

    exclusao_ensure_index($pdo, 'ordens_servico', 'idx_ordens_excluida_em', '`excluida_em`', $resultado);
    exclusao_ensure_index($pdo, 'limpeza_ordens', 'idx_limpeza_ordens_excluida_em', '`excluida_em`', $resultado);

    return $resultado;
}

function exclusao_schema_ready(PDO $pdo): bool
{
    $requiredSolicitacoes = [
        'entidade','ordem_id','numero_ordem','titulo_ordem','solicitado_por','motivo',
        'origem','status','analisado_por','analisado_em','parecer',
        'executado_por','executado_em','restaurado_por','restaurado_em',
        'criado_em','atualizado_em'
    ];

    if (!exclusao_table_exists($pdo, 'ordem_solicitacoes_exclusao')) return false;
    foreach ($requiredSolicitacoes as $column) {
        if (!exclusao_column_exists($pdo, 'ordem_solicitacoes_exclusao', $column)) return false;
    }

    foreach (['ordens_servico','limpeza_ordens'] as $table) {
        if (!exclusao_table_exists($pdo, $table)) return false;
        foreach (['excluida_em','excluida_por','exclusao_solicitacao_id'] as $column) {
            if (!exclusao_column_exists($pdo, $table, $column)) return false;
        }
    }

    return true;
}

function exclusao_sql_nao_excluida(PDO $pdo, string $table, string $alias = ''): string
{
    if (!exclusao_column_exists($pdo, $table, 'excluida_em')) return '1=1';
    $prefix = $alias !== '' ? $alias . '.' : '';
    return $prefix . 'excluida_em IS NULL';
}

function exclusao_entidade_config(string $entidade): array
{
    $entidade = strtoupper(trim($entidade));
    $map = [
        'ORDEM_SERVICO' => [
            'table' => 'ordens_servico',
            'numero' => 'numero_os',
            'titulo' => 'titulo',
            'detail' => 'ordem_detalhe',
            'list' => 'ordens',
            'label' => 'Ordem',
        ],
        'LIMPEZA_ORDEM' => [
            'table' => 'limpeza_ordens',
            'numero' => 'numero_ol',
            'titulo' => 'titulo',
            'detail' => 'ol_detalhe',
            'list' => 'ol',
            'label' => 'OL legada',
        ],
    ];
    if (!isset($map[$entidade])) {
        throw new InvalidArgumentException('Entidade de ordem inválida.');
    }
    return $map[$entidade] + ['entidade' => $entidade];
}

function exclusao_perfil_atual(?array $user = null): string
{
    if ($user === null && function_exists('current_user')) $user = current_user();
    return strtoupper((string)($user['perfil'] ?? ''));
}

function exclusao_pode_solicitar(?array $user = null): bool
{
    return in_array(exclusao_perfil_atual($user), ['GESTOR','GESTOR_SINDICO'], true);
}

function exclusao_pode_analisar(?array $user = null): bool
{
    return in_array(exclusao_perfil_atual($user), ['TI','GESTOR','GESTOR_SINDICO'], true);
}

function exclusao_pode_executar(?array $user = null): bool
{
    return exclusao_perfil_atual($user) === 'TI';
}

function exclusao_carregar_ordem(PDO $pdo, string $entidade, int $ordemId, bool $incluirExcluida = false): ?array
{
    if ($ordemId <= 0) return null;
    $cfg = exclusao_entidade_config($entidade);
    $table = $cfg['table'];
    $numero = $cfg['numero'];
    $titulo = $cfg['titulo'];

    $where = "id = ?";
    if (!$incluirExcluida) {
        $where .= ' AND ' . exclusao_sql_nao_excluida($pdo, $table);
    }

    $selectExtras = exclusao_column_exists($pdo, $table, 'excluida_em')
        ? ', excluida_em, excluida_por, exclusao_solicitacao_id'
        : ', NULL AS excluida_em, NULL AS excluida_por, NULL AS exclusao_solicitacao_id';

    $st = $pdo->prepare("SELECT id, `$numero` AS numero, `$titulo` AS titulo $selectExtras FROM `$table` WHERE $where LIMIT 1");
    $st->execute([$ordemId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['entidade'] = $cfg['entidade'];
    $row['detail'] = $cfg['detail'];
    $row['list'] = $cfg['list'];
    return $row;
}

function exclusao_solicitacao_ativa(PDO $pdo, string $entidade, int $ordemId): ?array
{
    if (!exclusao_table_exists($pdo, 'ordem_solicitacoes_exclusao')) return null;
    $st = $pdo->prepare("SELECT s.*,u.nome AS solicitado_por_nome,a.nome AS analisado_por_nome,e.nome AS executado_por_nome
        FROM ordem_solicitacoes_exclusao s
        LEFT JOIN usuarios u ON u.id=s.solicitado_por
        LEFT JOIN usuarios a ON a.id=s.analisado_por
        LEFT JOIN usuarios e ON e.id=s.executado_por
        WHERE s.entidade=? AND s.ordem_id=? AND s.status IN ('PENDENTE','APROVADA')
        ORDER BY s.id DESC LIMIT 1");
    $st->execute([$entidade,$ordemId]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

function exclusao_notificar_perfis(PDO $pdo, array $perfis, string $tipo, string $titulo, string $mensagem, string $url, string $chave, array $ignorarIds = []): void
{
    if (!function_exists('gestao_notificar') || !$perfis) return;
    $placeholders = implode(',', array_fill(0, count($perfis), '?'));
    try {
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE status='ATIVO' AND perfil IN ($placeholders)");
        $st->execute($perfis);
        foreach (array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []) as $uid) {
            if (in_array($uid, $ignorarIds, true)) continue;
            gestao_notificar($pdo, $uid, $tipo, $titulo, $mensagem, $url, $chave . '-' . $uid);
        }
    } catch (Throwable $e) {
        error_log('[GEOP exclusão notificação] ' . $e->getMessage());
    }
}

function exclusao_notificar_usuario(PDO $pdo, int $uid, string $tipo, string $titulo, string $mensagem, string $url, string $chave): void
{
    if ($uid <= 0 || !function_exists('gestao_notificar')) return;
    gestao_notificar($pdo, $uid, $tipo, $titulo, $mensagem, $url, $chave . '-' . $uid);
}


function exclusao_ordem_tipo_legacy(string $entidade): string
{
    $entidade = strtoupper(trim($entidade));
    return $entidade === 'LIMPEZA_ORDEM' ? 'OL' : 'OS';
}

function exclusao_solicitar(PDO $pdo, string $entidade, int $ordemId, int $usuarioId, string $motivo): int
{
    if (!exclusao_schema_ready($pdo)) throw new RuntimeException('Execute primeiro a atualização V7.5.');
    if (!exclusao_pode_solicitar()) throw new RuntimeException('Apenas Gestor ou Gestor Síndico pode solicitar exclusão.');
    $motivo = trim($motivo);
    if (mb_strlen($motivo) < 5) throw new RuntimeException('Informe um motivo com pelo menos 5 caracteres.');

    $ordem = exclusao_carregar_ordem($pdo, $entidade, $ordemId, false);
    if (!$ordem) throw new RuntimeException('A ordem não foi encontrada ou já está na lixeira.');

    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare("SELECT id FROM ordem_solicitacoes_exclusao
            WHERE entidade=? AND ordem_id=? AND status IN ('PENDENTE','APROVADA') FOR UPDATE");
        $lock->execute([$entidade,$ordemId]);
        if ($lock->fetchColumn()) throw new RuntimeException('Já existe uma solicitação de exclusão pendente/aprovada para esta ordem.');

        if (exclusao_column_exists($pdo, 'ordem_solicitacoes_exclusao', 'ordem_tipo')) {
            $st = $pdo->prepare("INSERT INTO ordem_solicitacoes_exclusao
                (entidade,ordem_tipo,ordem_id,numero_ordem,titulo_ordem,solicitado_por,motivo,origem,status)
                VALUES(?,?,?,?,?,?,?,'SOLICITACAO_GESTOR','PENDENTE')");
            $st->execute([
                $entidade,
                exclusao_ordem_tipo_legacy($entidade),
                $ordemId,
                $ordem['numero'],
                $ordem['titulo'],
                $usuarioId,
                $motivo
            ]);
        } else {
            $st = $pdo->prepare("INSERT INTO ordem_solicitacoes_exclusao
                (entidade,ordem_id,numero_ordem,titulo_ordem,solicitado_por,motivo,origem,status)
                VALUES(?,?,?,?,?,?,'SOLICITACAO_GESTOR','PENDENTE')");
            $st->execute([$entidade,$ordemId,$ordem['numero'],$ordem['titulo'],$usuarioId,$motivo]);
        }
        $id = (int)$pdo->lastInsertId();

        if (function_exists('auditar')) {
            auditar($pdo,'SOLICITAR_EXCLUSAO_ORDEM','ordem_solicitacoes_exclusao',$id,[],[
                'entidade'=>$entidade,'ordem_id'=>$ordemId,'numero'=>$ordem['numero'],'motivo'=>$motivo
            ]);
        }
        $pdo->commit();

        exclusao_notificar_perfis(
            $pdo,
            ['TI','GESTOR','GESTOR_SINDICO'],
            'EXCLUSAO_ORDEM_PENDENTE',
            'Exclusão de ordem aguardando análise',
            ($ordem['numero'] ?: '#'.$ordemId) . ' — ' . $ordem['titulo'],
            app_path('exclusoes_ordens'),
            'exclusao-ordem-pendente-'.$id,
            [$usuarioId]
        );
        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function exclusao_analisar(PDO $pdo, int $solicitacaoId, int $usuarioId, string $decisao, string $parecer = ''): void
{
    if (!exclusao_schema_ready($pdo)) throw new RuntimeException('Execute primeiro a atualização V7.5.');
    if (!exclusao_pode_analisar()) throw new RuntimeException('Sem permissão para analisar exclusões.');
    $decisao = strtoupper(trim($decisao));
    if (!in_array($decisao,['APROVAR','REPROVAR'],true)) throw new RuntimeException('Decisão inválida.');

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT * FROM ordem_solicitacoes_exclusao WHERE id=? FOR UPDATE");
        $st->execute([$solicitacaoId]);
        $req = $st->fetch(PDO::FETCH_ASSOC);
        if (!$req) throw new RuntimeException('Solicitação não encontrada.');
        if ($req['status'] !== 'PENDENTE') throw new RuntimeException('Esta solicitação não está pendente.');
        if ((int)$req['solicitado_por'] === $usuarioId) {
            throw new RuntimeException('O solicitante não pode aprovar ou reprovar a própria solicitação.');
        }

        $novo = $decisao === 'APROVAR' ? 'APROVADA' : 'REPROVADA';
        $pdo->prepare("UPDATE ordem_solicitacoes_exclusao
            SET status=?,analisado_por=?,analisado_em=NOW(),parecer=? WHERE id=?")
            ->execute([$novo,$usuarioId,trim($parecer) ?: null,$solicitacaoId]);

        if (function_exists('auditar')) {
            auditar($pdo,$novo === 'APROVADA' ? 'APROVAR_EXCLUSAO_ORDEM' : 'REPROVAR_EXCLUSAO_ORDEM',
                'ordem_solicitacoes_exclusao',$solicitacaoId,['status'=>'PENDENTE'],[
                    'status'=>$novo,'parecer'=>trim($parecer)
                ]);
        }
        $pdo->commit();

        $titulo = $novo === 'APROVADA' ? 'Exclusão aprovada' : 'Exclusão reprovada';
        exclusao_notificar_usuario(
            $pdo,(int)$req['solicitado_por'],'EXCLUSAO_ORDEM_'.$novo,$titulo,
            (string)($req['numero_ordem'] ?: '#'.$req['ordem_id']) . ($parecer !== '' ? ' — '.$parecer : ''),
            app_path('exclusoes_ordens'),'exclusao-ordem-'.$novo.'-'.$solicitacaoId
        );

        if ($novo === 'APROVADA') {
            exclusao_notificar_perfis(
                $pdo,['TI'],'EXCLUSAO_ORDEM_APROVADA','Exclusão aprovada aguardando TI',
                (string)($req['numero_ordem'] ?: '#'.$req['ordem_id']),
                app_path('exclusoes_ordens'),'exclusao-aprovada-ti-'.$solicitacaoId
            );
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function exclusao_aplicar_soft_delete(PDO $pdo, string $entidade, int $ordemId, int $usuarioId, int $solicitacaoId): void
{
    $cfg = exclusao_entidade_config($entidade);
    $table = $cfg['table'];
    $st = $pdo->prepare("UPDATE `$table`
        SET excluida_em=NOW(),excluida_por=?,exclusao_solicitacao_id=?
        WHERE id=? AND excluida_em IS NULL");
    $st->execute([$usuarioId,$solicitacaoId,$ordemId]);
    if ($st->rowCount() !== 1) throw new RuntimeException('A ordem não foi localizada ou já está na lixeira.');
}

function exclusao_executar(PDO $pdo, int $solicitacaoId, int $usuarioId): void
{
    if (!exclusao_schema_ready($pdo)) throw new RuntimeException('Execute primeiro a atualização V7.5.');
    if (!exclusao_pode_executar()) throw new RuntimeException('Apenas TI pode executar a exclusão.');

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT * FROM ordem_solicitacoes_exclusao WHERE id=? FOR UPDATE");
        $st->execute([$solicitacaoId]);
        $req = $st->fetch(PDO::FETCH_ASSOC);
        if (!$req) throw new RuntimeException('Solicitação não encontrada.');
        if ($req['status'] !== 'APROVADA') throw new RuntimeException('A solicitação precisa estar APROVADA.');

        exclusao_aplicar_soft_delete($pdo,(string)$req['entidade'],(int)$req['ordem_id'],$usuarioId,$solicitacaoId);
        $pdo->prepare("UPDATE ordem_solicitacoes_exclusao
            SET status='EXECUTADA',executado_por=?,executado_em=NOW() WHERE id=?")
            ->execute([$usuarioId,$solicitacaoId]);

        if (function_exists('auditar')) {
            auditar($pdo,'EXECUTAR_EXCLUSAO_ORDEM','ordem_solicitacoes_exclusao',$solicitacaoId,
                ['status'=>'APROVADA'],['status'=>'EXECUTADA','ordem_id'=>(int)$req['ordem_id'],'entidade'=>$req['entidade']]);
        }
        $pdo->commit();

        exclusao_notificar_usuario(
            $pdo,(int)$req['solicitado_por'],'EXCLUSAO_ORDEM_EXECUTADA','Ordem excluída pelo TI',
            (string)($req['numero_ordem'] ?: '#'.$req['ordem_id']),
            app_path('exclusoes_ordens'),'exclusao-executada-'.$solicitacaoId
        );
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function exclusao_excluir_direto(PDO $pdo, string $entidade, int $ordemId, int $usuarioId, string $motivo): int
{
    if (!exclusao_schema_ready($pdo)) throw new RuntimeException('Execute primeiro a atualização V7.5.');
    if (!exclusao_pode_executar()) throw new RuntimeException('Apenas TI pode excluir diretamente.');
    $motivo = trim($motivo);
    if (mb_strlen($motivo) < 5) throw new RuntimeException('Informe um motivo com pelo menos 5 caracteres.');

    $ordem = exclusao_carregar_ordem($pdo,$entidade,$ordemId,false);
    if (!$ordem) throw new RuntimeException('A ordem não foi encontrada ou já está na lixeira.');

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT * FROM ordem_solicitacoes_exclusao
            WHERE entidade=? AND ordem_id=? AND status IN ('PENDENTE','APROVADA')
            ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $st->execute([$entidade,$ordemId]);
        $req = $st->fetch(PDO::FETCH_ASSOC);

        if ($req) {
            $id = (int)$req['id'];
            $parecer = 'Exclusão direta executada pelo TI. Motivo: ' . $motivo;
            $pdo->prepare("UPDATE ordem_solicitacoes_exclusao
                SET status='EXECUTADA',analisado_por=?,analisado_em=NOW(),parecer=?,
                    executado_por=?,executado_em=NOW()
                WHERE id=?")
                ->execute([$usuarioId,$parecer,$usuarioId,$id]);
        } else {
            if (exclusao_column_exists($pdo, 'ordem_solicitacoes_exclusao', 'ordem_tipo')) {
                $stIns = $pdo->prepare("INSERT INTO ordem_solicitacoes_exclusao
                    (entidade,ordem_tipo,ordem_id,numero_ordem,titulo_ordem,solicitado_por,motivo,origem,status,
                     analisado_por,analisado_em,parecer,executado_por,executado_em)
                    VALUES(?,?,?,?,?,?,?,'DIRETA_TI','EXECUTADA',?,NOW(),'Exclusão direta pelo TI',?,NOW())");
                $stIns->execute([
                    $entidade,
                    exclusao_ordem_tipo_legacy($entidade),
                    $ordemId,
                    $ordem['numero'],
                    $ordem['titulo'],
                    $usuarioId,
                    $motivo,
                    $usuarioId,
                    $usuarioId
                ]);
            } else {
                $stIns = $pdo->prepare("INSERT INTO ordem_solicitacoes_exclusao
                    (entidade,ordem_id,numero_ordem,titulo_ordem,solicitado_por,motivo,origem,status,
                     analisado_por,analisado_em,parecer,executado_por,executado_em)
                    VALUES(?,?,?,?,?,?,'DIRETA_TI','EXECUTADA',?,NOW(),'Exclusão direta pelo TI',?,NOW())");
                $stIns->execute([$entidade,$ordemId,$ordem['numero'],$ordem['titulo'],$usuarioId,$motivo,$usuarioId,$usuarioId]);
            }
            $id = (int)$pdo->lastInsertId();
        }

        exclusao_aplicar_soft_delete($pdo,$entidade,$ordemId,$usuarioId,$id);

        if (function_exists('auditar')) {
            auditar($pdo,'EXCLUSAO_DIRETA_TI','ordem_solicitacoes_exclusao',$id,[],[
                'entidade'=>$entidade,'ordem_id'=>$ordemId,'numero'=>$ordem['numero'],'motivo'=>$motivo
            ]);
        }
        $pdo->commit();

        if ($req && (int)$req['solicitado_por'] !== $usuarioId) {
            exclusao_notificar_usuario(
                $pdo,(int)$req['solicitado_por'],'EXCLUSAO_ORDEM_EXECUTADA','Ordem excluída diretamente pelo TI',
                (string)($ordem['numero'] ?: '#'.$ordemId),
                app_path('exclusoes_ordens'),'exclusao-direta-'.$id
            );
        }

        return $id;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function exclusao_restaurar(PDO $pdo, string $entidade, int $ordemId, int $usuarioId): void
{
    if (!exclusao_schema_ready($pdo)) throw new RuntimeException('Execute primeiro a atualização V7.5.');
    if (!exclusao_pode_executar()) throw new RuntimeException('Apenas TI pode restaurar ordens.');

    $cfg = exclusao_entidade_config($entidade);
    $table = $cfg['table'];

    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT id,exclusao_solicitacao_id FROM `$table` WHERE id=? AND excluida_em IS NOT NULL FOR UPDATE");
        $st->execute([$ordemId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('A ordem não está na lixeira.');

        $pdo->prepare("UPDATE `$table`
            SET excluida_em=NULL,excluida_por=NULL,exclusao_solicitacao_id=NULL WHERE id=?")
            ->execute([$ordemId]);

        $sid = (int)($row['exclusao_solicitacao_id'] ?? 0);
        if ($sid > 0) {
            $pdo->prepare("UPDATE ordem_solicitacoes_exclusao
                SET restaurado_por=?,restaurado_em=NOW() WHERE id=?")
                ->execute([$usuarioId,$sid]);
        }

        if (function_exists('auditar')) {
            auditar($pdo,'RESTAURAR_ORDEM','ordem_solicitacoes_exclusao',$sid ?: null,[],[
                'entidade'=>$entidade,'ordem_id'=>$ordemId
            ]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function exclusao_status_label(string $status): string
{
    return [
        'PENDENTE'=>'Pendente',
        'APROVADA'=>'Aprovada — aguardando TI',
        'REPROVADA'=>'Reprovada',
        'EXECUTADA'=>'Executada',
        'CANCELADA'=>'Cancelada',
    ][$status] ?? $status;
}

function exclusao_status_class(string $status): string
{
    return [
        'PENDENTE'=>'warning',
        'APROVADA'=>'info',
        'REPROVADA'=>'danger',
        'EXECUTADA'=>'dark',
        'CANCELADA'=>'secondary',
    ][$status] ?? 'secondary';
}

function exclusao_render_card(PDO $pdo, string $entidade, int $ordemId, array $user): string
{
    if (!in_array(exclusao_perfil_atual($user), ['TI','GESTOR','GESTOR_SINDICO'], true)) return '';

    ob_start();
    if (!exclusao_schema_ready($pdo)) {
        if (exclusao_perfil_atual($user) === 'TI') {
            ?>
            <div class="card p-3 mb-3 border-warning">
                <h2 class="h5">Exclusão da ordem</h2>
                <div class="alert alert-warning mb-2">O módulo de exclusão ainda precisa ser ativado.</div>
                <a class="btn btn-warning w-100" href="<?= e(app_path('atualizar_exclusao_ordens_v7_5')) ?>">Ativar atualização V7.5</a>
            </div>
            <?php
        }
        return (string)ob_get_clean();
    }

    $req = exclusao_solicitacao_ativa($pdo,$entidade,$ordemId);
    ?>
    <div class="card p-3 mb-3 border-danger" id="exclusao-ordem">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
                <h2 class="h5 text-danger mb-1">Exclusão da ordem</h2>
                <div class="small text-muted">A exclusão envia a ordem para a lixeira e mantém auditoria e possibilidade de restauração pelo TI.</div>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(app_path('exclusoes_ordens')) ?>">Solicitações</a>
        </div>

        <?php if ($req): ?>
            <div class="alert alert-<?= e(exclusao_status_class((string)$req['status'])) ?> mt-3 mb-2">
                <strong><?= e(exclusao_status_label((string)$req['status'])) ?></strong><br>
                <span class="small">Solicitada por <?= e($req['solicitado_por_nome'] ?: ('Usuário #'.$req['solicitado_por'])) ?> em <?= e(date('d/m/Y H:i',strtotime((string)$req['criado_em']))) ?>.</span>
                <div class="small mt-1"><?= nl2br(e((string)$req['motivo'])) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($req && $req['status']==='PENDENTE' && exclusao_pode_analisar($user) && (int)$req['solicitado_por'] !== (int)$user['id']): ?>
            <form method="post" action="<?= e(app_path('exclusoes_ordens')) ?>" class="mt-2">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="solicitacao_id" value="<?= (int)$req['id'] ?>">
                <label class="form-label small">Parecer da análise</label>
                <textarea class="form-control form-control-sm mb-2" name="parecer" rows="2" placeholder="Observação opcional"></textarea>
                <div class="d-flex gap-2">
                    <button class="btn btn-success flex-fill" name="action" value="aprovar" onclick="return confirm('Aprovar esta solicitação de exclusão?')">Aprovar</button>
                    <button class="btn btn-outline-danger flex-fill" name="action" value="reprovar" onclick="return confirm('Reprovar esta solicitação de exclusão?')">Reprovar</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($req && $req['status']==='APROVADA' && exclusao_pode_executar($user)): ?>
            <form method="post" action="<?= e(app_path('exclusoes_ordens')) ?>" class="mt-2" onsubmit="return confirm('Enviar esta ordem para a lixeira agora?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="executar">
                <input type="hidden" name="solicitacao_id" value="<?= (int)$req['id'] ?>">
                <button class="btn btn-danger w-100">Executar exclusão aprovada</button>
            </form>
        <?php endif; ?>

        <?php if (!$req && exclusao_pode_solicitar($user)): ?>
            <form method="post" action="<?= e(app_path('exclusoes_ordens')) ?>" class="mt-3">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="solicitar">
                <input type="hidden" name="entidade" value="<?= e($entidade) ?>">
                <input type="hidden" name="ordem_id" value="<?= (int)$ordemId ?>">
                <label class="form-label">Motivo da exclusão *</label>
                <textarea class="form-control mb-2" name="motivo" rows="3" minlength="5" required placeholder="Explique por que esta ordem deve ser excluída"></textarea>
                <button class="btn btn-outline-danger w-100" onclick="return confirm('Enviar solicitação de exclusão para aprovação?')">Solicitar exclusão</button>
            </form>
        <?php elseif ($req && exclusao_pode_solicitar($user) && (int)$req['solicitado_por'] === (int)$user['id']): ?>
            <div class="small text-muted mt-2">Sua solicitação já está em análise. Você não pode aprovar a própria exclusão.</div>
        <?php endif; ?>

        <?php if (exclusao_pode_executar($user)): ?>
            <hr>
            <form method="post" action="<?= e(app_path('exclusoes_ordens')) ?>" onsubmit="return confirm('TI: excluir esta ordem diretamente e enviá-la para a lixeira?');">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="excluir_direto">
                <input type="hidden" name="entidade" value="<?= e($entidade) ?>">
                <input type="hidden" name="ordem_id" value="<?= (int)$ordemId ?>">
                <label class="form-label">Exclusão direta pelo TI *</label>
                <textarea class="form-control mb-2" name="motivo" rows="2" minlength="5" required placeholder="Motivo obrigatório"></textarea>
                <button class="btn btn-danger w-100">Excluir diretamente</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
    return (string)ob_get_clean();
}
