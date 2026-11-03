<?php
declare(strict_types=1);

/**
 * GEOP V7.6 — Conferência de custo na finalização da OS.
 *
 * A análise automática é indicativa:
 * - descrição/título/observações;
 * - materiais já registrados;
 * - movimentações/custos já existentes;
 * - descrições/metadados das fotos;
 * - nomes de itens do estoque encontrados no texto.
 *
 * Não afirma analisar semanticamente os pixels das imagens sem um serviço de visão configurado.
 */

function custo_finalizacao_table_exists(PDO $pdo, string $table): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
    try {
        $st = $pdo->prepare("SHOW TABLES LIKE ?");
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    } catch (Throwable $e) {
        try {
            $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
            return true;
        } catch (Throwable $ignored) {
            return false;
        }
    }
}

function custo_finalizacao_column_exists(PDO $pdo, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }

    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $st->execute([$column]);
        if ($st->fetch(PDO::FETCH_ASSOC)) {
            return true;
        }
    } catch (Throwable $e) {
        // Continua para o teste direto.
    }

    // Fallback para ambientes cPanel/MariaDB onde SHOW COLUMNS pode
    // retornar falso negativo por permissões/driver.
    try {
        $pdo->query("SELECT `{$column}` FROM `{$table}` LIMIT 0");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function custo_finalizacao_ensure_schema(PDO $pdo): array
{
    $resultado = [];

    if (!custo_finalizacao_column_exists($pdo, 'ordens_servico', 'custo_materiais')) {
        try {
            $pdo->exec("ALTER TABLE ordens_servico ADD COLUMN custo_materiais DECIMAL(12,2) NOT NULL DEFAULT 0.00");
            $resultado[] = 'ordens_servico.custo_materiais: criado';
        } catch (PDOException $e) {
            $driverCode = (int)($e->errorInfo[1] ?? 0);
            if ($driverCode === 1060 || stripos($e->getMessage(), 'Duplicate column') !== false) {
                $resultado[] = 'ordens_servico.custo_materiais: OK (já existia)';
            } else {
                throw $e;
            }
        }
    } else {
        $resultado[] = 'ordens_servico.custo_materiais: OK';
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS os_finalizacao_custos (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ordem_servico_id INT NOT NULL,
            houve_custo TINYINT(1) NOT NULL DEFAULT 0,
            custo_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            score_indicios SMALLINT NOT NULL DEFAULT 0,
            nivel_indicios VARCHAR(20) NOT NULL DEFAULT 'BAIXO',
            evidencias_json LONGTEXT NULL,
            justificativa_sem_custo TEXT NULL,
            confirmado_por INT NOT NULL,
            confirmado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_os_finalizacao_custos_os (ordem_servico_id),
            KEY idx_os_finalizacao_custos_usuario (confirmado_por),
            KEY idx_os_finalizacao_custos_data (confirmado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $resultado[] = 'os_finalizacao_custos: OK';

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS os_finalizacao_custo_itens (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            finalizacao_id BIGINT UNSIGNED NOT NULL,
            material_os_id INT NULL,
            estoque_material_id INT NULL,
            nome_material VARCHAR(180) NOT NULL,
            quantidade DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            unidade VARCHAR(30) NULL,
            custo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            custo_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            baixa_estoque TINYINT(1) NOT NULL DEFAULT 0,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ofci_finalizacao (finalizacao_id),
            KEY idx_ofci_material_os (material_os_id),
            KEY idx_ofci_estoque_material (estoque_material_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $resultado[] = 'os_finalizacao_custo_itens: OK';

    return $resultado;
}

function custo_finalizacao_normalizar(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = strtr($texto, [
        'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c'
    ]);
    return preg_replace('/\s+/', ' ', $texto) ?: $texto;
}

function custo_finalizacao_analisar_indicios(PDO $pdo, array $os, int $osId): array
{
    $score = 0;
    $evidencias = [];
    $sugestoes = [];
    $materiaisQtd = 0;
    $fotosQtd = 0;
    $custoAtual = (float)($os['custo_materiais'] ?? 0);

    if ($custoAtual > 0) {
        $score += 8;
        $evidencias[] = 'A OS já possui custo de materiais registrado: R$ ' . number_format($custoAtual, 2, ',', '.');
    }

    $materiais = [];
    try {
        $st = $pdo->prepare("SELECT * FROM materiais_os WHERE ordem_servico_id=? ORDER BY id");
        $st->execute([$osId]);
        $materiais = $st->fetchAll(PDO::FETCH_ASSOC);
        $materiaisQtd = count($materiais);
        if ($materiaisQtd > 0) {
            $score += 6;
            $evidencias[] = $materiaisQtd . ' material(is) já registrado(s) na OS.';
        }
    } catch (Throwable $e) {}

    $custoMov = 0.0;
    try {
        if (custo_finalizacao_table_exists($pdo, 'movimentacoes_estoque')) {
            $st = $pdo->prepare("
                SELECT COALESCE(SUM(quantidade*custo_unitario),0)
                FROM movimentacoes_estoque
                WHERE ordem_servico_id=? AND tipo='SAIDA_OS'
            ");
            $st->execute([$osId]);
            $custoMov = (float)$st->fetchColumn();
            if ($custoMov > 0) {
                $score += 8;
                $evidencias[] = 'Há saída de estoque vinculada à OS, totalizando aproximadamente R$ ' . number_format($custoMov, 2, ',', '.');
            }
        }
    } catch (Throwable $e) {}

    $fotoDescricoes = [];
    try {
        $st = $pdo->prepare("SELECT tipo,arquivo_url,descricao FROM anexos_os WHERE ordem_servico_id=?");
        $st->execute([$osId]);
        $fotos = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($fotos as $foto) {
            $ext = strtolower(pathinfo((string)($foto['arquivo_url'] ?? ''), PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
                $fotosQtd++;
                $fotoDescricoes[] = trim((string)($foto['descricao'] ?? ''));
            }
        }
    } catch (Throwable $e) {}

    $historicoTexto = '';
    try {
        $st = $pdo->prepare("SELECT observacao FROM historico_os WHERE ordem_servico_id=? ORDER BY criado_em DESC LIMIT 12");
        $st->execute([$osId]);
        $historicoTexto = implode(' ', array_filter(array_map('strval', $st->fetchAll(PDO::FETCH_COLUMN))));
    } catch (Throwable $e) {}

    $textoBase = implode(' ', array_filter([
        (string)($os['titulo'] ?? ''),
        (string)($os['descricao'] ?? ''),
        (string)($os['solicitacao_encerramento_obs'] ?? ''),
        (string)($os['observacao_aprovacao'] ?? ''),
        $historicoTexto,
        implode(' ', $fotoDescricoes),
    ]));
    $texto = custo_finalizacao_normalizar($textoBase);

    $palavras = [
        'troca','trocado','trocada','substituicao','substituido','substituida',
        'instalacao','instalado','instalada','comprado','comprada','compra',
        'material','peca','pecas','lampada','luminaria','tinta','massa','cabo',
        'conector','fonte','parafuso','bucha','fechadura','oring','vedacao',
        'silicone','cola','disjuntor','sensor','rolamento','bomba','valvula',
        'torneira','tubo','fio','fiação','ferragem','reparo','reposição','reposicao'
    ];
    $hits = [];
    foreach ($palavras as $palavra) {
        if (strpos($texto, custo_finalizacao_normalizar($palavra)) !== false) {
            $hits[] = $palavra;
        }
    }
    $hits = array_values(array_unique($hits));
    if ($hits) {
        $pontos = min(6, count($hits) * 2);
        $score += $pontos;
        $evidencias[] = 'A descrição/histórico contém termos associados a uso ou substituição de material: ' . implode(', ', array_slice($hits, 0, 8)) . '.';
    }

    $textoFotos = custo_finalizacao_normalizar(implode(' ', $fotoDescricoes));
    if ($textoFotos !== '') {
        $hitsFotos = [];
        foreach ($palavras as $palavra) {
            if (strpos($textoFotos, custo_finalizacao_normalizar($palavra)) !== false) {
                $hitsFotos[] = $palavra;
            }
        }
        $hitsFotos = array_values(array_unique($hitsFotos));
        if ($hitsFotos) {
            $score += min(4, count($hitsFotos) * 2);
            $evidencias[] = 'Descrições das imagens indicam possível uso de material: ' . implode(', ', array_slice($hitsFotos, 0, 6)) . '.';
        }
    }

    try {
        if (custo_finalizacao_table_exists($pdo, 'estoque_materiais')) {
            $estoque = $pdo->query("
                SELECT id,nome,unidade,quantidade_atual,custo_unitario
                FROM estoque_materiais WHERE ativo=1 ORDER BY nome
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($estoque as $m) {
                $nomeNorm = custo_finalizacao_normalizar((string)$m['nome']);
                if (mb_strlen($nomeNorm, 'UTF-8') >= 5 && strpos($texto, $nomeNorm) !== false) {
                    $score += 4;
                    $evidencias[] = 'Item do estoque citado no conteúdo da OS: ' . $m['nome'] . '.';
                    $sugestoes[] = [
                        'estoque_material_id' => (int)$m['id'],
                        'nome_material' => (string)$m['nome'],
                        'quantidade' => 1,
                        'unidade' => (string)($m['unidade'] ?? ''),
                        'custo_unitario' => (float)($m['custo_unitario'] ?? 0),
                        'sugerido' => true,
                    ];
                    if (count($sugestoes) >= 4) break;
                }
            }
        }
    } catch (Throwable $e) {}

    if ($fotosQtd > 0 && ($hits || $materiaisQtd > 0 || $custoAtual > 0)) {
        $score += 1;
        $evidencias[] = $fotosQtd . ' imagem(ns) estão disponíveis para conferência visual no momento da finalização.';
    }

    if (!$evidencias) {
        $evidencias[] = 'Nenhum indício forte de custo foi encontrado automaticamente. A confirmação humana continua obrigatória.';
    }

    $nivel = $score >= 8 ? 'ALTO' : ($score >= 3 ? 'MEDIO' : 'BAIXO');

    return [
        'score' => $score,
        'nivel' => $nivel,
        'provavel_custo' => $score >= 3,
        'evidencias' => array_values(array_unique($evidencias)),
        'sugestoes' => $sugestoes,
        'materiais_qtd' => $materiaisQtd,
        'fotos_qtd' => $fotosQtd,
        'custo_atual' => $custoAtual,
        'custo_movimentacoes' => $custoMov,
    ];
}

function custo_finalizacao_parse_decimal($valor): float
{
    if (is_string($valor)) {
        $valor = trim($valor);
        if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (strpos($valor, ',') !== false) {
            $valor = str_replace(',', '.', $valor);
        }
    }
    return round((float)$valor, 2);
}

function custo_finalizacao_validar_payload(array $post, array $analise): array
{
    if (empty($post['custo_finalizacao_confirmado'])) {
        throw new RuntimeException('Antes de finalizar, confirme no popup se a OS gerou custo com material.');
    }

    $houve = strtoupper(trim((string)($post['houve_custo_material'] ?? '')));
    if (!in_array($houve, ['SIM','NAO'], true)) {
        throw new RuntimeException('Informe se houve custo com material.');
    }

    $justificativa = trim((string)($post['justificativa_sem_custo'] ?? ''));
    if ($houve === 'NAO' && ($analise['score'] ?? 0) >= 8 && mb_strlen($justificativa, 'UTF-8') < 5) {
        throw new RuntimeException('Foram encontrados indícios fortes de custo. Informe uma justificativa para confirmar que não houve custo.');
    }

    $itens = [];
    $total = 0.0;

    if ($houve === 'SIM') {
        $raw = (string)($post['custos_json'] ?? '');
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Os dados dos materiais/custos não foram recebidos corretamente.');
        }

        foreach ($decoded as $item) {
            if (!is_array($item)) continue;
            $nome = trim((string)($item['nome_material'] ?? ''));
            $quantidade = custo_finalizacao_parse_decimal($item['quantidade'] ?? 0);
            $unidade = trim((string)($item['unidade'] ?? ''));
            $custoUnitario = custo_finalizacao_parse_decimal($item['custo_unitario'] ?? 0);
            $materialOsId = (int)($item['material_os_id'] ?? 0);
            $estoqueId = (int)($item['estoque_material_id'] ?? 0);
            $baixaEstoque = !empty($item['baixa_estoque']);

            if ($nome === '' && $quantidade <= 0 && $custoUnitario <= 0) continue;
            if ($nome === '') throw new RuntimeException('Informe o nome de todos os materiais.');
            if ($quantidade <= 0) throw new RuntimeException('A quantidade do material "' . $nome . '" deve ser maior que zero.');
            if ($custoUnitario < 0) throw new RuntimeException('O custo unitário não pode ser negativo.');
            if ($custoUnitario == 0.0) {
                throw new RuntimeException('Como foi informado que houve custo, preencha um custo unitário maior que zero para "' . $nome . '".');
            }

            $custoTotal = round($quantidade * $custoUnitario, 2);
            $total += $custoTotal;
            $itens[] = [
                'material_os_id' => $materialOsId,
                'estoque_material_id' => $estoqueId,
                'nome_material' => $nome,
                'quantidade' => $quantidade,
                'unidade' => $unidade,
                'custo_unitario' => $custoUnitario,
                'custo_total' => $custoTotal,
                'baixa_estoque' => $baixaEstoque,
            ];
        }

        if (!$itens) {
            throw new RuntimeException('Adicione pelo menos um material com quantidade e custo.');
        }
        if ($total <= 0) {
            throw new RuntimeException('O custo total informado deve ser maior que zero.');
        }
    }

    return [
        'houve_custo' => $houve === 'SIM',
        'itens' => $itens,
        'custo_total' => round($total, 2),
        'justificativa_sem_custo' => $justificativa,
    ];
}

function custo_finalizacao_material_ja_consumido(PDO $pdo, int $osId, int $estoqueMaterialId): bool
{
    if ($estoqueMaterialId <= 0 || !custo_finalizacao_table_exists($pdo, 'movimentacoes_estoque')) return false;
    try {
        $st = $pdo->prepare("
            SELECT COUNT(*) FROM movimentacoes_estoque
            WHERE ordem_servico_id=? AND material_id=? AND tipo='SAIDA_OS'
        ");
        $st->execute([$osId, $estoqueMaterialId]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Deve ser chamada dentro da mesma transação que encerra a OS.
 */
function custo_finalizacao_aplicar(PDO $pdo, int $osId, int $usuarioId, array $payload, array $analise): array
{
    $evidenciasJson = json_encode($analise['evidencias'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $st = $pdo->prepare("SELECT id FROM os_finalizacao_custos WHERE ordem_servico_id=? LIMIT 1 FOR UPDATE");
    $st->execute([$osId]);
    $finalizacaoId = (int)$st->fetchColumn();

    if ($finalizacaoId > 0) {
        $pdo->prepare("
            UPDATE os_finalizacao_custos
            SET houve_custo=?, custo_total=?, score_indicios=?, nivel_indicios=?,
                evidencias_json=?, justificativa_sem_custo=?, confirmado_por=?,
                confirmado_em=NOW(), atualizado_em=NOW()
            WHERE id=?
        ")->execute([
            $payload['houve_custo'] ? 1 : 0,
            $payload['custo_total'],
            (int)($analise['score'] ?? 0),
            (string)($analise['nivel'] ?? 'BAIXO'),
            $evidenciasJson,
            $payload['justificativa_sem_custo'] ?: null,
            $usuarioId,
            $finalizacaoId,
        ]);
        $pdo->prepare("DELETE FROM os_finalizacao_custo_itens WHERE finalizacao_id=?")->execute([$finalizacaoId]);
    } else {
        $pdo->prepare("
            INSERT INTO os_finalizacao_custos
            (ordem_servico_id,houve_custo,custo_total,score_indicios,nivel_indicios,
             evidencias_json,justificativa_sem_custo,confirmado_por,confirmado_em)
            VALUES(?,?,?,?,?,?,?,?,NOW())
        ")->execute([
            $osId,
            $payload['houve_custo'] ? 1 : 0,
            $payload['custo_total'],
            (int)($analise['score'] ?? 0),
            (string)($analise['nivel'] ?? 'BAIXO'),
            $evidenciasJson,
            $payload['justificativa_sem_custo'] ?: null,
            $usuarioId,
        ]);
        $finalizacaoId = (int)$pdo->lastInsertId();
    }

    $novosMateriais = 0;
    $baixasEstoque = 0;

    foreach ($payload['itens'] as $item) {
        $materialOsId = (int)$item['material_os_id'];
        $estoqueMaterialId = (int)$item['estoque_material_id'];

        if ($materialOsId <= 0) {
            $obs = 'Material/custo confirmado na finalização da OS. Custo unitário: R$ ' .
                number_format((float)$item['custo_unitario'], 2, ',', '.') .
                ' | total: R$ ' . number_format((float)$item['custo_total'], 2, ',', '.');

            $pdo->prepare("
                INSERT INTO materiais_os
                (ordem_servico_id,nome_material,quantidade,unidade,observacao)
                VALUES(?,?,?,?,?)
            ")->execute([
                $osId,
                $item['nome_material'],
                $item['quantidade'],
                $item['unidade'] ?: null,
                $obs,
            ]);
            $materialOsId = (int)$pdo->lastInsertId();
            $novosMateriais++;
        }

        if (!empty($item['baixa_estoque']) && $estoqueMaterialId > 0 &&
            custo_finalizacao_table_exists($pdo, 'estoque_materiais') &&
            !custo_finalizacao_material_ja_consumido($pdo, $osId, $estoqueMaterialId)) {

            $stEst = $pdo->prepare("SELECT id,nome,quantidade_atual FROM estoque_materiais WHERE id=? AND ativo=1 LIMIT 1 FOR UPDATE");
            $stEst->execute([$estoqueMaterialId]);
            $estoque = $stEst->fetch(PDO::FETCH_ASSOC);

            if (!$estoque) {
                throw new RuntimeException('O material "' . $item['nome_material'] . '" não está mais disponível no estoque.');
            }
            if ((float)$estoque['quantidade_atual'] < (float)$item['quantidade']) {
                throw new RuntimeException(
                    'Estoque insuficiente para "' . $item['nome_material'] . '". Disponível: ' .
                    $estoque['quantidade_atual'] . '. Desmarque "baixar do estoque" ou ajuste a quantidade.'
                );
            }

            $pdo->prepare("UPDATE estoque_materiais SET quantidade_atual=quantidade_atual-?, atualizado_em=NOW() WHERE id=?")
                ->execute([$item['quantidade'], $estoqueMaterialId]);

            if (custo_finalizacao_table_exists($pdo, 'movimentacoes_estoque')) {
                $pdo->prepare("
                    INSERT INTO movimentacoes_estoque
                    (material_id,ordem_servico_id,usuario_id,tipo,quantidade,custo_unitario,observacao)
                    VALUES(?,?,?,'SAIDA_OS',?,?,?)
                ")->execute([
                    $estoqueMaterialId,
                    $osId,
                    $usuarioId,
                    $item['quantidade'],
                    $item['custo_unitario'],
                    'Baixa confirmada no popup de finalização da OS',
                ]);
            }
            $baixasEstoque++;
        }

        $pdo->prepare("
            INSERT INTO os_finalizacao_custo_itens
            (finalizacao_id,material_os_id,estoque_material_id,nome_material,quantidade,unidade,
             custo_unitario,custo_total,baixa_estoque)
            VALUES(?,?,?,?,?,?,?,?,?)
        ")->execute([
            $finalizacaoId,
            $materialOsId ?: null,
            $estoqueMaterialId ?: null,
            $item['nome_material'],
            $item['quantidade'],
            $item['unidade'] ?: null,
            $item['custo_unitario'],
            $item['custo_total'],
            !empty($item['baixa_estoque']) ? 1 : 0,
        ]);
    }

    $pdo->prepare("UPDATE ordens_servico SET custo_materiais=? WHERE id=?")
        ->execute([$payload['houve_custo'] ? $payload['custo_total'] : 0.0, $osId]);

    return [
        'finalizacao_id' => $finalizacaoId,
        'houve_custo' => $payload['houve_custo'],
        'custo_total' => $payload['houve_custo'] ? $payload['custo_total'] : 0.0,
        'novos_materiais' => $novosMateriais,
        'baixas_estoque' => $baixasEstoque,
    ];
}

function custo_finalizacao_confirmacao(PDO $pdo, int $osId): ?array
{
    try {
        if (!custo_finalizacao_table_exists($pdo, 'os_finalizacao_custos')) return null;
        $st = $pdo->prepare("
            SELECT c.*,u.nome confirmado_por_nome
            FROM os_finalizacao_custos c
            LEFT JOIN usuarios u ON u.id=c.confirmado_por
            WHERE c.ordem_servico_id=? LIMIT 1
        ");
        $st->execute([$osId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function custo_finalizacao_itens(PDO $pdo, int $osId): array
{
    try {
        $st = $pdo->prepare("
            SELECT i.*
            FROM os_finalizacao_custo_itens i
            JOIN os_finalizacao_custos c ON c.id=i.finalizacao_id
            WHERE c.ordem_servico_id=?
            ORDER BY i.id
        ");
        $st->execute([$osId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function custo_finalizacao_materiais_iniciais(PDO $pdo, int $osId): array
{
    $rows = [];
    try {
        $st = $pdo->prepare("
            SELECT mo.id material_os_id,mo.nome_material,mo.quantidade,mo.unidade,
                   em.id estoque_material_id,em.custo_unitario
            FROM materiais_os mo
            LEFT JOIN estoque_materiais em ON em.nome=mo.nome_material AND em.ativo=1
            WHERE mo.ordem_servico_id=?
            ORDER BY mo.id
        ");
        $st->execute([$osId]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $rows[] = [
                'material_os_id' => (int)$r['material_os_id'],
                'estoque_material_id' => (int)($r['estoque_material_id'] ?? 0),
                'nome_material' => (string)$r['nome_material'],
                'quantidade' => (float)$r['quantidade'],
                'unidade' => (string)($r['unidade'] ?? ''),
                'custo_unitario' => (float)($r['custo_unitario'] ?? 0),
                'baixa_estoque' => false,
                'existente' => true,
            ];
        }
    } catch (Throwable $e) {}
    return $rows;
}
