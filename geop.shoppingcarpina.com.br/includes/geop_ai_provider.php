<?php
declare(strict_types=1);

require_once __DIR__ . '/geop_ai_openai.php';
require_once __DIR__ . '/geop_ai_ollama.php';
require_once __DIR__ . '/geop_ai_groq.php';

function geop_ai_chat_answer(
    PDO $pdo,
    array $user,
    int $conversationId,
    int $userMessageId,
    array $history
): array {
    $provider = geop_ai_provider($pdo);

    if ($provider === 'OPENAI') {
        return geop_ai_chat_answer_openai($pdo, $user, $conversationId, $userMessageId, $history);
    }

    if ($provider === 'OLLAMA') {
        return geop_ai_chat_answer_ollama($pdo, $user, $conversationId, $userMessageId, $history);
    }

    return geop_ai_chat_answer_groq($pdo, $user, $conversationId, $userMessageId, $history);
}
