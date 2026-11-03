<?php
declare(strict_types=1);

require_once __DIR__ . '/geop_ai_tools.php';

function geop_ai_ollama_request(PDO $pdo, array $payload, int $timeout = 90): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão PHP cURL é necessária para conectar ao Ollama.');
    }

    $url = geop_ai_ollama_base_url() . '/api/chat';
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($body === false) throw new RuntimeException('Falha ao serializar a requisição para o Ollama.');

    $ch = curl_init($url);
    if ($ch === false) throw new RuntimeException('Não foi possível inicializar a conexão com o Ollama.');

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => geop_ai_ollama_http_headers(),
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => max(15, $timeout),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException('Não foi possível acessar o Ollama: ' . ($error ?: 'erro de conexão'));
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
        throw new RuntimeException('O Ollama retornou uma resposta inválida.');
    }

    if ($http < 200 || $http >= 300) {
        $message = (string)($json['error'] ?? $json['message'] ?? ('HTTP ' . $http));
        throw new RuntimeException('Erro do Ollama: ' . $message);
    }

    return $json;
}

function geop_ai_ollama_get(string $path, int $timeout = 12): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão PHP cURL é necessária para conectar ao Ollama.');
    }

    $url = geop_ai_ollama_base_url() . '/' . ltrim($path, '/');
    $ch = curl_init($url);
    if ($ch === false) throw new RuntimeException('Não foi possível inicializar a conexão com o Ollama.');

    $headers = array_values(array_filter(
        geop_ai_ollama_http_headers(),
        static fn(string $h): bool => stripos($h, 'Content-Type:') !== 0
    ));

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_TIMEOUT => max(10, $timeout),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) throw new RuntimeException('Não foi possível acessar o Ollama: ' . ($error ?: 'erro de conexão'));

    $json = json_decode($raw, true);
    if (!is_array($json)) throw new RuntimeException('Resposta inválida do Ollama.');

    if ($http < 200 || $http >= 300) {
        $message = (string)($json['error'] ?? $json['message'] ?? ('HTTP ' . $http));
        throw new RuntimeException('Erro do Ollama: ' . $message);
    }

    return $json;
}

function geop_ai_ollama_tools(array $tools): array
{
    $out = [];
    foreach ($tools as $tool) {
        if (($tool['type'] ?? '') !== 'function' || empty($tool['name'])) continue;
        $out[] = [
            'type' => 'function',
            'function' => [
                'name' => (string)$tool['name'],
                'description' => (string)($tool['description'] ?? ''),
                'parameters' => $tool['parameters'] ?? [
                    'type'=>'object',
                    'properties'=>new stdClass(),
                ],
            ],
        ];
    }
    return $out;
}

function geop_ai_ollama_tool_calls(array $response): array
{
    $calls = [];
    foreach (($response['message']['tool_calls'] ?? []) as $idx => $call) {
        $function = $call['function'] ?? [];
        $name = trim((string)($function['name'] ?? ''));
        if ($name === '') continue;

        $args = $function['arguments'] ?? [];
        if (is_string($args)) {
            $decoded = json_decode($args, true);
            $args = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($args)) $args = [];

        $calls[] = [
            'name'=>$name,
            'arguments'=>$args,
            'index'=>$idx,
        ];
    }
    return $calls;
}

function geop_ai_ollama_test(PDO $pdo): array
{
    $started = microtime(true);
    $tags = geop_ai_ollama_get('/api/tags', 12);
    $model = geop_ai_ollama_model($pdo);
    $models = [];

    foreach (($tags['models'] ?? []) as $row) {
        if (!is_array($row)) continue;
        $name = (string)($row['name'] ?? $row['model'] ?? '');
        if ($name !== '') $models[] = $name;
    }

    $installed = in_array($model, $models, true);
    if (!$installed) {
        // Ollama tags may include :latest on the same base model.
        $base = explode(':', $model, 2)[0];
        foreach ($models as $candidate) {
            if ($candidate === $base || str_starts_with($candidate, $base . ':')) {
                if ($model === $base || $candidate === $model) {
                    $installed = true;
                    break;
                }
            }
        }
    }

    return [
        'ok'=>true,
        'servidor'=>geop_ai_ollama_base_url(),
        'modelo'=>$model,
        'modelo_instalado'=>$installed,
        'modelos'=>$models,
        'latencia_ms'=>(int)round((microtime(true)-$started)*1000),
    ];
}

function geop_ai_chat_answer_ollama(
    PDO $pdo,
    array $user,
    int $conversationId,
    int $userMessageId,
    array $history
): array {
    geop_ai_assert_available($pdo);

    $model = geop_ai_ollama_model($pdo);
    $tools = geop_ai_ollama_tools(geop_ai_tools_definition($user));
    $maxCalls = geop_ai_int_config($pdo, 'ai_chat_max_tool_calls', 8, 1, 20);
    $toolCallsUsed = 0;
    $sources = [];
    $started = microtime(true);

    $messages = [
        ['role'=>'system','content'=>geop_ai_system_instructions($user)],
    ];
    foreach ($history as $item) {
        if (!is_array($item)) continue;
        $role = (string)($item['role'] ?? '');
        if (!in_array($role, ['user','assistant'], true)) continue;
        $messages[] = ['role'=>$role,'content'=>(string)($item['content'] ?? '')];
    }

    while (true) {
        $payload = [
            'model'=>$model,
            'messages'=>$messages,
            'tools'=>$tools,
            'stream'=>false,
            'options'=>[
                'num_ctx'=>geop_ai_ollama_num_ctx($pdo),
                'temperature'=>0.2,
            ],
            'keep_alive'=>'10m',
        ];

        $response = geop_ai_ollama_request($pdo, $payload, 120);
        $message = $response['message'] ?? [];
        if (!is_array($message)) $message = [];

        $calls = geop_ai_ollama_tool_calls($response);
        if (!$calls) {
            $text = trim((string)($message['content'] ?? ''));
            if ($text === '') {
                $text = 'Não consegui produzir uma resposta textual com segurança a partir dos dados consultados.';
            }

            return [
                'texto'=>$text,
                'fontes'=>geop_ai_unique_sources($sources),
                'modelo'=>'ollama/' . $model,
                'input_tokens'=>(int)($response['prompt_eval_count'] ?? 0),
                'output_tokens'=>(int)($response['eval_count'] ?? 0),
                'latencia_ms'=>(int)round((microtime(true)-$started)*1000),
            ];
        }

        // Devolve a mensagem do assistant com os tool_calls para o próximo turno.
        $assistantMessage = [
            'role'=>'assistant',
            'content'=>(string)($message['content'] ?? ''),
            'tool_calls'=>$message['tool_calls'] ?? [],
        ];
        $messages[] = $assistantMessage;

        foreach ($calls as $call) {
            $toolCallsUsed++;
            if ($toolCallsUsed > $maxCalls) {
                $messages[] = [
                    'role'=>'tool',
                    'content'=>json_encode([
                        'ok'=>false,
                        'erro'=>'Limite de consultas atingido. Responda com os dados já obtidos.'
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'tool_name'=>$call['name'],
                ];
                continue;
            }

            $toolStart = microtime(true);
            try {
                $result = geop_ai_tool_execute($pdo, $user, $call['name'], $call['arguments']);
                $duration = (int)round((microtime(true)-$toolStart)*1000);
                $rows = (int)($result['linhas'] ?? 0);
                $summary = (string)($result['resumo'] ?? ($result['erro'] ?? 'Consulta concluída.'));
                foreach (($result['fontes'] ?? []) as $source) $sources[] = $source;

                geop_ai_execution_log(
                    $pdo,$conversationId,$userMessageId,(int)$user['id'],
                    $call['name'],$call['arguments'],$summary,$rows,$duration,
                    !empty($result['ok'])?'OK':'ERRO',
                    empty($result['ok'])?(string)($result['erro']??'Falha na ferramenta'):null
                );
            } catch (Throwable $e) {
                $duration = (int)round((microtime(true)-$toolStart)*1000);
                $result = ['ok'=>false,'erro'=>'Consulta bloqueada/indisponível: '.$e->getMessage()];
                geop_ai_execution_log(
                    $pdo,$conversationId,$userMessageId,(int)$user['id'],
                    $call['name'],$call['arguments'],'Erro de ferramenta',0,$duration,'ERRO',$e->getMessage()
                );
            }

            $serialized = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($serialized === false) $serialized = '{"ok":false,"erro":"Falha ao serializar resultado."}';
            if (strlen($serialized) > 80000) {
                $serialized = json_encode([
                    'ok'=>false,
                    'erro'=>'Resultado grande demais. Refine a consulta ou reduza o período.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $messages[] = [
                'role'=>'tool',
                'content'=>$serialized,
                'tool_name'=>$call['name'],
            ];
        }
    }
}
