<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/operacional.php';

function ensure_cftv_schema(PDO $pdo): void {
    ensure_cftv_user_profile($pdo);

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_relatorios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data_plantao DATE NOT NULL,
            turno VARCHAR(30) NOT NULL DEFAULT 'DIURNO',
            operador_id INT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'EM_ANDAMENTO',
            inicio_turno TIME NULL,
            fim_turno TIME NULL,
            passagem_servico TEXT NULL,
            cameras_funcionando TINYINT(1) NOT NULL DEFAULT 0,
            nvrs_gravando TINYINT(1) NOT NULL DEFAULT 0,
            horario_sincronizado TINYINT(1) NOT NULL DEFAULT 0,
            radios_ok TINYINT(1) NOT NULL DEFAULT 0,
            falhas_livro TEXT NULL,
            cameras_offline INT NOT NULL DEFAULT 0,
            rondas_virtuais INT NOT NULL DEFAULT 0,
            eventos_preventivos INT NOT NULL DEFAULT 0,
            fluxo_clientes INT NOT NULL DEFAULT 0,
            tempo_medio_resposta_min INT NULL,
            areas_prioritarias TEXT NULL,
            situacoes_suspeitas TEXT NULL,
            equipes_observadas TEXT NULL,
            ocorrencias_resumo TEXT NULL,
            observacoes_gerais TEXT NULL,
            plano_melhoria TEXT NULL,
            finalizado_em DATETIME NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL,
            UNIQUE KEY uq_cftv_relatorio_periodo (data_plantao, operador_id, turno),
            INDEX idx_data_plantao (data_plantao),
            INDEX idx_operador (operador_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    if (!app_column_exists($pdo, 'cftv_relatorios', 'fluxo_clientes')) {
        app_exec_safe($pdo, "ALTER TABLE cftv_relatorios ADD COLUMN fluxo_clientes INT NOT NULL DEFAULT 0 AFTER eventos_preventivos");
    }

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_ocorrencias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            relatorio_id INT NOT NULL,
            horario DATETIME NULL,
            nivel VARCHAR(20) NOT NULL DEFAULT 'NIVEL_1',
            tipo VARCHAR(100) NULL,
            local VARCHAR(160) NULL,
            descricao TEXT NULL,
            envolvidos TEXT NULL,
            imagens TEXT NULL,
            tempo_resposta_min INT NULL,
            equipes_acionadas TEXT NULL,
            encerramento TEXT NULL,
            preservar_imagens TINYINT(1) NOT NULL DEFAULT 0,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_relatorio (relatorio_id),
            INDEX idx_nivel (nivel),
            INDEX idx_horario (horario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_observacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            relatorio_id INT NOT NULL,
            equipe VARCHAR(100) NOT NULL,
            horario DATETIME NULL,
            local VARCHAR(160) NULL,
            item VARCHAR(120) NULL,
            classificacao VARCHAR(40) NOT NULL DEFAULT 'INFORMATIVA',
            descricao TEXT NULL,
            tempo_resposta_min INT NULL,
            providencia TEXT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_relatorio (relatorio_id),
            INDEX idx_equipe (equipe),
            INDEX idx_classificacao (classificacao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_tokens_edicao (
            id INT AUTO_INCREMENT PRIMARY KEY,
            relatorio_id INT NOT NULL,
            token_hash VARCHAR(255) NOT NULL,
            gerado_por INT NOT NULL,
            usado_por INT NULL,
            usado_em DATETIME NULL,
            expira_em DATETIME NOT NULL,
            motivo TEXT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_relatorio (relatorio_id),
            INDEX idx_expira (expira_em),
            INDEX idx_usado (usado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_liberacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            data_acesso DATE NOT NULL,
            data_fim DATE NULL,
            hora_inicio TIME NULL,
            hora_fim TIME NULL,
            nome_completo VARCHAR(180) NOT NULL,
            cpf VARCHAR(20) NOT NULL,
            email VARCHAR(190) NULL,
            destino_tipo VARCHAR(20) NOT NULL DEFAULT 'LOJISTA',
            destino_nome VARCHAR(180) NOT NULL,
            motivo TEXT NULL,
            observacoes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'ATIVA',
            criado_por INT NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_por INT NULL,
            atualizado_em DATETIME NULL,
            cancelado_por INT NULL,
            cancelado_em DATETIME NULL,
            INDEX idx_data_acesso (data_acesso),
            INDEX idx_cpf (cpf),
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_criado_por (criado_por)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_liberacao_confirmacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            relatorio_id INT NOT NULL,
            liberacao_id INT NOT NULL,
            status_confirmacao VARCHAR(40) NOT NULL DEFAULT 'PENDENTE',
            horario_confirmacao DATETIME NULL,
            alteracao_observada TEXT NULL,
            confirmado_por INT NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NULL,
            UNIQUE KEY uq_cftv_confirmacao_relatorio (relatorio_id, liberacao_id),
            INDEX idx_relatorio (relatorio_id),
            INDEX idx_liberacao (liberacao_id),
            INDEX idx_status_confirmacao (status_confirmacao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_liberacao_links (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            token_hash CHAR(64) NOT NULL,
            titulo VARCHAR(160) NOT NULL DEFAULT 'Solicitação de acesso',
            destino_padrao VARCHAR(180) NULL,
            expira_em DATETIME NOT NULL,
            limite_usos INT NOT NULL DEFAULT 1,
            usos INT NOT NULL DEFAULT 0,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_por INT NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_liberacao_link_token(token_hash),
            KEY idx_liberacao_link_validade(ativo,expira_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_solicitacoes_acesso (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            protocolo VARCHAR(30) NOT NULL,
            link_id BIGINT UNSIGNED NOT NULL,
            acompanhamento_hash CHAR(64) NOT NULL,
            nome_completo VARCHAR(180) NOT NULL,
            cpf VARCHAR(20) NOT NULL,
            telefone VARCHAR(30) NULL,
            email VARCHAR(190) NULL,
            empresa VARCHAR(180) NULL,
            data_acesso DATE NOT NULL,
            data_fim DATE NULL,
            hora_inicio TIME NULL,
            hora_fim TIME NULL,
            destino_tipo VARCHAR(20) NOT NULL DEFAULT 'LOJISTA',
            destino_nome VARCHAR(180) NOT NULL,
            responsavel_interno VARCHAR(180) NULL,
            motivo TEXT NULL,
            veiculo_placa VARCHAR(20) NULL,
            observacoes TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'PENDENTE',
            decisao_justificativa TEXT NULL,
            decidido_por INT NULL,
            decidido_em DATETIME NULL,
            liberacao_id INT NULL,
            ip_hash CHAR(64) NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_solicitacao_protocolo(protocolo),
            UNIQUE KEY uq_acompanhamento_hash(acompanhamento_hash),
            KEY idx_solicitacao_status(status,criado_em),
            KEY idx_solicitacao_email(email),
            KEY idx_solicitacao_link(link_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_destinos_acesso (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(30) NOT NULL,
            nome VARCHAR(180) NOT NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            criado_por INT NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_destino_tipo_nome(tipo,nome),
            KEY idx_destino_ativo(tipo,ativo,nome)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_solicitacao_pessoas (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            solicitacao_id BIGINT UNSIGNED NOT NULL,
            nome_completo VARCHAR(180) NOT NULL,
            cpf VARCHAR(20) NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_pessoa_solicitacao(solicitacao_id),
            KEY idx_pessoa_cpf(cpf)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_acesso_config (
            chave VARCHAR(80) PRIMARY KEY, valor VARCHAR(255) NOT NULL,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    app_exec_safe($pdo, "INSERT IGNORE INTO cftv_acesso_config(chave,valor) VALUES('qr_secret',SHA2(CONCAT(UUID(),RAND(),NOW()),256))");
    app_exec_safe($pdo, "
        CREATE TABLE IF NOT EXISTS cftv_acesso_eventos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            solicitacao_id BIGINT UNSIGNED NOT NULL,
            tipo VARCHAR(20) NOT NULL,
            registrado_por INT NOT NULL,
            registrado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            observacao VARCHAR(500) NULL,
            KEY idx_evento_solicitacao(solicitacao_id,registrado_em),
            KEY idx_evento_dia(registrado_em,tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Compatibilidade com instalações anteriores: novos pedidos exigem e-mail,
    // mas as colunas permanecem NULL para não invalidar registros históricos.
    if (!app_column_exists($pdo, 'cftv_solicitacoes_acesso', 'email')) {
        app_exec_safe($pdo, "ALTER TABLE cftv_solicitacoes_acesso ADD COLUMN email VARCHAR(190) NULL AFTER telefone");
    }
    if (!app_column_exists($pdo, 'cftv_solicitacoes_acesso', 'data_fim')) {
        app_exec_safe($pdo, "ALTER TABLE cftv_solicitacoes_acesso ADD COLUMN data_fim DATE NULL AFTER data_acesso");
    }
    if (!app_column_exists($pdo, 'cftv_liberacoes', 'email')) {
        app_exec_safe($pdo, "ALTER TABLE cftv_liberacoes ADD COLUMN email VARCHAR(190) NULL AFTER cpf");
    }
    if (!app_column_exists($pdo, 'cftv_liberacoes', 'data_fim')) {
        app_exec_safe($pdo, "ALTER TABLE cftv_liberacoes ADD COLUMN data_fim DATE NULL AFTER data_acesso");
    }
}

function cftv_email_remetente(): string {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $host = preg_replace('/:\d+$/', '', $host);
    if (!preg_match('/^[a-z0-9.-]+$/', $host)) $host = 'localhost';
    return 'no-reply@' . $host;
}

function cftv_enviar_email_solicitante(string $email, string $nome, string $assunto, string $mensagem): bool {
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $nome = trim($nome) !== '' ? trim($nome) : 'Solicitante';
    $corpo = "Olá, {$nome}.\n\n" . trim($mensagem) . "\n\nGEOP Shopping Carpina\n";
    $headers = 'From: ' . cftv_email_remetente() . "\r\n";
    $headers .= 'Reply-To: ' . cftv_email_remetente() . "\r\n";
    $headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
    $ok = @mail($email, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpo, $headers);
    if (!$ok) error_log('[GEOP CFTV e-mail] Não foi possível enviar para ' . $email);
    return $ok;
}

function cftv_email_status_solicitacao(array $solicitacao, string $status): bool {
    $email = (string)($solicitacao['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;
    $protocolo = (string)($solicitacao['protocolo'] ?? '');
    $consulta = app_url('solicitar_acesso', ['consultar' => 1]);
    $status = strtoupper($status);
    if ($status === 'APROVADA') {
        $assunto = 'Solicitação de acesso aprovada — ' . $protocolo;
        $texto = "Sua solicitação de acesso foi aprovada.\n";
    } elseif ($status === 'REPROVADA') {
        $assunto = 'Solicitação de acesso não aprovada — ' . $protocolo;
        $texto = "Sua solicitação de acesso foi reprovada.\n";
    } else {
        $assunto = 'Solicitação de acesso recebida — ' . $protocolo;
        $texto = "Sua solicitação de acesso foi recebida e aguarda análise.\n";
    }
    if (!empty($solicitacao['decisao_justificativa'])) {
        $texto .= 'Observação: ' . trim((string)$solicitacao['decisao_justificativa']) . "\n";
    }
    if (!empty($solicitacao['data_acesso'])) {
        $dataInicio=date('d/m/Y',strtotime((string)$solicitacao['data_acesso']));
        $dataFim=!empty($solicitacao['data_fim'])?date('d/m/Y',strtotime((string)$solicitacao['data_fim'])):$dataInicio;
        $faixa=$dataFim!==$dataInicio?$dataInicio.' a '.$dataFim:$dataInicio;
        $hi=substr((string)($solicitacao['hora_inicio']??''),0,5);
        $hf=substr((string)($solicitacao['hora_fim']??''),0,5);
        $texto .= 'Período autorizado: '.$faixa.($hi!==''&&$hf!==''?' · '.$hi.' às '.$hf:'')."\n";
    }
    $texto .= "Protocolo: {$protocolo}\nConsulte a situação em: {$consulta}";
    return cftv_enviar_email_solicitante($email, (string)($solicitacao['nome_completo'] ?? ''), $assunto, $texto);
}


function ensure_cftv_user_profile(PDO $pdo): void {
    try {
        $st = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'perfil'");
        $column = $st ? $st->fetch() : null;
        $type = (string)($column['Type'] ?? '');

        if (stripos($type, 'enum(') === 0) {
            preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
            $values = array_map('stripcslashes', $matches[1] ?? []);
            if (!$values) {
                $values = ['TI', 'GESTOR', 'AGENTE'];
            }
            $missing = array_diff(array_keys(app_user_profiles()), $values);
            if (!$missing) {
                return;
            }

            $values = array_merge($values, array_keys(app_user_profiles()));
            $values = array_values(array_unique($values));
            $enumSql = implode(',', array_map([$pdo, 'quote'], $values));
            $nullSql = (($column['Null'] ?? '') === 'YES') ? 'NULL' : 'NOT NULL';
            $default = (string)($column['Default'] ?? 'AGENTE');
            $defaultSql = in_array($default, $values, true) ? ' DEFAULT ' . $pdo->quote($default) : '';

            $pdo->exec("ALTER TABLE usuarios MODIFY perfil ENUM($enumSql) $nullSql$defaultSql");
        }
    } catch (Throwable $e) {
        // O cadastro continua exibindo o perfil; se o banco negar ALTER, o erro aparecera ao salvar.
    }
}

function cftv_turnos(): array {
    return ['DIURNO', 'NOTURNO'];
}

function cftv_liberacao_perfis(): array {
    return ['TI', 'GESTOR', 'GESTOR_SINDICO', 'DIRETOR_EXECUTIVO', 'COORDENADOR_OPERACOES_OBRAS'];
}

function cftv_can_manage_liberacoes(array $user): bool {
    // O perfil TI possui acesso administrativo completo ao módulo, independentemente
    // da matriz de perfis configurada para os demais aprovadores.
    if (($user['perfil'] ?? '') === 'TI') return true;
    return in_array($user['perfil'] ?? '', cftv_liberacao_perfis(), true);
}

function cftv_destinos_liberacao(): array {
    return [
        'LOJA_MALL' => 'Loja do Mall',
        'ADMINISTRACAO' => 'Administração',
        'PRACA_ALIMENTACAO' => 'Loja da Praça de Alimentação',
        'AREA_SERVICO' => 'Área de Serviço',
        'LOJA' => 'Loja (cadastro anterior)',
        'LOJISTA' => 'Lojista (cadastro antigo)',
        'EMPRESA' => 'Empresa/prestador',
        'SETOR' => 'Setor',
    ];
}

function cftv_status_confirmacao_liberacao(): array {
    return [
        'CONFIRMADA' => 'Confirmada conforme liberacao',
        'CONFIRMADA_COM_ALTERACAO' => 'Confirmada com alteracao',
        'NAO_COMPARECEU' => 'Nao compareceu',
        'DIVERGENTE' => 'Divergente / nao liberada',
    ];
}

function cftv_qr_secret(PDO $pdo): string {
    try {
        $st=$pdo->query("SELECT valor FROM cftv_acesso_config WHERE chave='qr_secret' LIMIT 1");
        $secret=$st ? trim((string)$st->fetchColumn()) : '';
        if($secret!=='') return $secret;

        $secret=bin2hex(random_bytes(32));
        $st=$pdo->prepare("INSERT INTO cftv_acesso_config(chave,valor) VALUES('qr_secret',?)
            ON DUPLICATE KEY UPDATE valor=IF(valor='',VALUES(valor),valor)");
        $st->execute([$secret]);
        $st=$pdo->query("SELECT valor FROM cftv_acesso_config WHERE chave='qr_secret' LIMIT 1");
        $persisted=$st ? trim((string)$st->fetchColumn()) : '';
        if($persisted!=='') return $persisted;
    } catch(Throwable $e) {
        error_log('[GEOP] QR config fallback: '.$e->getMessage());
    }

    // Mantém o QR funcional e estável mesmo antes da migration da tabela de configuração.
    return hash('sha256','GEOP-QR|'.public_os_secret());
}

function cftv_qr_codigo(PDO $pdo, int $solicitacaoId): string {
    $secret=cftv_qr_secret($pdo);
    $payload=(string)$solicitacaoId;
    return $payload.'.'.hash_hmac('sha256',$payload,$secret);
}

function cftv_link_publico_token(PDO $pdo): string {
    return rtrim(strtr(base64_encode(hash_hmac('sha256','solicitacao-acesso-publica-v1',public_os_secret(),true)),'+/','-_'),'=');
}

function cftv_voucher_utilizado(PDO $pdo, int $solicitacaoId): bool {
    $st=$pdo->prepare("SELECT data_acesso,COALESCE(data_fim,data_acesso) AS data_fim FROM cftv_solicitacoes_acesso WHERE id=? LIMIT 1");
    $st->execute([$solicitacaoId]);
    $periodo=$st->fetch(PDO::FETCH_ASSOC);
    if(!$periodo) return false;

    $inicio=(string)$periodo['data_acesso'];
    $fim=(string)$periodo['data_fim'];
    $ehPeriodo=$fim>$inicio;
    if(!$ehPeriodo){
        $st=$pdo->prepare("SELECT 1 FROM cftv_acesso_eventos WHERE solicitacao_id=? AND tipo='ENTRADA' LIMIT 1");
        $st->execute([$solicitacaoId]);
        return (bool)$st->fetchColumn();
    }

    $hoje=date('Y-m-d');
    if($hoje>$fim) return true;
    if($hoje<$inicio) return false;
    $st=$pdo->prepare("SELECT 1 FROM cftv_acesso_eventos WHERE solicitacao_id=? AND tipo='ENTRADA' AND DATE(registrado_em)=? LIMIT 1");
    $st->execute([$solicitacaoId,$hoje]);
    return (bool)$st->fetchColumn();
}

function cftv_qr_validar(PDO $pdo, string $codigo): int {
    if(!preg_match('/^([1-9][0-9]*)\.([a-f0-9]{64})$/',$codigo,$m)) return 0;
    $id=(int)$m[1];
    return hash_equals(cftv_qr_codigo($pdo,$id),$codigo)?$id:0;
}

function cftv_niveis_ocorrencia(): array {
    return [
        'NIVEL_1' => 'Nivel 1 - Informativa',
        'NIVEL_2' => 'Nivel 2 - Atencao',
        'NIVEL_3' => 'Nivel 3 - Critica',
    ];
}

function cftv_equipes_monitoradas(): array {
    return [
        'Fiscal de Mall',
        'Seguranca terceirizada',
        'Limpeza',
        'Manutencao',
        'Brigada/Bombeiro civil',
        'Estacionamento',
        'Prestadores de servico',
        'Lojistas em areas comuns',
    ];
}

function cftv_itens_observacao(): array {
    return [
        'Postura operacional',
        'Ronda / roteiro',
        'Uso de celular',
        'Abandono de posto',
        'Tempo parado',
        'Uniforme / EPI',
        'Sinalizacao de area',
        'Controle de acesso',
        'Tempo de resposta',
        'Apoio em ocorrencia',
        'Procedimento de seguranca',
    ];
}

function cftv_classificacoes_observacao(): array {
    return ['CONFORME', 'INFORMATIVA', 'NAO_CONFORMIDADE', 'CRITICA'];
}

function cftv_areas_prioritarias(): array {
    return [
        'Entradas e saidas',
        'Praca de alimentacao',
        'Estacionamento',
        'Docas',
        'Casa de maquinas',
        'Corredores tecnicos',
        'Administracao',
        'Caixa eletronico',
        'Escadas de emergencia',
        'Acessos de funcionarios',
        'Central de residuos',
        'Areas externas',
    ];
}

function cftv_default_areas_text(): string {
    return implode("\n", cftv_areas_prioritarias());
}

function cftv_badge_status($status): string {
    $status = (string)$status;
    $class = $status === 'FINALIZADO' ? 'dark' : 'success';
    return '<span class="badge text-bg-' . $class . '">' . e(str_replace('_', ' ', $status)) . '</span>';
}

function cftv_badge_nivel($nivel): string {
    $nivel = (string)$nivel;
    $map = [
        'NIVEL_1' => 'info',
        'NIVEL_2' => 'warning',
        'NIVEL_3' => 'danger',
    ];
    return '<span class="badge text-bg-' . ($map[$nivel] ?? 'secondary') . '">' . e(cftv_niveis_ocorrencia()[$nivel] ?? $nivel) . '</span>';
}

function cftv_badge_classificacao($classificacao): string {
    $classificacao = (string)$classificacao;
    $map = [
        'CONFORME' => 'success',
        'INFORMATIVA' => 'info',
        'NAO_CONFORMIDADE' => 'warning',
        'CRITICA' => 'danger',
    ];
    return '<span class="badge text-bg-' . ($map[$classificacao] ?? 'secondary') . '">' . e(str_replace('_', ' ', $classificacao)) . '</span>';
}

function cftv_badge_liberacao_status($status): string {
    $status = (string)$status;
    $map = [
        'ATIVA' => 'success',
        'CANCELADA' => 'secondary',
    ];
    return '<span class="badge text-bg-' . ($map[$status] ?? 'secondary') . '">' . e(str_replace('_', ' ', $status)) . '</span>';
}

function cftv_badge_confirmacao_liberacao($status): string {
    $status = (string)$status;
    $map = [
        'PENDENTE' => 'secondary',
        'CONFIRMADA' => 'success',
        'CONFIRMADA_COM_ALTERACAO' => 'warning',
        'NAO_COMPARECEU' => 'dark',
        'DIVERGENTE' => 'danger',
    ];
    $label = cftv_status_confirmacao_liberacao()[$status] ?? str_replace('_', ' ', $status);
    return '<span class="badge text-bg-' . ($map[$status] ?? 'secondary') . '">' . e($label) . '</span>';
}

function cftv_datetime_to_input($value): string {
    $value = trim((string)$value);
    if ($value === '') return '';
    $ts = strtotime($value);
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}

function cftv_input_datetime($value): ?string {
    $value = trim((string)$value);
    if ($value === '') return null;
    return str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
}

function cftv_device_datetime($value): ?DateTimeImmutable {
    $value = trim((string)$value);
    if ($value === '') return null;
    $value = str_replace('T', ' ', $value);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
        $value .= ':00';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
    return $date && $date->format('Y-m-d H:i:s') === $value ? $date : null;
}

function cftv_diurno_auto_close_deadline(array $relatorio): ?DateTimeImmutable {
    if (($relatorio['turno'] ?? '') !== 'DIURNO') return null;

    $dataPlantao = (string)($relatorio['data_plantao'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataPlantao)) {
        return null;
    }

    $deadline = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $dataPlantao . ' 17:10:00');
    return $deadline ?: null;
}

function cftv_can_auto_close_diurno(array $relatorio, $deviceDateTime): bool {
    if (($relatorio['status'] ?? '') === 'FINALIZADO') return false;

    $deviceDate = cftv_device_datetime($deviceDateTime);
    $deadline = cftv_diurno_auto_close_deadline($relatorio);

    return $deviceDate !== null && $deadline !== null && $deviceDate >= $deadline;
}

function cftv_normalizar_cpf($cpf): string {
    return substr(preg_replace('/\D+/', '', (string)$cpf), 0, 11);
}

function cftv_formatar_cpf($cpf): string {
    $cpf = cftv_normalizar_cpf($cpf);
    if (strlen($cpf) !== 11) {
        return $cpf;
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function cftv_normalizar_token($token): string {
    return preg_replace('/[^0-9A-Za-z]/', '', strtoupper((string)$token));
}

function cftv_report_unlocked(int $relatorioId): bool {
    $unlocks = $_SESSION['cftv_report_unlocks'] ?? [];
    $expires = (int)($unlocks[$relatorioId] ?? 0);
    return $expires >= time();
}

function cftv_mark_report_unlocked(int $relatorioId, int $seconds = 86400): void {
    if (!isset($_SESSION['cftv_report_unlocks']) || !is_array($_SESSION['cftv_report_unlocks'])) {
        $_SESSION['cftv_report_unlocks'] = [];
    }
    $_SESSION['cftv_report_unlocks'][$relatorioId] = time() + $seconds;
}

function cftv_can_view_report(array $relatorio, array $user): bool {
    if (($user['perfil'] ?? '') === 'TI') return true;
    if (($user['perfil'] ?? '') !== 'CFTV') return false;
    return (int)($relatorio['operador_id'] ?? 0) === (int)($user['id'] ?? 0);
}

function cftv_can_edit_report(array $relatorio, array $user): bool {
    if (($user['perfil'] ?? '') === 'TI') return true;
    if (!cftv_can_view_report($relatorio, $user)) return false;
    if (cftv_report_unlocked((int)$relatorio['id'])) return true;
    if (($relatorio['status'] ?? '') === 'FINALIZADO') return false;
    return (string)($relatorio['data_plantao'] ?? '') === date('Y-m-d');
}
