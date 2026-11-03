<?php
declare(strict_types=1);

require_once __DIR__ . '/geop_v8.php';

const GEOP_AI_VERSION = '8.1.4';

function geop_ai_access_allowed(?array $user = null): bool
{
    $user = $user ?: (function_exists('current_user') ? current_user() : null);
    if (!$user || empty($user['id'])) return false;

    return in_array((string)($user['perfil'] ?? ''), [
        'TI',
        'GESTOR',
        'GESTOR_SINDICO',
        'AGENTE',
        'DIRETOR_EXECUTIVO',
        'COORDENADOR_OPERACOES_OBRAS',
    ], true);
}

function geop_ai_finance_allowed(?array $user = null): bool
{
    $user = $user ?: (function_exists('current_user') ? current_user() : null);
    if (!$user) return false;

    return in_array((string)($user['perfil'] ?? ''), [
        'TI',
        'GESTOR',
        'GESTOR_SINDICO',
        'DIRETOR_EXECUTIVO',
        'COORDENADOR_OPERACOES_OBRAS',
    ], true);
}

function geop_ai_admin_allowed(?array $user = null): bool
{
    $user = $user ?: (function_exists('current_user') ? current_user() : null);
    return $user && (string)($user['perfil'] ?? '') === 'TI';
}

function geop_ai_patrimony_allowed(?array $user = null): bool
{
    $user = $user ?: (function_exists('current_user') ? current_user() : null);
    if (!$user) return false;
    if (geop_ai_finance_allowed($user)) return true;

    // Reaproveita o RBAC já existente no GEOP para perfis operacionais.
    if (function_exists('usuario_pode')) {
        try {
            return usuario_pode('patrimonio.consultar');
        } catch (Throwable $e) {
            return false;
        }
    }
    return false;
}

function geop_ai_schema(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ia_chat_conversas (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            usuario_id INT NOT NULL,
            titulo VARCHAR(190) NOT NULL DEFAULT 'Nova conversa',
            contexto_tipo VARCHAR(40) NULL,
            contexto_id BIGINT NULL,
            arquivada TINYINT(1) NOT NULL DEFAULT 0,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_ia_chat_conv_usuario(usuario_id,arquivada,atualizado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ia_chat_mensagens (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversa_id BIGINT UNSIGNED NOT NULL,
            usuario_id INT NULL,
            papel ENUM('USER','ASSISTANT','SYSTEM') NOT NULL,
            conteudo LONGTEXT NOT NULL,
            fontes_json LONGTEXT NULL,
            modelo VARCHAR(100) NULL,
            input_tokens INT UNSIGNED NULL,
            output_tokens INT UNSIGNED NULL,
            latencia_ms INT UNSIGNED NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_ia_chat_msg_conversa(conversa_id,id),
            KEY idx_ia_chat_msg_usuario(usuario_id,criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ia_chat_execucoes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversa_id BIGINT UNSIGNED NOT NULL,
            mensagem_usuario_id BIGINT UNSIGNED NULL,
            usuario_id INT NOT NULL,
            ferramenta VARCHAR(100) NOT NULL,
            argumentos_json LONGTEXT NULL,
            resultado_resumo VARCHAR(500) NULL,
            linhas_retornadas INT UNSIGNED NOT NULL DEFAULT 0,
            duracao_ms INT UNSIGNED NULL,
            status ENUM('OK','ERRO','BLOQUEADA') NOT NULL DEFAULT 'OK',
            erro VARCHAR(500) NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_ia_exec_conversa(conversa_id,criado_em),
            KEY idx_ia_exec_usuario(usuario_id,criado_em),
            KEY idx_ia_exec_ferramenta(ferramenta,criado_em)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $defaults = [
        ['ai_chat_enabled','0','BOOL','Ativar o GEOP IA Chat após configurar o provedor.'],
        ['ai_chat_provider','GROQ','STRING','Provedor do GEOP IA: GROQ, OLLAMA ou OPENAI.'],
        ['ai_chat_model','qwen/qwen3.8-27b','STRING','Modelo ativo do GEOP IA.'],
        ['ai_chat_groq_model','qwen/qwen3.8-27b','STRING','Modelo Groq Cloud principal.'],
        ['ai_chat_groq_fallback_model','openai/gpt-oss-20b','STRING','Modelo Groq usado automaticamente em rate limit do principal.'],
        ['ai_chat_groq_history_messages','4','INT','Máximo de mensagens anteriores enviadas à Groq.'],
        ['ai_chat_groq_max_tools','6','INT','Máximo de ferramentas descritas em uma requisição Groq.'],
        ['ai_chat_ollama_model','qwen3:4b-instruct','STRING','Modelo local do Ollama.'],
        ['ai_chat_openai_model','gpt-5.6','STRING','Modelo OpenAI quando esse provedor estiver ativo.'],
        ['ai_chat_ollama_num_ctx','8192','INT','Janela de contexto do Ollama.'],
        ['ai_chat_max_tool_calls','8','INT','Máximo de chamadas de ferramentas por pergunta.'],
        ['ai_chat_max_rows','50','INT','Máximo de registros retornados por ferramenta.'],
        ['ai_chat_history_messages','12','INT','Quantidade de mensagens anteriores enviadas como contexto.'],
        ['ai_chat_daily_limit','100','INT','Limite diário de perguntas por usuário.'],
    ];
    $st = $pdo->prepare("
        INSERT IGNORE INTO sistema_configuracoes(chave,valor,tipo,descricao)
        VALUES(?,?,?,?)
    ");
    foreach ($defaults as $row) $st->execute($row);
}

function geop_ai_install(PDO $pdo, ?int $userId = null): array
{
    geop_v8_core_schema($pdo);
    geop_v8_default_config($pdo);
    geop_ai_schema($pdo);

    $results = [];

    $migrations = [
        '8.1.0-geop-ai-chat' => 'GEOP IA Chat com ferramentas somente leitura e auditoria',
        '8.1.1-ollama-local' => 'Suporte a Ollama local com Qwen3, seletor de provedor e teste de conexão',
        '8.1.2-groq-cloud' => 'Suporte a Groq Cloud com Responses API, function calling e botão IA no canto inferior esquerdo',
        '8.1.3-groq-rate-limit' => 'Otimização de tokens Groq, seleção dinâmica de ferramentas e fallback automático em rate limit',
        '8.1.4-ui-notificacoes' => 'Botões GEOP IA/Chat movíveis e roteamento de notificações por perfil e pertinência',
    ];

    foreach ($migrations as $version => $description) {
        if (geop_v8_migration_applied($pdo, $version)) {
            $results[] = ['versao'=>$version,'status'=>'JA_APLICADA','descricao'=>$description];
            continue;
        }

        $start = microtime(true);
        geop_ai_schema($pdo);
        geop_v8_record_migration(
            $pdo,
            $version,
            $description,
            $userId,
            (int)round((microtime(true) - $start) * 1000)
        );
        $results[] = ['versao'=>$version,'status'=>'APLICADA','descricao'=>$description];
    }

    return $results;
}

function geop_ai_enabled(PDO $pdo): bool
{
    return (bool)geop_v8_config($pdo, 'ai_chat_enabled', false);
}

function geop_ai_provider(PDO $pdo): string
{
    $envProvider = function_exists('env') ? strtoupper(trim((string)(env('GEOP_AI_PROVIDER', '') ?? ''))) : '';
    if (in_array($envProvider, ['GROQ','OLLAMA','OPENAI'], true)) return $envProvider;

    $configured = strtoupper(trim((string)geop_v8_config($pdo, 'ai_chat_provider', 'GROQ')));
    return in_array($configured, ['GROQ','OLLAMA','OPENAI'], true) ? $configured : 'GROQ';
}

function geop_ai_groq_api_key_configured(): bool
{
    if (!function_exists('env')) return false;
    $key = trim((string)(env('GROQ_API_KEY', '') ?? ''));
    return $key !== '';
}

function geop_ai_groq_model(PDO $pdo): string
{
    $envModel = function_exists('env') ? trim((string)(env('GROQ_MODEL', '') ?? '')) : '';
    if ($envModel !== '') return $envModel;

    $configured = trim((string)geop_v8_config($pdo, 'ai_chat_groq_model', 'qwen/qwen3.8-27b'));
    return $configured !== '' ? $configured : 'qwen/qwen3.8-27b';
}

function geop_ai_groq_fallback_model(PDO $pdo): string
{
    $envModel = function_exists('env') ? trim((string)(env('GROQ_FALLBACK_MODEL', '') ?? '')) : '';
    if ($envModel !== '') return $envModel;

    $configured = trim((string)geop_v8_config($pdo, 'ai_chat_groq_fallback_model', 'openai/gpt-oss-20b'));
    return $configured !== '' ? $configured : 'openai/gpt-oss-20b';
}

function geop_ai_groq_history_messages(PDO $pdo): int
{
    return geop_ai_int_config($pdo, 'ai_chat_groq_history_messages', 4, 0, 10);
}

function geop_ai_groq_max_tools(PDO $pdo): int
{
    return geop_ai_int_config($pdo, 'ai_chat_groq_max_tools', 6, 2, 10);
}

function geop_ai_groq_endpoint(): string
{
    $value = function_exists('env')
        ? trim((string)(env('GROQ_API_BASE', 'https://api.groq.com/openai/v1') ?? ''))
        : 'https://api.groq.com/openai/v1';

    return rtrim($value !== '' ? $value : 'https://api.groq.com/openai/v1', '/');
}

function geop_ai_api_key_configured(): bool
{
    if (!function_exists('env')) return false;
    $key = trim((string)(env('OPENAI_API_KEY', '') ?? ''));
    return $key !== '';
}

function geop_ai_openai_model(PDO $pdo): string
{
    $envModel = function_exists('env') ? trim((string)(env('OPENAI_MODEL', '') ?? '')) : '';
    if ($envModel !== '') return $envModel;

    $configured = trim((string)geop_v8_config($pdo, 'ai_chat_openai_model', 'gpt-5.6'));
    return $configured !== '' ? $configured : 'gpt-5.6';
}

function geop_ai_ollama_model(PDO $pdo): string
{
    $envModel = function_exists('env') ? trim((string)(env('OLLAMA_MODEL', '') ?? '')) : '';
    if ($envModel !== '') return $envModel;

    $configured = trim((string)geop_v8_config($pdo, 'ai_chat_ollama_model', 'qwen3:4b-instruct'));
    return $configured !== '' ? $configured : 'qwen3:4b-instruct';
}

function geop_ai_model(PDO $pdo): string
{
    return match (geop_ai_provider($pdo)) {
        'OPENAI' => geop_ai_openai_model($pdo),
        'OLLAMA' => geop_ai_ollama_model($pdo),
        default => geop_ai_groq_model($pdo),
    };
}

function geop_ai_endpoint(): string
{
    $value = function_exists('env')
        ? trim((string)(env('OPENAI_API_BASE', 'https://api.openai.com/v1') ?? ''))
        : 'https://api.openai.com/v1';
    return rtrim($value !== '' ? $value : 'https://api.openai.com/v1', '/');
}

function geop_ai_ollama_base_url(): string
{
    $value = function_exists('env')
        ? trim((string)(env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434') ?? ''))
        : 'http://127.0.0.1:11434';

    return rtrim($value !== '' ? $value : 'http://127.0.0.1:11434', '/');
}

function geop_ai_ollama_num_ctx(PDO $pdo): int
{
    $env = function_exists('env') ? trim((string)(env('OLLAMA_NUM_CTX', '') ?? '')) : '';
    if ($env !== '' && ctype_digit($env)) return max(2048, min(65536, (int)$env));
    return geop_ai_int_config($pdo, 'ai_chat_ollama_num_ctx', 8192, 2048, 65536);
}

function geop_ai_ollama_http_headers(): array
{
    $headers = ['Content-Type: application/json'];

    if (function_exists('env')) {
        $bearer = trim((string)(env('OLLAMA_BEARER_TOKEN', '') ?? ''));
        if ($bearer !== '') $headers[] = 'Authorization: Bearer ' . $bearer;

        $cfId = trim((string)(env('OLLAMA_CF_ACCESS_CLIENT_ID', '') ?? ''));
        $cfSecret = trim((string)(env('OLLAMA_CF_ACCESS_CLIENT_SECRET', '') ?? ''));
        if ($cfId !== '') $headers[] = 'CF-Access-Client-Id: ' . $cfId;
        if ($cfSecret !== '') $headers[] = 'CF-Access-Client-Secret: ' . $cfSecret;
    }

    return $headers;
}

function geop_ai_provider_ready(PDO $pdo): array
{
    $provider = geop_ai_provider($pdo);

    if (!function_exists('curl_init')) {
        return ['ok'=>false,'provider'=>$provider,'mensagem'=>'A extensão PHP cURL não está disponível.'];
    }

    if ($provider === 'GROQ') {
        return geop_ai_groq_api_key_configured()
            ? ['ok'=>true,'provider'=>'GROQ','mensagem'=>'Groq Cloud configurada.']
            : ['ok'=>false,'provider'=>'GROQ','mensagem'=>'GROQ_API_KEY não configurada.'];
    }

    if ($provider === 'OPENAI') {
        return geop_ai_api_key_configured()
            ? ['ok'=>true,'provider'=>'OPENAI','mensagem'=>'OpenAI configurada.']
            : ['ok'=>false,'provider'=>'OPENAI','mensagem'=>'OPENAI_API_KEY não configurada.'];
    }

    $url = geop_ai_ollama_base_url();
    if ($url === '') {
        return ['ok'=>false,'provider'=>'OLLAMA','mensagem'=>'OLLAMA_BASE_URL não configurada.'];
    }

    return [
        'ok'=>true,
        'provider'=>'OLLAMA',
        'mensagem'=>'Ollama selecionado. Use o teste de conexão para validar servidor e modelo.',
    ];
}

function geop_ai_int_config(PDO $pdo, string $key, int $default, int $min, int $max): int
{
    $value = (int)geop_v8_config($pdo, $key, $default);
    return max($min, min($max, $value));
}

function geop_ai_assert_available(PDO $pdo): void
{
    $user = function_exists('current_user') ? current_user() : null;

    if (!geop_ai_access_allowed($user)) {
        throw new RuntimeException('Seu perfil não possui acesso ao GEOP IA.');
    }
    if (!geop_ai_enabled($pdo)) {
        throw new RuntimeException('O GEOP IA está desativado pelo TI.');
    }

    $provider = geop_ai_provider($pdo);
    if ($provider === 'GROQ' && !geop_ai_groq_api_key_configured()) {
        throw new RuntimeException('GROQ_API_KEY ainda não foi configurada no .env privado do GEOP.');
    }
    if ($provider === 'OPENAI' && !geop_ai_api_key_configured()) {
        throw new RuntimeException('OPENAI_API_KEY ainda não foi configurada no .env privado do GEOP.');
    }
    if ($provider === 'OLLAMA' && geop_ai_ollama_base_url() === '') {
        throw new RuntimeException('OLLAMA_BASE_URL ainda não foi configurada.');
    }
}

function geop_ai_check_daily_limit(PDO $pdo, int $userId): array
{
    $limit = geop_ai_int_config($pdo, 'ai_chat_daily_limit', 100, 1, 10000);
    $st = $pdo->prepare("
        SELECT COUNT(*)
        FROM ia_chat_mensagens
        WHERE usuario_id=? AND papel='USER' AND criado_em >= CURDATE()
    ");
    $st->execute([$userId]);
    $used = (int)$st->fetchColumn();

    return [
        'ok' => $used < $limit,
        'used' => $used,
        'limit' => $limit,
        'remaining' => max(0, $limit - $used),
    ];
}

function geop_ai_conversation_create(PDO $pdo, int $userId, string $title = 'Nova conversa', ?string $contextType = null, ?int $contextId = null): int
{
    $title = trim($title);
    if ($title === '') $title = 'Nova conversa';
    $title = mb_substr($title, 0, 190, 'UTF-8');

    $st = $pdo->prepare("
        INSERT INTO ia_chat_conversas(usuario_id,titulo,contexto_tipo,contexto_id)
        VALUES(?,?,?,?)
    ");
    $st->execute([$userId,$title,$contextType,$contextId]);
    return (int)$pdo->lastInsertId();
}

function geop_ai_conversation_owned(PDO $pdo, int $conversationId, int $userId): ?array
{
    $st = $pdo->prepare("
        SELECT *
        FROM ia_chat_conversas
        WHERE id=? AND usuario_id=? AND arquivada=0
        LIMIT 1
    ");
    $st->execute([$conversationId,$userId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function geop_ai_conversations(PDO $pdo, int $userId, int $limit = 30): array
{
    $limit = max(1,min(100,$limit));
    $st = $pdo->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM ia_chat_mensagens m WHERE m.conversa_id=c.id) total_mensagens
        FROM ia_chat_conversas c
        WHERE c.usuario_id=? AND c.arquivada=0
        ORDER BY c.atualizado_em DESC
        LIMIT {$limit}
    ");
    $st->execute([$userId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function geop_ai_messages(PDO $pdo, int $conversationId, int $userId, int $limit = 100): array
{
    if (!geop_ai_conversation_owned($pdo,$conversationId,$userId)) return [];
    $limit = max(1,min(200,$limit));

    $st = $pdo->prepare("
        SELECT *
        FROM (
            SELECT id,conversa_id,usuario_id,papel,conteudo,fontes_json,modelo,
                   input_tokens,output_tokens,latencia_ms,criado_em
            FROM ia_chat_mensagens
            WHERE conversa_id=?
            ORDER BY id DESC
            LIMIT {$limit}
        ) x
        ORDER BY id ASC
    ");
    $st->execute([$conversationId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['fontes'] = [];
        if (!empty($row['fontes_json'])) {
            $decoded = json_decode((string)$row['fontes_json'], true);
            if (is_array($decoded)) $row['fontes'] = $decoded;
        }
    }
    unset($row);
    return $rows;
}

function geop_ai_message_add(
    PDO $pdo,
    int $conversationId,
    ?int $userId,
    string $role,
    string $content,
    array $sources = [],
    ?string $model = null,
    ?int $inputTokens = null,
    ?int $outputTokens = null,
    ?int $latencyMs = null
): int {
    $role = strtoupper($role);
    if (!in_array($role,['USER','ASSISTANT','SYSTEM'],true)) {
        throw new InvalidArgumentException('Papel de mensagem inválido.');
    }

    $st = $pdo->prepare("
        INSERT INTO ia_chat_mensagens
        (conversa_id,usuario_id,papel,conteudo,fontes_json,modelo,input_tokens,output_tokens,latencia_ms)
        VALUES(?,?,?,?,?,?,?,?,?)
    ");
    $st->execute([
        $conversationId,
        $userId,
        $role,
        $content,
        $sources ? json_encode($sources,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
        $model,
        $inputTokens,
        $outputTokens,
        $latencyMs,
    ]);

    $pdo->prepare("UPDATE ia_chat_conversas SET atualizado_em=NOW() WHERE id=?")->execute([$conversationId]);
    return (int)$pdo->lastInsertId();
}

function geop_ai_maybe_title_conversation(PDO $pdo, int $conversationId, string $question): void
{
    $st = $pdo->prepare("SELECT titulo FROM ia_chat_conversas WHERE id=? LIMIT 1");
    $st->execute([$conversationId]);
    $title = (string)$st->fetchColumn();
    if ($title !== 'Nova conversa') return;

    $question = trim(preg_replace('/\s+/u',' ',$question) ?? $question);
    if ($question === '') return;

    $newTitle = mb_substr($question,0,72,'UTF-8');
    $pdo->prepare("UPDATE ia_chat_conversas SET titulo=? WHERE id=?")->execute([$newTitle,$conversationId]);
}

function geop_ai_history_for_model(PDO $pdo, int $conversationId, int $userId): array
{
    $max = geop_ai_int_config($pdo,'ai_chat_history_messages',12,2,30);
    $rows = geop_ai_messages($pdo,$conversationId,$userId,$max);

    $input = [];
    foreach ($rows as $row) {
        if (!in_array($row['papel'],['USER','ASSISTANT'],true)) continue;
        $input[] = [
            'role' => $row['papel'] === 'USER' ? 'user' : 'assistant',
            'content' => (string)$row['conteudo'],
        ];
    }
    return $input;
}

function geop_ai_execution_log(
    PDO $pdo,
    int $conversationId,
    ?int $userMessageId,
    int $userId,
    string $tool,
    array $arguments,
    string $summary,
    int $rows,
    int $durationMs,
    string $status = 'OK',
    ?string $error = null
): void {
    try {
        $st = $pdo->prepare("
            INSERT INTO ia_chat_execucoes
            (conversa_id,mensagem_usuario_id,usuario_id,ferramenta,argumentos_json,
             resultado_resumo,linhas_retornadas,duracao_ms,status,erro)
            VALUES(?,?,?,?,?,?,?,?,?,?)
        ");
        $st->execute([
            $conversationId,
            $userMessageId,
            $userId,
            $tool,
            json_encode($arguments,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            mb_substr($summary,0,500,'UTF-8'),
            max(0,$rows),
            max(0,$durationMs),
            $status,
            $error ? mb_substr($error,0,500,'UTF-8') : null,
        ]);
    } catch (Throwable $e) {
        error_log('[GEOP IA audit] '.$e->getMessage());
    }
}

function geop_ai_admin_stats(PDO $pdo): array
{
    $out = [
        'conversas_hoje'=>0,
        'perguntas_hoje'=>0,
        'tool_calls_hoje'=>0,
        'erros_hoje'=>0,
        'input_tokens_hoje'=>0,
        'output_tokens_hoje'=>0,
    ];
    try {
        $out['conversas_hoje']=(int)$pdo->query("SELECT COUNT(*) FROM ia_chat_conversas WHERE criado_em>=CURDATE()")->fetchColumn();
        $out['perguntas_hoje']=(int)$pdo->query("SELECT COUNT(*) FROM ia_chat_mensagens WHERE papel='USER' AND criado_em>=CURDATE()")->fetchColumn();
        $out['tool_calls_hoje']=(int)$pdo->query("SELECT COUNT(*) FROM ia_chat_execucoes WHERE criado_em>=CURDATE()")->fetchColumn();
        $out['erros_hoje']=(int)$pdo->query("SELECT COUNT(*) FROM ia_chat_execucoes WHERE status='ERRO' AND criado_em>=CURDATE()")->fetchColumn();
        $row=$pdo->query("SELECT COALESCE(SUM(input_tokens),0) i,COALESCE(SUM(output_tokens),0) o FROM ia_chat_mensagens WHERE papel='ASSISTANT' AND criado_em>=CURDATE()")->fetch(PDO::FETCH_ASSOC);
        $out['input_tokens_hoje']=(int)($row['i']??0);
        $out['output_tokens_hoje']=(int)($row['o']??0);
    } catch (Throwable $e) {}
    return $out;
}
