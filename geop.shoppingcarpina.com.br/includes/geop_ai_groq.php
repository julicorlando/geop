<?php
declare(strict_types=1);

require_once __DIR__ . '/geop_ai_tools.php';

final class GeopAiGroqRateLimitException extends RuntimeException
{
    public int $retryAfterSeconds;
    public string $model;

    public function __construct(string $message, int $retryAfterSeconds = 0, string $model = '')
    {
        parent::__construct($message);
        $this->retryAfterSeconds = max(0, $retryAfterSeconds);
        $this->model = $model;
    }
}

function geop_ai_groq_request(array $payload, string $path = '/responses', int $timeout = 60): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão PHP cURL é necessária para usar a Groq.');
    }

    $apiKey = trim((string)(env('GROQ_API_KEY','') ?? ''));
    if ($apiKey === '') {
        throw new RuntimeException('GROQ_API_KEY não configurada.');
    }

    $url = geop_ai_groq_endpoint() . '/' . ltrim($path, '/');
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($body === false) throw new RuntimeException('Falha ao serializar a requisição para a Groq.');

    $responseHeaders = [];
    $ch = curl_init($url);
    if ($ch === false) throw new RuntimeException('Não foi possível inicializar a conexão com a Groq.');

    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>[
            'Authorization: Bearer '.$apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS=>$body,
        CURLOPT_CONNECTTIMEOUT=>10,
        CURLOPT_TIMEOUT=>max(20,$timeout),
        CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_HEADERFUNCTION=>static function($curl, string $header) use (&$responseHeaders): int {
            $len = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return $len;
        },
    ]);

    $raw=curl_exec($ch);
    $http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curlError=curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        throw new RuntimeException('Falha de conexão com a Groq: '.($curlError ?: 'erro desconhecido'));
    }

    $json=json_decode($raw,true);
    if (!is_array($json)) {
        throw new RuntimeException('A Groq retornou uma resposta inválida.');
    }

    if ($http === 429) {
        $message=(string)($json['error']['message']??$json['message']??'Rate limit da Groq atingido.');
        $retryRaw=(string)($responseHeaders['retry-after']??'0');
        $retry=(int)ceil((float)$retryRaw);
        $model=(string)($payload['model']??'');
        throw new GeopAiGroqRateLimitException($message,$retry,$model);
    }

    if ($http < 200 || $http >= 300) {
        $message=(string)($json['error']['message']??$json['message']??('HTTP '.$http));
        throw new RuntimeException('Erro da Groq: '.$message);
    }

    return $json;
}

function geop_ai_groq_get(string $path, int $timeout = 15): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('A extensão PHP cURL é necessária para usar a Groq.');
    }

    $apiKey = trim((string)(env('GROQ_API_KEY','') ?? ''));
    if ($apiKey === '') throw new RuntimeException('GROQ_API_KEY não configurada.');

    $url = geop_ai_groq_endpoint() . '/' . ltrim($path, '/');
    $ch = curl_init($url);
    if ($ch === false) throw new RuntimeException('Não foi possível inicializar a conexão com a Groq.');

    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>[
            'Authorization: Bearer '.$apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_CONNECTTIMEOUT=>8,
        CURLOPT_TIMEOUT=>max(10,$timeout),
        CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_SSL_VERIFYHOST=>2,
    ]);

    $raw=curl_exec($ch);
    $http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curlError=curl_error($ch);
    curl_close($ch);

    if ($raw === false) throw new RuntimeException('Falha de conexão com a Groq: '.($curlError ?: 'erro desconhecido'));

    $json=json_decode($raw,true);
    if (!is_array($json)) throw new RuntimeException('A Groq retornou uma resposta inválida.');

    if ($http < 200 || $http >= 300) {
        $message=(string)($json['error']['message']??$json['message']??('HTTP '.$http));
        throw new RuntimeException('Erro da Groq: '.$message);
    }

    return $json;
}

function geop_ai_groq_response_text(array $response): string
{
    if (!empty($response['output_text']) && is_string($response['output_text'])) {
        return trim($response['output_text']);
    }

    $parts=[];
    foreach (($response['output']??[]) as $item) {
        if (($item['type']??'') !== 'message') continue;
        foreach (($item['content']??[]) as $content) {
            if (($content['type']??'') === 'output_text' && isset($content['text'])) {
                $parts[]=(string)$content['text'];
            }
        }
    }
    return trim(implode("\n",$parts));
}

function geop_ai_groq_function_calls(array $response): array
{
    $calls=[];
    foreach (($response['output']??[]) as $item) {
        if (($item['type']??'') !== 'function_call') continue;
        if (empty($item['name']) || empty($item['call_id'])) continue;

        $args=[];
        if (isset($item['arguments'])) {
            $decoded=json_decode((string)$item['arguments'],true);
            if (is_array($decoded)) $args=$decoded;
        }

        $calls[]=[
            'call_id'=>(string)$item['call_id'],
            'name'=>(string)$item['name'],
            'arguments'=>$args,
        ];
    }
    return $calls;
}

function geop_ai_groq_unique_sources(array $sources): array
{
    $out=[];$seen=[];
    foreach($sources as $source){
        if(!is_array($source)) continue;
        $key=(string)($source['nome']??'').'|'.(string)($source['detalhe']??'');
        if($key==='|' || isset($seen[$key])) continue;
        $seen[$key]=true;$out[]=$source;
    }
    return $out;
}

function geop_ai_groq_last_question(array $history): string
{
    for ($i=count($history)-1;$i>=0;$i--) {
        $item=$history[$i]??null;
        if (!is_array($item) || ($item['role']??'')!=='user') continue;
        return mb_strtolower(trim((string)($item['content']??'')),'UTF-8');
    }
    return '';
}

function geop_ai_groq_select_tools(PDO $pdo, array $tools, array $history): array
{
    $q=geop_ai_groq_last_question($history);
    $max=geop_ai_groq_max_tools($pdo);

    // Ferramentas-base para as perguntas mais comuns.
    $wanted=['buscar_ordens_servico','resumir_ordem_servico','consultar_indicadores_operacionais'];

    $rules=[
        'consultar_ordens_limpeza'=>['ol','limpeza','asg'],
        'consultar_estoque'=>['estoque','material','materiais','saldo','mínimo','minimo','produto'],
        'consultar_patrimonio'=>['patrimônio','patrimonio','ativo','equipamento','bem','série','serie'],
        'consultar_preventivas'=>['preventiva','preventivas','manutenção preventiva','manutencao preventiva'],
        'consultar_reincidencias'=>['reincid','repet','recorr','mais os','mais chamados','equipamento com mais'],
        'consultar_conhecimento'=>['base de conhecimento','procedimento','manual','como fazer','artigo'],
        'consultar_custos'=>['custo','custos','gast','financeir','r$','valor','caro','barato'],
        'consultar_aprovacoes_pendentes'=>['aprova','pendente','aguardando aprovação','aguardando aprovacao'],
        'consultar_liberacoes'=>['liberação','liberacao','acesso','cftv','visitante'],
        'consultar_auditoria'=>['auditoria','audit','alterou','quem mudou','quem excluiu','log'],
    ];

    foreach($rules as $tool=>$keywords){
        foreach($keywords as $keyword){
            if($q!=='' && mb_strpos($q,$keyword,0,'UTF-8')!==false){
                $wanted[]=$tool;
                break;
            }
        }
    }

    // Menções explícitas a OS normalmente não precisam de todos os módulos.
    if($q!=='' && (preg_match('/\bos\b/u',$q) || mb_strpos($q,'ordem de serviço',0,'UTF-8')!==false)){
        $wanted[]='buscar_ordens_servico';
        $wanted[]='resumir_ordem_servico';
    }

    $wanted=array_values(array_unique($wanted));
    $indexed=[];
    foreach($tools as $tool){
        $name=(string)($tool['name']??'');
        if($name!=='')$indexed[$name]=$tool;
    }

    $selected=[];
    foreach($wanted as $name){
        if(isset($indexed[$name]))$selected[]=$indexed[$name];
        if(count($selected)>=$max)break;
    }

    // Sempre deixa no mínimo duas ferramentas; evita mandar as 13 sem necessidade.
    if(count($selected)<2){
        foreach($tools as $tool){
            $name=(string)($tool['name']??'');
            if($name!=='' && !in_array($name,array_column($selected,'name'),true)){
                $selected[]=$tool;
            }
            if(count($selected)>=2)break;
        }
    }

    return array_slice($selected,0,$max);
}

function geop_ai_groq_trim_history(PDO $pdo, array $history): array
{
    $max=geop_ai_groq_history_messages($pdo);
    if($max<=0)return array_slice($history,-1);

    // Mantém sempre a pergunta atual e somente algumas mensagens anteriores.
    return array_slice($history,-($max+1));
}

function geop_ai_groq_test(PDO $pdo): array
{
    $started=microtime(true);
    $result=geop_ai_groq_get('/models',15);
    $model=geop_ai_groq_model($pdo);
    $fallback=geop_ai_groq_fallback_model($pdo);
    $models=[];

    foreach(($result['data']??[]) as $row){
        if(!is_array($row))continue;
        $id=trim((string)($row['id']??''));
        if($id!=='')$models[]=$id;
    }

    return [
        'ok'=>true,
        'modelo'=>$model,
        'modelo_disponivel'=>in_array($model,$models,true),
        'fallback_model'=>$fallback,
        'fallback_disponivel'=>in_array($fallback,$models,true),
        'modelos'=>$models,
        'latencia_ms'=>(int)round((microtime(true)-$started)*1000),
    ];
}

function geop_ai_chat_answer_groq_model(
    PDO $pdo,
    array $user,
    int $conversationId,
    int $userMessageId,
    array $history,
    string $model
): array {
    $allTools=geop_ai_tools_definition($user);
    $tools=geop_ai_groq_select_tools($pdo,$allTools,$history);
    $input=geop_ai_groq_trim_history($pdo,$history);
    $maxCalls=geop_ai_int_config($pdo,'ai_chat_max_tool_calls',8,1,20);
    $toolCallsUsed=0;
    $sources=[];
    $started=microtime(true);
    $usageInput=0;$usageOutput=0;

    $basePayload=[
        'model'=>$model,
        'instructions'=>geop_ai_system_instructions($user),
        'tools'=>$tools,
        'tool_choice'=>'auto',
        'store'=>false,
        'max_output_tokens'=>900,
    ];

    while(true){
        $payload=$basePayload;
        $payload['input']=$input;
        $response=geop_ai_groq_request($payload,'/responses',75);

        $usageInput+=(int)($response['usage']['input_tokens']??0);
        $usageOutput+=(int)($response['usage']['output_tokens']??0);

        $calls=geop_ai_groq_function_calls($response);
        if(!$calls){
            $text=geop_ai_groq_response_text($response);
            if($text==='') $text='Não consegui produzir uma resposta textual com segurança a partir dos dados consultados.';
            return [
                'texto'=>$text,
                'fontes'=>geop_ai_groq_unique_sources($sources),
                'modelo'=>'groq/'.$model,
                'input_tokens'=>$usageInput,
                'output_tokens'=>$usageOutput,
                'latencia_ms'=>(int)round((microtime(true)-$started)*1000),
                'tools_enviadas'=>array_values(array_filter(array_map(
                    static fn(array $t): string => (string)($t['name']??''),
                    $tools
                ))),
            ];
        }

        foreach(($response['output']??[]) as $item){
            if(is_array($item))$input[]=$item;
        }

        foreach($calls as $call){
            $toolCallsUsed++;
            if($toolCallsUsed>$maxCalls){
                $input[]=[
                    'type'=>'function_call_output',
                    'call_id'=>$call['call_id'],
                    'output'=>json_encode([
                        'ok'=>false,
                        'erro'=>'Limite de consultas atingido. Responda com os dados já obtidos.'
                    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                ];
                continue;
            }

            $toolStart=microtime(true);
            try{
                $result=geop_ai_tool_execute($pdo,$user,$call['name'],$call['arguments']);
                $duration=(int)round((microtime(true)-$toolStart)*1000);
                $rows=(int)($result['linhas']??0);
                $summary=(string)($result['resumo']??($result['erro']??'Consulta concluída.'));
                foreach(($result['fontes']??[]) as $src)$sources[]=$src;

                geop_ai_execution_log(
                    $pdo,$conversationId,$userMessageId,(int)$user['id'],
                    $call['name'],$call['arguments'],$summary,$rows,$duration,
                    !empty($result['ok'])?'OK':'ERRO',
                    empty($result['ok'])?(string)($result['erro']??'Falha na ferramenta'):null
                );
            }catch(Throwable $e){
                $duration=(int)round((microtime(true)-$toolStart)*1000);
                $result=['ok'=>false,'erro'=>'Consulta bloqueada/indisponível: '.$e->getMessage()];
                geop_ai_execution_log(
                    $pdo,$conversationId,$userMessageId,(int)$user['id'],
                    $call['name'],$call['arguments'],'Erro de ferramenta',0,$duration,'ERRO',$e->getMessage()
                );
            }

            $serialized=json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            if($serialized===false)$serialized='{"ok":false,"erro":"Falha ao serializar resultado."}';
            if(strlen($serialized)>50000){
                $serialized=json_encode([
                    'ok'=>false,
                    'erro'=>'Resultado grande demais. Refine a consulta ou reduza o período.'
                ],JSON_UNESCAPED_UNICODE);
            }

            $input[]=[
                'type'=>'function_call_output',
                'call_id'=>$call['call_id'],
                'output'=>$serialized,
            ];
        }
    }
}

function geop_ai_chat_answer_groq(
    PDO $pdo,
    array $user,
    int $conversationId,
    int $userMessageId,
    array $history
): array {
    geop_ai_assert_available($pdo);

    $primary=geop_ai_groq_model($pdo);
    $fallback=geop_ai_groq_fallback_model($pdo);

    try{
        return geop_ai_chat_answer_groq_model(
            $pdo,$user,$conversationId,$userMessageId,$history,$primary
        );
    }catch(GeopAiGroqRateLimitException $e){
        // Não espera o retry-after dentro da requisição PHP. Troca imediatamente
        // para outro modelo, que possui limite por modelo separado na Groq.
        if($fallback!=='' && $fallback!==$primary){
            geop_ai_execution_log(
                $pdo,$conversationId,$userMessageId,(int)$user['id'],
                'groq_rate_limit_fallback',
                [
                    'modelo_principal'=>$primary,
                    'modelo_fallback'=>$fallback,
                    'retry_after'=>$e->retryAfterSeconds,
                ],
                'Rate limit no modelo principal; fallback automático.',
                0,0,'OK',null
            );

            $result=geop_ai_chat_answer_groq_model(
                $pdo,$user,$conversationId,$userMessageId,$history,$fallback
            );
            $result['fallback_usado']=true;
            $result['fallback_de']=$primary;
            $result['retry_after']=$e->retryAfterSeconds;
            return $result;
        }

        $retry=$e->retryAfterSeconds>0?' Tente novamente em aproximadamente '.$e->retryAfterSeconds.' segundos.':'';
        throw new RuntimeException('Limite temporário da Groq atingido.'.$retry);
    }
}
