<?php
declare(strict_types=1);

require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/geop_ai_provider.php';

require_login();
geop_v8_boot($pdo);
geop_ai_schema($pdo);

header('Content-Type: application/json; charset=utf-8');

function ai_json(array $data,int $status=200): never {
    http_response_code($status);
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try{
    $user=current_user();
    if(!geop_ai_access_allowed($user)) ai_json(['ok'=>false,'erro'=>'Acesso negado.'],403);

    $payload=json_decode(file_get_contents('php://input')?:'{}',true);
    if(!is_array($payload))$payload=[];

    $csrfHeader=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');
    $csrfBody=(string)($payload['csrf']??'');
    $sessionCsrf=(string)($_SESSION['csrf']??'');
    if($sessionCsrf==='' || (!hash_equals($sessionCsrf,$csrfHeader) && !hash_equals($sessionCsrf,$csrfBody))){
        ai_json(['ok'=>false,'erro'=>'Sessão/CSRF inválido. Atualize a página.'],419);
    }

    $action=(string)($payload['action']??'');

    if($action==='nova_conversa'){
        $id=geop_ai_conversation_create($pdo,(int)$user['id']);
        ai_json(['ok'=>true,'conversa_id'=>$id]);
    }

    if($action==='perguntar'){
        geop_ai_assert_available($pdo);
        $limit=geop_ai_check_daily_limit($pdo,(int)$user['id']);
        if(!$limit['ok']) ai_json(['ok'=>false,'erro'=>'Seu limite diário de perguntas foi atingido.'],429);

        $question=trim((string)($payload['pergunta']??''));
        if($question==='') ai_json(['ok'=>false,'erro'=>'Digite uma pergunta.'],422);
        if(mb_strlen($question,'UTF-8')>4000) ai_json(['ok'=>false,'erro'=>'Pergunta muito longa. Limite: 4.000 caracteres.'],422);

        $conversationId=(int)($payload['conversa_id']??0);
        if($conversationId<=0){
            $conversationId=geop_ai_conversation_create($pdo,(int)$user['id']);
        }
        if(!geop_ai_conversation_owned($pdo,$conversationId,(int)$user['id'])){
            ai_json(['ok'=>false,'erro'=>'Conversa não encontrada.'],404);
        }

        $history=geop_ai_history_for_model($pdo,$conversationId,(int)$user['id']);
        $userMessageId=geop_ai_message_add($pdo,$conversationId,(int)$user['id'],'USER',$question);
        geop_ai_maybe_title_conversation($pdo,$conversationId,$question);

        // Add current question after prior history.
        $history[]=['role'=>'user','content'=>$question];

        $result=geop_ai_chat_answer($pdo,$user,$conversationId,$userMessageId,$history);

        geop_ai_message_add(
            $pdo,$conversationId,(int)$user['id'],'ASSISTANT',$result['texto'],$result['fontes'],
            $result['modelo'],$result['input_tokens'],$result['output_tokens'],$result['latencia_ms']
        );

        geop_v8_security_event(
            $pdo,(int)$user['id'],'GEOP_IA_CONSULTA',
            'Conversa #'.$conversationId.' | fontes: '.count($result['fontes'])
        );

        ai_json([
            'ok'=>true,
            'conversa_id'=>$conversationId,
            'resposta'=>$result['texto'],
            'fontes'=>$result['fontes'],
            'modelo'=>$result['modelo'],
            'latencia_ms'=>$result['latencia_ms'],
            'fallback_usado'=>!empty($result['fallback_usado']),
            'fallback_de'=>$result['fallback_de']??null,
            'retry_after'=>$result['retry_after']??null,
        ]);
    }

    ai_json(['ok'=>false,'erro'=>'Ação inválida.'],400);
}catch(Throwable $e){
    error_log('[GEOP IA] '.$e->getMessage());
    ai_json(['ok'=>false,'erro'=>$e->getMessage()],500);
}
