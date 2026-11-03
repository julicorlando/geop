<?php
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/cftv.php';
require_once __DIR__ . '/../includes/gestao_integrada.php';

require_role(array_values(array_unique(array_merge(['TI', 'CFTV'], cftv_liberacao_perfis()))));
check_csrf();
ensure_cftv_schema($pdo);

$user = current_user();
$view = $_GET['view'] ?? '';
$filtroDestaque = strtolower(trim((string)($_GET['filtro'] ?? '')));
if (!in_array($filtroDestaque, ['cameras_off', 'ocorrencias'], true)) {
    $filtroDestaque = '';
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $view === '' && empty($_GET['id']) && !in_array($user['perfil'] ?? '', ['TI', 'CFTV'], true)) {
    redirect('cftv?view=liberacoes');
}

$mensagemSucesso = $_SESSION['cftv_sucesso'] ?? '';
$mensagemErro = $_SESSION['cftv_erro'] ?? '';
$tokenGerado = $_SESSION['cftv_token_gerado'] ?? '';
$linkPublicoGerado = $_SESSION['cftv_link_publico'] ?? '';
unset($_SESSION['cftv_sucesso'], $_SESSION['cftv_erro'], $_SESSION['cftv_token_gerado'], $_SESSION['cftv_link_publico']);

function cftv_redirect_report(int $id): void {
    redirect('cftv?id=' . $id);
}

function cftv_load_report(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("
        SELECT r.*, u.nome AS operador_nome, u.email AS operador_email
        FROM cftv_relatorios r
        JOIN usuarios u ON u.id = r.operador_id
        WHERE r.id = ?
        LIMIT 1
    ");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function cftv_require_report(PDO $pdo, int $id, array $user): array {
    $relatorio = cftv_load_report($pdo, $id);
    if (!$relatorio || !cftv_can_view_report($relatorio, $user)) {
        http_response_code(403);
        die('Acesso negado.');
    }
    return $relatorio;
}

function cftv_post_bool(string $name): int {
    return isset($_POST[$name]) ? 1 : 0;
}

function cftv_post_int(string $name, ?int $max = null): int {
    $value = max(0, (int)($_POST[$name] ?? 0));
    return $max !== null ? min($value, $max) : $value;
}

function cftv_update_report_from_post(PDO $pdo, int $id): void {
    $st = $pdo->prepare("
        UPDATE cftv_relatorios
        SET inicio_turno = ?,
            fim_turno = ?,
            passagem_servico = ?,
            cameras_funcionando = ?,
            nvrs_gravando = ?,
            horario_sincronizado = ?,
            radios_ok = ?,
            falhas_livro = ?,
            cameras_offline = ?,
            rondas_virtuais = ?,
            eventos_preventivos = ?,
            fluxo_clientes = ?,
            tempo_medio_resposta_min = ?,
            areas_prioritarias = ?,
            situacoes_suspeitas = ?,
            equipes_observadas = ?,
            ocorrencias_resumo = ?,
            observacoes_gerais = ?,
            plano_melhoria = ?,
            atualizado_em = NOW()
        WHERE id = ?
    ");
    $st->execute([
        cftv_valid_time($_POST['inicio_turno'] ?? null),
        cftv_valid_time($_POST['fim_turno'] ?? null),
        trim((string)($_POST['passagem_servico'] ?? '')),
        cftv_post_bool('cameras_funcionando'),
        cftv_post_bool('nvrs_gravando'),
        cftv_post_bool('horario_sincronizado'),
        cftv_post_bool('radios_ok'),
        trim((string)($_POST['falhas_livro'] ?? '')),
        cftv_post_int('cameras_offline', 999),
        cftv_post_int('rondas_virtuais', 999),
        cftv_post_int('eventos_preventivos', 999),
        cftv_post_int('fluxo_clientes', 9999999),
        ($_POST['tempo_medio_resposta_min'] ?? '') === '' ? null : cftv_post_int('tempo_medio_resposta_min', 9999),
        trim((string)($_POST['areas_prioritarias'] ?? '')),
        trim((string)($_POST['situacoes_suspeitas'] ?? '')),
        trim((string)($_POST['equipes_observadas'] ?? '')),
        trim((string)($_POST['ocorrencias_resumo'] ?? '')),
        trim((string)($_POST['observacoes_gerais'] ?? '')),
        trim((string)($_POST['plano_melhoria'] ?? '')),
        $id,
    ]);
}

function cftv_valid_date(string $date): string {
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
}

function cftv_valid_time(?string $time): ?string {
    $time = trim((string)$time);
    return preg_match('/^\d{2}:\d{2}$/', $time) ? $time . ':00' : null;
}

function cftv_redirect_liberacoes(array $query = []): void {
    $query = array_merge(['view' => 'liberacoes'], $query);
    redirect('cftv?' . http_build_query($query));
}

function cftv_flash_success(string $message): void {
    $_SESSION['cftv_sucesso'] = $message;
}

function cftv_flash_error(string $message): void {
    $_SESSION['cftv_erro'] = $message;
}

function cftv_is_ajax_request(): bool {
    return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function cftv_json_response(array $payload): void {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'salvar_destino_acesso') {
        if (($user['perfil']??'')!=='TI') { http_response_code(403); die('Acesso negado.'); }
        $tipo=in_array($_POST['tipo']??'', ['LOJA_MALL','PRACA_ALIMENTACAO'],true)?(string)$_POST['tipo']:'LOJA_MALL';
        $nome=trim((string)($_POST['nome']??''));
        if($nome!=='') $pdo->prepare("INSERT INTO cftv_destinos_acesso(tipo,nome,criado_por)
            VALUES(?,?,?) ON DUPLICATE KEY UPDATE ativo=1")->execute([$tipo,$nome,$user['id']]);
        cftv_flash_success('Destino cadastrado para o formulário público.');
        cftv_redirect_liberacoes();
    }
    if ($action === 'alternar_destino_acesso') {
        if (($user['perfil']??'')!=='TI') { http_response_code(403); die('Acesso negado.'); }
        $pdo->prepare("UPDATE cftv_destinos_acesso SET ativo=1-ativo WHERE id=?")->execute([(int)($_POST['destino_id']??0)]);
        cftv_flash_success('Disponibilidade do destino atualizada.');
        cftv_redirect_liberacoes();
    }

    if ($action === 'gerar_link_liberacao') {
        if (!cftv_can_manage_liberacoes($user)) { http_response_code(403); die('Acesso negado.'); }
        $raw=bin2hex(random_bytes(24));
        $dias=max(1,min(90,(int)($_POST['validade_dias']??7)));
        $usos=max(1,min(500,(int)($_POST['limite_usos']??1)));
        $titulo=trim((string)($_POST['titulo']??'Solicitação de acesso'));
        $pdo->prepare("INSERT INTO cftv_liberacao_links(token_hash,titulo,destino_padrao,expira_em,limite_usos,criado_por)
            VALUES(?,?,?,DATE_ADD(NOW(),INTERVAL ? DAY),?,?)")
            ->execute([hash('sha256',$raw),$titulo?:'Solicitação de acesso',trim((string)($_POST['destino_padrao']??'')),$dias,$usos,$user['id']]);
        $_SESSION['cftv_link_publico']=app_url('solicitar_acesso',['t'=>$raw]);
        cftv_flash_success('Link público criado. Copie e envie ao solicitante.');
        cftv_redirect_liberacoes();
    }

    if ($action === 'desativar_link_liberacao') {
        if (!cftv_can_manage_liberacoes($user)) { http_response_code(403); die('Acesso negado.'); }
        $pdo->prepare("UPDATE cftv_liberacao_links SET ativo=0 WHERE id=?")->execute([(int)($_POST['link_id']??0)]);
        cftv_flash_success('Link público desativado.');
        cftv_redirect_liberacoes();
    }

    if (in_array($action,['aprovar_solicitacao_acesso','reprovar_solicitacao_acesso'],true)) {
        if (!cftv_can_manage_liberacoes($user)) { http_response_code(403); die('Acesso negado.'); }
        $solicitacaoId=(int)($_POST['solicitacao_id']??0);
        $justificativa=trim((string)($_POST['justificativa']??''));
        if($action==='reprovar_solicitacao_acesso'&&$justificativa==='') {
            cftv_flash_error('Informe a justificativa da reprovação.'); cftv_redirect_liberacoes();
        }
        $status='';$sol=null;
        $pdo->beginTransaction();
        try {
            $st=$pdo->prepare("SELECT * FROM cftv_solicitacoes_acesso WHERE id=? AND status='PENDENTE' FOR UPDATE");
            $st->execute([$solicitacaoId]); $sol=$st->fetch(PDO::FETCH_ASSOC);
            if($sol) {
                $liberacaoId=null;
                $status=$action==='aprovar_solicitacao_acesso'?'APROVADA':'REPROVADA';
                if($status==='APROVADA') {
                    $stPessoas=$pdo->prepare("SELECT nome_completo,cpf FROM cftv_solicitacao_pessoas WHERE solicitacao_id=? ORDER BY id");
                    $stPessoas->execute([$solicitacaoId]);
                    $pessoasAdicionais=$stPessoas->fetchAll(PDO::FETCH_ASSOC);
                    $textoPessoas='';
                    foreach($pessoasAdicionais as $pessoa) $textoPessoas.='Acompanhante: '.$pessoa['nome_completo'].' — CPF '.cftv_formatar_cpf($pessoa['cpf'])."\n";
                    $pdo->prepare("INSERT INTO cftv_liberacoes
                      (data_acesso,data_fim,hora_inicio,hora_fim,nome_completo,cpf,email,destino_tipo,destino_nome,motivo,observacoes,criado_por)
                      VALUES(?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$sol['data_acesso'],$sol['data_fim']??$sol['data_acesso'],$sol['hora_inicio'],$sol['hora_fim'],
                        $sol['nome_completo'],$sol['cpf'],$sol['email']??null,$sol['destino_tipo'],$sol['destino_nome'],$sol['motivo'],
                        trim(($sol['empresa']?'Empresa: '.$sol['empresa']."\n":'').($sol['telefone']?'Telefone: '.$sol['telefone']."\n":'').
                        (!empty($sol['email'])?'E-mail: '.$sol['email']."\n":'').
                        ($sol['responsavel_interno']?'Responsável: '.$sol['responsavel_interno']."\n":'').
                        ($sol['veiculo_placa']?'Placa: '.$sol['veiculo_placa']."\n":'').$textoPessoas.$sol['observacoes']),$user['id']]);
                    $liberacaoId=(int)$pdo->lastInsertId();
                }
                $pdo->prepare("UPDATE cftv_solicitacoes_acesso SET status=?,decisao_justificativa=?,
                    decidido_por=?,decidido_em=NOW(),liberacao_id=? WHERE id=?")
                    ->execute([$status,$justificativa?:($status==='APROVADA'?'Solicitação aprovada.':''),$user['id'],$liberacaoId,$solicitacaoId]);
                auditoria_log($pdo,'cftv_solicitacao_acesso',$solicitacaoId,$status,null,'PENDENTE',$status,$user['id']);
            }
            $pdo->commit();
            if($sol&&$status==='APROVADA') {
                // Emite e persiste a chave do QR imediatamente no momento da aprovação.
                cftv_qr_codigo($pdo,$solicitacaoId);
                $operadores=$pdo->query("SELECT id FROM usuarios WHERE status='ATIVO' AND perfil IN ('CFTV','CONTROLE_ACESSO')")->fetchAll(PDO::FETCH_COLUMN);
                foreach($operadores as $operadorId) gestao_notificar($pdo,(int)$operadorId,'ACESSO_APROVADO','Liberação aprovada — comunicar CFTV',
                    $sol['protocolo'].' — '.$sol['nome_completo'].' — '.$sol['destino_nome'],
                    app_path('liberacoes_aprovadas'),'acesso-aprovado-'.$solicitacaoId.'-'.$operadorId);
            }
            if($sol&&$status!=='') {
                $sol['decisao_justificativa']=$justificativa?:($status==='APROVADA'?'Solicitação aprovada.':'');
                cftv_email_status_solicitacao($sol,$status);
            }
            cftv_flash_success($action==='aprovar_solicitacao_acesso'?'Solicitação aprovada e liberação criada.':'Solicitação reprovada.');
        } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
        cftv_redirect_liberacoes();
    }

    if ($action === 'criar_relatorio') {
        $dataPlantao = cftv_valid_date(trim((string)($_POST['data_plantao'] ?? date('Y-m-d'))));
        $turno = strtoupper(trim((string)($_POST['turno'] ?? 'DIURNO')));
        if (!in_array($turno, cftv_turnos(), true)) {
            $turno = 'DIURNO';
        }

        if ($user['perfil'] === 'CFTV' && $dataPlantao !== date('Y-m-d')) {
            cftv_flash_error('Operador CFTV so pode abrir relatorio do dia atual.');
            redirect('cftv');
        }

        $operadorId = (int)$user['id'];
        if ($user['perfil'] === 'TI') {
            $operadorId = (int)($_POST['operador_id'] ?? 0);
        }

        $stOperador = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND perfil = 'CFTV' AND status = 'ATIVO' LIMIT 1");
        $stOperador->execute([$operadorId]);

        if (!$stOperador->fetchColumn()) {
            cftv_flash_error('Selecione um operador CFTV ativo.');
            redirect('cftv');
        }

        $stExiste = $pdo->prepare("SELECT id FROM cftv_relatorios WHERE data_plantao = ? AND operador_id = ? AND turno = ? LIMIT 1");
        $stExiste->execute([$dataPlantao, $operadorId, $turno]);
        $existente = (int)$stExiste->fetchColumn();

        if ($existente > 0) {
            cftv_flash_success('Relatorio do periodo localizado.');
            cftv_redirect_report($existente);
        }

        $st = $pdo->prepare("
            INSERT INTO cftv_relatorios
            (data_plantao, turno, operador_id, inicio_turno, areas_prioritarias, criado_em)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $st->execute([
            $dataPlantao,
            $turno,
            $operadorId,
            cftv_valid_time($_POST['inicio_turno'] ?? null),
            cftv_default_areas_text(),
        ]);

        $id = (int)$pdo->lastInsertId();
        auditoria_log($pdo, 'cftv_relatorio', $id, 'CRIAR_RELATORIO', null, null, $dataPlantao . ' ' . $turno, $user['id']);
        cftv_flash_success('Relatorio CFTV criado.');
        cftv_redirect_report($id);
    }

    if ($action === 'salvar_liberacao') {
        if (!cftv_can_manage_liberacoes($user)) {
            http_response_code(403);
            die('Acesso negado.');
        }

        $id = (int)($_POST['liberacao_id'] ?? 0);
        $novaLiberacao = $id <= 0;
        $dataAcesso = cftv_valid_date(trim((string)($_POST['data_acesso'] ?? date('Y-m-d'))));
        $dataFim = cftv_valid_date(trim((string)($_POST['data_fim'] ?? $dataAcesso)));
        $horaInicio = cftv_valid_time($_POST['hora_inicio'] ?? null);
        $horaFim = cftv_valid_time($_POST['hora_fim'] ?? null);
        $nomeCompleto = trim((string)($_POST['nome_completo'] ?? ''));
        $cpf = cftv_normalizar_cpf($_POST['cpf'] ?? '');
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $destinoTipo = strtoupper(trim((string)($_POST['destino_tipo'] ?? 'LOJISTA')));
        if (!array_key_exists($destinoTipo, cftv_destinos_liberacao())) {
            $destinoTipo = 'LOJISTA';
        }
        $destinoNome = trim((string)($_POST['destino_nome'] ?? ''));
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        $observacoes = trim((string)($_POST['observacoes'] ?? ''));

        if ($dataFim < $dataAcesso) {
            cftv_flash_error('A data final não pode ser anterior à data inicial.');
            cftv_redirect_liberacoes(['edit_liberacao' => $id ?: null]);
        }
        if (($horaInicio === null) xor ($horaFim === null)) {
            cftv_flash_error('Informe os horários de início e fim do período.');
            cftv_redirect_liberacoes(['edit_liberacao' => $id ?: null]);
        }
        if ($horaInicio !== null && $horaFim !== null && $horaFim <= $horaInicio) {
            cftv_flash_error('O horário final deve ser posterior ao horário inicial.');
            cftv_redirect_liberacoes(['edit_liberacao' => $id ?: null]);
        }

        if ($nomeCompleto === '' || strlen($cpf) !== 11 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) || $destinoNome === '') {
            cftv_flash_error('Informe nome completo, CPF válido, e-mail válido quando preenchido e o destino.');
            cftv_redirect_liberacoes(['edit_liberacao' => $id ?: null]);
        }

        if ($id > 0) {
            $st = $pdo->prepare("
                UPDATE cftv_liberacoes
                SET data_acesso = ?,
                    data_fim = ?,
                    hora_inicio = ?,
                    hora_fim = ?,
                    nome_completo = ?,
                    cpf = ?,
                    email = ?,
                    destino_tipo = ?,
                    destino_nome = ?,
                    motivo = ?,
                    observacoes = ?,
                    atualizado_por = ?,
                    atualizado_em = NOW()
                WHERE id = ?
            ");
            $st->execute([
                $dataAcesso,
                $dataFim,
                $horaInicio,
                $horaFim,
                $nomeCompleto,
                $cpf,
                $email !== '' ? $email : null,
                $destinoTipo,
                $destinoNome,
                $motivo,
                $observacoes,
                $user['id'],
                $id,
            ]);
            auditoria_log($pdo, 'cftv_liberacao', $id, 'EDITAR_LIBERACAO', null, null, $nomeCompleto, $user['id']);
            cftv_flash_success('Liberacao atualizada.');
        } else {
            $st = $pdo->prepare("
                INSERT INTO cftv_liberacoes
                (data_acesso, data_fim, hora_inicio, hora_fim, nome_completo, cpf, email, destino_tipo, destino_nome, motivo, observacoes, criado_por)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $st->execute([
                $dataAcesso,
                $dataFim,
                $horaInicio,
                $horaFim,
                $nomeCompleto,
                $cpf,
                $email !== '' ? $email : null,
                $destinoTipo,
                $destinoNome,
                $motivo,
                $observacoes,
                $user['id'],
            ]);
            $id = (int)$pdo->lastInsertId();
            auditoria_log($pdo, 'cftv_liberacao', $id, 'CRIAR_LIBERACAO', null, null, $nomeCompleto, $user['id']);
            cftv_flash_success('Liberacao cadastrada.');
        }

        if ($novaLiberacao && $id > 0) {
            $operadores = $pdo->query("SELECT id FROM usuarios WHERE status='ATIVO' AND perfil IN ('CFTV','CONTROLE_ACESSO')")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($operadores as $operadorId) {
                gestao_notificar($pdo,(int)$operadorId,'ACESSO_APROVADO','Nova liberação cadastrada',
                    $nomeCompleto.' — '.$destinoNome.' — '.date('d/m/Y',strtotime($dataAcesso)).($dataFim!==$dataAcesso?' a '.date('d/m/Y',strtotime($dataFim)):'').($horaInicio&&$horaFim?' — '.substr($horaInicio,0,5).' às '.substr($horaFim,0,5):''),
                    app_path('liberacoes_aprovadas'),'liberacao-manual-'.$id.'-'.$operadorId);
            }
        }

        cftv_redirect_liberacoes(['inicio' => $dataAcesso, 'fim' => $dataFim]);
    }

    if ($action === 'cancelar_liberacao') {
        if (!cftv_can_manage_liberacoes($user)) {
            http_response_code(403);
            die('Acesso negado.');
        }

        $id = (int)($_POST['liberacao_id'] ?? 0);
        if ($id > 0) {
            $pdo->prepare("
                UPDATE cftv_liberacoes
                SET status = 'CANCELADA',
                    cancelado_por = ?,
                    cancelado_em = NOW(),
                    atualizado_por = ?,
                    atualizado_em = NOW()
                WHERE id = ?
            ")->execute([$user['id'], $user['id'], $id]);
            auditoria_log($pdo, 'cftv_liberacao', $id, 'CANCELAR_LIBERACAO', 'status', 'ATIVA', 'CANCELADA', $user['id']);
            cftv_flash_success('Liberacao cancelada.');
        }

        cftv_redirect_liberacoes();
    }

    if ($action === 'salvar_relatorio') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);

        if (!cftv_can_edit_report($relatorio, $user)) {
            cftv_flash_error('Este relatorio esta fora do periodo de edicao. Solicite token ao TI.');
            cftv_redirect_report($id);
        }

        cftv_update_report_from_post($pdo, $id);

        auditoria_log($pdo, 'cftv_relatorio', $id, 'SALVAR_RELATORIO', null, null, 'Atualizacao de plantao', $user['id']);
        cftv_flash_success('Relatorio salvo.');
        cftv_redirect_report($id);
    }

    if ($action === 'confirmar_liberacao') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);

        if (!cftv_can_edit_report($relatorio, $user)) {
            cftv_flash_error('Relatorio bloqueado para confirmar liberacoes.');
            cftv_redirect_report($id);
        }

        $liberacaoId = (int)($_POST['liberacao_id'] ?? 0);
        $statusConfirmacao = strtoupper(trim((string)($_POST['status_confirmacao'] ?? 'CONFIRMADA')));
        if (!array_key_exists($statusConfirmacao, cftv_status_confirmacao_liberacao())) {
            $statusConfirmacao = 'CONFIRMADA';
        }
        $alteracao = trim((string)($_POST['alteracao_observada'] ?? ''));

        if (in_array($statusConfirmacao, ['CONFIRMADA_COM_ALTERACAO', 'DIVERGENTE'], true) && $alteracao === '') {
            cftv_flash_error('Informe a alteracao ou divergencia observada na liberacao.');
            cftv_redirect_report($id);
        }

        $st = $pdo->prepare("SELECT id FROM cftv_liberacoes WHERE id = ? AND ? BETWEEN data_acesso AND COALESCE(data_fim,data_acesso) AND status = 'ATIVA' LIMIT 1");
        $st->execute([$liberacaoId, $relatorio['data_plantao']]);
        if (!$st->fetchColumn()) {
            cftv_flash_error('Liberacao nao encontrada para a data do relatorio.');
            cftv_redirect_report($id);
        }

        $horarioConfirmacao = cftv_input_datetime($_POST['horario_confirmacao'] ?? '') ?: date('Y-m-d H:i:s');
        $st = $pdo->prepare("SELECT id FROM cftv_liberacao_confirmacoes WHERE relatorio_id = ? AND liberacao_id = ? LIMIT 1");
        $st->execute([$id, $liberacaoId]);
        $confirmacaoId = (int)$st->fetchColumn();

        if ($confirmacaoId > 0) {
            $pdo->prepare("
                UPDATE cftv_liberacao_confirmacoes
                SET status_confirmacao = ?,
                    horario_confirmacao = ?,
                    alteracao_observada = ?,
                    confirmado_por = ?,
                    atualizado_em = NOW()
                WHERE id = ?
            ")->execute([$statusConfirmacao, $horarioConfirmacao, $alteracao, $user['id'], $confirmacaoId]);
        } else {
            $pdo->prepare("
                INSERT INTO cftv_liberacao_confirmacoes
                (relatorio_id, liberacao_id, status_confirmacao, horario_confirmacao, alteracao_observada, confirmado_por)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$id, $liberacaoId, $statusConfirmacao, $horarioConfirmacao, $alteracao, $user['id']]);
            $confirmacaoId = (int)$pdo->lastInsertId();
        }

        auditoria_log($pdo, 'cftv_liberacao_confirmacao', $confirmacaoId, 'CONFIRMAR_LIBERACAO', 'status_confirmacao', null, $statusConfirmacao, $user['id']);
        cftv_flash_success('Liberacao confirmada no relatorio.');
        cftv_redirect_report($id);
    }

    if ($action === 'auto_finalizar_relatorio') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);
        $deviceDateTimeInput = trim((string)($_POST['dispositivo_horario'] ?? ''));
        $deviceDateTime = cftv_device_datetime($deviceDateTimeInput);

        if (!cftv_can_auto_close_diurno($relatorio, $deviceDateTimeInput) || !$deviceDateTime) {
            if (cftv_is_ajax_request()) {
                cftv_json_response(['ok' => false, 'message' => 'Fora do horario de fechamento automatico.']);
            }
            cftv_flash_error('Fechamento automatico disponivel apenas para plantao diurno apos 17:10 pela hora do dispositivo.');
            cftv_redirect_report($id);
        }

        if (isset($_POST['inicio_turno']) && cftv_can_edit_report($relatorio, $user)) {
            cftv_update_report_from_post($pdo, $id);
        }

        $finalizadoEm = $deviceDateTime->format('Y-m-d H:i:s');
        $fimTurno = $deviceDateTime->format('H:i:s');
        $st = $pdo->prepare("
            UPDATE cftv_relatorios
            SET status = 'FINALIZADO',
                finalizado_em = ?,
                fim_turno = COALESCE(fim_turno, ?),
                atualizado_em = ?
            WHERE id = ?
            AND status <> 'FINALIZADO'
            AND turno = 'DIURNO'
        ");
        $st->execute([$finalizadoEm, $fimTurno, $finalizadoEm, $id]);

        if ($st->rowCount() > 0) {
            auditoria_log($pdo, 'cftv_relatorio', $id, 'AUTO_FINALIZAR_RELATORIO', 'status', $relatorio['status'], 'FINALIZADO', $user['id']);
            cftv_flash_success('Plantao diurno finalizado automaticamente pela hora do dispositivo.');
        }

        if (cftv_is_ajax_request()) {
            cftv_json_response(['ok' => true]);
        }
        cftv_redirect_report($id);
    }

    if ($action === 'finalizar_relatorio') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);

        if (!cftv_can_edit_report($relatorio, $user)) {
            cftv_flash_error('Este relatorio nao pode ser finalizado sem token valido.');
            cftv_redirect_report($id);
        }

        if (isset($_POST['inicio_turno'])) {
            cftv_update_report_from_post($pdo, $id);
        }

        $pdo->prepare("
            UPDATE cftv_relatorios
            SET status = 'FINALIZADO',
                finalizado_em = NOW(),
                fim_turno = COALESCE(fim_turno, CURTIME()),
                atualizado_em = NOW()
            WHERE id = ?
        ")->execute([$id]);

        auditoria_log($pdo, 'cftv_relatorio', $id, 'FINALIZAR_RELATORIO', 'status', $relatorio['status'], 'FINALIZADO', $user['id']);
        cftv_flash_success('Plantao finalizado.');
        cftv_redirect_report($id);
    }

    if ($action === 'adicionar_ocorrencia') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);

        if (!cftv_can_edit_report($relatorio, $user)) {
            cftv_flash_error('Relatorio bloqueado para edicao.');
            cftv_redirect_report($id);
        }

        $nivel = strtoupper(trim((string)($_POST['nivel'] ?? 'NIVEL_1')));
        if (!array_key_exists($nivel, cftv_niveis_ocorrencia())) {
            $nivel = 'NIVEL_1';
        }

        $st = $pdo->prepare("
            INSERT INTO cftv_ocorrencias
            (relatorio_id, horario, nivel, tipo, local, descricao, envolvidos, imagens, tempo_resposta_min, equipes_acionadas, encerramento, preservar_imagens)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            $id,
            cftv_input_datetime($_POST['horario'] ?? ''),
            $nivel,
            trim((string)($_POST['tipo'] ?? '')),
            trim((string)($_POST['local'] ?? '')),
            trim((string)($_POST['descricao'] ?? '')),
            trim((string)($_POST['envolvidos'] ?? '')),
            trim((string)($_POST['imagens'] ?? '')),
            ($_POST['tempo_resposta_min'] ?? '') === '' ? null : cftv_post_int('tempo_resposta_min', 9999),
            trim((string)($_POST['equipes_acionadas'] ?? '')),
            trim((string)($_POST['encerramento'] ?? '')),
            cftv_post_bool('preservar_imagens'),
        ]);

        auditoria_log($pdo, 'cftv_ocorrencia', $pdo->lastInsertId(), 'ADICIONAR_OCORRENCIA', 'nivel', null, $nivel, $user['id']);
        cftv_flash_success('Ocorrencia adicionada.');
        cftv_redirect_report($id);
    }

    if ($action === 'adicionar_observacao') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);

        if (!cftv_can_edit_report($relatorio, $user)) {
            cftv_flash_error('Relatorio bloqueado para edicao.');
            cftv_redirect_report($id);
        }

        $equipe = trim((string)($_POST['equipe'] ?? ''));
        if (!in_array($equipe, cftv_equipes_monitoradas(), true)) {
            $equipe = 'Fiscal de Mall';
        }

        $classificacao = strtoupper(trim((string)($_POST['classificacao'] ?? 'INFORMATIVA')));
        if (!in_array($classificacao, cftv_classificacoes_observacao(), true)) {
            $classificacao = 'INFORMATIVA';
        }

        $st = $pdo->prepare("
            INSERT INTO cftv_observacoes
            (relatorio_id, equipe, horario, local, item, classificacao, descricao, tempo_resposta_min, providencia)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            $id,
            $equipe,
            cftv_input_datetime($_POST['horario'] ?? ''),
            trim((string)($_POST['local'] ?? '')),
            trim((string)($_POST['item'] ?? '')),
            $classificacao,
            trim((string)($_POST['descricao'] ?? '')),
            ($_POST['tempo_resposta_min'] ?? '') === '' ? null : cftv_post_int('tempo_resposta_min', 9999),
            trim((string)($_POST['providencia'] ?? '')),
        ]);

        auditoria_log($pdo, 'cftv_observacao', $pdo->lastInsertId(), 'ADICIONAR_OBSERVACAO', 'equipe', null, $equipe, $user['id']);
        cftv_flash_success('Observacao adicionada.');
        cftv_redirect_report($id);
    }

    if ($action === 'excluir_ocorrencia' || $action === 'excluir_observacao') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);

        if (!cftv_can_edit_report($relatorio, $user)) {
            cftv_flash_error('Relatorio bloqueado para edicao.');
            cftv_redirect_report($id);
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        if ($action === 'excluir_ocorrencia') {
            $pdo->prepare("DELETE FROM cftv_ocorrencias WHERE id = ? AND relatorio_id = ?")->execute([$itemId, $id]);
            auditoria_log($pdo, 'cftv_ocorrencia', $itemId, 'EXCLUIR_OCORRENCIA', null, null, null, $user['id']);
        } else {
            $pdo->prepare("DELETE FROM cftv_observacoes WHERE id = ? AND relatorio_id = ?")->execute([$itemId, $id]);
            auditoria_log($pdo, 'cftv_observacao', $itemId, 'EXCLUIR_OBSERVACAO', null, null, null, $user['id']);
        }

        cftv_flash_success('Registro removido.');
        cftv_redirect_report($id);
    }

    if ($action === 'gerar_token_edicao' && $user['perfil'] === 'TI') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);
        $token = (string)random_int(100000, 999999);
        $hash = password_hash($token, PASSWORD_DEFAULT);

        $pdo->prepare("
            UPDATE cftv_tokens_edicao
            SET usado_em = NOW(), usado_por = ?
            WHERE relatorio_id = ?
            AND usado_em IS NULL
        ")->execute([$user['id'], $id]);

        $st = $pdo->prepare("
            INSERT INTO cftv_tokens_edicao
            (relatorio_id, token_hash, gerado_por, expira_em, motivo)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), ?)
        ");
        $st->execute([
            $id,
            $hash,
            $user['id'],
            trim((string)($_POST['motivo'] ?? 'Edicao fora do periodo autorizada pelo TI.')),
        ]);

        auditoria_log($pdo, 'cftv_relatorio', $id, 'GERAR_TOKEN_EDICAO', null, null, 'Token valido por 24 horas', $user['id']);
        $_SESSION['cftv_token_gerado'] = $token;
        cftv_flash_success('Token de edicao gerado.');
        cftv_redirect_report($id);
    }

    if ($action === 'usar_token_edicao') {
        $id = (int)($_POST['relatorio_id'] ?? 0);
        $relatorio = cftv_require_report($pdo, $id, $user);
        $tokenDigitado = cftv_normalizar_token($_POST['token_edicao'] ?? '');

        $st = $pdo->prepare("
            SELECT *
            FROM cftv_tokens_edicao
            WHERE relatorio_id = ?
            AND usado_em IS NULL
            AND expira_em >= NOW()
            ORDER BY criado_em DESC
        ");
        $st->execute([$id]);

        $tokenValido = null;
        foreach ($st->fetchAll() as $tk) {
            if (password_verify($tokenDigitado, $tk['token_hash'])) {
                $tokenValido = $tk;
                break;
            }
        }

        if (!$tokenValido) {
            cftv_flash_error('Token invalido, expirado ou ja utilizado.');
            cftv_redirect_report($id);
        }

        $pdo->prepare("UPDATE cftv_tokens_edicao SET usado_em = NOW(), usado_por = ? WHERE id = ?")
            ->execute([$user['id'], $tokenValido['id']]);

        $seconds = max(300, strtotime($tokenValido['expira_em']) - time());
        cftv_mark_report_unlocked($id, $seconds);
        auditoria_log($pdo, 'cftv_relatorio', $id, 'USAR_TOKEN_EDICAO', null, null, 'Edicao liberada por token', $user['id']);
        cftv_flash_success('Edicao liberada temporariamente.');
        cftv_redirect_report($id);
    }
}

if ($view === 'liberacoes') {
    $canManageLiberacoes = cftv_can_manage_liberacoes($user);
    $solicitacoesPendentes=[];
    $linksPublicos=[];
    $resumoSolicitacoesSetor=[];
$linkPublicoPermanente=app_url('solicitar_acesso',['t'=>cftv_link_publico_token($pdo)]);
    $destinosCadastrados=[];
    // Todos os perfis autorizados ao módulo podem consultar as solicitações.
    // Somente os perfis gestores podem aprovar, reprovar ou alterar cadastros.
    $solicitacoesPendentes=$pdo->query("SELECT s.*,
        (SELECT GROUP_CONCAT(CONCAT(p.nome_completo,' (',p.cpf,')') SEPARATOR '; ')
         FROM cftv_solicitacao_pessoas p WHERE p.solicitacao_id=s.id) pessoas_adicionais
        FROM cftv_solicitacoes_acesso s
        WHERE status='PENDENTE' ORDER BY criado_em ASC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
    $resumoSolicitacoesSetor=$pdo->query("SELECT destino_tipo,destino_nome,COUNT(*) total,
        SUM(status='PENDENTE') pendentes,SUM(status='APROVADA') aprovadas,SUM(status='REPROVADA') reprovadas
        FROM cftv_solicitacoes_acesso
        WHERE criado_em>=DATE_FORMAT(CURRENT_DATE,'%Y-%m-01')
        GROUP BY destino_tipo,destino_nome ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
    if($canManageLiberacoes) {
        $linksPublicos=$pdo->query("SELECT l.*,u.nome criado_por_nome FROM cftv_liberacao_links l
            JOIN usuarios u ON u.id=l.criado_por ORDER BY l.criado_em DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        $destinosCadastrados=$pdo->query("SELECT * FROM cftv_destinos_acesso ORDER BY tipo,nome")->fetchAll(PDO::FETCH_ASSOC);
    }
    $inicio = cftv_valid_date(trim((string)($_GET['inicio'] ?? date('Y-m-d'))));
    $fim = cftv_valid_date(trim((string)($_GET['fim'] ?? date('Y-m-d'))));
    $statusFiltro = strtoupper(trim((string)($_GET['status'] ?? 'ATIVA')));
    $busca = trim((string)($_GET['busca'] ?? ''));

    $editLiberacao = null;
    $editLiberacaoId = (int)($_GET['edit_liberacao'] ?? 0);
    if ($canManageLiberacoes && $editLiberacaoId > 0) {
        $st = $pdo->prepare("SELECT * FROM cftv_liberacoes WHERE id = ? LIMIT 1");
        $st->execute([$editLiberacaoId]);
        $editLiberacao = $st->fetch() ?: null;
    }

    $where = ['l.data_acesso <= ?', 'COALESCE(l.data_fim,l.data_acesso) >= ?'];
    $params = [$fim, $inicio];
    if (in_array($statusFiltro, ['ATIVA', 'CANCELADA'], true)) {
        $where[] = 'l.status = ?';
        $params[] = $statusFiltro;
    }
    if ($busca !== '') {
        $cpfBusca = cftv_normalizar_cpf($busca);
        $where[] = '(l.nome_completo LIKE ? OR l.destino_nome LIKE ? OR l.cpf LIKE ? OR l.email LIKE ?)';
        $params[] = '%' . $busca . '%';
        $params[] = '%' . $busca . '%';
        $params[] = '%' . ($cpfBusca !== '' ? $cpfBusca : $busca) . '%';
        $params[] = '%' . $busca . '%';
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $st = $pdo->prepare("
        SELECT
            l.*,
            u.nome AS criado_por_nome,
            COALESCE(conf.total_confirmacoes, 0) AS total_confirmacoes,
            COALESCE(conf.confirmadas, 0) AS confirmadas,
            COALESCE(conf.alteradas, 0) AS alteradas,
            COALESCE(conf.alertas, 0) AS alertas
        FROM cftv_liberacoes l
        JOIN usuarios u ON u.id = l.criado_por
        LEFT JOIN (
            SELECT
                liberacao_id,
                COUNT(*) AS total_confirmacoes,
                SUM(status_confirmacao = 'CONFIRMADA') AS confirmadas,
                SUM(status_confirmacao = 'CONFIRMADA_COM_ALTERACAO') AS alteradas,
                SUM(status_confirmacao IN ('CONFIRMADA_COM_ALTERACAO','DIVERGENTE')) AS alertas
            FROM cftv_liberacao_confirmacoes
            GROUP BY liberacao_id
        ) conf ON conf.liberacao_id = l.id
        $whereSql
        ORDER BY l.data_acesso DESC, COALESCE(l.hora_inicio, '00:00:00') ASC, l.id DESC
        LIMIT 300
    ");
    $st->execute($params);
    $liberacoes = $st->fetchAll();

    render_header('Liberacoes CFTV');
    ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Liberacoes CFTV</h1>
            <small class="text-muted">Cadastro de acessos autorizados para conferencia pelo operador no relatorio do plantao.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-dark btn-sm" href="<?= e(app_path('cftv_historico_acessos')) ?>">Histórico de acessos</a>
            <?php if (($user['perfil'] ?? '') === 'TI'): ?>
                <a class="btn btn-outline-primary btn-sm" href="<?= e(app_path('cftv_destinos')) ?>">Cadastrar lojas</a>
            <?php endif; ?>
            <?php if (in_array($user['perfil'] ?? '', ['TI', 'CFTV'], true)): ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?= e(app_path('cftv')) ?>">Relatorios CFTV</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($mensagemSucesso): ?><div class="alert alert-success"><?= e($mensagemSucesso) ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger"><?= e($mensagemErro) ?></div><?php endif; ?>
    <script>function enviarLinkSolicitacao(){const url=document.getElementById('link-publico-acesso').value;const texto='Preencha sua solicitação de acesso e acompanhe a aprovação por este link:';if(navigator.share){navigator.share({title:'Solicitação de acesso',text:texto,url:url}).catch(()=>{});}else{window.open('https://wa.me/?text='+encodeURIComponent(texto+' '+url),'_blank','noopener');}}</script>

    <?php if ($canManageLiberacoes): ?>
        <div class="card p-3 mb-3 border-primary">
<div><h2 class="h5 mb-0">Link público protegido</h2><small class="text-muted">O endereço contém um token assinado, não expõe dados internos e pode receber novas solicitações.</small></div>
          <div class="input-group mt-3"><input class="form-control" id="link-publico-acesso" readonly value="<?=e($linkPublicoPermanente)?>"><button class="btn btn-outline-primary" type="button" onclick="navigator.clipboard.writeText(document.getElementById('link-publico-acesso').value)">Copiar</button><button class="btn btn-primary" type="button" onclick="enviarLinkSolicitacao()">Enviar link de solicitação</button></div>
        </div>
        <?php if($resumoSolicitacoesSetor): ?><div class="card p-3 mb-3"><h2 class="h5">Solicitações de liberação por setor <small class="text-muted">(mês atual)</small></h2><div class="row g-2"><?php foreach($resumoSolicitacoesSetor as $resumo): ?><div class="col-md-4"><div class="border rounded p-3 h-100"><strong><?=e(cftv_destinos_liberacao()[$resumo['destino_tipo']]??$resumo['destino_tipo'])?></strong><?php if(in_array($resumo['destino_tipo'],['LOJA','LOJA_MALL','PRACA_ALIMENTACAO'],true)): ?><div><?=e($resumo['destino_nome'])?></div><?php endif ?><div class="h4 mb-1"><?=e($resumo['total'])?></div><small class="text-muted"><?=e($resumo['pendentes'])?> pendente(s) · <?=e($resumo['aprovadas'])?> aprovada(s) · <?=e($resumo['reprovadas'])?> reprovada(s)</small></div></div><?php endforeach ?></div></div><?php endif ?>
        <?php if($solicitacoesPendentes): ?>
        <style>
        .solicitacao-card{border:1px solid var(--bs-border-color);border-left:5px solid var(--bs-warning);border-radius:.75rem;padding:1rem;background:var(--bs-body-bg)}
        .solicitacao-card+.solicitacao-card{margin-top:.75rem}.solicitacao-meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}
        .solicitacao-meta .meta-box{background:var(--bs-tertiary-bg);border-radius:.6rem;padding:.7rem}.solicitacao-meta small{display:block;color:var(--bs-secondary-color);margin-bottom:.15rem}
        .solicitacao-actions{border-top:1px solid var(--bs-border-color);padding-top:.8rem;margin-top:.8rem}
        @media(max-width:991px){.solicitacao-meta{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:575px){.solicitacao-meta{grid-template-columns:1fr}}
        </style>
        <div class="card p-3 mb-3 border-warning">
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div><h2 class="h5 mb-0">Solicitações aguardando aprovação <span class="badge text-bg-warning"><?=count($solicitacoesPendentes)?></span></h2><small class="text-muted">Todos os usuários autorizados podem consultar. As ações aparecem somente para quem pode decidir.</small></div>
          </div>
          <?php foreach($solicitacoesPendentes as $sol):
            $criadoTs=strtotime((string)$sol['criado_em']);
            $tempoEspera=$criadoTs?max(0,time()-$criadoTs):0;
            $horasEspera=(int)floor($tempoEspera/3600);
          ?>
          <article class="solicitacao-card">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
              <div><span class="badge text-bg-warning mb-2">Pendente</span><h3 class="h5 mb-1"><?=e($sol['nome_completo'])?></h3><div class="text-muted small">Protocolo <strong><?=e($sol['protocolo'])?></strong> · recebida em <?=e(date('d/m/Y H:i',$criadoTs))?></div></div>
              <div class="text-end"><strong><?=e(date('d/m/Y',strtotime($sol['data_acesso'])))?><?=(!empty($sol['data_fim'])&&$sol['data_fim']!==$sol['data_acesso'])?' a '.e(date('d/m/Y',strtotime($sol['data_fim']))):''?></strong><br><span class="text-muted"><?=e(substr((string)$sol['hora_inicio'],0,5))?> às <?=e(substr((string)$sol['hora_fim'],0,5))?></span><?php if($horasEspera>=1): ?><br><span class="badge text-bg-secondary mt-1">Aguardando há <?=$horasEspera?>h</span><?php endif ?></div>
            </div>
            <div class="solicitacao-meta">
              <div class="meta-box"><small>Contato</small><strong><?=e(cftv_formatar_cpf($sol['cpf']))?></strong><?php if($sol['telefone']): ?><br><a href="tel:<?=e(preg_replace('/\D+/','',$sol['telefone']))?>"><?=e($sol['telefone'])?></a><?php endif ?><?php if(!empty($sol['email'])): ?><br><a href="mailto:<?=e($sol['email'])?>"><?=e($sol['email'])?></a><?php endif ?></div>
              <div class="meta-box"><small>Destino</small><strong><?=e($sol['destino_nome'])?></strong><br><?=e(cftv_destinos_liberacao()[$sol['destino_tipo']]??$sol['destino_tipo'])?></div>
              <div class="meta-box"><small>Empresa / responsável</small><strong><?=e($sol['empresa']?:'Não informado')?></strong><?php if($sol['responsavel_interno']): ?><br><?=e($sol['responsavel_interno'])?><?php endif ?></div>
              <div class="meta-box"><small>Veículo</small><strong><?=e($sol['veiculo_placa']?:'Não informado')?></strong><?php if($sol['pessoas_adicionais']): ?><br><span class="small">Com acompanhante(s)</span><?php endif ?></div>
            </div>
            <details class="mt-3 border rounded p-2">
              <summary class="fw-semibold btn btn-sm btn-outline-primary">Ver ficha completa</summary>
              <div class="pt-3 small row g-2">
                <div class="col-md-4"><strong>Nome:</strong><br><?=e($sol['nome_completo']?:'—')?></div>
                <div class="col-md-4"><strong>CPF:</strong><br><?=e(cftv_formatar_cpf($sol['cpf']))?></div>
                <div class="col-md-4"><strong>Telefone:</strong><br><?=e($sol['telefone']?:'—')?></div>
                <div class="col-md-4"><strong>E-mail:</strong><br><?=e($sol['email']?:'—')?></div>
                <div class="col-md-4"><strong>Empresa:</strong><br><?=e($sol['empresa']?:'—')?></div>
                <div class="col-md-4"><strong>Responsável interno:</strong><br><?=e($sol['responsavel_interno']?:'—')?></div>
                <div class="col-md-4"><strong>Tipo de acesso:</strong><br><?=e(cftv_destinos_liberacao()[$sol['destino_tipo']]??$sol['destino_tipo'])?></div>
                <div class="col-md-4"><strong>Destino:</strong><br><?=e($sol['destino_nome']?:'—')?></div>
                <div class="col-md-4"><strong>Veículo:</strong><br><?=e($sol['veiculo_placa']?:'—')?></div>
                <div class="col-md-6"><strong>Data e horário:</strong><br><?=e(date('d/m/Y',strtotime($sol['data_acesso'])))?><?=(!empty($sol['data_fim'])&&$sol['data_fim']!==$sol['data_acesso'])?' a '.e(date('d/m/Y',strtotime($sol['data_fim']))):''?> · <?=e(substr((string)$sol['hora_inicio'],0,5))?> às <?=e(substr((string)$sol['hora_fim'],0,5))?></div>
                <div class="col-md-6"><strong>Protocolo:</strong><br><?=e($sol['protocolo']?:'—')?></div>
                <div class="col-12"><strong>Motivo:</strong><br><?=nl2br(e($sol['motivo']?:'—'))?></div>
                <div class="col-12"><strong>Observações:</strong><br><?=nl2br(e($sol['observacoes']?:'—'))?></div>
                <div class="col-12"><strong>Demais pessoas:</strong><br><?=e($sol['pessoas_adicionais']?:'Nenhum acompanhante informado.')?></div>
              </div>
            </details>
            <?php if($canManageLiberacoes): ?>
            <form method="post" class="solicitacao-actions row g-2 align-items-end">
              <input type="hidden" name="csrf" value="<?=csrf_token()?>"><input type="hidden" name="solicitacao_id" value="<?=e($sol['id'])?>">
              <div class="col-lg"><label class="form-label small">Observação da decisão</label><input class="form-control" name="justificativa" placeholder="Obrigatória para reprovar; opcional para aprovar"></div>
              <div class="col-auto d-flex gap-2"><button class="btn btn-success" name="action" value="aprovar_solicitacao_acesso">Aprovar</button><button class="btn btn-outline-danger" name="action" value="reprovar_solicitacao_acesso">Reprovar</button></div>
            </form>
            <?php else: ?><div class="alert alert-light border mt-3 mb-0 py-2 small">Consulta somente leitura. A decisão cabe aos perfis autorizados.</div><?php endif ?>
          </article>
          <?php endforeach ?>
        </div>
        <?php else: ?><div class="alert alert-light border mb-3">Não há solicitações pendentes no momento.</div><?php endif ?>
        <div class="card p-3 mb-3">
            <h2 class="h5"><?= $editLiberacao ? 'Editar liberacao' : 'Nova liberacao' ?></h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="salvar_liberacao">
                <input type="hidden" name="liberacao_id" value="<?= e($editLiberacao['id'] ?? '') ?>">
                <div class="col-md-3">
                    <label class="form-label">Data inicial</label>
                    <input class="form-control" type="date" name="data_acesso" value="<?= e($editLiberacao['data_acesso'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data final</label>
                    <input class="form-control" type="date" name="data_fim" value="<?= e($editLiberacao['data_fim'] ?? $editLiberacao['data_acesso'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Horário inicial</label>
                    <input class="form-control" type="time" name="hora_inicio" value="<?= e(substr((string)($editLiberacao['hora_inicio'] ?? ''), 0, 5)) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Horário final</label>
                    <input class="form-control" type="time" name="hora_fim" value="<?= e(substr((string)($editLiberacao['hora_fim'] ?? ''), 0, 5)) ?>">
                </div>
                <div class="col-12"><div class="form-text">Exemplo: 10/09 a 12/09, das 09:00 às 12:00. A autorização será válida nesse horário em cada dia do período.</div></div>
                <div class="col-md-5">
                    <label class="form-label">Nome completo</label>
                    <input class="form-control" name="nome_completo" value="<?= e($editLiberacao['nome_completo'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">CPF</label>
                    <input class="form-control" name="cpf" value="<?= e(isset($editLiberacao['cpf']) ? cftv_formatar_cpf($editLiberacao['cpf']) : '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">E-mail do solicitante</label>
                    <input class="form-control" type="email" name="email" value="<?= e($editLiberacao['email'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo de acesso</label>
                    <select class="form-select" name="destino_tipo">
                        <?php foreach (cftv_destinos_liberacao() as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= (($editLiberacao['destino_tipo'] ?? 'LOJISTA') === $key) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Lojista, empresa ou setor que vai acessar</label>
                    <input class="form-control" name="destino_nome" value="<?= e($editLiberacao['destino_nome'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Motivo</label>
                    <textarea class="form-control" rows="2" name="motivo"><?= e($editLiberacao['motivo'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Observacoes</label>
                    <textarea class="form-control" rows="2" name="observacoes"><?= e($editLiberacao['observacoes'] ?? '') ?></textarea>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary"><?= $editLiberacao ? 'Salvar alteracoes' : 'Cadastrar liberacao' ?></button>
                    <?php if ($editLiberacao): ?>
                        <a class="btn btn-outline-secondary" href="<?= e(app_path('cftv', ['view' => 'liberacoes'])) ?>">Cancelar edicao</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="card p-3 mb-3">
        <form class="row g-2">
            <input type="hidden" name="view" value="liberacoes">
            <div class="col-md-2">
                <label class="form-label">Inicio</label>
                <input class="form-control" type="date" name="inicio" value="<?= e($inicio) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Fim</label>
                <input class="form-control" type="date" name="fim" value="<?= e($fim) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Todos</option>
                    <option value="ATIVA" <?= $statusFiltro === 'ATIVA' ? 'selected' : '' ?>>ATIVA</option>
                    <option value="CANCELADA" <?= $statusFiltro === 'CANCELADA' ? 'selected' : '' ?>>CANCELADA</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input class="form-control" name="busca" value="<?= e($busca) ?>" placeholder="Nome, CPF, e-mail, lojista, empresa ou setor">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-dark w-100">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Pessoa liberada</th>
                        <th>Destino</th>
                        <th>Implementada por</th>
                        <th>Status</th>
                        <th>Confirmacoes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($liberacoes as $lib): ?>
                        <tr>
                            <td>
                                <div><?= e(date('d/m/Y',strtotime($lib['data_acesso']))) ?><?= (!empty($lib['data_fim']) && $lib['data_fim'] !== $lib['data_acesso']) ? ' a '.e(date('d/m/Y',strtotime($lib['data_fim']))) : '' ?></div>
                                <div class="small text-muted"><?= e(substr((string)$lib['hora_inicio'], 0, 5) ?: '-') ?> às <?= e(substr((string)$lib['hora_fim'], 0, 5) ?: '-') ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($lib['nome_completo']) ?></div>
                                <div class="small text-muted"><?= e(cftv_formatar_cpf($lib['cpf'])) ?></div>
                                <?php if (!empty($lib['email'])): ?><div class="small"><a href="mailto:<?= e($lib['email']) ?>"><?= e($lib['email']) ?></a></div><?php endif; ?>
                            </td>
                            <td>
                                <div><?= e(cftv_destinos_liberacao()[$lib['destino_tipo']] ?? $lib['destino_tipo']) ?></div>
                                <div class="small text-muted"><?= e($lib['destino_nome']) ?></div>
                            </td>
                            <td><?= e($lib['criado_por_nome']) ?></td>
                            <td><?= cftv_badge_liberacao_status($lib['status']) ?></td>
                            <td>
                                <div><?= (int)$lib['total_confirmacoes'] ?> registro(s)</div>
                                <div class="small text-muted"><?= (int)$lib['confirmadas'] ?> conforme | <?= (int)$lib['alteradas'] ?> com alteracao</div>
                            </td>
                            <td class="text-end">
                                <?php if ($canManageLiberacoes): ?>
                                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('cftv', ['view' => 'liberacoes', 'edit_liberacao' => $lib['id'], 'inicio' => $inicio, 'fim' => $fim, 'status' => $statusFiltro, 'busca' => $busca])) ?>">Editar</a>
                                        <?php if ($lib['status'] === 'ATIVA'): ?>
                                            <form method="post" onsubmit="return confirm('Cancelar esta liberacao?')">
                                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="cancelar_liberacao">
                                                <input type="hidden" name="liberacao_id" value="<?= e($lib['id']) ?>">
                                                <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$liberacoes): ?>
                        <tr><td colspan="7" class="text-center text-muted">Nenhuma liberacao encontrada.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    render_footer();
    exit;
}

if ($view === 'geral') {
    if ($user['perfil'] !== 'TI') {
        http_response_code(403);
        die('Acesso negado.');
    }

    $inicio = cftv_valid_date(trim((string)($_GET['inicio'] ?? date('Y-m-01'))));
    $fim = cftv_valid_date(trim((string)($_GET['fim'] ?? date('Y-m-d'))));
    $operadorFiltro = (int)($_GET['operador_id'] ?? 0);

    $where = ['r.data_plantao >= ?', 'r.data_plantao <= ?'];
    $params = [$inicio, $fim];
    if ($operadorFiltro > 0) {
        $where[] = 'r.operador_id = ?';
        $params[] = $operadorFiltro;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);

    $st = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(r.status = 'FINALIZADO') AS finalizados,
            SUM(r.cameras_offline) AS cameras_offline,
            SUM(r.rondas_virtuais) AS rondas,
            SUM(r.eventos_preventivos) AS preventivos,
            AVG(r.tempo_medio_resposta_min) AS tempo_medio
        FROM cftv_relatorios r
        $whereSql
    ");
    $st->execute($params);
    $kpis = $st->fetch() ?: [];

    $st = $pdo->prepare("
        SELECT o.nivel, COUNT(*) AS total, AVG(o.tempo_resposta_min) AS tempo_medio
        FROM cftv_ocorrencias o
        JOIN cftv_relatorios r ON r.id = o.relatorio_id
        $whereSql
        GROUP BY o.nivel
        ORDER BY o.nivel
    ");
    $st->execute($params);
    $porNivel = $st->fetchAll();

    $st = $pdo->prepare("
        SELECT ob.equipe, COUNT(*) AS total, SUM(ob.classificacao IN ('NAO_CONFORMIDADE','CRITICA')) AS alertas
        FROM cftv_observacoes ob
        JOIN cftv_relatorios r ON r.id = ob.relatorio_id
        $whereSql
        GROUP BY ob.equipe
        ORDER BY alertas DESC, total DESC
    ");
    $st->execute($params);
    $porEquipe = $st->fetchAll();

    $st = $pdo->prepare("
        SELECT COALESCE(NULLIF(o.local, ''), 'Sem local') AS local_nome, COUNT(*) AS total
        FROM cftv_ocorrencias o
        JOIN cftv_relatorios r ON r.id = o.relatorio_id
        $whereSql
        GROUP BY local_nome
        ORDER BY total DESC
        LIMIT 10
    ");
    $st->execute($params);
    $porLocal = $st->fetchAll();

    $st = $pdo->prepare("
        SELECT o.*, r.data_plantao, r.turno, u.nome AS operador_nome
        FROM cftv_ocorrencias o
        JOIN cftv_relatorios r ON r.id = o.relatorio_id
        JOIN usuarios u ON u.id = r.operador_id
        $whereSql
        AND o.nivel = 'NIVEL_3'
        ORDER BY COALESCE(o.horario, o.criado_em) DESC
        LIMIT 20
    ");
    $st->execute($params);
    $criticas = $st->fetchAll();

    $operadores = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'CFTV' ORDER BY nome")->fetchAll();

    render_header('Relatorio geral CFTV');
    ?>

    <style>
    @media print {
        .navbar, .no-print { display: none !important; }
        main.container-fluid { padding: 0 !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
    }
    </style>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Relatorio geral CFTV</h1>
            <small class="text-muted">Consolidado operacional do periodo.</small>
        </div>
        <div class="d-flex gap-2 no-print">
            <button class="btn btn-outline-dark" onclick="window.print()">Imprimir</button>
            <a class="btn btn-outline-secondary" href="<?= e(app_path('cftv')) ?>">Voltar</a>
        </div>
    </div>

    <?php if ($mensagemSucesso): ?><div class="alert alert-success"><?= e($mensagemSucesso) ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger"><?= e($mensagemErro) ?></div><?php endif; ?>

    <div class="card p-3 mb-3 no-print">
        <form class="row g-2">
            <input type="hidden" name="view" value="geral">
            <div class="col-md-3">
                <label class="form-label">Inicio</label>
                <input class="form-control" type="date" name="inicio" value="<?= e($inicio) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Fim</label>
                <input class="form-control" type="date" name="fim" value="<?= e($fim) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Operador</label>
                <select class="form-select" name="operador_id">
                    <option value="">Todos</option>
                    <?php foreach ($operadores as $op): ?>
                        <option value="<?= e($op['id']) ?>" <?= $operadorFiltro === (int)$op['id'] ? 'selected' : '' ?>><?= e($op['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-2"><div class="card p-3 h-100"><div class="metric"><?= (int)($kpis['total'] ?? 0) ?></div><div class="metric-label">Relatorios</div></div></div>
        <div class="col-md-2"><div class="card p-3 h-100"><div class="metric"><?= (int)($kpis['finalizados'] ?? 0) ?></div><div class="metric-label">Finalizados</div></div></div>
        <div class="col-md-2"><div class="card p-3 h-100"><div class="metric"><?= (int)($kpis['cameras_offline'] ?? 0) ?></div><div class="metric-label">Cameras offline</div></div></div>
        <div class="col-md-2"><div class="card p-3 h-100"><div class="metric"><?= (int)($kpis['rondas'] ?? 0) ?></div><div class="metric-label">Rondas virtuais</div></div></div>
        <div class="col-md-2"><div class="card p-3 h-100"><div class="metric"><?= (int)($kpis['preventivos'] ?? 0) ?></div><div class="metric-label">Eventos preventivos</div></div></div>
        <div class="col-md-2"><div class="card p-3 h-100"><div class="metric"><?= ($kpis['tempo_medio'] ?? null) !== null ? number_format((float)$kpis['tempo_medio'], 1, ',', '.') : '-' ?></div><div class="metric-label">Media resposta min</div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h5">Ocorrencias por nivel</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Nivel</th><th>Total</th><th>Resp.</th></tr></thead>
                        <tbody>
                            <?php foreach ($porNivel as $row): ?>
                                <tr>
                                    <td><?= cftv_badge_nivel($row['nivel']) ?></td>
                                    <td><?= (int)$row['total'] ?></td>
                                    <td><?= $row['tempo_medio'] !== null ? number_format((float)$row['tempo_medio'], 1, ',', '.') . ' min' : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$porNivel): ?><tr><td colspan="3" class="text-muted text-center">Sem ocorrencias.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h5">Equipes observadas</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Equipe</th><th>Total</th><th>Alertas</th></tr></thead>
                        <tbody>
                            <?php foreach ($porEquipe as $row): ?>
                                <tr><td><?= e($row['equipe']) ?></td><td><?= (int)$row['total'] ?></td><td><?= (int)$row['alertas'] ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (!$porEquipe): ?><tr><td colspan="3" class="text-muted text-center">Sem observacoes.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3 h-100">
                <h2 class="h5">Locais recorrentes</h2>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Local</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($porLocal as $row): ?>
                                <tr><td><?= e($row['local_nome']) ?></td><td><?= (int)$row['total'] ?></td></tr>
                            <?php endforeach; ?>
                            <?php if (!$porLocal): ?><tr><td colspan="2" class="text-muted text-center">Sem dados.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3 mt-3">
        <h2 class="h5">Eventos criticos</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead><tr><th>Data</th><th>Operador</th><th>Local</th><th>Tipo</th><th>Descricao</th><th>Encerramento</th></tr></thead>
                <tbody>
                    <?php foreach ($criticas as $row): ?>
                        <tr>
                            <td><?= e($row['data_plantao']) ?> <?= e($row['turno']) ?></td>
                            <td><?= e($row['operador_nome']) ?></td>
                            <td><?= e($row['local'] ?: '-') ?></td>
                            <td><?= e($row['tipo'] ?: '-') ?></td>
                            <td style="max-width:360px; white-space:normal;"><?= e($row['descricao'] ?: '-') ?></td>
                            <td style="max-width:260px; white-space:normal;"><?= e($row['encerramento'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$criticas): ?><tr><td colspan="6" class="text-center text-muted">Nenhum evento critico no periodo.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    render_footer();
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $relatorio = cftv_require_report($pdo, $id, $user);
    $podeEditar = cftv_can_edit_report($relatorio, $user);
    $relatorioNoturno = ($relatorio['turno'] ?? '') === 'NOTURNO';
    $relatorioAutoFechaDiurno = ($relatorio['turno'] ?? '') === 'DIURNO' && ($relatorio['status'] ?? '') !== 'FINALIZADO';

    $st = $pdo->prepare("SELECT * FROM cftv_ocorrencias WHERE relatorio_id = ? ORDER BY COALESCE(horario, criado_em) DESC, id DESC");
    $st->execute([$id]);
    $ocorrencias = $st->fetchAll();

    $st = $pdo->prepare("SELECT * FROM cftv_observacoes WHERE relatorio_id = ? ORDER BY COALESCE(horario, criado_em) DESC, id DESC");
    $st->execute([$id]);
    $observacoes = $st->fetchAll();

    $st = $pdo->prepare("
        SELECT
            l.*,
            c.status_confirmacao,
            c.horario_confirmacao,
            c.alteracao_observada,
            c.confirmado_por,
            u.nome AS confirmado_por_nome
        FROM cftv_liberacoes l
        LEFT JOIN cftv_liberacao_confirmacoes c ON c.liberacao_id = l.id AND c.relatorio_id = ?
        LEFT JOIN usuarios u ON u.id = c.confirmado_por
        WHERE ? BETWEEN l.data_acesso AND COALESCE(l.data_fim,l.data_acesso)
        AND (l.status = 'ATIVA' OR c.id IS NOT NULL)
        ORDER BY COALESCE(l.hora_inicio, '00:00:00') ASC, l.nome_completo ASC
    ");
    $st->execute([$id, $relatorio['data_plantao']]);
    $liberacoesPlantao = $st->fetchAll();

    $tokenAtivo = null;
    if ($user['perfil'] === 'TI') {
        $st = $pdo->prepare("
            SELECT t.*, u.nome AS ti_nome
            FROM cftv_tokens_edicao t
            JOIN usuarios u ON u.id = t.gerado_por
            WHERE t.relatorio_id = ?
            AND t.usado_em IS NULL
            AND t.expira_em >= NOW()
            ORDER BY t.criado_em DESC
            LIMIT 1
        ");
        $st->execute([$id]);
        $tokenAtivo = $st->fetch();
    }

    render_header('Relatorio CFTV');
    ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-0">Relatorio CFTV</h1>
            <small class="text-muted"><?= e($relatorio['data_plantao']) ?> - <?= e($relatorio['turno']) ?> - <?= e($relatorio['operador_nome']) ?></small>
        </div>
        <div class="d-flex gap-2">
            <?= cftv_badge_status($relatorio['status']) ?>
            <a class="btn btn-outline-secondary btn-sm" href="<?= e(app_path('cftv')) ?>">Voltar</a>
        </div>
    </div>

    <?php if ($mensagemSucesso): ?><div class="alert alert-success"><?= e($mensagemSucesso) ?></div><?php endif; ?>
    <?php if ($mensagemErro): ?><div class="alert alert-danger"><?= e($mensagemErro) ?></div><?php endif; ?>
    <?php if ($tokenGerado): ?>
        <div class="alert alert-warning">
            <b>Token de edicao:</b> <span class="fs-4 fw-bold ms-2"><?= e($tokenGerado) ?></span>
            <div class="small">Valido por 24 horas e utilizavel uma unica vez.</div>
        </div>
    <?php endif; ?>

    <?php if (!$podeEditar && $user['perfil'] === 'CFTV'): ?>
        <div class="card p-3 mb-3">
            <h2 class="h5">Liberar edicao com token</h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="usar_token_edicao">
                <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">
                <div class="col-md-9">
                    <label class="form-label">Token TI</label>
                    <input class="form-control" name="token_edicao" autocomplete="off" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-warning w-100">Liberar</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card p-3 mb-3">
                <form method="post" class="row g-3" id="cftv-relatorio-form">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="salvar_relatorio">
                    <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">

                    <div class="<?= $relatorioNoturno ? 'col-md-2' : 'col-md-3' ?>">
                        <label class="form-label">Inicio</label>
                        <input class="form-control" type="time" name="inicio_turno" value="<?= e(substr((string)$relatorio['inicio_turno'], 0, 5)) ?>" <?= $podeEditar ? '' : 'disabled' ?>>
                    </div>
                    <div class="<?= $relatorioNoturno ? 'col-md-2' : 'col-md-3' ?>">
                        <label class="form-label">Fim</label>
                        <input class="form-control" type="time" name="fim_turno" value="<?= e(substr((string)$relatorio['fim_turno'], 0, 5)) ?>" <?= $podeEditar ? '' : 'disabled' ?>>
                    </div>
                    <?php if ($relatorioNoturno): ?>
                        <div class="col-md-2">
                            <label class="form-label">Fluxo clientes</label>
                            <input class="form-control" type="number" min="0" name="fluxo_clientes" value="<?= e($relatorio['fluxo_clientes'] ?? 0) ?>" <?= $podeEditar ? '' : 'disabled' ?>>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="fluxo_clientes" value="<?= e($relatorio['fluxo_clientes'] ?? 0) ?>">
                    <?php endif; ?>
                    <div class="col-md-2">
                        <label class="form-label">Offline</label>
                        <input class="form-control" type="number" min="0" name="cameras_offline" value="<?= e($relatorio['cameras_offline']) ?>" <?= $podeEditar ? '' : 'disabled' ?>>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rondas</label>
                        <input class="form-control" type="number" min="0" name="rondas_virtuais" value="<?= e($relatorio['rondas_virtuais']) ?>" <?= $podeEditar ? '' : 'disabled' ?>>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Preventivos</label>
                        <input class="form-control" type="number" min="0" name="eventos_preventivos" value="<?= e($relatorio['eventos_preventivos']) ?>" <?= $podeEditar ? '' : 'disabled' ?>>
                    </div>

                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cameras_funcionando" id="cameras_funcionando" <?= $relatorio['cameras_funcionando'] ? 'checked' : '' ?> <?= $podeEditar ? '' : 'disabled' ?>>
                                <label class="form-check-label" for="cameras_funcionando">Cameras verificadas</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="nvrs_gravando" id="nvrs_gravando" <?= $relatorio['nvrs_gravando'] ? 'checked' : '' ?> <?= $podeEditar ? '' : 'disabled' ?>>
                                <label class="form-check-label" for="nvrs_gravando">NVRs gravando</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="horario_sincronizado" id="horario_sincronizado" <?= $relatorio['horario_sincronizado'] ? 'checked' : '' ?> <?= $podeEditar ? '' : 'disabled' ?>>
                                <label class="form-check-label" for="horario_sincronizado">Horario sincronizado</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="radios_ok" id="radios_ok" <?= $relatorio['radios_ok'] ? 'checked' : '' ?> <?= $podeEditar ? '' : 'disabled' ?>>
                                <label class="form-check-label" for="radios_ok">Radios OK</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Passagem de servico</label>
                        <textarea class="form-control" rows="4" name="passagem_servico" <?= $podeEditar ? '' : 'disabled' ?>><?= e($relatorio['passagem_servico']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Falhas no livro digital</label>
                        <textarea class="form-control" rows="4" name="falhas_livro" <?= $podeEditar ? '' : 'disabled' ?>><?= e($relatorio['falhas_livro']) ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tempo medio resposta (min)</label>
                        <input class="form-control" type="number" min="0" name="tempo_medio_resposta_min" value="<?= e($relatorio['tempo_medio_resposta_min']) ?>" <?= $podeEditar ? '' : 'disabled' ?>>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Areas prioritarias monitoradas</label>
                        <textarea class="form-control" rows="3" name="areas_prioritarias" <?= $podeEditar ? '' : 'disabled' ?>><?= e($relatorio['areas_prioritarias']) ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Situacoes suspeitas</label>
                        <textarea class="form-control" rows="4" name="situacoes_suspeitas" <?= $podeEditar ? '' : 'disabled' ?>><?= e($relatorio['situacoes_suspeitas']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Equipes observadas</label>
                        <textarea class="form-control" rows="4" name="equipes_observadas" <?= $podeEditar ? '' : 'disabled' ?>><?= e($relatorio['equipes_observadas']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Resumo das ocorrencias</label>
                        <textarea class="form-control" rows="4" name="ocorrencias_resumo" <?= $podeEditar ? '' : 'disabled' ?>><?= e($relatorio['ocorrencias_resumo']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Observacoes gerais</label>
                        <textarea class="form-control" rows="4" name="observacoes_gerais" <?= $podeEditar ? '' : 'disabled' ?>><?= e($relatorio['observacoes_gerais']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Plano de melhoria</label>
                        <textarea class="form-control" rows="3" name="plano_melhoria" <?= $podeEditar ? '' : 'disabled' ?>><?= e($relatorio['plano_melhoria']) ?></textarea>
                    </div>

                    <?php if ($podeEditar): ?>
                        <div class="col-12 d-flex flex-wrap gap-2">
                            <button class="btn btn-primary">Salvar relatorio</button>
                            <?php if ($relatorio['status'] !== 'FINALIZADO'): ?>
                                <button class="btn btn-dark" name="action" value="finalizar_relatorio" onclick="return confirm('Finalizar o plantao?')">Finalizar dia</button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-secondary mb-0">Relatorio bloqueado para edicao.</div>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-xl-4">
            <?php if ($user['perfil'] === 'TI'): ?>
                <div class="card p-3 mb-3">
                    <h2 class="h5">Token de edicao</h2>
                    <?php if ($tokenAtivo): ?>
                        <div class="small text-muted mb-2">Token ativo gerado por <?= e($tokenAtivo['ti_nome']) ?>, valido ate <?= e($tokenAtivo['expira_em']) ?>.</div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="gerar_token_edicao">
                        <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">
                        <label class="form-label">Motivo</label>
                        <textarea class="form-control mb-2" name="motivo" rows="2">Edicao fora do periodo autorizada pelo TI.</textarea>
                        <button class="btn btn-warning w-100">Gerar token</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="card p-3">
                <h2 class="h5">Procedimentos base</h2>
                <ul class="small mb-0">
                    <li>Rondas virtuais em cameras criticas.</li>
                    <li>Acompanhamento de entradas, praca, estacionamento, docas e acessos tecnicos.</li>
                    <li>Registro de ocorrencias com hora, local, envolvidos, imagens, resposta e encerramento.</li>
                    <li>Preservacao de imagens em furto, acidente, auditoria, reclamacao e solicitacao policial.</li>
                    <li>Observacao operacional de mall, seguranca, limpeza, manutencao, brigada e estacionamento.</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="card p-3 mt-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="h5 mb-0">Liberacoes previstas para o plantao</h2>
                <small class="text-muted">Confirmar pessoas, empresas/lojistas ou setores liberados para <?= e($relatorio['data_plantao']) ?>.</small>
            </div>
            <a class="btn btn-sm btn-outline-secondary" href="<?= e(app_path('cftv', ['view' => 'liberacoes', 'inicio' => $relatorio['data_plantao'], 'fim' => $relatorio['data_plantao']])) ?>">Ver liberacoes</a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pessoa liberada</th>
                        <th>Destino</th>
                        <th>Horario previsto</th>
                        <th>Confirmacao</th>
                        <th style="min-width: 360px;">Registro do CFTV</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($liberacoesPlantao as $lib): ?>
                        <?php
                            $statusAtual = $lib['status_confirmacao'] ?: 'PENDENTE';
                            $statusForm = $lib['status_confirmacao'] ?: 'CONFIRMADA';
                            $horarioConfirmacao = cftv_datetime_to_input($lib['horario_confirmacao'] ?: ($relatorio['data_plantao'] . ' ' . date('H:i:s')));
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($lib['nome_completo']) ?></div>
                                <div class="small text-muted"><?= e(cftv_formatar_cpf($lib['cpf'])) ?></div>
                            </td>
                            <td>
                                <div><?= e(cftv_destinos_liberacao()[$lib['destino_tipo']] ?? $lib['destino_tipo']) ?></div>
                                <div class="small text-muted"><?= e($lib['destino_nome']) ?></div>
                                <?php if (trim((string)$lib['motivo']) !== ''): ?>
                                    <div class="small text-muted">Motivo: <?= e($lib['motivo']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= e(substr((string)$lib['hora_inicio'], 0, 5) ?: '-') ?> ate <?= e(substr((string)$lib['hora_fim'], 0, 5) ?: '-') ?></td>
                            <td>
                                <?= cftv_badge_confirmacao_liberacao($statusAtual) ?>
                                <?php if (($lib['status'] ?? 'ATIVA') !== 'ATIVA'): ?>
                                    <div class="mt-1"><?= cftv_badge_liberacao_status($lib['status']) ?></div>
                                <?php endif; ?>
                                <?php if ($lib['confirmado_por_nome']): ?>
                                    <div class="small text-muted"><?= e($lib['confirmado_por_nome']) ?> em <?= e($lib['horario_confirmacao'] ?: '-') ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($podeEditar && ($lib['status'] ?? 'ATIVA') === 'ATIVA'): ?>
                                    <form method="post" class="row g-2">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="action" value="confirmar_liberacao">
                                        <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">
                                        <input type="hidden" name="liberacao_id" value="<?= e($lib['id']) ?>">
                                        <div class="col-md-6">
                                            <select class="form-select form-select-sm" name="status_confirmacao">
                                                <?php foreach (cftv_status_confirmacao_liberacao() as $key => $label): ?>
                                                    <option value="<?= e($key) ?>" <?= $statusForm === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <input class="form-control form-control-sm" type="datetime-local" name="horario_confirmacao" value="<?= e($horarioConfirmacao) ?>">
                                        </div>
                                        <div class="col-12">
                                            <textarea class="form-control form-control-sm" rows="2" name="alteracao_observada" placeholder="Informe alteracao, divergencia ou observacao da liberacao"><?= e($lib['alteracao_observada'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <button class="btn btn-sm btn-outline-primary">Confirmar liberacao</button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <div style="max-width:360px; white-space:normal;"><?= e($lib['alteracao_observada'] ?: '-') ?></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$liberacoesPlantao): ?>
                        <tr><td colspan="5" class="text-center text-muted">Nenhuma liberacao cadastrada para a data deste plantao.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-6">
            <div class="card p-3 h-100">
                <h2 class="h5">Ocorrencias</h2>
                <?php if ($podeEditar): ?>
                    <form method="post" class="row g-2 mb-3">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="adicionar_ocorrencia">
                        <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">
                        <div class="col-md-5">
                            <label class="form-label">Horario</label>
                            <input class="form-control" type="datetime-local" name="horario" value="<?= e(date('Y-m-d\TH:i')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nivel</label>
                            <select class="form-select" name="nivel">
                                <?php foreach (cftv_niveis_ocorrencia() as $key => $label): ?>
                                    <option value="<?= e($key) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Resposta min</label>
                            <input class="form-control" type="number" min="0" name="tempo_resposta_min">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <input class="form-control" name="tipo" placeholder="Furto, briga, fluxo intenso...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Local</label>
                            <input class="form-control" name="local">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descricao detalhada</label>
                            <textarea class="form-control" rows="3" name="descricao" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Envolvidos</label>
                            <textarea class="form-control" rows="2" name="envolvidos"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Equipes acionadas</label>
                            <textarea class="form-control" rows="2" name="equipes_acionadas"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Imagens vinculadas</label>
                            <input class="form-control" name="imagens" placeholder="Camera, horario, arquivo ou protocolo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Encerramento</label>
                            <input class="form-control" name="encerramento">
                        </div>
                        <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="preservar_imagens" id="preservar_imagens">
                                <label class="form-check-label" for="preservar_imagens">Preservar imagens</label>
                            </div>
                            <button class="btn btn-outline-primary">Adicionar ocorrencia</button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Horario</th><th>Nivel</th><th>Local</th><th>Descricao</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($ocorrencias as $oc): ?>
                                <tr>
                                    <td><?= e($oc['horario'] ?: $oc['criado_em']) ?></td>
                                    <td><?= cftv_badge_nivel($oc['nivel']) ?></td>
                                    <td><?= e($oc['local'] ?: '-') ?></td>
                                    <td style="max-width:320px; white-space:normal;">
                                        <div><?= e($oc['descricao'] ?: '-') ?></div>
                                        <div class="small text-muted"><?= e($oc['tipo'] ?: '-') ?> | Resp.: <?= e($oc['tempo_resposta_min'] !== null ? $oc['tempo_resposta_min'] . ' min' : '-') ?><?= $oc['preservar_imagens'] ? ' | Preservar imagens' : '' ?></div>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($podeEditar): ?>
                                            <form method="post" onsubmit="return confirm('Remover ocorrencia?')" class="d-inline">
                                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="excluir_ocorrencia">
                                                <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">
                                                <input type="hidden" name="item_id" value="<?= e($oc['id']) ?>">
                                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$ocorrencias): ?><tr><td colspan="5" class="text-center text-muted">Nenhuma ocorrencia registrada.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card p-3 h-100">
                <h2 class="h5">Vistoria operacional</h2>
                <?php if ($podeEditar): ?>
                    <form method="post" class="row g-2 mb-3">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="adicionar_observacao">
                        <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">
                        <div class="col-md-4">
                            <label class="form-label">Equipe</label>
                            <select class="form-select" name="equipe">
                                <?php foreach (cftv_equipes_monitoradas() as $eq): ?>
                                    <option value="<?= e($eq) ?>"><?= e($eq) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Item</label>
                            <select class="form-select" name="item">
                                <?php foreach (cftv_itens_observacao() as $item): ?>
                                    <option value="<?= e($item) ?>"><?= e($item) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Classificacao</label>
                            <select class="form-select" name="classificacao">
                                <?php foreach (cftv_classificacoes_observacao() as $cl): ?>
                                    <option value="<?= e($cl) ?>"><?= e(str_replace('_', ' ', $cl)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Horario</label>
                            <input class="form-control" type="datetime-local" name="horario" value="<?= e(date('Y-m-d\TH:i')) ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Local</label>
                            <input class="form-control" name="local">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Resposta min</label>
                            <input class="form-control" type="number" min="0" name="tempo_resposta_min">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Descricao</label>
                            <textarea class="form-control" rows="3" name="descricao" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Providencia</label>
                            <textarea class="form-control" rows="3" name="providencia"></textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-outline-primary">Adicionar observacao</button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Equipe</th><th>Item</th><th>Class.</th><th>Descricao</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($observacoes as $ob): ?>
                                <tr>
                                    <td><?= e($ob['equipe']) ?></td>
                                    <td><?= e($ob['item'] ?: '-') ?></td>
                                    <td><?= cftv_badge_classificacao($ob['classificacao']) ?></td>
                                    <td style="max-width:330px; white-space:normal;">
                                        <div><?= e($ob['descricao'] ?: '-') ?></div>
                                        <div class="small text-muted"><?= e($ob['local'] ?: '-') ?> | <?= e($ob['horario'] ?: $ob['criado_em']) ?></div>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($podeEditar): ?>
                                            <form method="post" onsubmit="return confirm('Remover observacao?')" class="d-inline">
                                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                                <input type="hidden" name="action" value="excluir_observacao">
                                                <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">
                                                <input type="hidden" name="item_id" value="<?= e($ob['id']) ?>">
                                                <button class="btn btn-sm btn-outline-danger">Remover</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$observacoes): ?><tr><td colspan="5" class="text-center text-muted">Nenhuma auditoria registrada.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($relatorioAutoFechaDiurno): ?>
        <form id="cftv-auto-finalizar-diurno" class="d-none" method="post" action="<?= e(app_path('cftv')) ?>" data-data-plantao="<?= e($relatorio['data_plantao']) ?>">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="auto_finalizar_relatorio">
            <input type="hidden" name="relatorio_id" value="<?= e($id) ?>">
            <input type="hidden" name="dispositivo_horario" value="">
        </form>
        <script>
        (function () {
            var form = document.getElementById('cftv-auto-finalizar-diurno');
            if (!form) return;

            var dataPlantao = form.getAttribute('data-data-plantao') || '';
            var partes = dataPlantao.split('-').map(function (parte) { return parseInt(parte, 10); });
            if (partes.length !== 3 || partes.some(function (parte) { return isNaN(parte); })) return;

            var limite = new Date(partes[0], partes[1] - 1, partes[2], 17, 10, 0, 0);
            var pad = function (valor) { return String(valor).padStart(2, '0'); };
            var formatarDataHoraDispositivo = function (data) {
                return data.getFullYear() + '-' +
                    pad(data.getMonth() + 1) + '-' +
                    pad(data.getDate()) + ' ' +
                    pad(data.getHours()) + ':' +
                    pad(data.getMinutes()) + ':' +
                    pad(data.getSeconds());
            };

            var tentarFinalizar = function () {
                if (form.getAttribute('data-enviando') === '1') return;

                var agora = new Date();
                if (agora < limite) return;

                form.setAttribute('data-enviando', '1');
                var horarioDispositivo = formatarDataHoraDispositivo(agora);
                form.querySelector('[name="dispositivo_horario"]').value = horarioDispositivo;

                var relatorioForm = document.getElementById('cftv-relatorio-form');
                var dados = relatorioForm ? new FormData(relatorioForm) : new FormData(form);
                dados.set('action', 'auto_finalizar_relatorio');
                dados.set('relatorio_id', form.querySelector('[name="relatorio_id"]').value);
                dados.set('dispositivo_horario', horarioDispositivo);

                fetch(form.getAttribute('action') || window.location.href, {
                    method: 'POST',
                    body: dados,
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function (resposta) {
                    return resposta.json().catch(function () { return null; });
                })
                .then(function (dados) {
                    if (dados && dados.ok) {
                        window.location.reload();
                        return;
                    }
                    form.setAttribute('data-enviando', '0');
                })
                .catch(function () {
                    form.setAttribute('data-enviando', '0');
                });
            };

            var atraso = limite.getTime() - Date.now();
            if (atraso <= 0) {
                tentarFinalizar();
            } else {
                setTimeout(tentarFinalizar, Math.min(atraso + 500, 2147483647));
            }
            setInterval(tentarFinalizar, 60000);
        })();
        </script>
    <?php endif; ?>

    <?php
    render_footer();
    exit;
}

$inicio = cftv_valid_date(trim((string)($_GET['inicio'] ?? date('Y-m-01'))));
$fim = cftv_valid_date(trim((string)($_GET['fim'] ?? date('Y-m-d'))));
$statusFiltro = strtoupper(trim((string)($_GET['status'] ?? '')));
$operadorFiltro = (int)($_GET['operador_id'] ?? 0);

$where = ['r.data_plantao >= ?', 'r.data_plantao <= ?'];
$params = [$inicio, $fim];

if ($user['perfil'] === 'CFTV') {
    $where[] = 'r.operador_id = ?';
    $params[] = $user['id'];
} elseif ($operadorFiltro > 0) {
    $where[] = 'r.operador_id = ?';
    $params[] = $operadorFiltro;
}

if (in_array($statusFiltro, ['EM_ANDAMENTO', 'FINALIZADO'], true)) {
    $where[] = 'r.status = ?';
    $params[] = $statusFiltro;
}

if ($filtroDestaque === 'cameras_off') {
    $where[] = 'r.cameras_offline > 0';
} elseif ($filtroDestaque === 'ocorrencias') {
    $where[] = 'EXISTS (SELECT 1 FROM cftv_ocorrencias ofx WHERE ofx.relatorio_id = r.id)';
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$st = $pdo->prepare("
    SELECT
        r.*,
        u.nome AS operador_nome,
        COALESCE(oc.ocorrencias_total, 0) AS ocorrencias_total,
        COALESCE(oc.ocorrencias_criticas, 0) AS ocorrencias_criticas
    FROM cftv_relatorios r
    JOIN usuarios u ON u.id = r.operador_id
    LEFT JOIN (
        SELECT
            relatorio_id,
            COUNT(*) AS ocorrencias_total,
            SUM(nivel = 'NIVEL_3') AS ocorrencias_criticas
        FROM cftv_ocorrencias
        GROUP BY relatorio_id
    ) oc ON oc.relatorio_id = r.id
    $whereSql
    ORDER BY r.data_plantao DESC, r.criado_em DESC
    LIMIT 200
");
$st->execute($params);
$relatorios = $st->fetchAll();

$operadores = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'CFTV' ORDER BY nome")->fetchAll();

$st = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(r.status = 'EM_ANDAMENTO') AS em_andamento,
        SUM(r.status = 'FINALIZADO') AS finalizados,
        SUM(r.cameras_offline) AS cameras_offline
    FROM cftv_relatorios r
    $whereSql
");
$st->execute($params);
$listaKpis = $st->fetch() ?: [];

$filtroLabel = [
    'cameras_off' => 'Câmeras OFF',
    'ocorrencias' => 'Ocorrências',
][$filtroDestaque] ?? '';

render_header($filtroLabel !== '' ? 'CFTV - ' . $filtroLabel : 'CFTV');
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <h1 class="h3 mb-0">CFTV</h1>
            <?php if ($filtroLabel !== ''): ?><span class="badge text-bg-primary"><?= e($filtroLabel) ?></span><?php endif; ?>
        </div>
        <small class="text-muted">Relatórios de plantão, ocorrências e auditoria operacional.</small>
        <?php if ($filtroLabel !== ''): ?>
            <div class="mt-1"><a class="small" href="<?= e(app_path('cftv')) ?>">Limpar filtro especializado</a></div>
        <?php endif; ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php if (cftv_can_manage_liberacoes($user) || $user['perfil'] === 'CFTV'): ?>
<a class="btn btn-outline-primary" href="<?= e(app_url('app_controle_acesso', ['destino' => 'liberacoes'])) ?>">Liberacoes</a>
        <?php endif; ?>
        <?php if ($user['perfil'] === 'TI'): ?>
            <a class="btn btn-outline-dark" href="<?= e(app_path('cftv', ['view' => 'geral'])) ?>">Relatorio geral</a>
            <a class="btn btn-outline-secondary" href="<?= e(app_path('usuarios', ['perfil' => 'CFTV'])) ?>">Cadastrar operador</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($mensagemSucesso): ?><div class="alert alert-success"><?= e($mensagemSucesso) ?></div><?php endif; ?>
<?php if ($mensagemErro): ?><div class="alert alert-danger"><?= e($mensagemErro) ?></div><?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card p-3 h-100">
            <h2 class="h5"><?= $user['perfil'] === 'TI' ? 'Novo relatorio' : 'Meu plantao' ?></h2>
            <form method="post" class="row g-2">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="action" value="criar_relatorio">
                <div class="col-md-6">
                    <label class="form-label">Data</label>
                    <input class="form-control" type="date" name="data_plantao" value="<?= e(date('Y-m-d')) ?>" <?= $user['perfil'] === 'CFTV' ? 'min="' . e(date('Y-m-d')) . '" max="' . e(date('Y-m-d')) . '"' : '' ?> required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Turno</label>
                    <select class="form-select" name="turno">
                        <?php foreach (cftv_turnos() as $turno): ?>
                            <option value="<?= e($turno) ?>"><?= e($turno) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Inicio</label>
                    <input class="form-control" type="time" name="inicio_turno" value="<?= e(date('H:i')) ?>">
                </div>
                <?php if ($user['perfil'] === 'TI'): ?>
                    <div class="col-md-6">
                        <label class="form-label">Operador</label>
                        <select class="form-select" name="operador_id" required>
                            <option value="">Selecione</option>
                            <?php foreach ($operadores as $op): ?>
                                <option value="<?= e($op['id']) ?>"><?= e($op['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-12">
                    <button class="btn btn-primary w-100">Abrir relatorio</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-sm-3"><div class="card p-3 h-100"><div class="metric"><?= (int)($listaKpis['total'] ?? 0) ?></div><div class="metric-label">Relatorios</div></div></div>
            <div class="col-sm-3"><div class="card p-3 h-100"><div class="metric"><?= (int)($listaKpis['em_andamento'] ?? 0) ?></div><div class="metric-label">Em andamento</div></div></div>
            <div class="col-sm-3"><div class="card p-3 h-100"><div class="metric"><?= (int)($listaKpis['finalizados'] ?? 0) ?></div><div class="metric-label">Finalizados</div></div></div>
            <div class="col-sm-3"><div class="card p-3 h-100"><div class="metric"><?= (int)($listaKpis['cameras_offline'] ?? 0) ?></div><div class="metric-label">Cameras offline</div></div></div>
        </div>
    </div>
</div>

<div class="card p-3 mb-3">
    <form class="row g-2">
        <?php if ($filtroDestaque !== ''): ?><input type="hidden" name="filtro" value="<?= e($filtroDestaque) ?>"><?php endif; ?>
        <div class="col-md-2">
            <label class="form-label">Inicio</label>
            <input class="form-control" type="date" name="inicio" value="<?= e($inicio) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Fim</label>
            <input class="form-control" type="date" name="fim" value="<?= e($fim) ?>">
        </div>
        <?php if ($user['perfil'] === 'TI'): ?>
            <div class="col-md-3">
                <label class="form-label">Operador</label>
                <select class="form-select" name="operador_id">
                    <option value="">Todos</option>
                    <?php foreach ($operadores as $op): ?>
                        <option value="<?= e($op['id']) ?>" <?= $operadorFiltro === (int)$op['id'] ? 'selected' : '' ?>><?= e($op['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
                <option value="">Todos</option>
                <option value="EM_ANDAMENTO" <?= $statusFiltro === 'EM_ANDAMENTO' ? 'selected' : '' ?>>EM ANDAMENTO</option>
                <option value="FINALIZADO" <?= $statusFiltro === 'FINALIZADO' ? 'selected' : '' ?>>FINALIZADO</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button class="btn btn-dark w-100">Filtrar</button>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Turno</th>
                    <th>Operador</th>
                    <th>Status</th>
                    <th>Ocorrencias</th>
                    <th>Criticas</th>
                    <th>Cameras offline</th>
                    <th>Fluxo clientes</th>
                    <th>Atualizado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($relatorios as $rel): ?>
                    <tr>
                        <td><?= e($rel['data_plantao']) ?></td>
                        <td><?= e($rel['turno']) ?></td>
                        <td><?= e($rel['operador_nome']) ?></td>
                        <td><?= cftv_badge_status($rel['status']) ?></td>
                        <td><?= (int)$rel['ocorrencias_total'] ?></td>
                        <td><?= (int)$rel['ocorrencias_criticas'] ?></td>
                        <td><?= (int)$rel['cameras_offline'] ?></td>
                        <td><?= ($rel['turno'] ?? '') === 'NOTURNO' ? (int)($rel['fluxo_clientes'] ?? 0) : '-' ?></td>
                        <td><?= e($rel['atualizado_em'] ?: $rel['criado_em']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(app_path('cftv', ['id' => $rel['id']])) ?>">Abrir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$relatorios): ?>
                    <tr><td colspan="10" class="text-center text-muted">Nenhum relatorio encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php render_footer(); ?>
