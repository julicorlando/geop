<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/operacional.php';
require_once __DIR__ . '/../includes/conhecimento.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';
require_once __DIR__ . '/../includes/custo_finalizacao.php';
require_once __DIR__ . '/../includes/ordens_unificadas.php';
require_once __DIR__ . '/../includes/geop_v8.php';

require_login();
check_csrf();
ensure_operational_schema($pdo);
gestao_ensure_schema($pdo);
geop_v8_boot($pdo);
custo_finalizacao_ensure_schema($pdo);

$id = (int)($_GET['id'] ?? 0);
$user = current_user();

if (($user['perfil'] ?? '') === 'CFTV') {
    http_response_code(403);
    die('Acesso negado.');
}

$mensagemSucesso = $_SESSION['mensagem_sucesso'] ?? '';
$mensagemErro = $_SESSION['mensagem_erro'] ?? '';
$tokenGerado = $_SESSION['token_reabertura_gerado'] ?? '';
unset($_SESSION['mensagem_sucesso'], $_SESSION['mensagem_erro'], $_SESSION['token_reabertura_gerado']);

function load_os($pdo, $id) {
    $ordemAtivaSql = exclusao_sql_nao_excluida($pdo, 'ordens_servico', 'os');
    $s = $pdo->prepare("
        SELECT 
            os.*, 
            l.nome AS local_nome, 
            c.nome AS categoria_nome, 
            g.nome AS gestor_nome, 
            a.nome AS agente_nome,
            GROUP_CONCAT(DISTINCT CASE WHEN oa.papel = 'AUXILIAR' THEN ux.nome END ORDER BY ux.nome SEPARATOR ', ') AS agentes_auxiliares_nomes
        FROM ordens_servico os
        JOIN locais l ON l.id = os.local_id
        JOIN categorias c ON c.id = os.categoria_id
        JOIN usuarios g ON g.id = os.gestor_id
        LEFT JOIN usuarios a ON a.id = os.agente_id
        LEFT JOIN ordem_agentes oa ON oa.ordem_servico_id = os.id
        LEFT JOIN usuarios ux ON ux.id = oa.usuario_id
        WHERE os.id = ?
          AND $ordemAtivaSql
        GROUP BY os.id
    ");
    $s->execute([$id]);
    return $s->fetch();
}

function anexo_eh_imagem($arquivoUrl) {
    $ext = strtolower(pathinfo((string)$arquivoUrl, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
}

function montar_url_relativa_script(string $caminho): string {
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if ($base === '/' || $base === '.') {
        $base = '';
    }

    $partes = array_map('rawurlencode', array_filter(explode('/', ltrim($caminho, '/')), 'strlen'));
    return $base . '/' . implode('/', $partes);
}

function anexo_url_segura($arquivoUrl) {
    $arquivoUrl = trim((string)$arquivoUrl);
    if ($arquivoUrl === '') return '';

    $arquivoUrl = str_replace('\\', '/', $arquivoUrl);

    if (preg_match('/^https?:\/\//i', $arquivoUrl)) {
        return $arquivoUrl;
    }

    $arquivoUrl = preg_replace('/\?.*$/', '', $arquivoUrl);

    if (strpos($arquivoUrl, '/uploads/') !== false) {
        $arquivoUrl = substr($arquivoUrl, strpos($arquivoUrl, '/uploads/') + 1);
    }

    if (preg_match('/^[A-Za-z]:\//', $arquivoUrl) || strpos($arquivoUrl, '/home/') === 0 || strpos($arquivoUrl, __DIR__) === 0) {
        $arquivoUrl = 'uploads/' . basename($arquivoUrl);
    }

    $arquivoUrl = ltrim($arquivoUrl, '/');

    if (strpos($arquivoUrl, 'uploads/') === 0) {
        return montar_url_relativa_script($arquivoUrl);
    }

    if (strpos($arquivoUrl, '../') === 0 || strpos($arquivoUrl, './') === 0) {
        return $arquivoUrl;
    }

    if (strpos($arquivoUrl, '/') === false) {
        return montar_url_relativa_script('uploads/' . $arquivoUrl);
    }

    return montar_url_relativa_script($arquivoUrl);
}

function ensure_token_reabertura_table(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tokens_reabertura_os (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ordem_servico_id INT NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            gerado_por INT NOT NULL,
            usado_por INT NULL,
            usado_em DATETIME NULL,
            expira_em DATETIME NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            solicitacao_id INT NULL,
            INDEX idx_ordem_servico_id (ordem_servico_id),
            INDEX idx_expira_em (expira_em),
            INDEX idx_usado_em (usado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function normalizar_token_reabertura($token): string {
    return preg_replace('/[^0-9A-Za-z]/', '', strtoupper((string)$token));
}

ensure_token_reabertura_table($pdo);

$os = load_os($pdo, $id);
$checklistOrdem = ordem_checklist($pdo, $id);
$tipoOrdemAtual = strtoupper((string)($os['tipo_ordem'] ?? (str_starts_with((string)($os['numero_os'] ?? ''),'OL-') ? 'OL' : 'OS')));

if (!$os) {
    die('OS não encontrada.');
}

$usuarioParticipaOs = os_agente_participa($pdo, $id, (int)$user['id']);

if ($user['perfil'] === 'AGENTE' && !$usuarioParticipaOs) {
    die('Acesso negado.');
}

$podeGerenciarOs = can_manage_os();
$podeExecutarOs = can_execute_os() && $usuarioParticipaOs;
$podeAtualizarOs = $podeGerenciarOs || $podeExecutarOs;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $old = $os['status'];

    if ($action === 'gerar_token_reabertura' && $user['perfil'] === 'TI') {
        if ($os['status'] !== 'ENCERRADA') {
            $_SESSION['mensagem_erro'] = 'Token só pode ser gerado para OS encerrada.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $token = (string)random_int(100000, 999999);
        $hash = password_hash($token, PASSWORD_DEFAULT);
        $pdo->beginTransaction();
        try {
            $solicitacaoId = null;
            if (app_table_exists($pdo, 'token_reabertura_solicitacoes')) {
                $pendente = $pdo->prepare("SELECT id FROM token_reabertura_solicitacoes
                    WHERE ordem_servico_id = ? AND status = 'PENDENTE'
                    ORDER BY criado_em DESC LIMIT 1 FOR UPDATE");
                $pendente->execute([$id]);
                $solicitacaoId = ($value = $pendente->fetchColumn()) ? (int)$value : null;
            }

            $pdo->prepare("
                UPDATE tokens_reabertura_os
                SET usado_em = NOW(), usado_por = ?
                WHERE ordem_servico_id = ? AND usado_em IS NULL
            ")->execute([$user['id'], $id]);

            $st = $pdo->prepare("
                INSERT INTO tokens_reabertura_os
                (ordem_servico_id, token_hash, gerado_por, expira_em, solicitacao_id)
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), ?)
            ");
            $st->execute([$id, $hash, $user['id'], $solicitacaoId]);
            $tokenId = (int)$pdo->lastInsertId();

            if ($solicitacaoId !== null) {
                $pdo->prepare("UPDATE token_reabertura_solicitacoes
                    SET status='ATENDIDA', atendido_por=?, atendido_em=NOW(),
                        resolvida_por=?, resolvida_em=NOW(), token_id=?
                    WHERE id=?")->execute([$user['id'], $user['id'], $tokenId, $solicitacaoId]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $exception;
        }

        registrar_historico(
            $pdo,
            $id,
            $user['id'],
            $old,
            $old,
            'TI gerou token para reabertura da OS. Validade: 24 horas.'
        );

        $_SESSION['token_reabertura_gerado'] = $token;
        $_SESSION['mensagem_sucesso'] = 'Token de reabertura gerado com sucesso.';
        redirect('ordem_detalhe?id=' . $id);
    }

    if ($action === 'solicitar_token_reabertura') {
        if ($user['perfil'] === 'TI') {
            $_SESSION['mensagem_erro'] = 'O TI pode gerar o token diretamente nesta OS.';
            redirect('ordem_detalhe?id=' . $id);
        }

        if ($os['status'] !== 'ENCERRADA') {
            $_SESSION['mensagem_erro'] = 'Token só pode ser solicitado para OS encerrada.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $motivoToken = trim((string)($_POST['motivo_token'] ?? 'Solicitação de token para reabertura da OS.'));
        if ($motivoToken === '') {
            $motivoToken = 'Solicitação de token para reabertura da OS.';
        }

        if (!token_reabertura_tem_solicitacao_pendente($pdo, $id)) {
            $st = $pdo->prepare("INSERT INTO token_reabertura_solicitacoes (ordem_servico_id, solicitado_por, motivo) VALUES (?, ?, ?)");
            $st->execute([$id, $user['id'], $motivoToken]);

            if (geop_v8_table_exists($pdo, 'os_reabertura_solicitacoes')) {
                $checkCentral = $pdo->prepare("SELECT id FROM os_reabertura_solicitacoes WHERE ordem_servico_id=? AND status='PENDENTE' LIMIT 1");
                $checkCentral->execute([$id]);
                if (!$checkCentral->fetchColumn()) {
                    $pdo->prepare("INSERT INTO os_reabertura_solicitacoes(ordem_servico_id,solicitado_por,motivo) VALUES(?,?,?)")
                        ->execute([$id, $user['id'], $motivoToken]);
                }
            }

            registrar_historico($pdo, $id, $user['id'], $old, $old, 'Solicitou token de reabertura ao TI: ' . $motivoToken);
            auditoria_log($pdo, 'ordem_servico', $id, 'SOLICITAR_TOKEN_REABERTURA', null, null, $motivoToken, $user['id']);
        }

        $_SESSION['mensagem_sucesso'] = 'Solicitação enviada ao TI. O alerta ficará visível para o TI até o token ser gerado.';
        redirect('ordem_detalhe?id=' . $id);
    }

    if ($action === 'reabrir_com_token') {
        if (!$podeAtualizarOs) {
            $_SESSION['mensagem_erro'] = 'Voce nao tem permissao para reabrir esta OS.';
            redirect('ordem_detalhe?id=' . $id);
        }

        if ($os['status'] !== 'ENCERRADA') {
            $_SESSION['mensagem_erro'] = 'Esta OS não está encerrada.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $tokenDigitado = normalizar_token_reabertura($_POST['token_reabertura'] ?? '');
        $obs = trim($_POST['obs_reabertura'] ?? 'OS reaberta com token autorizado pelo TI.');

        if ($tokenDigitado === '') {
            $_SESSION['mensagem_erro'] = 'Informe o token de reabertura.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $tokens = $pdo->prepare("
            SELECT *
            FROM tokens_reabertura_os
            WHERE ordem_servico_id = ?
            AND usado_em IS NULL
            AND expira_em >= NOW()
            ORDER BY criado_em DESC
        ");
        $tokens->execute([$id]);

        $tokenValido = null;
        foreach ($tokens->fetchAll() as $tk) {
            if (password_verify($tokenDigitado, $tk['token_hash'])) {
                $tokenValido = $tk;
                break;
            }
        }

        if (!$tokenValido) {
            $_SESSION['mensagem_erro'] = 'Token inválido, expirado ou já utilizado.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $pdo->prepare("
            UPDATE ordens_servico
            SET status = 'ABERTA',
                data_encerramento = NULL,
                observacao_aprovacao = NULL,
                reabertura_contagem = COALESCE(reabertura_contagem,0) + 1
            WHERE id = ?
        ")->execute([$id]);
        gestao_registrar_tempo_status($pdo, $id, (string)$old, 'ABERTA', (int)$user['id']);

        $pdo->prepare("
            UPDATE tokens_reabertura_os
            SET usado_em = NOW(), usado_por = ?
            WHERE id = ?
        ")->execute([$user['id'], $tokenValido['id']]);

        registrar_historico(
            $pdo,
            $id,
            $user['id'],
            $old,
            'ABERTA',
            $obs !== '' ? $obs : 'OS reaberta com token autorizado pelo TI.'
        );
        foreach (gestao_usuarios_gestao($pdo) as $gestorId) {
            gestao_notificar($pdo, $gestorId, 'OS_REABERTA', 'OS reaberta',
                ($os['numero_os'] ?? ('OS #' . $id)) . ' — ' . $obs,
                app_path('ordem_detalhe', ['id' => $id]), 'os-reaberta-' . $id . '-' . time() . '-' . $gestorId);
        }

        $_SESSION['mensagem_sucesso'] = 'OS reaberta com sucesso usando token autorizado pelo TI.';
        redirect('ordem_detalhe?id=' . $id);
    }

    if ($action === 'v8_checklist_update' && $podeAtualizarOs && geop_v8_table_exists($pdo, 'os_checklist_itens')) {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $concluido = !empty($_POST['concluido']) ? 1 : 0;
        $observacao = trim((string)($_POST['observacao_checklist'] ?? ''));
        $stItem = $pdo->prepare("SELECT * FROM os_checklist_itens WHERE id=? AND ordem_servico_id=? LIMIT 1");
        $stItem->execute([$itemId,$id]);
        $itemChecklist = $stItem->fetch(PDO::FETCH_ASSOC);
        if (!$itemChecklist) {
            $_SESSION['mensagem_erro'] = 'Item de checklist não encontrado.';
            redirect('ordem_detalhe?id=' . $id);
        }
        if ($concluido && !empty($itemChecklist['exige_foto'])) {
            $stFotoChecklist = $pdo->prepare("SELECT COUNT(*) FROM anexos_os WHERE ordem_servico_id=? AND tipo IN ('FOTO_DEPOIS','OUTRO')");
            $stFotoChecklist->execute([$id]);
            if ((int)$stFotoChecklist->fetchColumn() <= 0) {
                $_SESSION['mensagem_erro'] = 'Este item exige evidência fotográfica. Envie uma foto antes de marcá-lo como concluído.';
                redirect('ordem_detalhe?id=' . $id);
            }
        }
        $pdo->prepare("UPDATE os_checklist_itens SET concluido=?,observacao=?,concluido_por=?,concluido_em=? WHERE id=? AND ordem_servico_id=?")
            ->execute([$concluido,$observacao?:null,$concluido?(int)$user['id']:null,$concluido?date('Y-m-d H:i:s'):null,$itemId,$id]);
        registrar_historico($pdo,$id,(int)$user['id'],$old,$old,'Checklist atualizado: '.$itemChecklist['descricao'].($concluido?' — concluído.':' — reaberto.'));
        auditoria_log($pdo,'ordem_servico',$id,'CHECKLIST','item_'.$itemId,$itemChecklist['concluido']??0,$concluido,(int)$user['id']);
        $_SESSION['mensagem_sucesso'] = 'Checklist atualizado.';
        redirect('ordem_detalhe?id=' . $id);
    }

    if ($action === 'mover_foto' && $podeGerenciarOs) {
        $anexoId = (int)($_POST['anexo_id'] ?? 0);
        $novoTipoFoto = strtoupper(trim((string)($_POST['novo_tipo'] ?? '')));
        $tiposPermitidosFoto = ['FOTO_ANTES', 'FOTO_DEPOIS', 'OUTRO'];

        if ($anexoId <= 0 || !in_array($novoTipoFoto, $tiposPermitidosFoto, true)) {
            $_SESSION['mensagem_erro'] = 'Dados invalidos para mover a foto.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $stFoto = $pdo->prepare("
            SELECT *
            FROM anexos_os
            WHERE id = ?
            AND ordem_servico_id = ?
            LIMIT 1
        ");
        $stFoto->execute([$anexoId, $id]);
        $fotoMover = $stFoto->fetch();

        if (!$fotoMover) {
            $_SESSION['mensagem_erro'] = 'Foto nao encontrada nesta OS.';
            redirect('ordem_detalhe?id=' . $id);
        }

        if (!anexo_eh_imagem($fotoMover['arquivo_url'] ?? '')) {
            $_SESSION['mensagem_erro'] = 'Apenas imagens podem ser movidas entre Antes e Depois.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $tipoAnteriorFoto = strtoupper((string)($fotoMover['tipo'] ?? ''));

        if ($tipoAnteriorFoto === $novoTipoFoto) {
            $_SESSION['mensagem_erro'] = 'A foto ja esta nesta categoria.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $pdo->prepare("
            UPDATE anexos_os
            SET tipo = ?
            WHERE id = ?
            AND ordem_servico_id = ?
        ")->execute([$novoTipoFoto, $anexoId, $id]);

        registrar_historico(
            $pdo,
            $id,
            $user['id'],
            $old,
            $old,
            'Foto movida de ' . str_replace('_', ' ', $tipoAnteriorFoto ?: 'SEM TIPO') . ' para ' . str_replace('_', ' ', $novoTipoFoto) . '.'
        );

        auditoria_log($pdo, 'ordem_servico', $id, 'MOVER_FOTO', 'anexos_os.tipo', $tipoAnteriorFoto, $novoTipoFoto, $user['id']);

        $_SESSION['mensagem_sucesso'] = 'Foto movida com sucesso.';
        redirect('ordem_detalhe?id=' . $id);
    }


    if ($action === 'excluir_foto') {
        $anexoId = (int)($_POST['anexo_id'] ?? 0);
        if ($anexoId <= 0) {
            $_SESSION['mensagem_erro'] = 'Foto inválida para exclusão.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $stFotoExcluir = $pdo->prepare("SELECT * FROM anexos_os WHERE id=? AND ordem_servico_id=? LIMIT 1");
        $stFotoExcluir->execute([$anexoId, $id]);
        $fotoExcluir = $stFotoExcluir->fetch(PDO::FETCH_ASSOC);
        if (!$fotoExcluir || !anexo_eh_imagem($fotoExcluir['arquivo_url'] ?? '')) {
            $_SESSION['mensagem_erro'] = 'Foto não encontrada nesta OS.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $podeExcluirFoto = $podeGerenciarOs
            || ($podeExecutarOs && (int)($fotoExcluir['usuario_id'] ?? 0) === (int)$user['id'] && $os['status'] !== 'ENCERRADA');
        if (!$podeExcluirFoto) {
            $_SESSION['mensagem_erro'] = 'Você não tem permissão para apagar esta foto.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $arquivoExcluir = (string)($fotoExcluir['arquivo_url'] ?? '');
        $descricaoExcluir = (string)($fotoExcluir['descricao'] ?? '');
        $tipoExcluir = (string)($fotoExcluir['tipo'] ?? '');

        $delFoto = $pdo->prepare("DELETE FROM anexos_os WHERE id=? AND ordem_servico_id=?");
        $delFoto->execute([$anexoId, $id]);
        if ($delFoto->rowCount() < 1) {
            $_SESSION['mensagem_erro'] = 'Não foi possível apagar a foto.';
            redirect('ordem_detalhe?id=' . $id);
        }

        remover_upload_local_se_sem_referencia($pdo, $arquivoExcluir);
        registrar_historico($pdo, $id, $user['id'], $old, $old, 'Foto removida da OS (' . ($tipoExcluir ?: 'SEM TIPO') . ').');
        auditoria_log($pdo, 'ordem_servico', $id, 'EXCLUIR_FOTO', 'anexos_os',
            json_encode(['id'=>$anexoId,'tipo'=>$tipoExcluir,'arquivo'=>$arquivoExcluir,'descricao'=>$descricaoExcluir], JSON_UNESCAPED_UNICODE),
            null, $user['id']);

        $_SESSION['mensagem_sucesso'] = 'Foto apagada com sucesso.';
        redirect('ordem_detalhe?id=' . $id);
    }

    if ($action === 'editar' && $podeGerenciarOs) {
        if ($os['status'] === 'ENCERRADA' && $user['perfil'] !== 'TI') {
            $_SESSION['mensagem_erro'] = 'OS encerrada não pode ser editada sem reabertura autorizada pelo TI.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $local_id = (int)($_POST['local_id'] ?? 0);
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $prioridade = $_POST['prioridade'] ?? 'MEDIA';
        $prazo = trim($_POST['prazo'] ?? '');

        $prioridadesPermitidas = ['BAIXA', 'MEDIA', 'ALTA', 'CRITICA', 'URGENTE'];

        if (
            $titulo !== '' &&
            $descricao !== '' &&
            $local_id > 0 &&
            $categoria_id > 0 &&
            in_array($prioridade, $prioridadesPermitidas, true)
        ) {
            $alteracoes = [];

            if ($titulo !== $os['titulo']) {
                $alteracoes[] = 'Título';
            }

            if ($descricao !== $os['descricao']) {
                $alteracoes[] = 'Descrição';
            }

            if ($local_id !== (int)$os['local_id']) {
                $alteracoes[] = 'Local';
            }

            if ($categoria_id !== (int)$os['categoria_id']) {
                $alteracoes[] = 'Categoria';
            }

            if ($prioridade !== $os['prioridade']) {
                $alteracoes[] = 'Prioridade';
            }

            $prazoBanco = $prazo !== ''
                ? str_replace('T', ' ', $prazo)
                : calcular_vencimento_sla($prioridade, $os['criado_em'] ?? null);
            $slaHoras = sla_horas_por_prioridade($prioridade);
            $prazoAtual = $os['prazo'] ? str_replace(' ', 'T', substr($os['prazo'], 0, 16)) : '';

            if ($prazo !== $prazoAtual) {
                $alteracoes[] = 'Prazo';
            }

            $sqlEdit = "
                UPDATE ordens_servico
                SET 
                    titulo = ?, 
                    descricao = ?, 
                    local_id = ?, 
                    categoria_id = ?, 
                    prioridade = ?, 
                    prazo = ?
            ";

            $paramsEdit = [
                $titulo,
                $descricao,
                $local_id,
                $categoria_id,
                $prioridade,
                $prazoBanco,
            ];

            if (app_column_exists($pdo, 'ordens_servico', 'sla_horas')) {
                $sqlEdit .= ", sla_horas = ?";
                $paramsEdit[] = $slaHoras;
            }

            if (app_column_exists($pdo, 'ordens_servico', 'sla_vencimento')) {
                $sqlEdit .= ", sla_vencimento = ?";
                $paramsEdit[] = $prazoBanco;
            }

            $sqlEdit .= " WHERE id = ?";
            $paramsEdit[] = $id;

            $st = $pdo->prepare($sqlEdit);
            $st->execute($paramsEdit);

            auditoria_campos(
                $pdo,
                'ordem_servico',
                $id,
                'EDITAR',
                $os,
                [
                    'titulo' => $titulo,
                    'descricao' => $descricao,
                    'local_id' => $local_id,
                    'categoria_id' => $categoria_id,
                    'prioridade' => $prioridade,
                    'prazo' => $prazoBanco,
                ],
                ['titulo', 'descricao', 'local_id', 'categoria_id', 'prioridade', 'prazo'],
                $user['id']
            );

            registrar_historico(
                $pdo,
                $id,
                $user['id'],
                $old,
                $old,
                !empty($alteracoes)
                    ? 'OS editada. Campos alterados: ' . implode(', ', $alteracoes) . '.'
                    : 'OS editada sem alteração de dados.'
            );
        }
    }

    if ($action === 'atribuir' && $podeGerenciarOs) {
        if ($os['status'] === 'ENCERRADA' && $user['perfil'] !== 'TI') {
            $_SESSION['mensagem_erro'] = 'OS encerrada não pode ser alterada sem reabertura autorizada pelo TI.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $agente_id = !empty($_POST['agente_id']) ? (int)$_POST['agente_id'] : null;
        if ($agente_id && $user['perfil'] !== 'TI') {
            $cargaAtivaSql = exclusao_sql_nao_excluida($pdo,'ordens_servico','carga');
            $stCap = $pdo->prepare("SELECT COALESCE(ee.estado,'DISPONIVEL') estado,
                COALESCE(ee.capacidade_maxima,5) capacidade,
                (SELECT COUNT(*) FROM ordens_servico carga WHERE carga.agente_id=u.id
                 AND $cargaAtivaSql
                 AND carga.status NOT IN ('ENCERRADA','CONCLUIDA_PELO_AGENTE','REPROVADA','CANCELADA')) carga
                FROM usuarios u LEFT JOIN equipe_estado ee ON ee.usuario_id=u.id WHERE u.id=?");
            $stCap->execute([$agente_id]);
            $capacidade = $stCap->fetch(PDO::FETCH_ASSOC);
            if (!$capacidade || in_array($capacidade['estado'],['AUSENTE','FERIAS','AFASTADO'],true)
                || (int)$capacidade['carga'] >= (int)$capacidade['capacidade']) {
                $_SESSION['mensagem_erro'] = 'O agente está indisponível ou atingiu sua capacidade máxima de OS.';
                redirect('ordem_detalhe?id='.$id);
            }
        }
        $agentes_auxiliares = array_values(array_unique(array_map('intval', $_POST['agentes_auxiliares'] ?? [])));
        $agentes_auxiliares = array_filter($agentes_auxiliares, function($aux) use ($agente_id) {
            return $aux > 0 && (!$agente_id || $aux !== (int)$agente_id);
        });
        $novo = $agente_id ? 'ATRIBUIDA' : 'ABERTA';

        $st = $pdo->prepare("UPDATE ordens_servico SET agente_id = ?, status = ? WHERE id = ?");
        $st->execute([$agente_id, $novo, $id]);
        gestao_registrar_tempo_status($pdo,$id,(string)$old,$novo,(int)$user['id']);
        os_sync_agentes($pdo, $id, $agente_id, $agentes_auxiliares, (int)$user['id']);

        auditoria_log($pdo, 'ordem_servico', $id, 'ATRIBUIR', 'agente_id', $os['agente_id'] ?? null, $agente_id, $user['id']);
        auditoria_log($pdo, 'ordem_servico', $id, 'ALTERAR_STATUS', 'status', $old, $novo, $user['id']);

        $notificarIds = array_values(array_unique(array_filter(array_merge([$agente_id], $agentes_auxiliares))));
        foreach ($notificarIds as $uidNotificar) {
            $osNotificacao = array_merge($os, [
                'id' => $id,
                'status' => $novo,
                'agente_id' => $uidNotificar,
            ]);
            whatsapp_queue_user($pdo, $uidNotificar, mensagem_whatsapp_os('OS atribuida para voce', $osNotificacao), 'Avisar agente');
            gestao_notificar($pdo,(int)$uidNotificar,'OS_ATRIBUIDA','OS atribuída para você',
                ($os['numero_os'] ?? ('OS #'.$id)).' — '.($os['titulo'] ?? ''),app_path('ordem_detalhe',['id'=>$id]),
                'os-atribuida-'.$id.'-'.$uidNotificar);
        }

        $nomesAux = [];
        if ($agentes_auxiliares) {
            $place = implode(',', array_fill(0, count($agentes_auxiliares), '?'));
            $stAux = $pdo->prepare("SELECT nome FROM usuarios WHERE id IN ($place) ORDER BY nome");
            $stAux->execute($agentes_auxiliares);
            $nomesAux = $stAux->fetchAll(PDO::FETCH_COLUMN);
        }
        registrar_historico($pdo, $id, $user['id'], $old, $novo, 'Responsável alterado. Auxiliar(es): ' . ($nomesAux ? implode(', ', $nomesAux) : 'nenhum') . '.');
    }

    if ($action === 'status') {
        $allowed = [];

        if ($podeExecutarOs) {
            $allowed = [
                'EM_ANDAMENTO',
                'PAUSADA',
                'AGUARDANDO_MATERIAL',
                'CONCLUIDA_PELO_AGENTE'
            ];
        }

        if ($podeGerenciarOs) {
            $allowed = [
                'ABERTA',
                'ATRIBUIDA',
                'EM_ANDAMENTO',
                'PAUSADA',
                'AGUARDANDO_MATERIAL',
                'REPROVADA',
                'ENCERRADA'
            ];
        }

        $novo = $_POST['status'] ?? '';
        $obs = trim((string)($_POST['obs'] ?? ''));

        if ($novo === 'REPROVADA' && $obs === '') {
            $_SESSION['mensagem_erro'] = 'A reprovação exige uma justificativa.';
            redirect('ordem_detalhe?id='.$id);
        }
        $custoFinalizacaoPayload = null;
        $analiseCustoFinalizacaoPost = null;

        if ($novo === 'ENCERRADA') {
            $analiseCustoFinalizacaoPost = custo_finalizacao_analisar_indicios($pdo, $os, $id);
            try {
                $custoFinalizacaoPayload = custo_finalizacao_validar_payload($_POST, $analiseCustoFinalizacaoPost);
            } catch (Throwable $e) {
                $_SESSION['mensagem_erro'] = $e->getMessage();
                redirect('ordem_detalhe?id=' . $id);
            }

            if ($old !== 'CONCLUIDA_PELO_AGENTE' && !$podeGerenciarOs) {
                $_SESSION['mensagem_erro'] = 'O técnico precisa solicitar a conclusão antes da aprovação.';
                redirect('ordem_detalhe?id='.$id);
            }
            try {
                $categoriaRegra = $pdo->prepare("SELECT exige_foto_antes,exige_foto_durante,exige_foto_depois FROM categorias WHERE id=?");
                $categoriaRegra->execute([(int)$os['categoria_id']]);
                $regra = $categoriaRegra->fetch(PDO::FETCH_ASSOC) ?: [];
                $fotosStmt = $pdo->prepare("SELECT UPPER(tipo) tipo,COUNT(*) total FROM anexos_os
                    WHERE ordem_servico_id=? GROUP BY UPPER(tipo)");
                $fotosStmt->execute([$id]);
                $fotos = array_column($fotosStmt->fetchAll(PDO::FETCH_ASSOC),'total','tipo');
                $faltas = [];
                if (!empty($regra['exige_foto_antes']) && empty($fotos['FOTO_ANTES'])) $faltas[]='foto antes';
                if (!empty($regra['exige_foto_durante']) && empty($fotos['FOTO_DURANTE'])) $faltas[]='foto durante';
                if (!empty($regra['exige_foto_depois']) && empty($fotos['FOTO_DEPOIS'])) $faltas[]='foto depois';
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM checklist_itens ci
                    JOIN checklist_modelos cm ON cm.id=ci.modelo_id AND cm.ativo=1
                    LEFT JOIN checklist_respostas cr ON cr.item_id=ci.id AND cr.ordem_servico_id=?
                    WHERE ci.obrigatorio=1 AND (cm.categoria_id=? OR cm.categoria_id IS NULL)
                      AND cr.id IS NULL");
                $checkStmt->execute([$id,(int)$os['categoria_id']]);
                if ((int)$checkStmt->fetchColumn()>0) $faltas[]='checklist obrigatório';
                if ($faltas) {
                    $_SESSION['mensagem_erro']='Não é possível aprovar: falta '.implode(', ',$faltas).'.';
                    redirect('ordem_detalhe?id='.$id);
                }
            } catch (Throwable $e) {
                // Instalações antigas sem checklist continuam usando as regras de evidência disponíveis.
            }
        }

        if ($os['status'] === 'ENCERRADA' && $novo !== 'ENCERRADA') {
            $_SESSION['mensagem_erro'] = 'Para reabrir uma OS encerrada, use o token gerado pelo TI.';
            redirect('ordem_detalhe?id=' . $id);
        }

        if (in_array($novo, $allowed, true)) {
            $obs = $obs ?: 'Status atualizado.';

            $sql = "UPDATE ordens_servico SET status = ?";
            $params = [$novo];

            if ($novo === 'EM_ANDAMENTO') {
                $sql .= ", data_inicio = COALESCE(data_inicio, NOW())";
            }

            if ($novo === 'CONCLUIDA_PELO_AGENTE') {
                $sql .= ", data_conclusao = NOW()";
            }

            if ($novo === 'ENCERRADA') {
                $sql .= ", data_conclusao = COALESCE(data_conclusao, NOW()), data_encerramento = NOW(), aprovado_por = ?, observacao_aprovacao = ?";
                $params[] = $user['id'];
                $params[] = $obs;
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $resultadoCustoFinalizacao = null;
            if ($novo === 'ENCERRADA') {
                try {
                    $pdo->beginTransaction();
                    $resultadoCustoFinalizacao = custo_finalizacao_aplicar(
                        $pdo,
                        $id,
                        (int)$user['id'],
                        $custoFinalizacaoPayload,
                        $analiseCustoFinalizacaoPost
                    );
                    $pdo->prepare($sql)->execute($params);
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $_SESSION['mensagem_erro'] = 'Não foi possível finalizar a OS: ' . $e->getMessage();
                    redirect('ordem_detalhe?id=' . $id);
                }
            } else {
                $pdo->prepare($sql)->execute($params);
            }

            gestao_registrar_tempo_status($pdo,$id,(string)$old,(string)$novo,(int)$user['id']);

            $obsHistorico = $obs ?: 'Status atualizado.';
            if ($novo === 'ENCERRADA' && is_array($resultadoCustoFinalizacao)) {
                $obsHistorico .= $resultadoCustoFinalizacao['houve_custo']
                    ? ' | Custo de material confirmado: R$ ' . number_format((float)$resultadoCustoFinalizacao['custo_total'], 2, ',', '.') . '.'
                    : ' | Finalização confirmada sem custo de material.';
            }

            registrar_historico($pdo, $id, $user['id'], $old, $novo, $obsHistorico);
            auditoria_log($pdo, 'ordem_servico', $id, 'ALTERAR_STATUS', 'status', $old, $novo, $user['id']);
            if ($novo === 'ENCERRADA' && is_array($resultadoCustoFinalizacao)) {
                auditoria_log(
                    $pdo,
                    'ordem_servico',
                    $id,
                    'CONFIRMAR_CUSTO_FINALIZACAO',
                    'custo_materiais',
                    $os['custo_materiais'] ?? 0,
                    $resultadoCustoFinalizacao['custo_total'] ?? 0,
                    $user['id']
                );
            }

            $osNotificacao = array_merge($os, [
                'id' => $id,
                'status' => $novo,
            ]);

            if ($podeExecutarOs) {
                whatsapp_queue_user($pdo, $os['gestor_id'] ?? null, mensagem_whatsapp_os('Status da OS atualizado pelo agente', $osNotificacao), 'Avisar gestor');
            } elseif (!empty($os['agente_id'])) {
                whatsapp_queue_user($pdo, $os['agente_id'], mensagem_whatsapp_os('Status da sua OS foi atualizado', $osNotificacao), 'Avisar agente');
            }
            if ($novo === 'CONCLUIDA_PELO_AGENTE' && !empty($os['gestor_id'])) {
                gestao_notificar($pdo,(int)$os['gestor_id'],'APROVACAO_CONCLUSAO','Conclusão aguardando aprovação',
                    ($os['numero_os'] ?? ('OS #'.$id)).' — '.($os['titulo'] ?? ''),app_path('ordem_detalhe',['id'=>$id]),
                    'conclusao-'.$id);
            }
            if ($novo === 'REPROVADA' && !empty($os['agente_id'])) {
                gestao_notificar($pdo,(int)$os['agente_id'],'OS_REPROVADA','Conclusão reprovada',
                    $obs ?: 'A OS retornou para correção.',app_path('ordem_detalhe',['id'=>$id]),
                    'reprovada-'.$id.'-'.time());
            }
            if ($novo === 'ENCERRADA') {
                if (!empty($os['ativo_id'])) {
                    $pdo->prepare("INSERT INTO ativo_falhas(ativo_id,ordem_servico_id,categoria_id,resumo)
                        SELECT ?,?,?,? WHERE NOT EXISTS
                        (SELECT 1 FROM ativo_falhas WHERE ordem_servico_id=?)")
                        ->execute([(int)$os['ativo_id'],$id,(int)$os['categoria_id'],
                            substr((string)($os['titulo'] ?? ''),0,255),$id]);
                }
                gestao_gerar_artigo_os($pdo,$id,(int)$user['id']);
            }
        }
    }

    if ($action === 'material') {
        if (!$podeAtualizarOs) {
            $_SESSION['mensagem_erro'] = 'Voce nao tem permissao para alterar esta OS.';
            redirect('ordem_detalhe?id=' . $id);
        }

        if ($os['status'] === 'ENCERRADA' && $user['perfil'] !== 'TI') {
            $_SESSION['mensagem_erro'] = 'OS encerrada não permite adicionar materiais sem reabertura autorizada pelo TI.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $st = $pdo->prepare("
            INSERT INTO materiais_os 
            (ordem_servico_id, nome_material, quantidade, unidade, observacao) 
            VALUES (?, ?, ?, ?, ?)
        ");

        $st->execute([
            $id,
            $_POST['nome_material'],
            $_POST['quantidade'],
            $_POST['unidade'],
            $_POST['observacao']
        ]);

        $movimentoMaterial = gestao_movimentar_material_os(
            $pdo,$id,trim($_POST['nome_material'] ?? ''),
            (float)($_POST['quantidade'] ?? 0),(int)$user['id'],!empty($_POST['somente_reservar'])
        );
        $custoConsumido = (float)($movimentoMaterial['custo'] ?? 0);
        if (($movimentoMaterial['status'] ?? '') === 'AGUARDANDO_APROVACAO') {
            $_SESSION['mensagem_erro'] = 'Material indisponível. A solicitação de estoque negativo foi enviada para aprovação.';
        } elseif (($movimentoMaterial['status'] ?? '') === 'RESERVADA') {
            $_SESSION['mensagem_sucesso'] = 'Material reservado para esta OS.';
        }
        auditoria_log($pdo, 'ordem_servico', $id, 'MATERIAL', 'materiais', null, trim($_POST['nome_material'] ?? ''), $user['id']);

        registrar_historico(
            $pdo,
            $id,
            $user['id'],
            $old,
            $old,
            'Material registrado: ' . $_POST['nome_material'] . ($custoConsumido > 0 ? ' | custo: R$ ' . number_format($custoConsumido, 2, ',', '.') : '')
        );
    }

    if ($action === 'anexo' && isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        if (!$podeAtualizarOs) {
            $_SESSION['mensagem_erro'] = 'Voce nao tem permissao para anexar nesta OS.';
            redirect('ordem_detalhe?id=' . $id);
        }

        if ($os['status'] === 'ENCERRADA' && $user['perfil'] !== 'TI') {
            $_SESSION['mensagem_erro'] = 'OS encerrada não permite anexos sem reabertura autorizada pelo TI.';
            redirect('ordem_detalhe?id=' . $id);
        }

        $ext = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));
        $permitidos = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (in_array($ext, $permitidos, true)) {
            $uploadDir = __DIR__ . '/uploads';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $nome = 'os_' . $id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . '/' . $nome;

            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
                $url = 'uploads/' . $nome;

                $st = $pdo->prepare("
                    INSERT INTO anexos_os 
                    (ordem_servico_id, usuario_id, tipo, arquivo_url, descricao) 
                    VALUES (?, ?, ?, ?, ?)
                ");

                $st->execute([
                    $id,
                    $user['id'],
                    $_POST['tipo'],
                    $url,
                    $_POST['descricao']
                ]);

                registrar_historico($pdo, $id, $user['id'], $old, $old, 'Anexo enviado.');
                auditoria_log($pdo, 'ordem_servico', $id, 'ANEXO', 'arquivo', null, $url, $user['id']);
            }
        }
    }

    redirect('ordem_detalhe?id=' . $id);
}

$os = load_os($pdo, $id);
$checklistOrdem = ordem_checklist($pdo, $id);
$tipoOrdemAtual = strtoupper((string)($os['tipo_ordem'] ?? (str_starts_with((string)($os['numero_os'] ?? ''),'OL-') ? 'OL' : 'OS')));
$equipeLimpezaOrdem = $tipoOrdemAtual === 'OL' ? ordem_equipe_limpeza($pdo, $id) : [];
$asgsLimpezaOrdem = array_values(array_filter($equipeLimpezaOrdem, static fn($m)=>(string)($m['papel']??'')==='ASG'));
$supervisorLimpezaOrdem = null; foreach($equipeLimpezaOrdem as $m){ if((string)($m['papel']??'')==='SUPERVISOR'){ $supervisorLimpezaOrdem=$m; break; } }

$agentes = $pdo->query("
    SELECT id, nome 
    FROM usuarios 
    WHERE perfil IN ('AGENTE','COORDENADOR_OPERACOES_OBRAS') 
    AND status = 'ATIVO' 
    ORDER BY nome
")->fetchAll();
$agentesAuxiliaresSelecionados = os_auxiliares_ids($pdo, $id);
$agentesDaOs = os_agentes_nomes($pdo, $id);
$agentePrincipalPrint = trim((string)($os['agente_nome'] ?? ''));
$agentesAuxiliaresPrint = trim((string)($os['agentes_auxiliares_nomes'] ?? ''));

// Reforço: busca diretamente na tabela ordem_agentes para garantir que a impressão
// use a mesma fonte do agente auxiliar da tela de detalhes.
try {
    $stEquipePrint = $pdo->prepare("
        SELECT oa.papel, u.nome
        FROM ordem_agentes oa
        JOIN usuarios u ON u.id = oa.usuario_id
        WHERE oa.ordem_servico_id = ?
        ORDER BY CASE WHEN oa.papel = 'PRINCIPAL' THEN 0 ELSE 1 END, u.nome
    ");
    $stEquipePrint->execute([$id]);
    $principalEquipe = '';
    $auxiliaresEquipe = [];
    foreach ($stEquipePrint->fetchAll() as $membroEquipe) {
        if (($membroEquipe['papel'] ?? '') === 'PRINCIPAL' && $principalEquipe === '') {
            $principalEquipe = trim((string)$membroEquipe['nome']);
        } elseif (($membroEquipe['papel'] ?? '') === 'AUXILIAR') {
            $auxiliaresEquipe[] = trim((string)$membroEquipe['nome']);
        }
    }
    if ($agentePrincipalPrint === '' && $principalEquipe !== '') {
        $agentePrincipalPrint = $principalEquipe;
    }
    if (!empty($auxiliaresEquipe)) {
        $agentesAuxiliaresPrint = implode(', ', array_unique(array_filter($auxiliaresEquipe)));
    }
} catch (Throwable $e) {}

if ($agentePrincipalPrint === '') {
    $agentePrincipalPrint = '-';
}
if ($agentesAuxiliaresPrint === '') {
    $agentesAuxiliaresPrint = '-';
}

$locais = $pdo->query("
    SELECT id, nome 
    FROM locais 
    ORDER BY nome
")->fetchAll();

$categorias = $pdo->query("
    SELECT id, nome 
    FROM categorias 
    ORDER BY nome
")->fetchAll();

$hist = $pdo->prepare("
    SELECT h.*, u.nome AS usuario_nome 
    FROM historico_os h 
    JOIN usuarios u ON u.id = h.usuario_id 
    WHERE ordem_servico_id = ? 
    ORDER BY h.criado_em DESC
");
$hist->execute([$id]);

$an = $pdo->prepare("
    SELECT a.*, u.nome AS usuario_nome 
    FROM anexos_os a 
    JOIN usuarios u ON u.id = a.usuario_id 
    WHERE ordem_servico_id = ? 
    ORDER BY a.criado_em DESC
");
$an->execute([$id]);
$anexos = $an->fetchAll();

$fotosAntes = [];
$fotosDepois = [];
$fotosOutras = [];
$documentosAnexos = [];

foreach ($anexos as $anexoFoto) {
    if (!anexo_eh_imagem($anexoFoto['arquivo_url'] ?? '')) {
        $documentosAnexos[] = $anexoFoto;
        continue;
    }

    $tipoFoto = strtoupper((string)($anexoFoto['tipo'] ?? ''));
    if ($tipoFoto === 'FOTO_ANTES') $fotosAntes[] = $anexoFoto;
    elseif ($tipoFoto === 'FOTO_DEPOIS') $fotosDepois[] = $anexoFoto;
    else $fotosOutras[] = $anexoFoto;
}

$totalFotosOs = count($fotosAntes) + count($fotosDepois) + count($fotosOutras);
$podeMoverFotos = $podeGerenciarOs;

$mat = $pdo->prepare("
    SELECT * 
    FROM materiais_os 
    WHERE ordem_servico_id = ? 
    ORDER BY criado_em DESC
");
$mat->execute([$id]);

$materiaisPrint = $pdo->prepare("
    SELECT * 
    FROM materiais_os 
    WHERE ordem_servico_id = ? 
    ORDER BY criado_em ASC
");
$materiaisPrint->execute([$id]);

$materiaisEstoque = [];
try {
    $materiaisEstoque = $pdo->query("
        SELECT nome, unidade, quantidade_atual, custo_unitario
        FROM estoque_materiais
        WHERE ativo = 1
        ORDER BY nome
    ")->fetchAll();
} catch (Throwable $e) {
    $materiaisEstoque = [];
}

$analiseCustoFinalizacao = custo_finalizacao_analisar_indicios($pdo, $os, $id);
$materiaisCustoIniciais = custo_finalizacao_materiais_iniciais($pdo, $id);
$confirmacaoCustoFinal = custo_finalizacao_confirmacao($pdo, $id);
$itensCustoFinal = custo_finalizacao_itens($pdo, $id);

// Se não há materiais registrados, use apenas sugestões de estoque citadas no texto.
if (!$materiaisCustoIniciais && !empty($analiseCustoFinalizacao['sugestoes'])) {
    $materiaisCustoIniciais = $analiseCustoFinalizacao['sugestoes'];
}

$qualidadeV8 = ['percentual'=>0,'itens'=>[]];
$checklistV8 = [];
if (geop_v8_table_exists($pdo, 'os_checklist_itens')) {
    $qualidadeV8 = geop_v8_quality($pdo, $id);
    $stChecklistV8 = $pdo->prepare("SELECT ci.*,u.nome concluido_por_nome FROM os_checklist_itens ci LEFT JOIN usuarios u ON u.id=ci.concluido_por WHERE ci.ordem_servico_id=? ORDER BY ci.ordem,ci.id");
    $stChecklistV8->execute([$id]);
    $checklistV8 = $stChecklistV8->fetchAll(PDO::FETCH_ASSOC);
}

$tokenAtivoStmt = $pdo->prepare("
    SELECT t.*, u.nome AS ti_nome
    FROM tokens_reabertura_os t
    JOIN usuarios u ON u.id = t.gerado_por
    WHERE t.ordem_servico_id = ?
    AND t.usado_em IS NULL
    AND t.expira_em >= NOW()
    ORDER BY t.criado_em DESC
    LIMIT 1
");
$tokenAtivoStmt->execute([$id]);
$tokenAtivo = $tokenAtivoStmt->fetch();

$auditoriaOs = [];
try {
    $auditoriaStmt = $pdo->prepare("
        SELECT ae.*, u.nome AS usuario_nome
        FROM auditoria_eventos ae
        LEFT JOIN usuarios u ON u.id = ae.usuario_id
        WHERE ae.entidade = 'ordem_servico'
        AND ae.entidade_id = ?
        ORDER BY ae.criado_em DESC
        LIMIT 30
    ");
    $auditoriaStmt->execute([$id]);
    $auditoriaOs = $auditoriaStmt->fetchAll();
} catch (Throwable $e) {
    $auditoriaOs = [];
}

$sugestoesConhecimento = conhecimento_sugestoes(
    $pdo,
    (int)($os['categoria_id'] ?? 0),
    (string)($os['titulo'] . ' ' . $os['descricao']),
    4
);

render_header('Detalhe da ' . $tipoOrdemAtual);
?>

<style>
.print-area {
    display: none;
}


.anexos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
.anexo-card-img { position: relative; border: 1px solid #dee2e6; border-radius: 12px; padding: 8px; background: #fff; }
.anexo-card-img img { width: 100%; height: 150px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; background: #f8f9fa; }
.anexo-card-img small { display: block; margin-top: 6px; color: #6c757d; }
.foto-acoes { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
.foto-acoes form { margin: 0; }

.foto-excluir-form { position: absolute; top: 14px; right: 14px; z-index: 5; margin: 0; }
.foto-excluir-btn { width: 30px; height: 30px; padding: 0; border: 2px solid #fff; border-radius: 50%; background: #dc3545; color: #fff; font-size: 20px; line-height: 24px; font-weight: 700; box-shadow: 0 2px 8px rgba(0,0,0,.35); display: flex; align-items: center; justify-content: center; }
.foto-excluir-btn:hover { background: #bb2d3b; color: #fff; }

.comparativo-fotos { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.comparativo-coluna { border: 1px solid #dee2e6; border-radius: 12px; padding: 10px; background: #f8f9fa; }
.comparativo-coluna h3 { font-size: 16px; margin-bottom: 10px; }
.custo-finalizacao-row { border: 1px solid #dee2e6; border-radius: 12px; padding: 12px; margin-bottom: 10px; background: #f8f9fa; }
.custo-finalizacao-fotos { display: grid; grid-template-columns: repeat(4, minmax(90px,1fr)); gap: 8px; }
.custo-finalizacao-fotos img { width: 100%; height: 100px; object-fit: cover; border-radius: 10px; border: 1px solid #dee2e6; }
@media (max-width: 768px) {
    .comparativo-fotos { grid-template-columns: 1fr; }
    .custo-finalizacao-fotos { grid-template-columns: repeat(2, minmax(90px,1fr)); }
}

@media print {
    @page {
        size: A4;
        margin: 0;
    }

    body * {
        visibility: hidden !important;
    }

    .print-area,
    .print-area * {
        visibility: visible !important;
    }

    .print-area {
        display: block !important;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0;
        margin: 0;
        font-family: Arial, Helvetica, sans-serif;
        color: #000;
        background: #fff;
    }

    .no-print {
        display: none !important;
    }

    .os-print-sheet {
        width: 203mm;
        min-height: 287mm;
        margin: 5mm auto;
        border: 2px solid #000;
        background: #fff;
    }

    .os-print-header {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0 0 4mm;
    }

    .os-print-header td,
    .os-print-header th {
        border: 1px solid #000;
        padding: 2px 4px;
        font-size: 9px;
        line-height: 1.15;
        vertical-align: middle;
    }

    .os-print-header .logo-cell {
        width: 24mm;
        text-align: center;
    }

    .os-print-header img {
        max-width: 17mm;
        max-height: 17mm;
    }

    .os-print-header .title-cell,
    .os-print-header .subtitle-cell {
        text-align: center;
        font-size: 11px !important;
        font-weight: bold;
    }

    .os-print-header .info-cell {
        width: 29mm;
        font-size: 7px !important;
        font-weight: bold;
    }

    .os-print-content {
        padding: 0 4mm 4mm;
    }

    .print-section {
        margin-bottom: 4mm;
    }

    .print-section h4 {
        margin: 0;
        border: 1px solid #000;
        border-bottom: 0;
        padding: 2px 4px;
        font-size: 9px;
        text-transform: uppercase;
        background: #fff;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
    }

    .print-table td,
    .print-table th {
        border: 1px solid #000;
        padding: 2px 4px;
        font-size: 8.5px;
        line-height: 1.2;
        vertical-align: top;
    }

    .print-description {
        min-height: 34mm;
        text-align: justify;
        font-size: 9px;
        line-height: 1.3;
        padding: 5px !important;
    }

    .print-lines td {
        height: 8mm;
        border: 1px solid #000;
    }

    .signature-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .signature-table td {
        width: 50%;
        text-align: center;
        height: 30mm;
        vertical-align: bottom;
        border: 1px solid #000;
        padding: 4px;
        font-size: 9px;
    }


    .print-photo-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4mm; margin-top: 2mm; }
    .print-photo-card { border: 1px solid #000; padding: 2mm; break-inside: avoid; page-break-inside: avoid; }
    .print-photo-card img { width: 100%; height: 55mm; object-fit: cover; border: 1px solid #000; display: block; }
    .print-photo-card div { font-size: 8px; margin-top: 1mm; line-height: 1.2; }
    .print-photo-title { font-weight: bold; font-size: 9px; margin: 2mm 0 1mm; text-transform: uppercase; }

    body.imprimir-os .relatorio-fotografico {
        display: none !important;
        visibility: hidden !important;
    }

    body.imprimir-relatorio .relatorio-fotografico {
        display: block !important;
        visibility: visible !important;
    }

    body.imprimir-relatorio .observacoes-os-simples {
        display: none !important;
        visibility: hidden !important;
    }
}

@media print {
    body.imprimir-relatorio .os-print-sheet {
        border: 1px solid #000;
    }

    body.imprimir-relatorio .print-photo-card img {
        height: 70mm;
        object-fit: contain;
        background: #fff;
    }
}

</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h1 class="h3">
        <?= e($os['numero_os']) ?> - <?= e($os['titulo']) ?>
    </h1>

    <div class="d-flex gap-2">
        <button type="button" onclick="imprimirOS()" class="btn btn-success">
            Imprimir <?= e($tipoOrdemAtual) ?>
        </button>

        <button type="button" onclick="gerarRelatorioOS()" class="btn btn-primary">
            Gerar relatório
        </button>

        <a class="btn btn-outline-secondary" href="<?= e(app_path('ordens')) ?>">
            Voltar
        </a>
    </div>
</div>

<?php if ($mensagemSucesso): ?>
    <div class="alert alert-success no-print"><?= e($mensagemSucesso) ?></div>
<?php endif; ?>

<?php if ($mensagemErro): ?>
    <div class="alert alert-danger no-print"><?= e($mensagemErro) ?></div>
<?php endif; ?>

<?php if ($tokenGerado): ?>
    <div class="alert alert-warning no-print">
        <b>Token de reabertura gerado:</b>
        <span class="fs-4 fw-bold ms-2"><?= e($tokenGerado) ?></span>
        <div class="small mt-1">Passe este token ao responsável que precisa reabrir a ordem. Ele vale por 24 horas e só pode ser usado uma vez.</div>
    </div>
<?php endif; ?>

<?php render_whatsapp_queue(); ?>

<div class="row g-3 no-print">
    <div class="col-lg-8">
        <div class="card p-3 mb-3">
            <h2 class="h5">Informações</h2>

            <p><?= nl2br(e($os['descricao'])) ?></p>

            <?php if ($tipoOrdemAtual === 'OL'): ?>
                <div class="alert alert-success py-2"><strong>Ordem de Limpeza (OL)</strong> — esta ordem usa o mesmo fluxo da OS e possui checklist próprio.</div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <b>Local:</b><br>
                    <?= e($os['local_nome']) ?>
                </div>

                <div class="col-md-4">
                    <b>Categoria:</b><br>
                    <?= e($os['categoria_nome']) ?>
                </div>

                <div class="col-md-4">
                    <b>Prioridade:</b><br>
                    <?= badge_prioridade($os['prioridade']) ?>
                </div>

                <div class="col-md-4 mt-2">
                    <b>Status:</b><br>
                    <?= badge_status($os['status']) ?>
                </div>

                <div class="col-md-4 mt-2">
                    <b>Gestor:</b><br>
                    <?= e($os['gestor_nome']) ?>
                </div>

                <?php if ($tipoOrdemAtual === 'OL'): ?>
                    <div class="col-md-4 mt-2">
                        <b>Responsável principal:</b><br>
                        <?php $principalNome='-'; foreach($asgsLimpezaOrdem as $m){ if(!empty($m['principal'])){$principalNome=$m['nome_colaborador'];break;} } ?>
                        <?= e($principalNome) ?>
                    </div>
                    <div class="col-md-4 mt-2">
                        <b>Equipe ASG:</b><br>
                        <?= e($asgsLimpezaOrdem ? implode(', ', array_column($asgsLimpezaOrdem,'nome_colaborador')) : '-') ?>
                    </div>
                    <div class="col-md-4 mt-2">
                        <b>Supervisor:</b><br>
                        <?= e($supervisorLimpezaOrdem['nome_colaborador'] ?? '-') ?>
                    </div>
                <?php else: ?>
                    <div class="col-md-4 mt-2">
                        <b>Agente principal:</b><br>
                        <?= e($os['agente_nome'] ?: '-') ?>
                    </div>
                    <div class="col-md-4 mt-2">
                        <b>Agente auxiliar:</b><br>
                        <?= e($os['agentes_auxiliares_nomes'] ?: '-') ?>
                    </div>
                <?php endif; ?>

                <div class="col-md-4 mt-2">
                    <b>Prazo:</b><br>
                    <?= e($os['prazo'] ?: '-') ?>
                </div>

                <div class="col-md-4 mt-2">
                    <b>SLA:</b><br>
                    <?= badge_sla($os) ?>
                </div>

                <div class="col-md-4 mt-2">
                    <b>Custo materiais:</b><br>
                    R$ <?= e(number_format((float)($os['custo_materiais'] ?? 0), 2, ',', '.')) ?>
                </div>
                <?php if (geop_v8_column_exists($pdo,'ordens_servico','custo_total_final')): ?>
                <div class="col-md-4 mt-2"><b>Custo final:</b><br>R$ <?= e(number_format((float)($os['custo_total_final'] ?? 0),2,',','.')) ?> <a class="small" href="<?= e(app_path('custos_os',['os_id'=>$id])) ?>">detalhar</a></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($sugestoesConhecimento): ?>
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <h2 class="h5 mb-0">Soluções sugeridas</h2>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('conhecimento', ['categoria_id' => $os['categoria_id']])) ?>">Base completa</a>
                </div>
                <div class="accordion" id="solucoes-os">
                    <?php foreach ($sugestoesConhecimento as $indice => $artigo): ?>
                        <div class="accordion-item">
                            <h3 class="accordion-header">
                                <button class="accordion-button <?= $indice ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#solucao-<?= e($artigo['id']) ?>">
                                    <?= e($artigo['titulo']) ?>
                                </button>
                            </h3>
                            <div id="solucao-<?= e($artigo['id']) ?>" class="accordion-collapse collapse <?= !$indice ? 'show' : '' ?>" data-bs-parent="#solucoes-os">
                                <div class="accordion-body">
                                    <?php if (!empty($artigo['problema'])): ?><p><strong>Problema:</strong><br><?= nl2br(e($artigo['problema'])) ?></p><?php endif; ?>
                                    <?php if (!empty($artigo['causa'])): ?><p><strong>Causa provável:</strong><br><?= nl2br(e($artigo['causa'])) ?></p><?php endif; ?>
                                    <div class="alert alert-success mb-0"><strong>Solução:</strong><br><?= nl2br(e($artigo['solucao'] ?: $artigo['conteudo'])) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($os['status'] === 'ENCERRADA' && conhecimento_pode_gerenciar()): ?>
            <div class="card p-3 mb-3">
                <h2 class="h5">Registrar solução na base</h2>
                <p class="text-muted">Use os dados desta OS para documentar uma solução reutilizável.</p>
                <a class="btn btn-outline-success" href="<?= e(app_path('conhecimento', [
                    'nova_os_id' => $os['id']
                ])) ?>">Criar artigo a partir desta OS</a>
            </div>
        <?php endif; ?>

        <?php if ($podeGerenciarOs): ?>
            <div class="card p-3 mb-3">
                <h2 class="h5">Editar OS</h2>

                <form method="post" class="row g-2">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="editar">

                    <div class="col-md-12">
                        <label class="form-label">Título</label>
                        <input class="form-control" name="titulo" value="<?= e($os['titulo']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Local</label>
                        <select class="form-select" name="local_id" required>
                            <?php foreach ($locais as $l): ?>
                                <option value="<?= e($l['id']) ?>" <?= (int)$os['local_id'] === (int)$l['id'] ? 'selected' : '' ?>>
                                    <?= e($l['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="categoria_id" required>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= (int)$os['categoria_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Prioridade</label>
                        <select class="form-select" name="prioridade" required>
                            <?php foreach (['BAIXA', 'MEDIA', 'ALTA', 'CRITICA', 'URGENTE'] as $p): ?>
                                <option value="<?= e($p) ?>" <?= $os['prioridade'] === $p ? 'selected' : '' ?>>
                                    <?= e($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Prazo</label>
                        <input 
                            class="form-control" 
                            type="datetime-local" 
                            name="prazo" 
                            value="<?= e($os['prazo'] ? str_replace(' ', 'T', substr($os['prazo'], 0, 16)) : '') ?>"
                        >
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" name="descricao" rows="5" required><?= e($os['descricao']) ?></textarea>
                    </div>

                    <div class="col-md-12">
                        <button class="btn btn-warning w-100">
                            Salvar edição da OS
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="card p-3 mb-3">
            <h2 class="h5">Anexos</h2>

            <?php if ($podeAtualizarOs): ?>
            <form method="post" enctype="multipart/form-data" class="row g-2 mb-3">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="anexo">

                <div class="col-md-3">
                    <select class="form-select" name="tipo">
                        <option>FOTO_ANTES</option>
                        <option>FOTO_DEPOIS</option>
                        <option>DOCUMENTO</option>
                        <option>OUTRO</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <input class="form-control" type="file" name="arquivo" required>
                </div>

                <div class="col-md-3">
                    <input class="form-control" name="descricao" placeholder="Descrição">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-dark w-100">Enviar</button>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($totalFotosOs > 0): ?>
                <div class="mb-4">
                    <h3 class="h6 mb-3">Registros fotográficos da OS</h3>

                    <div class="comparativo-fotos">
                        <div class="comparativo-coluna">
                            <h3>Antes</h3>
                            <?php if ($fotosAntes): ?>
                                <div class="anexos-grid">
                                    <?php foreach ($fotosAntes as $foto): ?>
                                        <?php $fotoUrl = anexo_url_segura($foto['arquivo_url']); ?>
                                        <div class="anexo-card-img">
                                            <?php $podeExcluirEstaFoto = $podeGerenciarOs || ($podeExecutarOs && (int)($foto['usuario_id'] ?? 0) === (int)$user['id'] && $os['status'] !== 'ENCERRADA'); ?>
                                            <?php if ($podeExcluirEstaFoto): ?>
                                                <form method="post" class="foto-excluir-form" onsubmit="return confirm('Apagar esta foto definitivamente?');">
                                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="excluir_foto">
                                                    <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                    <button type="submit" class="foto-excluir-btn" title="Apagar foto" aria-label="Apagar foto">&times;</button>
                                                </form>
                                            <?php endif; ?>
                                            <a class="text-decoration-none text-dark" href="<?= e($fotoUrl) ?>" target="_blank">
                                                <img src="<?= e($fotoUrl) ?>" alt="Foto antes da OS <?= e($os['numero_os']) ?>" loading="lazy">
                                                <small><?= e($foto['descricao'] ?: 'Sem descrição') ?><br>Enviado por <?= e($foto['usuario_nome']) ?> em <?= e($foto['criado_em']) ?></small>
                                            </a>
                                            <?php if ($podeMoverFotos): ?>
                                                <div class="foto-acoes">
                                                    <form method="post">
                                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="action" value="mover_foto">
                                                        <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                        <input type="hidden" name="novo_tipo" value="FOTO_DEPOIS">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">Mover para Depois</button>
                                                    </form>
                                                    <form method="post">
                                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="action" value="mover_foto">
                                                        <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                        <input type="hidden" name="novo_tipo" value="OUTRO">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Mover para Outras</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">Nenhuma foto antes enviada.</div>
                            <?php endif; ?>
                        </div>

                        <div class="comparativo-coluna">
                            <h3>Depois</h3>
                            <?php if ($fotosDepois): ?>
                                <div class="anexos-grid">
                                    <?php foreach ($fotosDepois as $foto): ?>
                                        <?php $fotoUrl = anexo_url_segura($foto['arquivo_url']); ?>
                                        <div class="anexo-card-img">
                                            <?php $podeExcluirEstaFoto = $podeGerenciarOs || ($podeExecutarOs && (int)($foto['usuario_id'] ?? 0) === (int)$user['id'] && $os['status'] !== 'ENCERRADA'); ?>
                                            <?php if ($podeExcluirEstaFoto): ?>
                                                <form method="post" class="foto-excluir-form" onsubmit="return confirm('Apagar esta foto definitivamente?');">
                                                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                    <input type="hidden" name="action" value="excluir_foto">
                                                    <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                    <button type="submit" class="foto-excluir-btn" title="Apagar foto" aria-label="Apagar foto">&times;</button>
                                                </form>
                                            <?php endif; ?>
                                            <a class="text-decoration-none text-dark" href="<?= e($fotoUrl) ?>" target="_blank">
                                                <img src="<?= e($fotoUrl) ?>" alt="Foto depois da OS <?= e($os['numero_os']) ?>" loading="lazy">
                                                <small><?= e($foto['descricao'] ?: 'Sem descrição') ?><br>Enviado por <?= e($foto['usuario_nome']) ?> em <?= e($foto['criado_em']) ?></small>
                                            </a>
                                            <?php if ($podeMoverFotos): ?>
                                                <div class="foto-acoes">
                                                    <form method="post">
                                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="action" value="mover_foto">
                                                        <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                        <input type="hidden" name="novo_tipo" value="FOTO_ANTES">
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">Mover para Antes</button>
                                                    </form>
                                                    <form method="post">
                                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                        <input type="hidden" name="action" value="mover_foto">
                                                        <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                        <input type="hidden" name="novo_tipo" value="OUTRO">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Mover para Outras</button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">Nenhuma foto depois enviada.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($fotosOutras): ?>
                        <div class="mt-3">
                            <h3 class="h6">Outras imagens</h3>
                            <div class="anexos-grid">
                                <?php foreach ($fotosOutras as $foto): ?>
                                    <?php $fotoUrl = anexo_url_segura($foto['arquivo_url']); ?>
                                    <div class="anexo-card-img">
                                        <?php $podeExcluirEstaFoto = $podeGerenciarOs || ($podeExecutarOs && (int)($foto['usuario_id'] ?? 0) === (int)$user['id'] && $os['status'] !== 'ENCERRADA'); ?>
                                        <?php if ($podeExcluirEstaFoto): ?>
                                            <form method="post" class="foto-excluir-form" onsubmit="return confirm('Apagar esta foto definitivamente?');">
                                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="action" value="excluir_foto">
                                                <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                <button type="submit" class="foto-excluir-btn" title="Apagar foto" aria-label="Apagar foto">&times;</button>
                                            </form>
                                        <?php endif; ?>
                                        <a class="text-decoration-none text-dark" href="<?= e($fotoUrl) ?>" target="_blank">
                                            <img src="<?= e($fotoUrl) ?>" alt="Imagem anexada na OS <?= e($os['numero_os']) ?>" loading="lazy">
                                            <small><b><?= e($foto['tipo']) ?></b><br><?= e($foto['descricao'] ?: 'Sem descrição') ?><br>Enviado por <?= e($foto['usuario_nome']) ?> em <?= e($foto['criado_em']) ?></small>
                                        </a>
                                        <?php if ($podeMoverFotos): ?>
                                            <div class="foto-acoes">
                                                <form method="post">
                                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="action" value="mover_foto">
                                                    <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                    <input type="hidden" name="novo_tipo" value="FOTO_ANTES">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Mover para Antes</button>
                                                </form>
                                                <form method="post">
                                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                    <input type="hidden" name="action" value="mover_foto">
                                                    <input type="hidden" name="anexo_id" value="<?= e($foto['id']) ?>">
                                                    <input type="hidden" name="novo_tipo" value="FOTO_DEPOIS">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Mover para Depois</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($documentosAnexos): ?>
                <h3 class="h6">Documentos anexados</h3>
            <?php endif; ?>

            <?php foreach ($documentosAnexos as $a): ?>
                <?php $arquivoUrl = anexo_url_segura($a['arquivo_url']); ?>
                <div class="border rounded p-2 mb-2">
                    <b><?= e($a['tipo']) ?></b>
                    por <?= e($a['usuario_nome']) ?> - <?= e($a['criado_em']) ?><br>
                    <a href="<?= e($arquivoUrl) ?>" target="_blank"><?= e($arquivoUrl) ?></a><br>
                    <?= e($a['descricao']) ?>
                </div>
            <?php endforeach; ?>

            <?php if (!$anexos): ?>
                <div class="text-muted">Nenhum anexo enviado para esta OS.</div>
            <?php endif; ?>
        </div>

        <?php if ($checklistV8): ?>
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2"><h2 class="h5 mb-0">Checklist da categoria</h2><span class="badge text-bg-secondary"><?= count(array_filter($checklistV8, fn($ci)=>!empty($ci['concluido']))) ?>/<?= count($checklistV8) ?></span></div>
            <?php foreach ($checklistV8 as $ci): ?>
                <form method="post" class="border rounded p-2 mb-2">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="action" value="v8_checklist_update"><input type="hidden" name="item_id" value="<?= (int)$ci['id'] ?>">
                    <div class="d-flex gap-2 align-items-start">
                        <input class="form-check-input mt-1" type="checkbox" name="concluido" value="1" <?= !empty($ci['concluido'])?'checked':'' ?> <?= !$podeAtualizarOs?'disabled':'' ?>>
                        <div class="flex-grow-1"><strong><?= e($ci['descricao']) ?></strong><?php if(!empty($ci['obrigatorio'])): ?><span class="badge text-bg-danger ms-1">obrigatório</span><?php endif; ?><?php if(!empty($ci['exige_foto'])): ?><span class="badge text-bg-info ms-1">foto</span><?php endif; ?><input class="form-control form-control-sm mt-1" name="observacao_checklist" value="<?= e($ci['observacao']??'') ?>" placeholder="Observação" <?= !$podeAtualizarOs?'disabled':'' ?>></div>
                        <?php if($podeAtualizarOs): ?><button class="btn btn-sm btn-outline-primary">Salvar</button><?php endif; ?>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card p-3">
            <h2 class="h5">Histórico</h2>

            <?php foreach ($hist as $h): ?>
                <div class="border-start border-3 ps-3 mb-3">
                    <b><?= e($h['usuario_nome']) ?></b> -
                    <?= e($h['criado_em']) ?>
                    <br>

                    <?= e($h['status_anterior'] ?: '-') ?>
                    →
                    <?= e($h['status_novo'] ?: '-') ?>

                    <br>
                    <?= e($h['descricao']) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($podeGerenciarOs): ?>
            <div class="card p-3 mt-3">
                <h2 class="h5">Auditoria</h2>

                <?php foreach ($auditoriaOs as $aev): ?>
                    <div class="border rounded p-2 mb-2 small">
                        <b><?= e($aev['acao']) ?></b>
                        <?php if (!empty($aev['campo'])): ?>
                            em <b><?= e($aev['campo']) ?></b>
                        <?php endif; ?>
                        por <?= e($aev['usuario_nome'] ?: 'Sistema') ?>
                        em <?= e($aev['criado_em']) ?>
                        <br>
                        IP: <?= e($aev['ip'] ?: '-') ?>
                        <?php if ($aev['valor_anterior'] !== null || $aev['valor_novo'] !== null): ?>
                            <br>
                            <span class="text-muted">Antes:</span> <?= e($aev['valor_anterior'] ?: '-') ?>
                            <br>
                            <span class="text-muted">Depois:</span> <?= e($aev['valor_novo'] ?: '-') ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (!$auditoriaOs): ?>
                    <div class="text-muted small">Nenhum evento de auditoria registrado para esta OS.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <?= exclusao_render_card($pdo, 'ORDEM_SERVICO', $id, $user) ?>
        <?php if (geop_v8_table_exists($pdo, 'os_checklist_itens')): ?>
        <?php $qPct=(int)($qualidadeV8['percentual']??0); $qClass=$qPct>=90?'success':($qPct>=70?'warning':'danger'); ?>
        <div class="card p-3 mb-3 border-<?= e($qClass) ?>">
            <div class="d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Qualidade da OS</h2><span class="badge text-bg-<?= e($qClass) ?> fs-6"><?= $qPct ?>%</span></div>
            <div class="progress my-2" role="progressbar"><div class="progress-bar bg-<?= e($qClass) ?>" style="width:<?= $qPct ?>%"></div></div>
            <?php foreach ($qualidadeV8['itens'] as $qi): ?>
                <div class="small <?= $qi['ok']?'text-success':'text-muted' ?>"><?= $qi['ok']?'✓':'○' ?> <?= e($qi['label']) ?><?php if(!$qi['ok']&&!empty($qi['detalhe'])): ?> — <?= e($qi['detalhe']) ?><?php endif; ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($os['status'] === 'ENCERRADA'): ?>
            <div class="card p-3 mb-3 border-warning" id="token-reabertura">
                <h2 class="h5 text-warning">Reabertura com token</h2>
                <p class="small text-muted mb-2">Esta OS está encerrada. Para reabrir, o TI precisa gerar um token nesta própria ordem de serviço.</p>

                <?php if ($user['perfil'] === 'TI'): ?>
                    <form method="post" class="mb-2">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="gerar_token_reabertura">
                        <button class="btn btn-warning w-100">Gerar token de reabertura</button>
                    </form>
                    <?php if ($tokenAtivo): ?>
                        <div class="small text-muted">Já existe token ativo gerado por <?= e($tokenAtivo['ti_nome']) ?> em <?= e($tokenAtivo['criado_em']) ?>, válido até <?= e($tokenAtivo['expira_em']) ?>. Gerar outro invalida os anteriores.</div>
                    <?php endif; ?>
                <?php elseif ($podeAtualizarOs): ?>
                    <?php $tokenPendenteSolicitado = token_reabertura_tem_solicitacao_pendente($pdo, $id); ?>
                    <form method="post" class="row g-2 mb-3">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="solicitar_token_reabertura">
                        <div class="col-12">
                            <label class="form-label">Solicitar token ao TI</label>
                            <textarea class="form-control" name="motivo_token" rows="2" placeholder="Explique por que precisa reabrir esta OS" <?= $tokenPendenteSolicitado ? 'disabled' : '' ?>></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-danger w-100" <?= $tokenPendenteSolicitado ? 'disabled' : '' ?>>
                                <?= $tokenPendenteSolicitado ? 'Token já solicitado ao TI' : 'Solicitar token ao TI' ?>
                            </button>
                        </div>
                    </form>

                    <form method="post" class="row g-2">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="reabrir_com_token">
                        <div class="col-12">
                            <label class="form-label">Token autorizado pelo TI</label>
                            <input class="form-control" name="token_reabertura" placeholder="Digite o token" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motivo da reabertura</label>
                            <textarea class="form-control" name="obs_reabertura" rows="2" placeholder="Informe o motivo"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-warning w-100">Reabrir com token</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">Acesso somente para consulta.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card p-3 mb-3" id="atribuir-agente">
            <h2 class="h5">Atualizar</h2>

            <?php if ($podeGerenciarOs && ($os['status'] !== 'ENCERRADA' || $user['perfil'] === 'TI')): ?>
                <form method="post" class="mb-3">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="atribuir">

                    <label class="form-label">Agente principal / executante</label>

                    <select class="form-select mb-2" name="agente_id">
                        <option value="">Sem agente</option>

                        <?php foreach ($agentes as $a): ?>
                            <option value="<?= e($a['id']) ?>" <?= (int)$os['agente_id'] === (int)$a['id'] ? 'selected' : '' ?>>
                                <?= e($a['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label mt-2">Agente auxiliar</label>
                    <select class="form-select mb-2" name="agentes_auxiliares[]" multiple size="4">
                        <?php foreach ($agentes as $a): ?>
                            <option value="<?= e($a['id']) ?>" <?= in_array((int)$a['id'], $agentesAuxiliaresSelecionados, true) ? 'selected' : '' ?>>
                                <?= e($a['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text mb-2">Segure Ctrl para selecionar mais de um auxiliar. A OS aparece para todos, mas no relatório geral continua contando uma única OS.</div>

                    <button class="btn btn-primary w-100">
                        Salvar responsável
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($podeGerenciarOs && !in_array($os['status'], ['CONCLUIDA_PELO_AGENTE','ENCERRADA'], true)): ?>
            <div class="border border-success rounded p-3 mb-3">
                <h3 class="h6">Finalização pelo gestor</h3>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="status" value="ENCERRADA">
                    <textarea class="form-control mb-2" name="obs" placeholder="Parecer obrigatório para finalizar a OS" required></textarea>
                    <button class="btn btn-success w-100">Finalizar OS agora</button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($podeGerenciarOs && $os['status'] === 'CONCLUIDA_PELO_AGENTE'): ?>
            <div class="alert alert-info">O técnico solicitou a conclusão. Confira fotos, checklist e solução antes de finalizar.</div>
            <form method="post" class="mb-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="status" value="ENCERRADA">
                <label class="form-label">Parecer do gestor</label>
                <textarea class="form-control mb-2" name="obs" placeholder="Informe o parecer da aprovação" required></textarea>
                <button class="btn btn-success w-100">Finalizar OS</button>
            </form>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="status" value="REPROVADA">
                <textarea class="form-control mb-2" name="obs" placeholder="Justificativa obrigatória para devolver ao técnico" required></textarea>
                <button class="btn btn-outline-danger w-100">Reprovar conclusão</button>
            </form>
            <?php elseif ($podeAtualizarOs && $os['status'] !== 'ENCERRADA'): ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="status">

                <label class="form-label">Status</label>

                <select class="form-select mb-2" name="status">
                    <?php
                    $opts = $podeExecutarOs && !$podeGerenciarOs
                        ? ['EM_ANDAMENTO', 'PAUSADA', 'AGUARDANDO_MATERIAL', 'CONCLUIDA_PELO_AGENTE']
                        : ['ABERTA', 'ATRIBUIDA', 'EM_ANDAMENTO', 'PAUSADA', 'AGUARDANDO_MATERIAL', 'REPROVADA', 'ENCERRADA'];

                    foreach ($opts as $o):
                    ?>
                        <option value="<?= e($o) ?>" <?= $os['status'] === $o ? 'selected' : '' ?>>
                            <?= e(str_replace('_', ' ', $o)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <textarea class="form-control mb-2" name="obs" placeholder="Observação"></textarea>

                <button class="btn btn-success w-100">
                    Atualizar status
                </button>
            </form>
            <?php elseif ($os['status'] === 'ENCERRADA'): ?>
                <div class="alert alert-warning mb-0">OS encerrada. Para reabrir, use o token gerado pelo TI.</div>
            <?php else: ?>
                <div class="alert alert-secondary mb-0">Voce possui acesso somente para consulta desta OS.</div>
            <?php endif; ?>
        </div>

        <div class="card p-3">
            <h2 class="h5">Materiais</h2>

            <?php if ($confirmacaoCustoFinal): ?>
                <div class="alert <?= !empty($confirmacaoCustoFinal['houve_custo']) ? 'alert-success' : 'alert-secondary' ?> py-2">
                    <strong>Conferência de custo na finalização:</strong>
                    <?= !empty($confirmacaoCustoFinal['houve_custo']) ? 'houve custo' : 'sem custo de material' ?>.
                    <?php if (!empty($confirmacaoCustoFinal['houve_custo'])): ?>
                        Total confirmado: <strong>R$ <?= e(number_format((float)$confirmacaoCustoFinal['custo_total'], 2, ',', '.')) ?></strong>.
                    <?php endif; ?>
                    <div class="small mt-1">
                        Confirmado por <?= e($confirmacaoCustoFinal['confirmado_por_nome'] ?: ('usuário #' . $confirmacaoCustoFinal['confirmado_por'])) ?>
                        em <?= e($confirmacaoCustoFinal['confirmado_em']) ?>.
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($podeAtualizarOs): ?>
            <form method="post" class="row g-2 mb-3">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="material">

                <div class="col-12">
                    <input class="form-control" name="nome_material" list="materiais-estoque" placeholder="Material" required>
                    <datalist id="materiais-estoque">
                        <?php foreach ($materiaisEstoque as $me): ?>
                            <option value="<?= e($me['nome']) ?>">
                                <?= e($me['nome']) ?> | estoque <?= e($me['quantidade_atual']) ?> <?= e($me['unidade']) ?> | R$ <?= e(number_format((float)$me['custo_unitario'], 2, ',', '.')) ?>
                            </option>
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="col-6">
                    <input class="form-control" name="quantidade" type="number" step="0.01" placeholder="Qtd" required>
                </div>

                <div class="col-6">
                    <input class="form-control" name="unidade" placeholder="Unidade">
                </div>

                <div class="col-12">
                    <input class="form-control" name="observacao" placeholder="Observação">
                </div>
                <div class="col-12 form-check ms-2">
                    <input class="form-check-input" type="checkbox" name="somente_reservar" value="1" id="somente_reservar">
                    <label class="form-check-label" for="somente_reservar">Reservar para uso posterior (não consumir agora)</label>
                </div>

                <div class="col-12">
                    <button class="btn btn-dark w-100">
                        Adicionar
                    </button>
                </div>
            </form>
            <?php endif; ?>

            <?php foreach ($mat as $m): ?>
                <div class="border rounded p-2 mb-2">
                    <?= e($m['nome_material']) ?> -
                    <?= e($m['quantidade']) ?>
                    <?= e($m['unidade']) ?>

                    <br>
                    <small><?= e($m['observacao']) ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if ($podeGerenciarOs && $os['status'] !== 'ENCERRADA'): ?>
<div class="modal fade no-print" id="modalCustoFinalizacao" tabindex="-1" aria-labelledby="modalCustoFinalizacaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="modalCustoFinalizacaoLabel">Finalizar OS — conferência de custo de material</h2>
                    <div class="small text-muted"><?= e($os['numero_os']) ?> — <?= e($os['titulo']) ?></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <?php
                $nivelCusto = (string)($analiseCustoFinalizacao['nivel'] ?? 'BAIXO');
                $classeCusto = $nivelCusto === 'ALTO' ? 'alert-warning' : ($nivelCusto === 'MEDIO' ? 'alert-info' : 'alert-secondary');
                ?>
                <div class="alert <?= e($classeCusto) ?>">
                    <div class="fw-bold">
                        Análise automática: indício <?= e($nivelCusto) ?> de custo
                        <?php if (!empty($analiseCustoFinalizacao['provavel_custo'])): ?> — confira antes de encerrar.<?php endif; ?>
                    </div>
                    <ul class="mb-0 mt-2">
                        <?php foreach (($analiseCustoFinalizacao['evidencias'] ?? []) as $evidencia): ?>
                            <li><?= e($evidencia) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="small mt-2">
                        A análise automática usa descrição, histórico, materiais, custos existentes e descrições das imagens.
                        As fotos abaixo também são exibidas para conferência visual do gestor.
                    </div>
                </div>

                <?php
                $fotosPopup = array_slice(array_values(array_merge($fotosDepois, $fotosAntes, $fotosOutras)), 0, 4);
                ?>
                <?php if ($fotosPopup): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Imagens da OS para conferência</label>
                        <div class="custo-finalizacao-fotos">
                            <?php foreach ($fotosPopup as $fotoPopup): ?>
                                <?php $fotoPopupUrl = anexo_url_segura($fotoPopup['arquivo_url']); ?>
                                <a href="<?= e($fotoPopupUrl) ?>" target="_blank" title="<?= e($fotoPopup['descricao'] ?: 'Imagem da OS') ?>">
                                    <img src="<?= e($fotoPopupUrl) ?>" alt="Evidência da OS">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card border-0 bg-light p-3 mb-3">
                    <label class="form-label fw-bold mb-2">Esta OS gerou algum custo com material?</label>
                    <div class="d-flex gap-4 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modal_houve_custo" id="modalCustoSim" value="SIM"
                                <?= !empty($analiseCustoFinalizacao['provavel_custo']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modalCustoSim">Sim, houve custo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="modal_houve_custo" id="modalCustoNao" value="NAO"
                                <?= empty($analiseCustoFinalizacao['provavel_custo']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="modalCustoNao">Não houve custo</label>
                        </div>
                    </div>
                </div>

                <div id="boxMateriaisFinalizacao">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h3 class="h6 mb-0">Materiais e custos</h3>
                            <div class="small text-muted">Confira as sugestões, ajuste quantidade/custo ou remova o que não se aplicar.</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAdicionarCustoMaterial">+ Adicionar material</button>
                    </div>
                    <div id="listaMateriaisFinalizacao"></div>
                    <div class="text-end fs-5 mt-2">
                        Total confirmado: <strong id="totalCustoFinalizacao">R$ 0,00</strong>
                    </div>
                </div>

                <div id="boxJustificativaSemCusto" class="mt-3 d-none">
                    <label class="form-label">
                        Justificativa para ausência de custo
                        <?php if (($analiseCustoFinalizacao['score'] ?? 0) >= 8): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>
                    <textarea class="form-control" id="justificativaSemCusto" rows="2" placeholder="Ex.: foi utilizado material reaproveitado, sem custo para esta OS."></textarea>
                    <?php if (($analiseCustoFinalizacao['score'] ?? 0) >= 8): ?>
                        <div class="form-text text-warning">A análise encontrou indícios fortes de custo; a justificativa é obrigatória se você marcar “Não”.</div>
                    <?php endif; ?>
                </div>

                <datalist id="materiaisEstoqueFinalizacao">
                    <?php foreach ($materiaisEstoque as $me): ?>
                        <option
                            value="<?= e($me['nome']) ?>"
                            data-unidade="<?= e($me['unidade'] ?? '') ?>"
                            data-custo="<?= e((string)($me['custo_unitario'] ?? 0)) ?>"
                        ><?= e($me['nome']) ?> — R$ <?= e(number_format((float)$me['custo_unitario'], 2, ',', '.')) ?></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                <button type="button" class="btn btn-success" id="btnConfirmarFinalizacaoComCusto">Confirmar custos e finalizar OS</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="print-area">
    <div class="os-print-sheet">
        <table class="os-print-header">
            <tr>
                <td rowspan="3" class="logo-cell">
                    <img src="../public/assets/imobilizado_logo.png" alt="Logo">
                </td>
                <td colspan="3" class="title-cell">SISTEMA DE GESTAO DE ORDENS DE SERVICO</td>
                <td class="info-cell">CODIGO: OS</td>
            </tr>
            <tr>
                <td colspan="3" class="subtitle-cell">ORDEM DE SERVICO</td>
                <td class="info-cell">REVISAO: 00</td>
            </tr>
            <tr>
                <td colspan="3"></td>
                <td class="info-cell">DATA: <?= date('d/m/Y') ?></td>
            </tr>
            <tr>
                <td colspan="2"><b>NUMERO:</b> <?= e($os['numero_os']) ?></td>
                <td colspan="2"><b>STATUS:</b> <?= e(str_replace('_', ' ', $os['status'])) ?></td>
                <td><b>EMISSAO:</b> <?= date('d/m/Y H:i') ?></td>
            </tr>
        </table>

        <div class="os-print-content">
            <div class="print-section relatorio-fotografico">
                <h4>Relatório fotográfico da ordem de serviço</h4>
                <table class="print-table">
                    <tr>
                        <td><b>OS:</b> <?= e($os['numero_os']) ?></td>
                        <td><b>LOCAL:</b> <?= e($os['local_nome']) ?></td>
                        <td><b>AGENTE PRINCIPAL:</b> <?= e($agentePrincipalPrint !== '' ? $agentePrincipalPrint : '-') ?></td>
                        <td><b>AUXILIAR:</b> <?= e($agentesAuxiliaresPrint !== '' ? $agentesAuxiliaresPrint : '-') ?></td>
                        <td><b>EMISSÃO:</b> <?= date('d/m/Y H:i') ?></td>
                    </tr>
                </table>
            </div>

            <div class="print-section equipe-executora-print">
                <h4>Equipe executora</h4>
                <table class="print-table">
                    <tr>
                        <td style="width:50%"><b>AGENTE PRINCIPAL / EXECUTANTE:</b><br><?= e($agentePrincipalPrint) ?></td>
                        <td style="width:50%"><b>AGENTE AUXILIAR:</b><br><?= e($agentesAuxiliaresPrint) ?></td>
                    </tr>
                </table>
            </div>

            <div class="print-section">
                <h4>Dados da ordem</h4>

                <table class="print-table">
                    <tr>
                        <td colspan="3"><b>TITULO:</b> <?= e($os['titulo']) ?></td>
                        <td><b>PRIORIDADE:</b> <?= e($os['prioridade']) ?></td>
                    </tr>
                    <tr>
                        <td><b>LOCAL:</b> <?= e($os['local_nome']) ?></td>
                        <td><b>CATEGORIA:</b> <?= e($os['categoria_nome']) ?></td>
                        <td><b>GESTOR:</b> <?= e($os['gestor_nome']) ?></td>
                        <td><b>AGENTE PRINCIPAL:</b> <?= e($agentePrincipalPrint !== '' ? $agentePrincipalPrint : '-') ?></td>
                    </tr>
                    <tr>
                        <td colspan="4"><b>AGENTE AUXILIAR:</b> <?= e($agentesAuxiliaresPrint !== '' ? $agentesAuxiliaresPrint : '-') ?></td>
                    </tr>
                    <tr>
                        <td><b>ABERTURA:</b> <?= e($os['criado_em']) ?></td>
                        <td><b>PRAZO:</b> <?= e($os['prazo'] ?: '-') ?></td>
                        <td><b>INICIO:</b> <?= e($os['data_inicio'] ?: '-') ?></td>
                        <td><b>CONCLUSAO:</b> <?= e($os['data_conclusao'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="print-description">
                            <b>DESCRICAO:</b><br>
                            <?= nl2br(e($os['descricao'])) ?>
                        </td>
                    </tr>
                    <?php if (!empty($os['observacao_aprovacao'])): ?>
                        <tr>
                            <td colspan="4">
                                <b>OBSERVACAO DA APROVACAO:</b><br>
                                <?= nl2br(e($os['observacao_aprovacao'])) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="print-section">
                <h4>Materiais utilizados</h4>

                <table class="print-table">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>Quantidade</th>
                            <th>Unidade</th>
                            <th>Observacao</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        $temMaterial = false;

                        foreach ($materiaisPrint as $mp):
                            $temMaterial = true;
                        ?>
                            <tr>
                                <td><?= e($mp['nome_material']) ?></td>
                                <td><?= e($mp['quantidade']) ?></td>
                                <td><?= e($mp['unidade']) ?></td>
                                <td><?= e($mp['observacao']) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$temMaterial): ?>
                            <tr>
                                <td colspan="4" style="text-align:center;">Nenhum material registrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalFotosOs > 0): ?>
                <div class="print-section relatorio-fotografico">
                    <h4>Registros fotográficos da OS</h4>
                    <?php if ($fotosAntes || $fotosDepois): ?>
                        <div class="print-photo-title">Comparativo Antes x Depois</div>
                        <div class="print-photo-grid">
                            <?php foreach ($fotosAntes as $foto): ?>
                                <?php $fotoUrl = anexo_url_segura($foto['arquivo_url']); ?>
                                <div class="print-photo-card">
                                    <img src="<?= e($fotoUrl) ?>" alt="Foto antes">
                                    <div><b>ANTES</b><br><?= e($foto['descricao'] ?: 'Sem descrição') ?><br><?= e($foto['criado_em']) ?> - <?= e($foto['usuario_nome']) ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($fotosDepois as $foto): ?>
                                <?php $fotoUrl = anexo_url_segura($foto['arquivo_url']); ?>
                                <div class="print-photo-card">
                                    <img src="<?= e($fotoUrl) ?>" alt="Foto depois">
                                    <div><b>DEPOIS</b><br><?= e($foto['descricao'] ?: 'Sem descrição') ?><br><?= e($foto['criado_em']) ?> - <?= e($foto['usuario_nome']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($fotosOutras): ?>
                        <div class="print-photo-title">Outras imagens</div>
                        <div class="print-photo-grid">
                            <?php foreach ($fotosOutras as $foto): ?>
                                <?php $fotoUrl = anexo_url_segura($foto['arquivo_url']); ?>
                                <div class="print-photo-card">
                                    <img src="<?= e($fotoUrl) ?>" alt="Outra imagem da OS">
                                    <div><b><?= e($foto['tipo']) ?></b><br><?= e($foto['descricao'] ?: 'Sem descrição') ?><br><?= e($foto['criado_em']) ?> - <?= e($foto['usuario_nome']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="print-section relatorio-fotografico">
                    <h4>Registros fotográficos da OS</h4>
                    <table class="print-table">
                        <tr>
                            <td style="text-align:center; padding:8mm;">Nenhuma imagem enviada para esta OS.</td>
                        </tr>
                    </table>
                </div>
            <?php endif; ?>

            <div class="print-section observacoes-os-simples">
                <h4>Observacoes</h4>

                <table class="print-table print-lines">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <tr><td></td></tr>
                    <?php endfor; ?>
                </table>
            </div>

            <div class="print-section">
                <h4>Assinaturas</h4>

                <table class="signature-table">
                    <tr>
                        <td>
                            _________________________________<br>
                            Assinatura do agente principal<br>
                            <?= e($agentePrincipalPrint) ?>
                            <?php if ($agentesAuxiliaresPrint !== ''): ?>
                                <br><small>Auxiliar: <?= e($agentesAuxiliaresPrint) ?></small>
                            <?php endif; ?>
                        </td>

                        <td>
                            _________________________________<br>
                            Assinatura do gestor<br>
                            <?= e($os['gestor_nome']) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    const modalEl = document.getElementById('modalCustoFinalizacao');
    if (!modalEl) return;

    const initialRows = <?= json_encode($materiaisCustoIniciais, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const estoque = <?= json_encode(array_values($materiaisEstoque), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const analiseScore = <?= (int)($analiseCustoFinalizacao['score'] ?? 0) ?>;
    const custoAtual = <?= json_encode((float)($os['custo_materiais'] ?? 0)) ?>;
    let activeForm = null;
    let modalInstance = null;

    const lista = document.getElementById('listaMateriaisFinalizacao');
    const boxMateriais = document.getElementById('boxMateriaisFinalizacao');
    const boxJustificativa = document.getElementById('boxJustificativaSemCusto');
    const totalEl = document.getElementById('totalCustoFinalizacao');

    function money(v) {
        return new Intl.NumberFormat('pt-BR', {style:'currency', currency:'BRL'}).format(Number(v || 0));
    }

    function findEstoque(nome) {
        const alvo = String(nome || '').trim().toLowerCase();
        return estoque.find(m => String(m.nome || '').trim().toLowerCase() === alvo) || null;
    }

    function criarLinha(item = {}) {
        const row = document.createElement('div');
        row.className = 'custo-finalizacao-row';
        row.dataset.materialOsId = item.material_os_id || '';
        row.dataset.estoqueMaterialId = item.estoque_material_id || '';

        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Material</label>
                    <input class="form-control cf-nome" list="materiaisEstoqueFinalizacao" value="${escapeHtml(item.nome_material || '')}" placeholder="Ex.: Lâmpada 9W">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Quantidade</label>
                    <input class="form-control cf-qtd" type="number" min="0.01" step="0.01" value="${Number(item.quantidade || 1)}">
                </div>
                <div class="col-6 col-lg-1">
                    <label class="form-label">Unidade</label>
                    <input class="form-control cf-unidade" value="${escapeHtml(item.unidade || 'UND')}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Custo unitário</label>
                    <input class="form-control cf-custo" type="number" min="0" step="0.01" value="${Number(item.custo_unitario || 0).toFixed(2)}">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label">Total</label>
                    <input class="form-control cf-total" value="${money((item.quantidade || 1) * (item.custo_unitario || 0))}" readonly>
                </div>
                <div class="col-lg-1 d-grid">
                    <button type="button" class="btn btn-outline-danger cf-remover" title="Remover">×</button>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input cf-baixa" type="checkbox" ${item.baixa_estoque ? 'checked' : ''}>
                        <label class="form-check-label">Baixar do estoque também</label>
                    </div>
                    <div class="form-text cf-info"></div>
                </div>
            </div>
        `;

        const nome = row.querySelector('.cf-nome');
        const qtd = row.querySelector('.cf-qtd');
        const unidade = row.querySelector('.cf-unidade');
        const custo = row.querySelector('.cf-custo');
        const baixa = row.querySelector('.cf-baixa');
        const info = row.querySelector('.cf-info');

        function syncEstoque() {
            const m = findEstoque(nome.value);
            if (m) {
                row.dataset.estoqueMaterialId = m.id || '';
                if (!unidade.value) unidade.value = m.unidade || '';
                if (Number(custo.value || 0) <= 0) custo.value = Number(m.custo_unitario || 0).toFixed(2);
                info.textContent = `Estoque: ${m.quantidade_atual} ${m.unidade || ''} | custo cadastrado ${money(m.custo_unitario)}`;
            } else {
                row.dataset.estoqueMaterialId = '';
                baixa.checked = false;
                info.textContent = 'Material não localizado exatamente no estoque; o custo pode ser registrado manualmente.';
            }
            calc();
        }

        function calc() {
            const total = Number(qtd.value || 0) * Number(custo.value || 0);
            row.querySelector('.cf-total').value = money(total);
            recalcularTotal();
        }

        nome.addEventListener('change', syncEstoque);
        nome.addEventListener('blur', syncEstoque);
        qtd.addEventListener('input', calc);
        custo.addEventListener('input', calc);
        row.querySelector('.cf-remover').addEventListener('click', () => {
            row.remove();
            recalcularTotal();
        });

        lista.appendChild(row);
        syncEstoque();
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'","&#039;");
    }

    function recalcularTotal() {
        let total = 0;
        lista.querySelectorAll('.custo-finalizacao-row').forEach(row => {
            total += Number(row.querySelector('.cf-qtd').value || 0) * Number(row.querySelector('.cf-custo').value || 0);
        });
        totalEl.textContent = money(total);
    }

    function houveCusto() {
        return document.querySelector('input[name="modal_houve_custo"]:checked')?.value || 'NAO';
    }

    function atualizarVisibilidade() {
        const sim = houveCusto() === 'SIM';
        boxMateriais.classList.toggle('d-none', !sim);
        boxJustificativa.classList.toggle('d-none', sim);
    }

    function carregarLinhas() {
        if (lista.dataset.loaded === '1') return;
        lista.dataset.loaded = '1';
        const rows = Array.isArray(initialRows) ? initialRows : [];
        if (rows.length) rows.forEach(criarLinha);
        else criarLinha({quantidade:1, unidade:'UND', custo_unitario:0});
        recalcularTotal();
    }

    document.getElementById('btnAdicionarCustoMaterial').addEventListener('click', () => criarLinha({quantidade:1, unidade:'UND', custo_unitario:0}));
    document.querySelectorAll('input[name="modal_houve_custo"]').forEach(r => r.addEventListener('change', atualizarVisibilidade));

    document.addEventListener('submit', function(ev) {
        const form = ev.target;
        if (!(form instanceof HTMLFormElement)) return;
        const action = form.querySelector('input[name="action"]')?.value;
        if (action !== 'status') return;

        const statusInput = form.querySelector('[name="status"]');
        const status = statusInput ? statusInput.value : '';
        if (status !== 'ENCERRADA') return;
        if (form.dataset.custoFinalizacaoOk === '1') return;

        ev.preventDefault();
        activeForm = form;
        carregarLinhas();
        atualizarVisibilidade();
        modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalInstance.show();
    }, true);

    document.getElementById('btnConfirmarFinalizacaoComCusto').addEventListener('click', function(){
        if (!activeForm) return;

        const houve = houveCusto();
        const justificativa = document.getElementById('justificativaSemCusto').value.trim();
        const itens = [];

        if (houve === 'SIM') {
            for (const row of lista.querySelectorAll('.custo-finalizacao-row')) {
                const nome = row.querySelector('.cf-nome').value.trim();
                const quantidade = Number(row.querySelector('.cf-qtd').value || 0);
                const unidade = row.querySelector('.cf-unidade').value.trim();
                const custo = Number(row.querySelector('.cf-custo').value || 0);

                if (!nome || quantidade <= 0 || custo <= 0) {
                    alert('Confira material, quantidade e custo unitário. Todos são obrigatórios quando houve custo.');
                    return;
                }
                itens.push({
                    material_os_id: Number(row.dataset.materialOsId || 0),
                    estoque_material_id: Number(row.dataset.estoqueMaterialId || 0),
                    nome_material: nome,
                    quantidade: quantidade,
                    unidade: unidade,
                    custo_unitario: custo,
                    baixa_estoque: row.querySelector('.cf-baixa').checked
                });
            }
            if (!itens.length) {
                alert('Adicione pelo menos um material.');
                return;
            }
        } else if (analiseScore >= 8 && justificativa.length < 5) {
            alert('A análise encontrou indícios fortes de custo. Informe uma justificativa para marcar que não houve custo.');
            document.getElementById('justificativaSemCusto').focus();
            return;
        }

        function hidden(name, value) {
            let el = activeForm.querySelector(`input[name="${name}"]`);
            if (!el) {
                el = document.createElement('input');
                el.type = 'hidden';
                el.name = name;
                activeForm.appendChild(el);
            }
            el.value = value;
        }

        hidden('custo_finalizacao_confirmado', '1');
        hidden('houve_custo_material', houve);
        hidden('custos_json', JSON.stringify(itens));
        hidden('justificativa_sem_custo', justificativa);

        activeForm.dataset.custoFinalizacaoOk = '1';
        modalInstance?.hide();
        activeForm.requestSubmit();
    });

    carregarLinhas();
    atualizarVisibilidade();
})();
function prepararImpressao(classe, titulo) {
    document.body.classList.remove('imprimir-os', 'imprimir-relatorio');
    document.body.classList.add(classe);

    const tituloOriginal = document.title;
    document.title = titulo;

    window.print();

    setTimeout(() => {
        document.body.classList.remove('imprimir-os', 'imprimir-relatorio');
        document.title = tituloOriginal;
    }, 1000);
}

function imprimirOS() {
    const numeroOS = "<?= preg_replace('/[^A-Za-z0-9_-]/', '_', $os['numero_os']) ?>";
    const agente = "<?= preg_replace('/[^A-Za-z0-9_-]/', '_', $agentePrincipalPrint !== '-' ? $agentePrincipalPrint : 'SEM_AGENTE') ?>";
    prepararImpressao('imprimir-os', "OS_" + numeroOS + "_" + agente);
}

function gerarRelatorioOS() {
    const numeroOS = "<?= preg_replace('/[^A-Za-z0-9_-]/', '_', $os['numero_os']) ?>";
    const agente = "<?= preg_replace('/[^A-Za-z0-9_-]/', '_', $agentePrincipalPrint !== '-' ? $agentePrincipalPrint : 'SEM_AGENTE') ?>";
    prepararImpressao('imprimir-relatorio', "Relatorio_Fotografico_OS_" + numeroOS + "_" + agente);
}
</script>

<?php render_footer(); ?>
