<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/gestao_integrada.php';
require_once __DIR__ . '/exclusao_ordens.php';

function cron_proximo_dia_util(PDO $pdo, string $date): string
{
    $timestamp = strtotime($date) ?: time();
    for ($i=0;$i<370;$i++) {
        $candidate = date('Y-m-d',$timestamp);
        $weekDay = (int)date('N',$timestamp);
        $st = $pdo->prepare("SELECT COUNT(*) FROM feriados_operacionais WHERE data=? AND ativo=1");
        $st->execute([$candidate]);
        if ($weekDay<6 && !(int)$st->fetchColumn()) return $candidate;
        $timestamp = strtotime('+1 day',$timestamp);
    }
    return date('Y-m-d',$timestamp);
}

function cron_authorize(): void
{
    if (PHP_SAPI === 'cli') return;
    $expected = (string) env('CRON_SECRET', '');
    $provided = (string) ($_SERVER['HTTP_X_CRON_SECRET'] ?? '');
    if ($expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(404);
        exit;
    }
}

function cron_preventivas(PDO $pdo, bool $simulate = false): array
{
    gestao_ensure_schema($pdo);
    $result = ['eligible' => 0, 'created' => 0, 'ids' => []];
    $pdo->beginTransaction();
    try {
        $rows = $pdo->query("SELECT mp.* FROM manutencoes_preventivas mp
            LEFT JOIN ativos_medidores am ON am.ativo_id=mp.ativo_id AND am.tipo=mp.medidor_tipo
            WHERE mp.ativo=1 AND (
              (COALESCE(mp.tipo_gatilho,'CALENDARIO')='CALENDARIO' AND mp.proxima_execucao<=CURRENT_DATE)
              OR (mp.tipo_gatilho='MEDIDOR' AND am.valor_atual>=mp.medidor_limite)
            ) ORDER BY mp.proxima_execucao,mp.id FOR UPDATE")->fetchAll();
        foreach ($rows as $row) {
            $result['eligible']++;
            $cycle = (string)($row['proxima_execucao'] ?: date('Y-m-d'));
            $ordemAtivaSql = exclusao_sql_nao_excluida($pdo, 'ordens_servico');
            $dup = $pdo->prepare("SELECT id FROM ordens_servico WHERE preventiva_id=? AND DATE(data_abertura)=? AND $ordemAtivaSql LIMIT 1");
            $dup->execute([(int)$row['id'], $cycle]);
            if ($dup->fetchColumn() || $simulate) continue;
            $agenteId = (int)($row['agente_id'] ?? 0);
            if ($agenteId) {
                $disponibilidade = $pdo->prepare("SELECT estado FROM equipe_estado WHERE usuario_id=?");
                $disponibilidade->execute([$agenteId]);
                if (in_array((string)$disponibilidade->fetchColumn(), ['AUSENTE','FERIAS','AFASTADO'], true)) {
                    $agenteId = 0;
                }
            }
            if (!$agenteId) {
                $recomendados = gestao_recomendar_agentes($pdo, (int)$row['categoria_id'], (int)$row['local_id'], 1);
                $agenteId = (int)($recomendados[0]['id'] ?? 0);
            }
            $statusInicial = $agenteId ? 'ATRIBUIDA' : 'ABERTA';
            $number = gerar_numero_os($pdo);
            $insert = $pdo->prepare("INSERT INTO ordens_servico
                (numero_os,titulo,descricao,local_id,categoria_id,prioridade,status,agente_id,origem,preventiva_id,data_abertura)
                VALUES (?,?,?,?,?,?,?,?, 'PREVENTIVA',?,NOW())");
            $insert->execute([$number,$row['titulo'],$row['descricao'],$row['local_id'],$row['categoria_id'],validar_prioridade($row['prioridade']),$statusInicial,$agenteId?:null,$row['id']]);
            $result['ids'][] = (int)$pdo->lastInsertId();
            gestao_registrar_tempo_status($pdo, $result['ids'][count($result['ids'])-1], '', $statusInicial, null);
            $result['created']++;
            $days = max(1, (int)($row['intervalo_dias'] ?? 30));
            $nextDate = cron_proximo_dia_util($pdo,date('Y-m-d',strtotime($cycle.' +'.$days.' days')));
            $pdo->prepare("UPDATE manutencoes_preventivas SET ultimo_gerado_em=NOW(),
                proxima_execucao=? WHERE id=?")->execute([$nextDate,$row['id']]);
            if (($row['tipo_gatilho'] ?? '')==='MEDIDOR' && !empty($row['ativo_id'])) {
                $incremento = max(1.0, (float)($row['medidor_limite'] ?? 1));
                $pdo->prepare("UPDATE manutencoes_preventivas
                    SET medidor_limite=COALESCE(medidor_limite,0)+? WHERE id=?")
                    ->execute([$incremento,$row['id']]);
                $pdo->prepare("UPDATE ativos_medidores SET limite_proxima_os=COALESCE(limite_proxima_os,valor_atual)+COALESCE(incremento_ciclo,?)
                    WHERE ativo_id=? AND tipo=?")->execute([$incremento,$row['ativo_id'],$row['medidor_tipo']]);
            }
            if ($agenteId) {
                gestao_notificar($pdo,$agenteId,'PREVENTIVA_GERADA','OS preventiva gerada',
                    $row['titulo'],app_path('ordem_detalhe',['id'=>$result['ids'][count($result['ids'])-1]]),
                    'preventiva-os-'.$result['ids'][count($result['ids'])-1]);
            }
        }
        $simulate ? $pdo->rollBack() : $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function cron_cftv(PDO $pdo): int
{
    $st = $pdo->prepare("UPDATE cftv_relatorios SET status='FINALIZADO', finalizado_em=COALESCE(finalizado_em,NOW())
        WHERE status<>'FINALIZADO' AND CONCAT(data_plantao,' 17:10:00')<=NOW()");
    $st->execute();
    return $st->rowCount();
}
