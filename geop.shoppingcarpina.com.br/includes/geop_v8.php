<?php
declare(strict_types=1);

/**
 * GEOP V8.0 — Núcleo de gestão, segurança, qualidade, custos e atualizações.
 *
 * Este módulo foi desenhado para coexistir com o GEOP atual e reaproveitar:
 * - notificacoes / gestao_integrada;
 * - aprovacoes;
 * - estoque_materiais / movimentacoes_estoque;
 * - ordens_servico / materiais_os / anexos_os;
 * - ativos / ativo_falhas;
 * - auditoria_eventos.
 */

const GEOP_V8_VERSION = '8.0.1';

function geop_v8_table_exists(PDO $pdo, string $table): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) return false;
    try {
        $st = $pdo->prepare('SHOW TABLES LIKE ?');
        $st->execute([$table]);
        if ($st->fetchColumn()) return true;
    } catch (Throwable $e) {}
    try {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function geop_v8_column_exists(PDO $pdo, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) return false;
    try {
        $st = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
        $st->execute([$column]);
        if ($st->fetch(PDO::FETCH_ASSOC)) return true;
    } catch (Throwable $e) {}
    try {
        $pdo->query("SELECT `{$column}` FROM `{$table}` LIMIT 0");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function geop_v8_index_exists(PDO $pdo, string $table, string $index): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $index)) return false;
    try {
        $st = $pdo->query("SHOW INDEX FROM `{$table}`");
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (($row['Key_name'] ?? '') === $index) return true;
        }
    } catch (Throwable $e) {}
    return false;
}

function geop_v8_add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (geop_v8_column_exists($pdo, $table, $column)) return;
    try {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    } catch (PDOException $e) {
        $driverCode = (int)($e->errorInfo[1] ?? 0);
        if ($driverCode === 1060 || stripos($e->getMessage(), 'Duplicate column') !== false) return;
        throw $e;
    }
}

function geop_v8_add_index(PDO $pdo, string $table, string $index, string $columns, bool $unique = false): void
{
    if (geop_v8_index_exists($pdo, $table, $index)) return;
    try {
        $pdo->exec('CREATE ' . ($unique ? 'UNIQUE ' : '') . "INDEX `{$index}` ON `{$table}` ({$columns})");
    } catch (PDOException $e) {
        $driverCode = (int)($e->errorInfo[1] ?? 0);
        if ($driverCode === 1061 || stripos($e->getMessage(), 'Duplicate key name') !== false) return;
        throw $e;
    }
}

function geop_v8_core_schema(PDO $pdo): void
{
    /*
     * Compatibilidade com o GEOP legado:
     * versões anteriores já possuem schema_migrations(version, applied_at),
     * com PRIMARY KEY em version. Por isso a V8 não força a troca de nomes.
     *
     * Em instalações novas usamos os mesmos nomes do legado e acrescentamos
     * metadados opcionais da V8.
     */
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(64) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        descricao VARCHAR(190) NULL,
        checksum CHAR(64) NULL,
        aplicada_por INT NULL,
        duracao_ms INT UNSIGNED NULL,
        PRIMARY KEY(version)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Enriquece tanto a tabela legada (version/applied_at) quanto a tabela
    // criada pela primeira V8 (versao/aplicada_em), sem renomear nem apagar.
    if (geop_v8_table_exists($pdo, 'schema_migrations')) {
        geop_v8_add_column($pdo, 'schema_migrations', 'descricao', 'VARCHAR(190) NULL');
        geop_v8_add_column($pdo, 'schema_migrations', 'checksum', 'CHAR(64) NULL');
        geop_v8_add_column($pdo, 'schema_migrations', 'aplicada_por', 'INT NULL');
        geop_v8_add_column($pdo, 'schema_migrations', 'duracao_ms', 'INT UNSIGNED NULL');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS sistema_configuracoes (
        chave VARCHAR(120) NOT NULL,
        valor LONGTEXT NULL,
        tipo VARCHAR(30) NOT NULL DEFAULT 'STRING',
        descricao VARCHAR(255) NULL,
        atualizado_por INT NULL,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(chave)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sistema_backups (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        tipo VARCHAR(30) NOT NULL DEFAULT 'BANCO',
        arquivo VARCHAR(255) NOT NULL,
        tamanho_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
        sha256 CHAR(64) NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'CONCLUIDO',
        observacao TEXT NULL,
        criado_por INT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_sistema_backups_data(criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sistema_execucoes (
        chave VARCHAR(120) NOT NULL,
        ultima_execucao DATETIME NULL,
        ultimo_status VARCHAR(30) NULL,
        detalhe VARCHAR(500) NULL,
        PRIMARY KEY(chave)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function geop_v8_security_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS seguranca_login_tentativas (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NULL,
        usuario_id INT NULL,
        ip VARCHAR(45) NULL,
        sucesso TINYINT(1) NOT NULL DEFAULT 0,
        motivo VARCHAR(100) NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_login_tentativa_email(email,criado_em),
        KEY idx_login_tentativa_ip(ip,criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS seguranca_eventos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id INT NULL,
        tipo VARCHAR(80) NOT NULL,
        detalhe TEXT NULL,
        ip VARCHAR(45) NULL,
        user_agent VARCHAR(500) NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_seg_evento_usuario(usuario_id,criado_em),
        KEY idx_seg_evento_tipo(tipo,criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    geop_v8_add_column($pdo, 'usuarios', 'totp_secret_enc', 'TEXT NULL');
    geop_v8_add_column($pdo, 'usuarios', 'totp_enabled', 'TINYINT(1) NOT NULL DEFAULT 0');
    geop_v8_add_column($pdo, 'usuarios', 'totp_confirmed_em', 'DATETIME NULL');
    geop_v8_add_column($pdo, 'usuarios', 'ultimo_login_em', 'DATETIME NULL');
    geop_v8_add_column($pdo, 'usuarios', 'ultimo_login_ip', 'VARCHAR(45) NULL');
}

function geop_v8_feature_schema(PDO $pdo): void
{
    geop_v8_security_schema($pdo);
    // Compras vinculadas às OS.
    $pdo->exec("CREATE TABLE IF NOT EXISTS compras_solicitacoes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ordem_servico_id INT NULL,
        solicitante_id INT NOT NULL,
        aprovador_id INT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'RASCUNHO',
        justificativa TEXT NULL,
        fornecedor VARCHAR(180) NULL,
        numero_documento VARCHAR(100) NULL,
        valor_previsto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        valor_final DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        solicitado_em DATETIME NULL,
        decidido_em DATETIME NULL,
        comprado_em DATETIME NULL,
        recebido_em DATETIME NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_compra_status(status,criado_em),
        KEY idx_compra_os(ordem_servico_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS compras_itens (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        compra_id BIGINT UNSIGNED NOT NULL,
        material_id INT NULL,
        descricao VARCHAR(190) NOT NULL,
        quantidade DECIMAL(12,2) NOT NULL DEFAULT 1.00,
        unidade VARCHAR(30) NULL,
        custo_unitario_previsto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        custo_unitario_final DECIMAL(12,2) NULL,
        PRIMARY KEY(id),
        KEY idx_compra_item_compra(compra_id),
        KEY idx_compra_item_material(material_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Custos adicionais/documentos.
    $pdo->exec("CREATE TABLE IF NOT EXISTS os_custos_documentos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ordem_servico_id INT NOT NULL,
        tipo VARCHAR(40) NOT NULL DEFAULT 'OUTRO',
        fornecedor VARCHAR(180) NULL,
        documento VARCHAR(100) NULL,
        descricao VARCHAR(255) NULL,
        valor DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        arquivo_url VARCHAR(255) NULL,
        registrado_por INT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_os_custos_os(ordem_servico_id,criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Checklist por categoria e snapshot por OS.
    $pdo->exec("CREATE TABLE IF NOT EXISTS checklist_modelos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        categoria_id INT NOT NULL,
        nome VARCHAR(160) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        criado_por INT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_checklist_modelo_categoria(categoria_id,ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS checklist_modelo_itens (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        modelo_id BIGINT UNSIGNED NOT NULL,
        descricao VARCHAR(255) NOT NULL,
        obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
        exige_foto TINYINT(1) NOT NULL DEFAULT 0,
        ordem SMALLINT NOT NULL DEFAULT 0,
        PRIMARY KEY(id),
        KEY idx_checklist_modelo_itens(modelo_id,ordem)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS os_checklist_itens (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ordem_servico_id INT NOT NULL,
        origem_item_id BIGINT UNSIGNED NULL,
        descricao VARCHAR(255) NOT NULL,
        obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
        exige_foto TINYINT(1) NOT NULL DEFAULT 0,
        concluido TINYINT(1) NOT NULL DEFAULT 0,
        observacao VARCHAR(500) NULL,
        concluido_por INT NULL,
        concluido_em DATETIME NULL,
        ordem SMALLINT NOT NULL DEFAULT 0,
        PRIMARY KEY(id),
        KEY idx_os_checklist_os(ordem_servico_id,ordem)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Compatibilidade com o fluxo de token de reabertura existente.
    $pdo->exec("CREATE TABLE IF NOT EXISTS tokens_reabertura_os (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ordem_servico_id INT NOT NULL,
        token_hash VARCHAR(255) NOT NULL,
        gerado_por INT NOT NULL,
        usado_por INT NULL,
        usado_em DATETIME NULL,
        expira_em DATETIME NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token_reabertura_os (ordem_servico_id),
        INDEX idx_token_reabertura_expira (expira_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS token_reabertura_solicitacoes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ordem_servico_id INT NOT NULL,
        solicitado_por INT NOT NULL,
        motivo TEXT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_token_reabertura_solic_os (ordem_servico_id,status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Solicitações de reabertura centralizadas.
    $pdo->exec("CREATE TABLE IF NOT EXISTS os_reabertura_solicitacoes (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ordem_servico_id INT NOT NULL,
        solicitado_por INT NOT NULL,
        motivo TEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
        analisado_por INT NULL,
        analisado_em DATETIME NULL,
        parecer TEXT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_reabertura_status(status,criado_em),
        KEY idx_reabertura_os(ordem_servico_id,status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Segurança.
    $pdo->exec("CREATE TABLE IF NOT EXISTS seguranca_login_tentativas (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NULL,
        usuario_id INT NULL,
        ip VARCHAR(45) NULL,
        sucesso TINYINT(1) NOT NULL DEFAULT 0,
        motivo VARCHAR(100) NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_login_tentativa_email(email,criado_em),
        KEY idx_login_tentativa_ip(ip,criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS seguranca_eventos (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        usuario_id INT NULL,
        tipo VARCHAR(80) NOT NULL,
        detalhe TEXT NULL,
        ip VARCHAR(45) NULL,
        user_agent VARCHAR(500) NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_seg_evento_usuario(usuario_id,criado_em),
        KEY idx_seg_evento_tipo(tipo,criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // IA assistiva / rastreabilidade de sugestões.
    $pdo->exec("CREATE TABLE IF NOT EXISTS ia_analises (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        ordem_servico_id INT NULL,
        usuario_id INT NULL,
        tipo VARCHAR(60) NOT NULL,
        entrada_hash CHAR(64) NULL,
        resultado_json LONGTEXT NULL,
        origem VARCHAR(40) NOT NULL DEFAULT 'HEURISTICA_LOCAL',
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_ia_analise_os(ordem_servico_id,criado_em),
        KEY idx_ia_analise_tipo(tipo,criado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Integridade de anexos.
    $pdo->exec("CREATE TABLE IF NOT EXISTS arquivo_integridade (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        tabela_origem VARCHAR(80) NOT NULL,
        registro_id BIGINT UNSIGNED NOT NULL,
        arquivo_url VARCHAR(255) NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
        tamanho_bytes BIGINT UNSIGNED NULL,
        sha256 CHAR(64) NULL,
        verificado_em DATETIME NULL,
        PRIMARY KEY(id),
        UNIQUE KEY uq_arquivo_integridade(tabela_origem,registro_id),
        KEY idx_arquivo_status(status,verificado_em)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Aprovações pode já existir; garante o mínimo.
    if (!geop_v8_table_exists($pdo, 'aprovacoes')) {
        $pdo->exec("CREATE TABLE aprovacoes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            tipo VARCHAR(40) NOT NULL,
            entidade VARCHAR(60) NOT NULL,
            entidade_id BIGINT NOT NULL,
            solicitante_id INT NOT NULL,
            aprovador_id INT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'PENDENTE',
            observacao TEXT NULL,
            decidido_em DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_aprovacoes_status(status,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Campos auxiliares já existentes em boa parte das instalações.
    geop_v8_add_column($pdo, 'ordens_servico', 'qualidade_percentual', 'TINYINT UNSIGNED NULL');
    geop_v8_add_column($pdo, 'ordens_servico', 'qualidade_atualizada_em', 'DATETIME NULL');
    geop_v8_add_column($pdo, 'ordens_servico', 'custo_mao_obra', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
    geop_v8_add_column($pdo, 'ordens_servico', 'custo_terceiros', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
    geop_v8_add_column($pdo, 'ordens_servico', 'custo_total_final', 'DECIMAL(12,2) NOT NULL DEFAULT 0.00');
    geop_v8_add_column($pdo, 'ordens_servico', 'duplicidade_ignorada', 'TINYINT(1) NOT NULL DEFAULT 0');
    geop_v8_add_column($pdo, 'ordens_servico', 'duplicidade_referencia_id', 'INT NULL');

    geop_v8_add_column($pdo, 'usuarios', 'totp_secret_enc', 'TEXT NULL');
    geop_v8_add_column($pdo, 'usuarios', 'totp_enabled', 'TINYINT(1) NOT NULL DEFAULT 0');
    geop_v8_add_column($pdo, 'usuarios', 'totp_confirmed_em', 'DATETIME NULL');
    geop_v8_add_column($pdo, 'usuarios', 'ultimo_login_em', 'DATETIME NULL');
    geop_v8_add_column($pdo, 'usuarios', 'ultimo_login_ip', 'VARCHAR(45) NULL');

    geop_v8_add_index($pdo, 'ordens_servico', 'idx_os_ativo_status_v8', '`ativo_id`,`status`');
    geop_v8_add_index($pdo, 'ordens_servico', 'idx_os_categoria_local_v8', '`categoria_id`,`local_id`,`status`');
}

function geop_v8_seed_checklists(PDO $pdo, ?int $userId = null): void
{
    if (!geop_v8_table_exists($pdo, 'categorias') || !geop_v8_table_exists($pdo, 'checklist_modelos')) return;
    $catalog = [
        'eletr' => [
            ['Isolar/desenergizar o circuito quando aplicável',1,0],
            ['Registrar condição antes da intervenção',1,1],
            ['Conferir conexões e componentes substituídos',1,0],
            ['Testar funcionamento após o reparo',1,0],
            ['Registrar evidência após a execução',1,1],
        ],
        'hidraul' => [
            ['Identificar origem do vazamento/falha',1,0],
            ['Registrar condição antes da intervenção',1,1],
            ['Conferir vedação e ausência de vazamento',1,0],
            ['Limpar a área após o serviço',0,0],
            ['Registrar evidência final',1,1],
        ],
        'climat' => [
            ['Verificar alimentação e condições do equipamento',1,0],
            ['Conferir filtros e serpentinas',1,0],
            ['Registrar condição antes da intervenção',1,1],
            ['Testar operação e temperatura após o serviço',1,0],
            ['Registrar evidência final',1,1],
        ],
        'ti' => [
            ['Validar sintoma relatado pelo usuário',1,0],
            ['Registrar configuração/estado inicial quando relevante',0,0],
            ['Executar teste de conectividade/funcionamento',1,0],
            ['Documentar alteração realizada',1,0],
            ['Confirmar funcionamento com o usuário ou evidência',1,0],
        ],
        'civil' => [
            ['Isolar/sinalizar a área quando necessário',1,0],
            ['Registrar condição antes da intervenção',1,1],
            ['Conferir acabamento e segurança',1,0],
            ['Limpar a área após a execução',0,0],
            ['Registrar evidência final',1,1],
        ],
        'manuten' => [
            ['Registrar condição antes da intervenção',1,1],
            ['Confirmar materiais/peças utilizados',1,0],
            ['Executar teste funcional',1,0],
            ['Registrar resultado e observações',1,0],
            ['Registrar evidência final',1,1],
        ],
    ];
    try {
        $cats = $pdo->query("SELECT id,nome FROM categorias WHERE ativo=1")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cats as $cat) {
            $name = geop_v8_normalize_text((string)$cat['nome']);
            $chosen = null;
            foreach ($catalog as $key => $items) {
                if (strpos($name, $key) !== false) { $chosen = $items; break; }
            }
            if (!$chosen) continue;
            $st = $pdo->prepare("SELECT id FROM checklist_modelos WHERE categoria_id=? AND ativo=1 LIMIT 1");
            $st->execute([(int)$cat['id']]);
            if ($st->fetchColumn()) continue;
            $pdo->prepare("INSERT INTO checklist_modelos(categoria_id,nome,criado_por) VALUES(?,?,?)")
                ->execute([(int)$cat['id'],'Checklist padrão — '.$cat['nome'],$userId]);
            $modelId = (int)$pdo->lastInsertId();
            $ins = $pdo->prepare("INSERT INTO checklist_modelo_itens(modelo_id,descricao,obrigatorio,exige_foto,ordem) VALUES(?,?,?,?,?)");
            foreach ($chosen as $idx => $item) $ins->execute([$modelId,$item[0],$item[1],$item[2],($idx+1)*10]);
        }
    } catch (Throwable $e) {
        error_log('[GEOP V8 checklist seed] '.$e->getMessage());
    }
}

function geop_v8_default_config(PDO $pdo): void
{
    $defaults = [
        ['session_idle_minutes','60','INT','Tempo máximo de inatividade da sessão.'],
        ['max_login_attempts','5','INT','Tentativas de login antes de bloqueio temporário.'],
        ['login_lock_minutes','15','INT','Minutos de bloqueio após excesso de tentativas.'],
        ['2fa_ti_required','0','BOOL','Exigir 2FA para TI depois que todos estiverem cadastrados.'],
        ['os_stale_hours','48','INT','Horas sem atualização para alertar OS parada.'],
        ['os_duplicate_threshold','0.55','FLOAT','Similaridade mínima para sugerir OS duplicada.'],
        ['purchase_approval_limit','500','FLOAT','Valor a partir do qual compra exige aprovação.'],
        ['quality_warning_threshold','80','INT','Percentual abaixo do qual a OS mostra alerta de qualidade.'],
        ['upload_max_mb','12','INT','Tamanho máximo recomendado por arquivo.'],
        ['backup_retention_days','30','INT','Retenção padrão dos backups internos.'],
        ['ai_local_enabled','1','BOOL','Ativar sugestões heurísticas locais.'],
        ['vision_enabled','0','BOOL','Ativar integração visual externa quando configurada.'],
    ];
    $st = $pdo->prepare("INSERT IGNORE INTO sistema_configuracoes(chave,valor,tipo,descricao) VALUES(?,?,?,?)");
    foreach ($defaults as $d) $st->execute($d);
}

function geop_v8_config(PDO $pdo, string $key, $default = null)
{
    try {
        $st = $pdo->prepare('SELECT valor,tipo FROM sistema_configuracoes WHERE chave=? LIMIT 1');
        $st->execute([$key]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return $default;
        $value = $row['valor'];
        return match (strtoupper((string)$row['tipo'])) {
            'INT' => (int)$value,
            'FLOAT' => (float)$value,
            'BOOL' => in_array(strtolower((string)$value), ['1','true','yes','on'], true),
            default => $value,
        };
    } catch (Throwable $e) {
        return $default;
    }
}

function geop_v8_config_set(PDO $pdo, string $key, string $value, ?int $userId = null): void
{
    $pdo->prepare("UPDATE sistema_configuracoes SET valor=?,atualizado_por=?,atualizado_em=NOW() WHERE chave=?")
        ->execute([$value,$userId,$key]);
}

function geop_v8_migration_schema(PDO $pdo): array
{
    geop_v8_core_schema($pdo);

    $versionCol = geop_v8_column_exists($pdo, 'schema_migrations', 'version')
        ? 'version'
        : (geop_v8_column_exists($pdo, 'schema_migrations', 'versao') ? 'versao' : null);

    $dateCol = geop_v8_column_exists($pdo, 'schema_migrations', 'applied_at')
        ? 'applied_at'
        : (geop_v8_column_exists($pdo, 'schema_migrations', 'aplicada_em') ? 'aplicada_em' : null);

    if ($versionCol === null) {
        throw new RuntimeException(
            'A tabela schema_migrations existe, mas não possui as colunas version nem versao.'
        );
    }

    return [
        'version' => $versionCol,
        'date' => $dateCol,
        'descricao' => geop_v8_column_exists($pdo, 'schema_migrations', 'descricao') ? 'descricao' : null,
        'checksum' => geop_v8_column_exists($pdo, 'schema_migrations', 'checksum') ? 'checksum' : null,
        'user' => geop_v8_column_exists($pdo, 'schema_migrations', 'aplicada_por') ? 'aplicada_por' : null,
        'duration' => geop_v8_column_exists($pdo, 'schema_migrations', 'duracao_ms') ? 'duracao_ms' : null,
    ];
}

function geop_v8_list_migrations(PDO $pdo): array
{
    try {
        $schema = geop_v8_migration_schema($pdo);
        $v = '`' . $schema['version'] . '`';
        $d = $schema['date'] ? '`' . $schema['date'] . '`' : 'NULL';
        $desc = $schema['descricao'] ? '`' . $schema['descricao'] . '`' : 'NULL';
        $checksum = $schema['checksum'] ? '`' . $schema['checksum'] . '`' : 'NULL';
        $user = $schema['user'] ? '`' . $schema['user'] . '`' : 'NULL';
        $duration = $schema['duration'] ? '`' . $schema['duration'] . '`' : 'NULL';

        $sql = "
            SELECT
                {$v} AS versao,
                {$d} AS aplicada_em,
                {$desc} AS descricao,
                {$checksum} AS checksum,
                {$user} AS aplicada_por,
                {$duration} AS duracao_ms
            FROM schema_migrations
        ";

        if ($schema['date']) {
            $sql .= " ORDER BY `{$schema['date']}` DESC, `{$schema['version']}` DESC";
        } else {
            $sql .= " ORDER BY `{$schema['version']}` DESC";
        }

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function geop_v8_latest_migration(PDO $pdo): ?array
{
    $rows = geop_v8_list_migrations($pdo);
    return $rows[0] ?? null;
}

function geop_v8_migration_applied(PDO $pdo, string $version): bool
{
    $schema = geop_v8_migration_schema($pdo);
    $column = $schema['version'];
    $st = $pdo->prepare("SELECT 1 FROM schema_migrations WHERE `{$column}`=? LIMIT 1");
    $st->execute([$version]);
    return (bool)$st->fetchColumn();
}

function geop_v8_record_migration(PDO $pdo, string $version, string $description, ?int $userId, int $durationMs): void
{
    $schema = geop_v8_migration_schema($pdo);
    $checksum = hash('sha256', $version . '|' . $description);

    $columns = [$schema['version']];
    $values = [$version];
    $placeholders = ['?'];

    if ($schema['descricao']) {
        $columns[] = $schema['descricao'];
        $values[] = $description;
        $placeholders[] = '?';
    }
    if ($schema['checksum']) {
        $columns[] = $schema['checksum'];
        $values[] = $checksum;
        $placeholders[] = '?';
    }
    if ($schema['user']) {
        $columns[] = $schema['user'];
        $values[] = $userId;
        $placeholders[] = '?';
    }
    if ($schema['duration']) {
        $columns[] = $schema['duration'];
        $values[] = $durationMs;
        $placeholders[] = '?';
    }

    $quoted = array_map(static fn($c) => "`{$c}`", $columns);
    $sql = 'INSERT IGNORE INTO schema_migrations (' . implode(',', $quoted) . ')
            VALUES (' . implode(',', $placeholders) . ')';

    $pdo->prepare($sql)->execute($values);
}

function geop_v8_apply_migrations(PDO $pdo, ?int $userId = null): array
{
    geop_v8_core_schema($pdo);
    $steps = [
        '8.0.0-core' => ['Núcleo de migrations e configurações', function() use ($pdo) {
            geop_v8_core_schema($pdo);
            geop_v8_default_config($pdo);
        }],
        '8.0.1-gestao' => ['Compras, custos, checklists, segurança e IA assistiva', function() use ($pdo, $userId) {
            geop_v8_feature_schema($pdo);
            geop_v8_default_config($pdo);
            geop_v8_seed_checklists($pdo, $userId);
        }],
        '8.0.2-integracao' => ['Índices e campos de integração da OS', function() use ($pdo) {
            geop_v8_feature_schema($pdo);
        }],
    ];

    $result = [];
    foreach ($steps as $version => [$description,$callback]) {
        if (geop_v8_migration_applied($pdo, $version)) {
            $result[] = ['versao'=>$version,'status'=>'JA_APLICADA','descricao'=>$description];
            continue;
        }
        $start = microtime(true);
        $callback();
        $ms = (int)round((microtime(true)-$start)*1000);
        geop_v8_record_migration($pdo,$version,$description,$userId,$ms);
        $result[] = ['versao'=>$version,'status'=>'APLICADA','descricao'=>$description,'duracao_ms'=>$ms];
    }
    return $result;
}

function geop_v8_preflight(PDO $pdo): array
{
    $checks = [];
    $checks[] = ['nome'=>'PHP >= 8.1','ok'=>version_compare(PHP_VERSION,'8.1.0','>='),'detalhe'=>PHP_VERSION,'critico'=>true];
    $checks[] = ['nome'=>'PDO MySQL','ok'=>extension_loaded('pdo_mysql'),'detalhe'=>extension_loaded('pdo_mysql')?'carregado':'ausente','critico'=>true];
    $checks[] = ['nome'=>'OpenSSL','ok'=>extension_loaded('openssl'),'detalhe'=>extension_loaded('openssl')?'carregado':'ausente','critico'=>false];
    foreach (['usuarios','ordens_servico','anexos_os','materiais_os','estoque_materiais'] as $table) {
        $checks[] = ['nome'=>'Tabela '.$table,'ok'=>geop_v8_table_exists($pdo,$table),'detalhe'=>geop_v8_table_exists($pdo,$table)?'OK':'não encontrada','critico'=>true];
    }
    $privateRoot = function_exists('env') ? (env('PRIVATE_PATH', dirname(__DIR__) . '/var') ?: dirname(__DIR__) . '/var') : dirname(__DIR__) . '/var';
    $checks[] = ['nome'=>'Diretório privado','ok'=>is_dir($privateRoot) && is_writable($privateRoot),'detalhe'=>$privateRoot,'critico'=>true];
    $free = @disk_free_space(dirname(__DIR__));
    $checks[] = ['nome'=>'Espaço livre','ok'=>$free === false || $free > 200*1024*1024,'detalhe'=>$free===false?'não medido':number_format($free/1024/1024,0,',','.').' MB','critico'=>false];
    return $checks;
}

function geop_v8_postflight(PDO $pdo): array
{
    $checks = [];
    foreach (['schema_migrations','sistema_configuracoes','compras_solicitacoes','compras_itens','os_custos_documentos','checklist_modelos','checklist_modelo_itens','os_checklist_itens','os_reabertura_solicitacoes','seguranca_login_tentativas','seguranca_eventos','ia_analises'] as $table) {
        $checks[] = ['nome'=>$table,'ok'=>geop_v8_table_exists($pdo,$table)];
    }
    foreach (['qualidade_percentual','custo_total_final','duplicidade_ignorada'] as $col) {
        $checks[] = ['nome'=>'ordens_servico.'.$col,'ok'=>geop_v8_column_exists($pdo,'ordens_servico',$col)];
    }
    return $checks;
}

function geop_v8_normalize_text(string $text): string
{
    $text = mb_strtolower(trim($text),'UTF-8');
    $text = strtr($text, [
        'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c'
    ]);
    $text = preg_replace('/[^a-z0-9\s-]+/u',' ',$text) ?? $text;
    return trim(preg_replace('/\s+/',' ',$text) ?? $text);
}

function geop_v8_tokens(string $text): array
{
    $stop = ['de','da','do','das','dos','e','a','o','as','os','em','no','na','nos','nas','para','por','com','um','uma','ao','à','the'];
    $tokens = preg_split('/\s+/', geop_v8_normalize_text($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $tokens = array_values(array_unique(array_filter($tokens, fn($t)=>mb_strlen($t,'UTF-8')>=3 && !in_array($t,$stop,true))));
    return $tokens;
}

function geop_v8_similarity(string $a, string $b): float
{
    $ta = geop_v8_tokens($a); $tb = geop_v8_tokens($b);
    if (!$ta || !$tb) return 0.0;
    $inter = count(array_intersect($ta,$tb));
    $union = count(array_unique(array_merge($ta,$tb)));
    return $union ? $inter/$union : 0.0;
}

function geop_v8_find_duplicates(PDO $pdo, string $title, string $description, int $localId = 0, int $categoryId = 0, int $limit = 5): array
{
    $where = "status NOT IN ('ENCERRADA','CANCELADA','REPROVADA')";
    if (geop_v8_column_exists($pdo,'ordens_servico','excluida_em')) $where .= ' AND excluida_em IS NULL';
    $params=[];
    if ($localId>0) { $where.=' AND local_id=?'; $params[]=$localId; }
    if ($categoryId>0) { $where.=' AND categoria_id=?'; $params[]=$categoryId; }
    $st=$pdo->prepare("SELECT id,numero_os,titulo,descricao,status,local_id,categoria_id,criado_em FROM ordens_servico WHERE {$where} ORDER BY criado_em DESC LIMIT 100");
    $st->execute($params);
    $base=$title.' '.$description;
    $rows=[];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $score=geop_v8_similarity($base, ($r['titulo']??'').' '.($r['descricao']??''));
        if ($score>=0.25) { $r['similaridade']=$score; $rows[]=$r; }
    }
    usort($rows, fn($x,$y)=>$y['similaridade']<=>$x['similaridade']);
    return array_slice($rows,0,max(1,$limit));
}

function geop_v8_ai_assist(PDO $pdo, string $title, string $description, int $localId = 0): array
{
    $text=geop_v8_normalize_text($title.' '.$description);
    $priority='MEDIA';
    if (preg_match('/incend|choque|curto|vazamento grande|alagamento|risco|pane total|sem energia|urgente|emergenc/',$text)) $priority='CRITICA';
    elseif (preg_match('/parado|quebrado|vazamento|falha|nao funciona|não funciona|inoperante/',$text)) $priority='ALTA';
    elseif (preg_match('/preventiv|limpeza|ajuste|inspec|verificar/',$text)) $priority='BAIXA';

    $categories=[];
    try { $categories=$pdo->query("SELECT id,nome FROM categorias WHERE ativo=1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC); } catch(Throwable $e){}
    $catScores=[];
    foreach($categories as $c){
        $n=geop_v8_normalize_text((string)$c['nome']);
        $score=0;
        foreach(geop_v8_tokens($n) as $t) if(strpos($text,$t)!==false) $score+=3;
        $syn=[
            'eletrica'=>['lampada','luminaria','energia','fio','cabo','disjuntor','tomada','curto'],
            'hidraulica'=>['vazamento','torneira','tubo','registro','bomba','agua','hidrante'],
            'climatizacao'=>['ar condicionado','climatizacao','evaporadora','condensadora','filtro'],
            'ti'=>['rede','internet','wifi','computador','impressora','switch','roteador','camera'],
            'limpeza'=>['limpeza','higienizacao','lavagem','lixeira','banheiro','piso'],
            'civil'=>['pintura','parede','piso','telhado','massa','porta','forro'],
        ];
        foreach($syn as $k=>$terms) if(strpos($n,$k)!==false) foreach($terms as $term) if(strpos($text,$term)!==false) $score+=2;
        if($score>0) $catScores[]=['id'=>(int)$c['id'],'nome'=>$c['nome'],'score'=>$score];
    }
    usort($catScores,fn($a,$b)=>$b['score']<=>$a['score']);

    $materials=[];
    try {
        $stock=$pdo->query("SELECT id,nome,unidade,custo_unitario,quantidade_atual FROM estoque_materiais WHERE ativo=1 ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
        foreach($stock as $m){
            $name=geop_v8_normalize_text((string)$m['nome']);
            $mt=geop_v8_tokens($name);
            $hits=0; foreach($mt as $t) if(strpos($text,$t)!==false) $hits++;
            if($hits>=1 && count($mt)<=5 || $hits>=2) { $m['score']=$hits; $materials[]=$m; }
        }
        usort($materials,fn($a,$b)=>($b['score']??0)<=>($a['score']??0));
        $materials=array_slice($materials,0,5);
    } catch(Throwable $e){}

    $dups=geop_v8_find_duplicates($pdo,$title,$description,$localId,0,5);
    return [
        'prioridade_sugerida'=>$priority,
        'categorias_sugeridas'=>array_slice($catScores,0,3),
        'materiais_sugeridos'=>$materials,
        'duplicidades'=>$dups,
        'observacao'=>'Sugestões automáticas. A decisão final permanece com o usuário.'
    ];
}

function geop_v8_checklist_instantiate(PDO $pdo, int $osId, int $categoryId): int
{
    if (!geop_v8_table_exists($pdo, 'checklist_modelos') || !geop_v8_table_exists($pdo, 'checklist_modelo_itens') || !geop_v8_table_exists($pdo, 'os_checklist_itens')) return 0;
    $st=$pdo->prepare("SELECT id FROM checklist_modelos WHERE categoria_id=? AND ativo=1 ORDER BY id DESC LIMIT 1");
    $st->execute([$categoryId]);
    $model=(int)$st->fetchColumn();
    if($model<=0) return 0;
    $exists=$pdo->prepare("SELECT COUNT(*) FROM os_checklist_itens WHERE ordem_servico_id=?");
    $exists->execute([$osId]);
    if((int)$exists->fetchColumn()>0) return 0;
    $items=$pdo->prepare("SELECT * FROM checklist_modelo_itens WHERE modelo_id=? ORDER BY ordem,id");
    $items->execute([$model]);
    $ins=$pdo->prepare("INSERT INTO os_checklist_itens(ordem_servico_id,origem_item_id,descricao,obrigatorio,exige_foto,ordem) VALUES(?,?,?,?,?,?)");
    $count=0;
    foreach($items->fetchAll(PDO::FETCH_ASSOC) as $i){
        $ins->execute([$osId,$i['id'],$i['descricao'],$i['obrigatorio'],$i['exige_foto'],$i['ordem']]); $count++;
    }
    return $count;
}

function geop_v8_quality(PDO $pdo, int $osId): array
{
    $st=$pdo->prepare("SELECT * FROM ordens_servico WHERE id=? LIMIT 1"); $st->execute([$osId]); $os=$st->fetch(PDO::FETCH_ASSOC);
    if(!$os) return ['percentual'=>0,'itens'=>[]];
    $items=[]; $points=0; $max=0;
    $add=function(string $label,bool $ok,int $weight,string $detail='') use (&$items,&$points,&$max){$max+=$weight;if($ok)$points+=$weight;$items[]=['label'=>$label,'ok'=>$ok,'peso'=>$weight,'detalhe'=>$detail];};
    $add('Descrição detalhada',mb_strlen(trim((string)($os['descricao']??'')),'UTF-8')>=20,12,'mínimo recomendado: 20 caracteres');
    $add('Responsável definido',!empty($os['agente_id']),10);
    $cntFoto=function($tipo) use($pdo,$osId){$st=$pdo->prepare("SELECT COUNT(*) FROM anexos_os WHERE ordem_servico_id=? AND tipo=?");$st->execute([$osId,$tipo]);return (int)$st->fetchColumn();};
    $add('Foto antes',$cntFoto('FOTO_ANTES')>0,10);
    $add('Foto depois',$cntFoto('FOTO_DEPOIS')>0,15);
    $totalCl=0; $pending=0;
    if (geop_v8_table_exists($pdo, 'os_checklist_itens')) {
        $st=$pdo->prepare("SELECT COUNT(*) total,SUM(concluido=1) concluidos,SUM(obrigatorio=1 AND concluido=0) pend_obr FROM os_checklist_itens WHERE ordem_servico_id=?");$st->execute([$osId]);$cl=$st->fetch(PDO::FETCH_ASSOC)?:[];
        $totalCl=(int)($cl['total']??0); $pending=(int)($cl['pend_obr']??0);
    }
    $add('Checklist obrigatório concluído',$totalCl===0 || $pending===0,15,$totalCl===0?'sem checklist configurado':($pending.' obrigatório(s) pendente(s)'));
    $st=$pdo->prepare("SELECT COUNT(*) FROM materiais_os WHERE ordem_servico_id=?");$st->execute([$osId]);$mat=(int)$st->fetchColumn();
    $cost=(float)($os['custo_materiais']??0)+(float)($os['custo_mao_obra']??0)+(float)($os['custo_terceiros']??0);
    $add('Materiais/custos conferidos',$mat===0 || $cost>=0,8,$mat.' material(is)');
    $conclusion=trim((string)($os['observacao_aprovacao']??$os['solicitacao_encerramento_obs']??''));
    $add('Resultado da conclusão registrado',mb_strlen($conclusion,'UTF-8')>=5,12);
    $add('Prazo/SLA definido',!empty($os['sla_vencimento']) || !empty($os['prazo']),8);
    $add('Patrimônio vinculado',!empty($os['ativo_id']),10,'recomendado quando aplicável');
    $pct=$max?min(100,(int)round($points/$max*100)):0;
    try{$pdo->prepare("UPDATE ordens_servico SET qualidade_percentual=?,qualidade_atualizada_em=NOW() WHERE id=?")->execute([$pct,$osId]);}catch(Throwable $e){}
    return ['percentual'=>$pct,'itens'=>$items];
}

function geop_v8_financial_total(PDO $pdo, int $osId): float
{
    $st=$pdo->prepare("SELECT COALESCE(custo_materiais,0)+COALESCE(custo_mao_obra,0)+COALESCE(custo_terceiros,0) FROM ordens_servico WHERE id=?");
    $st->execute([$osId]);
    $base=(float)$st->fetchColumn();
    try{$s=$pdo->prepare("SELECT COALESCE(SUM(valor),0) FROM os_custos_documentos WHERE ordem_servico_id=?");$s->execute([$osId]);$docs=(float)$s->fetchColumn();}catch(Throwable $e){$docs=0;}
    return round($base+$docs,2);
}

function geop_v8_update_financial_total(PDO $pdo, int $osId): float
{
    $total=geop_v8_financial_total($pdo,$osId);
    try{$pdo->prepare("UPDATE ordens_servico SET custo_total_final=? WHERE id=?")->execute([$total,$osId]);}catch(Throwable $e){}
    return $total;
}

function geop_v8_security_event(PDO $pdo, ?int $userId, string $type, string $detail=''): void
{
    try{$pdo->prepare("INSERT INTO seguranca_eventos(usuario_id,tipo,detalhe,ip,user_agent) VALUES(?,?,?,?,?)")
        ->execute([$userId,$type,$detail,$_SERVER['REMOTE_ADDR']??null,substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500)]);}catch(Throwable $e){}
}

function geop_v8_login_blocked(PDO $pdo, string $email, string $ip): array
{
    geop_v8_core_schema($pdo); geop_v8_security_schema($pdo); geop_v8_default_config($pdo);
    $max=(int)geop_v8_config($pdo,'max_login_attempts',5); $mins=(int)geop_v8_config($pdo,'login_lock_minutes',15);
    $since=date('Y-m-d H:i:s',time()-$mins*60);
    $st=$pdo->prepare("SELECT COUNT(*) FROM seguranca_login_tentativas WHERE sucesso=0 AND criado_em>=? AND (LOWER(email)=LOWER(?) OR ip=?)");
    $st->execute([$since,$email,$ip]); $count=(int)$st->fetchColumn();
    return ['blocked'=>$count>=$max,'count'=>$count,'max'=>$max,'minutes'=>$mins];
}

function geop_v8_login_attempt(PDO $pdo, string $email, ?int $userId, bool $success, string $reason=''): void
{
    try{$pdo->prepare("INSERT INTO seguranca_login_tentativas(email,usuario_id,ip,sucesso,motivo) VALUES(?,?,?,?,?)")
        ->execute([$email,$userId,$_SERVER['REMOTE_ADDR']??null,$success?1:0,$reason]);}catch(Throwable $e){}
    if($success){
        try{$pdo->prepare("UPDATE usuarios SET ultimo_login_em=NOW(),ultimo_login_ip=? WHERE id=?")->execute([$_SERVER['REMOTE_ADDR']??null,$userId]);}catch(Throwable $e){}
    }
}

function geop_v8_session_guard(PDO $pdo): bool
{
    $idle=(int)geop_v8_config($pdo,'session_idle_minutes',60);
    if($idle<=0) return true;
    $last=(int)($_SESSION['geop_last_activity']??0);
    if($last>0 && time()-$last>$idle*60) return false;
    $_SESSION['geop_last_activity']=time();
    return true;
}

function geop_v8_crypto_key(): string
{
    $root=function_exists('env') ? (env('PRIVATE_PATH',dirname(__DIR__).'/var') ?: dirname(__DIR__).'/var') : dirname(__DIR__).'/var';
    if(!is_dir($root)) @mkdir($root,0750,true);
    $file=$root.'/security.key';
    if(!is_file($file)) { file_put_contents($file,random_bytes(32),LOCK_EX); @chmod($file,0600); }
    $key=file_get_contents($file);
    if($key===false || strlen($key)<32) throw new RuntimeException('Chave privada de segurança indisponível.');
    return substr(hash('sha256',$key,true),0,32);
}

function geop_v8_encrypt(string $plain): string
{
    $key=geop_v8_crypto_key(); $iv=random_bytes(12); $tag='';
    $cipher=openssl_encrypt($plain,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);
    if($cipher===false) throw new RuntimeException('Falha ao proteger segredo 2FA.');
    return base64_encode($iv.$tag.$cipher);
}

function geop_v8_decrypt(string $encoded): string
{
    $raw=base64_decode($encoded,true); if($raw===false || strlen($raw)<28) return '';
    $iv=substr($raw,0,12); $tag=substr($raw,12,16); $cipher=substr($raw,28);
    $plain=openssl_decrypt($cipher,'aes-256-gcm',geop_v8_crypto_key(),OPENSSL_RAW_DATA,$iv,$tag);
    return $plain===false?'':$plain;
}

function geop_v8_base32_encode(string $data): string
{
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $bits='';
    foreach(str_split($data) as $c) $bits.=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);
    $out=''; foreach(str_split($bits,5) as $chunk){$chunk=str_pad($chunk,5,'0');$out.=$alphabet[bindec($chunk)];}
    return $out;
}

function geop_v8_base32_decode(string $s): string
{
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $s=strtoupper(preg_replace('/[^A-Z2-7]/i','',$s)??''); $bits='';
    foreach(str_split($s) as $c){$p=strpos($alphabet,$c);if($p===false)continue;$bits.=str_pad(decbin($p),5,'0',STR_PAD_LEFT);} $out='';
    foreach(str_split($bits,8) as $chunk) if(strlen($chunk)===8) $out.=chr(bindec($chunk));
    return $out;
}

function geop_v8_totp_generate_secret(): string { return geop_v8_base32_encode(random_bytes(20)); }

function geop_v8_totp_code(string $secret, ?int $time=null): string
{
    $time??=time(); $counter=intdiv($time,30); $bin=pack('N*',0).pack('N*',$counter); $key=geop_v8_base32_decode($secret);
    $hash=hash_hmac('sha1',$bin,$key,true); $offset=ord(substr($hash,-1))&0x0F;
    $value=((ord($hash[$offset])&0x7F)<<24)|((ord($hash[$offset+1])&0xFF)<<16)|((ord($hash[$offset+2])&0xFF)<<8)|(ord($hash[$offset+3])&0xFF);
    return str_pad((string)($value%1000000),6,'0',STR_PAD_LEFT);
}

function geop_v8_totp_verify(string $secret, string $code, int $window=1): bool
{
    $code=preg_replace('/\D/','',$code)??''; if(strlen($code)!==6)return false;
    for($i=-$window;$i<=$window;$i++) if(hash_equals(geop_v8_totp_code($secret,time()+$i*30),$code)) return true;
    return false;
}

function geop_v8_totp_uri(string $email, string $secret): string
{
    $issuer='GEOP Shopping Carpina';
    return 'otpauth://totp/'.rawurlencode($issuer.':'.$email).'?secret='.rawurlencode($secret).'&issuer='.rawurlencode($issuer).'&digits=6&period=30';
}

function geop_v8_record_ai_analysis(PDO $pdo, ?int $osId, ?int $userId, string $type, array $result, string $input=''): void
{
    try{$pdo->prepare("INSERT INTO ia_analises(ordem_servico_id,usuario_id,tipo,entrada_hash,resultado_json) VALUES(?,?,?,?,?)")
        ->execute([$osId,$userId,$type,$input!==''?hash('sha256',$input):null,json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);}catch(Throwable $e){}
}

function geop_v8_pending_approvals(PDO $pdo): array
{
    $rows=[];
    try{
        $q=$pdo->query("SELECT a.*,u.nome solicitante_nome FROM aprovacoes a LEFT JOIN usuarios u ON u.id=a.solicitante_id WHERE a.status='PENDENTE' ORDER BY a.created_at");
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$r['_fonte']='APROVACOES';$rows[]=$r;}
    }catch(Throwable $e){}
    try{
        if(geop_v8_table_exists($pdo,'ordem_solicitacoes_exclusao')){
            $q=$pdo->query("SELECT s.id,'EXCLUSAO_ORDEM' tipo,'ordem_solicitacoes_exclusao' entidade,s.id entidade_id,s.solicitado_por solicitante_id,u.nome solicitante_nome,s.status,s.motivo observacao,s.criado_em created_at,s.entidade ordem_entidade,s.ordem_id FROM ordem_solicitacoes_exclusao s LEFT JOIN usuarios u ON u.id=s.solicitado_por WHERE s.status='PENDENTE' ORDER BY s.criado_em");
            foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$r['_fonte']='EXCLUSAO';$rows[]=$r;}
        }
    }catch(Throwable $e){}
    try{
        $q=$pdo->query("SELECT r.id,'REABERTURA_OS' tipo,'os_reabertura_solicitacoes' entidade,r.id entidade_id,r.solicitado_por solicitante_id,u.nome solicitante_nome,r.status,r.motivo observacao,r.criado_em created_at,r.ordem_servico_id FROM os_reabertura_solicitacoes r LEFT JOIN usuarios u ON u.id=r.solicitado_por WHERE r.status='PENDENTE' ORDER BY r.criado_em");
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$r['_fonte']='REABERTURA';$rows[]=$r;}
    }catch(Throwable $e){}
    usort($rows,fn($a,$b)=>strcmp((string)($a['created_at']??''),(string)($b['created_at']??'')));
    return $rows;
}

function geop_v8_notify_operations(PDO $pdo): void
{
    if(!function_exists('gestao_notificar')) return;
    $staleHours=(int)geop_v8_config($pdo,'os_stale_hours',48);
    try{
        $where="status NOT IN ('ENCERRADA','CANCELADA','REPROVADA','CONCLUIDA_PELO_AGENTE') AND atualizado_em < DATE_SUB(NOW(),INTERVAL {$staleHours} HOUR)";
        if(geop_v8_column_exists($pdo,'ordens_servico','excluida_em')) $where.=' AND excluida_em IS NULL';
        $rows=$pdo->query("SELECT id,numero_os,titulo,gestor_id,agente_id,atualizado_em FROM ordens_servico WHERE {$where} LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r){
            foreach(array_unique(array_filter([(int)$r['gestor_id'],(int)$r['agente_id']])) as $uid){
                gestao_notificar($pdo,$uid,'OS_SEM_ATUALIZACAO','OS sem atualização',$r['numero_os'].' — '.$r['titulo'].' está sem atualização há mais de '.$staleHours.'h.',app_path('ordem_detalhe',['id'=>$r['id']]),'os-stale-'.$r['id'].'-'.$uid);
            }
        }
    }catch(Throwable $e){}
    try{
        $pending=count(geop_v8_pending_approvals($pdo));
        if($pending>0 && function_exists('gestao_usuarios_gestao')) foreach(gestao_usuarios_gestao($pdo) as $uid){
            gestao_notificar($pdo,$uid,'APROVACOES_PENDENTES','Aprovações pendentes','Existem '.$pending.' aprovações aguardando decisão.',app_path('central_aprovacoes'),'aprovacoes-pendentes-'.$uid);
        }
    }catch(Throwable $e){}
    try{$pdo->prepare("INSERT INTO sistema_execucoes(chave,ultima_execucao,ultimo_status,detalhe) VALUES('automacoes_v8',NOW(),'OK','Rotina executada') ON DUPLICATE KEY UPDATE ultima_execucao=NOW(),ultimo_status='OK',detalhe='Rotina executada'")->execute();}catch(Throwable $e){}
}

function geop_v8_backup_dir(): string
{
    $root=function_exists('env') ? (env('PRIVATE_PATH',dirname(__DIR__).'/var') ?: dirname(__DIR__).'/var') : dirname(__DIR__).'/var';
    $dir=$root.'/backups'; if(!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir)) throw new RuntimeException('Não foi possível criar diretório privado de backups.');
    return $dir;
}

function geop_v8_sql_literal(PDO $pdo, $value): string
{
    if($value===null) return 'NULL';
    return $pdo->quote((string)$value);
}

function geop_v8_create_database_backup(PDO $pdo, ?int $userId=null): array
{
    geop_v8_core_schema($pdo);
    $dir=geop_v8_backup_dir(); $name='geop_db_'.date('Ymd_His').'.sql.gz'; $path=$dir.'/'.$name;
    $gz=gzopen($path,'wb9'); if(!$gz) throw new RuntimeException('Não foi possível criar o arquivo de backup.');
    try{
        $db=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
        gzwrite($gz,"-- GEOP backup\n-- Banco: {$db}\n-- Data: ".date('c')."\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        $tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach($tables as $table){
            if(!preg_match('/^[A-Za-z0-9_]+$/',(string)$table)) continue;
            $row=$pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            if(!$row) continue;
            gzwrite($gz,"DROP TABLE IF EXISTS `{$table}`;\n".$row[1].";\n");
            $st=$pdo->query("SELECT * FROM `{$table}`");
            while($data=$st->fetch(PDO::FETCH_ASSOC)){
                $cols=array_map(fn($c)=>'`'.str_replace('`','``',$c).'`',array_keys($data));
                $vals=array_map(fn($v)=>geop_v8_sql_literal($pdo,$v),array_values($data));
                gzwrite($gz,"INSERT INTO `{$table}` (".implode(',',$cols).") VALUES (".implode(',',$vals).");\n");
            }
            gzwrite($gz,"\n");
        }
        gzwrite($gz,"SET FOREIGN_KEY_CHECKS=1;\n");
    } finally { gzclose($gz); }
    $size=(int)filesize($path); $sha=hash_file('sha256',$path);
    $pdo->prepare("INSERT INTO sistema_backups(tipo,arquivo,tamanho_bytes,sha256,criado_por) VALUES('BANCO',?,?,?,?)")
        ->execute([$name,$size,$sha,$userId]);
    return ['arquivo'=>$name,'path'=>$path,'tamanho'=>$size,'sha256'=>$sha];
}

function geop_v8_health(PDO $pdo): array
{
    $root=dirname(__DIR__); $private=function_exists('env')?(env('PRIVATE_PATH',$root.'/var')?:$root.'/var'):$root.'/var';
    $checks=[];
    $push=function($name,$status,$detail,$critical=false) use (&$checks){$checks[]=['nome'=>$name,'status'=>$status,'detalhe'=>$detail,'critico'=>$critical];};
    try{$pdo->query('SELECT 1');$push('Banco de dados','OK','Conexão ativa',true);}catch(Throwable $e){$push('Banco de dados','ERRO',$e->getMessage(),true);}
    $push('PHP','OK',PHP_VERSION,true);
    $free=@disk_free_space($root); $total=@disk_total_space($root); $pct=($free!==false&&$total)?round(100*(1-$free/$total),1):null;
    $push('Disco',$pct!==null&&$pct>=90?'ALERTA':'OK',$pct===null?'não medido':$pct.'% utilizado',false);
    $push('Diretório privado',is_dir($private)&&is_writable($private)?'OK':'ERRO',$private,true);
    $upload=$root.'/public/uploads'; $push('Uploads',is_dir($upload)&&is_writable($upload)?'OK':'ALERTA',$upload,false);
    $push('OpenSSL',extension_loaded('openssl')?'OK':'ALERTA',extension_loaded('openssl')?'disponível':'ausente; 2FA não poderá proteger segredos',false);
    foreach(['schema_migrations','notificacoes','auditoria_eventos','ordens_servico','estoque_materiais'] as $t) $push('Tabela '.$t,geop_v8_table_exists($pdo,$t)?'OK':'ERRO',geop_v8_table_exists($pdo,$t)?'presente':'ausente',true);
    try{$last=$pdo->query("SELECT ultima_execucao,ultimo_status FROM sistema_execucoes WHERE chave='automacoes_v8'")->fetch(PDO::FETCH_ASSOC);$push('Automações V8',$last?'OK':'ALERTA',$last?('Última: '.$last['ultima_execucao'].' / '.$last['ultimo_status']):'ainda não registradas',false);}catch(Throwable $e){$push('Automações V8','ALERTA','sem leitura',false);}
    try{
        $m=geop_v8_latest_migration($pdo);
        $push(
            'Migration mais recente',
            $m?'OK':'ALERTA',
            $m ? (($m['versao'] ?? '-') . (!empty($m['aplicada_em']) ? ' em ' . $m['aplicada_em'] : '')) : 'nenhuma',
            false
        );
    }catch(Throwable $e){}
    return $checks;
}

function geop_v8_attachment_stats(PDO $pdo): array
{
    $root=dirname(__DIR__).'/public'; $refs=[]; $missing=[]; $size=0;
    try{$rows=$pdo->query("SELECT id,arquivo_url FROM anexos_os")->fetchAll(PDO::FETCH_ASSOC);foreach($rows as $r){$u=(string)$r['arquivo_url'];$rel=preg_replace('#^/?public/#','',$u);$rel=ltrim($rel,'/');$path=$root.'/'.$rel;if(is_file($path)){$size+=filesize($path);$refs[$path]=true;}else{$missing[]=$r;}}}catch(Throwable $e){}
    $files=[]; $upload=$root.'/uploads'; if(is_dir($upload)){foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload,FilesystemIterator::SKIP_DOTS)) as $f){if($f->isFile())$files[]=$f->getPathname();}}
    $orphans=array_values(array_filter($files,fn($f)=>!isset($refs[$f])));
    return ['referenciados'=>count($refs),'ausentes'=>count($missing),'orfaos'=>count($orphans),'bytes'=>$size,'missing_rows'=>$missing,'orphan_files'=>$orphans];
}

function geop_v8_monthly_metrics(PDO $pdo, string $month): array
{
    if(!preg_match('/^\d{4}-\d{2}$/',$month)) $month=date('Y-m');
    $start=$month.'-01 00:00:00'; $end=date('Y-m-d H:i:s',strtotime($month.'-01 +1 month'));
    $whereDel=geop_v8_column_exists($pdo,'ordens_servico','excluida_em')?' AND excluida_em IS NULL':'';
    $st=$pdo->prepare("SELECT COUNT(*) total,
        SUM(status='ENCERRADA') encerradas,
        SUM(status NOT IN ('ENCERRADA','CANCELADA','REPROVADA')) abertas,
        SUM(status='ENCERRADA' AND data_encerramento<=COALESCE(sla_vencimento,prazo)) sla_ok,
        SUM(status='ENCERRADA' AND data_encerramento>COALESCE(sla_vencimento,prazo)) sla_fora,
        COALESCE(SUM(custo_total_final),0) custo_total,
        COALESCE(SUM(custo_materiais),0) custo_materiais,
        ROUND(AVG(CASE WHEN qualidade_percentual IS NOT NULL THEN qualidade_percentual END),1) qualidade_media
        FROM ordens_servico WHERE criado_em>=? AND criado_em<? {$whereDel}");
    $st->execute([$start,$end]); $k=$st->fetch(PDO::FETCH_ASSOC)?:[];
    $cat=$pdo->prepare("SELECT c.nome,COUNT(*) qtd,COALESCE(SUM(os.custo_total_final),0) custo FROM ordens_servico os LEFT JOIN categorias c ON c.id=os.categoria_id WHERE os.criado_em>=? AND os.criado_em<? {$whereDel} GROUP BY c.id,c.nome ORDER BY qtd DESC LIMIT 10");$cat->execute([$start,$end]);
    $loc=$pdo->prepare("SELECT l.nome,COUNT(*) qtd,COALESCE(SUM(os.custo_total_final),0) custo FROM ordens_servico os LEFT JOIN locais l ON l.id=os.local_id WHERE os.criado_em>=? AND os.criado_em<? {$whereDel} GROUP BY l.id,l.nome ORDER BY qtd DESC LIMIT 10");$loc->execute([$start,$end]);
    return ['mes'=>$month,'kpi'=>$k,'categorias'=>$cat->fetchAll(PDO::FETCH_ASSOC),'locais'=>$loc->fetchAll(PDO::FETCH_ASSOC)];
}

function geop_v8_recurrences(PDO $pdo, int $days=90): array
{
    $days=max(7,min(3650,$days));
    $whereDel=geop_v8_column_exists($pdo,'ordens_servico','excluida_em')?' AND os.excluida_em IS NULL':'';
    $rows=[];
    try{
        $q=$pdo->query("SELECT os.ativo_id,a.codigo_patrimonial,COUNT(*) ocorrencias,COALESCE(SUM(os.custo_total_final),0) custo,MAX(os.criado_em) ultima FROM ordens_servico os LEFT JOIN ativos a ON a.id=os.ativo_id WHERE os.ativo_id IS NOT NULL AND os.criado_em>=DATE_SUB(NOW(),INTERVAL {$days} DAY) {$whereDel} GROUP BY os.ativo_id,a.codigo_patrimonial HAVING COUNT(*)>=2 ORDER BY ocorrencias DESC,custo DESC LIMIT 50");
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$r['tipo']='PATRIMONIO';$rows[]=$r;}
    }catch(Throwable $e){}
    try{
        $q=$pdo->query("SELECT os.local_id,os.categoria_id,l.nome local_nome,c.nome categoria_nome,COUNT(*) ocorrencias,COALESCE(SUM(os.custo_total_final),0) custo,MAX(os.criado_em) ultima FROM ordens_servico os LEFT JOIN locais l ON l.id=os.local_id LEFT JOIN categorias c ON c.id=os.categoria_id WHERE os.criado_em>=DATE_SUB(NOW(),INTERVAL {$days} DAY) {$whereDel} GROUP BY os.local_id,os.categoria_id,l.nome,c.nome HAVING COUNT(*)>=3 ORDER BY ocorrencias DESC,custo DESC LIMIT 50");
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $r){$r['tipo']='LOCAL_CATEGORIA';$rows[]=$r;}
    }catch(Throwable $e){}
    return $rows;
}

function geop_v8_feature_ready(PDO $pdo): bool
{
    return geop_v8_table_exists($pdo, 'compras_solicitacoes')
        && geop_v8_table_exists($pdo, 'checklist_modelos')
        && geop_v8_table_exists($pdo, 'seguranca_login_tentativas')
        && geop_v8_column_exists($pdo, 'ordens_servico', 'custo_total_final');
}

function geop_v8_boot(PDO $pdo): void
{
    static $done=false; if($done)return; $done=true;
    geop_v8_core_schema($pdo);
    // Não aplica migrations pesadas automaticamente; apenas configurações básicas se já houver tabela.
    geop_v8_default_config($pdo);
}
