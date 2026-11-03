<?php
// public/chat_api.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/chat_core.php';
require_once __DIR__ . '/../includes/upload.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

$user = current_user();
$me = (int)($user['id'] ?? 0);
$action = (string)($_REQUEST['action'] ?? '');

function chat_json(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function chat_profile_label_api(?string $perfil): string {
    return function_exists('profile_label') ? profile_label($perfil) : (string)$perfil;
}

function chat_total_unread_api(PDO $pdo, int $me): int {
    $st = $pdo->prepare("SELECT COUNT(*) FROM chat_mensagens WHERE destinatario_id = ? AND lida_em IS NULL AND excluida_em IS NULL");
    $st->execute([$me]);
    $total = (int)$st->fetchColumn();
    $st = $pdo->prepare("SELECT COUNT(*)
        FROM chat_mensagens m
        JOIN chat_grupo_participantes gp ON gp.conversa_id=m.conversa_id AND gp.usuario_id=?
        LEFT JOIN chat_mensagem_leituras ml ON ml.mensagem_id=m.id AND ml.usuario_id=?
        WHERE m.destinatario_id=0 AND m.remetente_id<>? AND m.excluida_em IS NULL AND ml.mensagem_id IS NULL");
    $st->execute([$me,$me,$me]);
    return $total + (int)$st->fetchColumn();
}

function chat_groups_payload_api(PDO $pdo, int $me): array {
    $st = $pdo->prepare("SELECT c.id,c.nome,c.criador_id,c.atualizado_em,
        (SELECT COUNT(*) FROM chat_grupo_participantes p WHERE p.conversa_id=c.id) participantes,
        (SELECT COUNT(*) FROM chat_mensagens m
          LEFT JOIN chat_mensagem_leituras ml ON ml.mensagem_id=m.id AND ml.usuario_id=?
          WHERE m.conversa_id=c.id AND m.remetente_id<>? AND m.excluida_em IS NULL AND ml.mensagem_id IS NULL) nao_lidas
        FROM chat_conversas c
        JOIN chat_grupo_participantes gp ON gp.conversa_id=c.id AND gp.usuario_id=?
        WHERE c.tipo='GRUPO' ORDER BY c.atualizado_em DESC,c.nome");
    $st->execute([$me,$me,$me]);
    return array_map(static function(array $row): array {
        $row['id'] = (int)$row['id'];
        $row['criador_id'] = (int)$row['criador_id'];
        $row['participantes'] = (int)$row['participantes'];
        $row['nao_lidas'] = (int)$row['nao_lidas'];
        return $row;
    }, $st->fetchAll(PDO::FETCH_ASSOC) ?: []);
}

function chat_group_access_api(PDO $pdo, int $conversationId, int $me): bool {
    $st = $pdo->prepare("SELECT 1 FROM chat_grupo_participantes
        WHERE conversa_id=? AND usuario_id=? LIMIT 1");
    $st->execute([$conversationId,$me]);
    return (bool)$st->fetchColumn();
}

function chat_pending_notifications_api(PDO $pdo, int $me): array {
    $st = $pdo->prepare("SELECT m.id,m.conversa_id,m.remetente_id,
        CASE WHEN m.visualizacao_unica=1 THEN 'Mensagem de visualização única' ELSE m.mensagem END mensagem,
        m.anexo_nome,
        u.nome remetente_nome,c.tipo,c.nome grupo_nome
        FROM chat_mensagens m
        JOIN usuarios u ON u.id=m.remetente_id
        JOIN chat_conversas c ON c.id=m.conversa_id
        LEFT JOIN chat_grupo_participantes gp ON gp.conversa_id=c.id AND gp.usuario_id=?
        LEFT JOIN chat_mensagem_leituras ml ON ml.mensagem_id=m.id AND ml.usuario_id=?
        WHERE m.remetente_id<>? AND m.excluida_em IS NULL AND (
          (c.tipo='DIRETA' AND m.destinatario_id=? AND m.lida_em IS NULL)
          OR (c.tipo='GRUPO' AND gp.usuario_id IS NOT NULL AND ml.mensagem_id IS NULL)
        ) ORDER BY m.id DESC LIMIT 20");
    $st->execute([$me,$me,$me,$me]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function chat_users_payload_api(PDO $pdo, int $me): array {
    $st = $pdo->prepare("SELECT id,nome,email,perfil
        FROM usuarios
        WHERE id<>? AND status='ATIVO'
        ORDER BY nome");
    $st->execute([$me]);
    $users = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $unread = [];
    $st = $pdo->prepare("SELECT remetente_id,COUNT(*) total
        FROM chat_mensagens WHERE destinatario_id=? AND lida_em IS NULL AND excluida_em IS NULL GROUP BY remetente_id");
    $st->execute([$me]);
    foreach ($st->fetchAll() as $row) $unread[(int)$row['remetente_id']] = (int)$row['total'];

    $last = [];
    $st = $pdo->prepare("SELECT
        CASE WHEN remetente_id=? THEN destinatario_id ELSE remetente_id END outro_id,
        MAX(criado_em) ultima_msg
        FROM chat_mensagens
        WHERE remetente_id=? OR destinatario_id=?
        GROUP BY CASE WHEN remetente_id=? THEN destinatario_id ELSE remetente_id END");
    $st->execute([$me,$me,$me,$me]);
    foreach ($st->fetchAll() as $row) $last[(int)$row['outro_id']] = (string)$row['ultima_msg'];

    foreach ($users as &$u) {
        $u['id'] = (int)$u['id'];
        $u['nao_lidas'] = $unread[$u['id']] ?? 0;
        $u['ultima_msg'] = $last[$u['id']] ?? '';
        $u['perfil_label'] = chat_profile_label_api($u['perfil'] ?? '');
    }
    unset($u);
    usort($users, static function(array $a, array $b): int {
        return ($b['nao_lidas'] <=> $a['nao_lidas'])
            ?: strcmp($b['ultima_msg'], $a['ultima_msg'])
            ?: strcasecmp($a['nome'], $b['nome']);
    });

    return $users;
}

function chat_user_exists_api(PDO $pdo, int $userId): bool {
    $st = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND status = 'ATIVO' LIMIT 1");
    $st->execute([$userId]);
    return (bool)$st->fetchColumn();
}

function chat_messages_payload_api(PDO $pdo, int $conversaId, int $beforeId = 0, int $viewerId = 0): array {
    $st = $pdo->prepare("
        SELECT
            m.id,
            m.remetente_id,
            m.destinatario_id,
            m.mensagem,
            m.lida_em,
            m.editada_em,
            m.excluida_em,
            m.anexo_url,
            m.anexo_nome,
            m.anexo_mime,
            m.anexo_tamanho,
            m.visualizacao_unica,
            m.resposta_a_id,
            m.encaminhada_de_id,
            CASE
              WHEN r.id IS NULL THEN NULL
              WHEN r.excluida_em IS NOT NULL THEN 'Mensagem apagada'
              WHEN r.visualizacao_unica=1 THEN 'Mensagem de visualização única'
              ELSE LEFT(r.mensagem,240)
            END AS resposta_texto,
            ru.nome AS resposta_autor,
            EXISTS(SELECT 1 FROM chat_mensagem_visualizacoes vu
                WHERE vu.mensagem_id=m.id AND vu.usuario_id=?) AS visualizada_por_mim,
            u.nome AS remetente_nome,
            (m.criado_em >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)) AS pode_alterar,
            DATE_FORMAT(m.criado_em, '%d/%m/%Y %H:%i') AS criado_em
        FROM chat_mensagens m
        JOIN usuarios u ON u.id=m.remetente_id
        LEFT JOIN chat_mensagens r ON r.id=m.resposta_a_id
        LEFT JOIN usuarios ru ON ru.id=r.remetente_id
        WHERE m.conversa_id = ?
        " . ($beforeId > 0 ? "AND m.id < ?" : "") . "
        ORDER BY m.id DESC
        LIMIT 200
    ");
    $params = [$viewerId, $conversaId];
    if ($beforeId > 0) $params[] = $beforeId;
    $st->execute($params);
    $messages = array_reverse($st->fetchAll(PDO::FETCH_ASSOC) ?: []);
    foreach ($messages as &$message) {
        $message['id'] = (int)$message['id'];
        $message['remetente_id'] = (int)$message['remetente_id'];
        $message['destinatario_id'] = (int)$message['destinatario_id'];
        $message['anexo_tamanho'] = (int)($message['anexo_tamanho'] ?? 0);
        $message['pode_alterar'] = (bool)$message['pode_alterar'];
        $message['visualizacao_unica'] = (bool)$message['visualizacao_unica'];
        $message['visualizada_por_mim'] = (bool)$message['visualizada_por_mim'];
        $message['resposta_a_id'] = (int)($message['resposta_a_id'] ?? 0);
        $message['encaminhada_de_id'] = (int)($message['encaminhada_de_id'] ?? 0);
        if ($message['visualizacao_unica']) {
            $message['mensagem'] = '';
        }
        if (!empty($message['anexo_url'])) {
            $message['anexo_url'] = app_path((string)$message['anexo_url']);
        }
        if (!empty($message['excluida_em'])) {
            $message['mensagem'] = '';
            $message['anexo_url'] = null;
            $message['anexo_nome'] = null;
            $message['anexo_mime'] = null;
            $message['anexo_tamanho'] = 0;
        }
    }
    unset($message);
    return $messages;
}

try {
    if ($me <= 0) {
        http_response_code(401);
        chat_json(['ok' => false, 'error' => 'Usuário não autenticado.']);
    }

    chat_ensure_schema($pdo);
    if (!chat_is_enabled($pdo)) {
        http_response_code(503);
        chat_json(['ok'=>false,'disabled'=>true,'error'=>'O chat foi desabilitado pelo TI.']);
    }

    if ($action === 'users') {
        chat_json([
            'ok' => true,
            'users' => chat_users_payload_api($pdo, $me),
            'groups' => chat_groups_payload_api($pdo, $me),
            'notifications' => chat_pending_notifications_api($pdo, $me),
            'total_unread' => chat_total_unread_api($pdo, $me),
        ]);
    }

    if ($action === 'messages') {
        $other = (int)($_GET['user_id'] ?? 0);
        $requestedConversation = (int)($_GET['conversation_id'] ?? 0);

        if ($requestedConversation > 0) {
            if (!chat_group_access_api($pdo, $requestedConversation, $me)) {
                chat_json(['ok'=>false,'error'=>'Grupo não encontrado ou acesso negado.']);
            }
            $conversaId = $requestedConversation;
            $st = $pdo->prepare("INSERT IGNORE INTO chat_mensagem_leituras(mensagem_id,usuario_id,lida_em)
                SELECT id,?,NOW() FROM chat_mensagens
                WHERE conversa_id=? AND remetente_id<>? AND excluida_em IS NULL");
            $st->execute([$me,$conversaId,$me]);
            chat_json([
                'ok'=>true,
                'conversa_id'=>$conversaId,
                'messages'=>chat_messages_payload_api($pdo,$conversaId,max(0,(int)($_GET['before_id'] ?? 0)),$me),
                'users'=>chat_users_payload_api($pdo,$me),
                'groups'=>chat_groups_payload_api($pdo,$me),
                'total_unread'=>chat_total_unread_api($pdo,$me),
            ]);
        }

        if ($other <= 0 || $other === $me) {
            chat_json(['ok' => false, 'error' => 'Destinatário inválido.']);
        }

        if (!chat_user_exists_api($pdo, $other)) {
            chat_json(['ok' => false, 'error' => 'Usuário não encontrado.']);
        }

        $conversaId = chat_find_conversa($pdo, $me, $other);

        if ($conversaId > 0) {
            $st = $pdo->prepare("
                UPDATE chat_mensagens SET lida_em=NOW()
                WHERE conversa_id=? AND destinatario_id=? AND lida_em IS NULL
            ");
            $st->execute([$conversaId, $me]);
        }

        chat_json([
            'ok' => true,
            'conversa_id' => $conversaId,
            'messages' => $conversaId > 0 ? chat_messages_payload_api($pdo, $conversaId, max(0, (int)($_GET['before_id'] ?? 0)), $me) : [],
            'users' => chat_users_payload_api($pdo, $me),
            'groups' => chat_groups_payload_api($pdo, $me),
            'total_unread' => chat_total_unread_api($pdo, $me),
        ]);
    }

    if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $other = (int)($_POST['user_id'] ?? 0);
        $groupConversation = (int)($_POST['conversation_id'] ?? 0);
        $message = trim((string)($_POST['message'] ?? ''));
        $viewOnce = !empty($_POST['view_once']);
        $replyToId = max(0, (int)($_POST['reply_to_id'] ?? 0));
        $hasAttachment = isset($_FILES['attachment'])
            && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($groupConversation > 0) {
            if (!chat_group_access_api($pdo,$groupConversation,$me)) {
                chat_json(['ok'=>false,'error'=>'Grupo não encontrado ou acesso negado.']);
            }
            $other = 0;
        } elseif ($other <= 0 || $other === $me) {
            chat_json(['ok' => false, 'error' => 'Destinatário inválido.']);
        }

        if ($message === '' && !$hasAttachment) {
            chat_json(['ok' => false, 'error' => 'Digite uma mensagem ou selecione um anexo.']);
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($message, 'UTF-8') > 2000) {
                chat_json(['ok' => false, 'error' => 'Mensagem muito longa. Limite de 2000 caracteres.']);
            }
        } elseif (strlen($message) > 4000) {
            chat_json(['ok' => false, 'error' => 'Mensagem muito longa.']);
        }

        if ($groupConversation <= 0 && !chat_user_exists_api($pdo, $other)) {
            chat_json(['ok' => false, 'error' => 'Usuário não encontrado.']);
        }

        $attachment = ['url'=>null,'name'=>null,'mime'=>null,'size'=>null,'path'=>null];
        if ($hasAttachment) {
            try {
                $stored = upload_store(
                    $_FILES['attachment'],
                    __DIR__ . '/uploads/chat',
                    'chat_' . $me . '_'
                );
            } catch (RuntimeException $e) {
                chat_json(['ok'=>false,'error'=>$e->getMessage()]);
            }
            $attachment = [
                'url' => 'uploads/chat/' . $stored['name'],
                'name' => substr(basename((string)($_FILES['attachment']['name'] ?? 'anexo')), 0, 255),
                'mime' => $stored['mime'],
                'size' => $stored['size'],
                'path' => $stored['path'],
            ];
        }
        if ($viewOnce && ($message === '' || $hasAttachment)) {
            chat_json(['ok'=>false,'error'=>'A visualização única está disponível somente para mensagens de texto.']);
        }

        $pdo->beginTransaction();

        $conversaId = $groupConversation > 0 ? $groupConversation : chat_get_or_create_conversa($pdo, $me, $other);

        if ($replyToId > 0) {
            $st = $pdo->prepare("SELECT id FROM chat_mensagens
                WHERE id=? AND conversa_id=? AND excluida_em IS NULL LIMIT 1");
            $st->execute([$replyToId,$conversaId]);
            if (!$st->fetchColumn()) {
                throw new RuntimeException('A mensagem selecionada para resposta não está disponível.');
            }
        }

        $st = $pdo->prepare("
            INSERT INTO chat_mensagens
            (conversa_id, remetente_id, destinatario_id, mensagem, visualizacao_unica, resposta_a_id,
             anexo_url, anexo_nome, anexo_mime, anexo_tamanho, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $st->execute([
            $conversaId, $me, $other, $message, $viewOnce ? 1 : 0, $replyToId ?: null,
            $attachment['url'], $attachment['name'], $attachment['mime'], $attachment['size']
        ]);
        $mensagemId = (int)$pdo->lastInsertId();

        $st = $pdo->prepare("
            UPDATE chat_conversas
            SET atualizado_em = NOW()
            WHERE id = ?
        ");
        $st->execute([$conversaId]);

        $pdo->commit();

        chat_json([
            'ok' => true,
            'id' => $mensagemId,
            'conversa_id' => $conversaId,
            'messages' => chat_messages_payload_api($pdo, $conversaId, 0, $me),
            'users' => chat_users_payload_api($pdo, $me),
            'groups' => chat_groups_payload_api($pdo, $me),
            'total_unread' => chat_total_unread_api($pdo, $me),
        ]);
    }

    if ($action === 'create_group' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $name = trim((string)($_POST['name'] ?? ''));
        $memberIds = json_decode((string)($_POST['member_ids'] ?? '[]'), true);
        if ($name === '' || (function_exists('mb_strlen') ? mb_strlen($name,'UTF-8') : strlen($name)) > 120) {
            chat_json(['ok'=>false,'error'=>'Informe um nome de grupo com até 120 caracteres.']);
        }
        if (!is_array($memberIds)) $memberIds = [];
        $memberIds = array_values(array_unique(array_filter(array_map('intval',$memberIds), static fn(int $id): bool => $id > 0 && $id !== $me)));
        if (!$memberIds) {
            chat_json(['ok'=>false,'error'=>'Selecione pelo menos um participante.']);
        }
        $placeholders = implode(',',array_fill(0,count($memberIds),'?'));
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE status='ATIVO' AND id IN ($placeholders)");
        $st->execute($memberIds);
        $validMembers = array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN));
        if (count($validMembers) !== count($memberIds)) {
            chat_json(['ok'=>false,'error'=>'Um dos participantes é inválido.']);
        }
        $pdo->beginTransaction();
        do {
            $negativePair = -random_int(1,2147483647);
            $st = $pdo->prepare("SELECT 1 FROM chat_conversas WHERE usuario_1_id=0 AND usuario_2_id=?");
            $st->execute([$negativePair]);
        } while ($st->fetchColumn());
        $st = $pdo->prepare("INSERT INTO chat_conversas
            (usuario_1_id,usuario_2_id,tipo,nome,criador_id,criado_em,atualizado_em)
            VALUES(0,?,'GRUPO',?,?,NOW(),NOW())");
        $st->execute([$negativePair,$name,$me]);
        $conversationId = (int)$pdo->lastInsertId();
        $insert = $pdo->prepare("INSERT INTO chat_grupo_participantes(conversa_id,usuario_id) VALUES(?,?)");
        $insert->execute([$conversationId,$me]);
        foreach ($validMembers as $memberId) $insert->execute([$conversationId,$memberId]);
        $pdo->commit();
        chat_json(['ok'=>true,'conversation_id'=>$conversationId,'groups'=>chat_groups_payload_api($pdo,$me)]);
    }

    if ($action === 'forward' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $messageId = (int)($_POST['message_id'] ?? 0);
        $targetUser = (int)($_POST['user_id'] ?? 0);
        $targetGroup = (int)($_POST['conversation_id'] ?? 0);
        $st = $pdo->prepare("SELECT m.id,m.mensagem,m.remetente_id,m.destinatario_id,
            m.conversa_id,m.visualizacao_unica,m.excluida_em,c.tipo
            FROM chat_mensagens m JOIN chat_conversas c ON c.id=m.conversa_id
            WHERE m.id=? LIMIT 1");
        $st->execute([$messageId]);
        $source = $st->fetch(PDO::FETCH_ASSOC);
        if (!$source || !empty($source['excluida_em']) || !empty($source['visualizacao_unica'])
            || trim((string)$source['mensagem']) === '') {
            chat_json(['ok'=>false,'error'=>'Esta mensagem não pode ser encaminhada.']);
        }
        $sourceAllowed = $source['tipo'] === 'GRUPO'
            ? chat_group_access_api($pdo,(int)$source['conversa_id'],$me)
            : ((int)$source['remetente_id']===$me || (int)$source['destinatario_id']===$me);
        if (!$sourceAllowed) chat_json(['ok'=>false,'error'=>'Acesso negado.']);

        if ($targetGroup > 0) {
            if (!chat_group_access_api($pdo,$targetGroup,$me)) {
                chat_json(['ok'=>false,'error'=>'Grupo de destino inválido.']);
            }
            $targetConversation = $targetGroup;
            $recipientId = 0;
        } else {
            if ($targetUser <= 0 || $targetUser === $me || !chat_user_exists_api($pdo,$targetUser)) {
                chat_json(['ok'=>false,'error'=>'Destinatário inválido.']);
            }
            $targetConversation = chat_get_or_create_conversa($pdo,$me,$targetUser);
            $recipientId = $targetUser;
        }
        $pdo->beginTransaction();
        $st = $pdo->prepare("INSERT INTO chat_mensagens
            (conversa_id,remetente_id,destinatario_id,mensagem,encaminhada_de_id,criado_em)
            VALUES(?,?,?,?,?,NOW())");
        $st->execute([$targetConversation,$me,$recipientId,(string)$source['mensagem'],$messageId]);
        $pdo->prepare("UPDATE chat_conversas SET atualizado_em=NOW() WHERE id=?")
            ->execute([$targetConversation]);
        $pdo->commit();
        chat_json(['ok'=>true]);
    }

    if ($action === 'view_once' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $messageId = (int)($_POST['message_id'] ?? 0);
        $st = $pdo->prepare("SELECT m.mensagem,m.remetente_id,m.destinatario_id,m.conversa_id,c.tipo
            FROM chat_mensagens m JOIN chat_conversas c ON c.id=m.conversa_id
            WHERE m.id=? AND m.visualizacao_unica=1 AND m.excluida_em IS NULL LIMIT 1");
        $st->execute([$messageId]);
        $oneTimeMessage = $st->fetch(PDO::FETCH_ASSOC);
        if (!$oneTimeMessage || (int)$oneTimeMessage['remetente_id'] === $me) {
            chat_json(['ok'=>false,'error'=>'Esta mensagem não está disponível para visualização.']);
        }
        $allowed = $oneTimeMessage['tipo'] === 'GRUPO'
            ? chat_group_access_api($pdo,(int)$oneTimeMessage['conversa_id'],$me)
            : (int)$oneTimeMessage['destinatario_id'] === $me;
        if (!$allowed) {
            chat_json(['ok'=>false,'error'=>'Acesso negado.']);
        }
        $st = $pdo->prepare("INSERT IGNORE INTO chat_mensagem_visualizacoes
            (mensagem_id,usuario_id,visualizada_em) VALUES(?,?,NOW())");
        $st->execute([$messageId,$me]);
        if ($st->rowCount() !== 1) {
            chat_json(['ok'=>false,'viewed'=>true,'error'=>'Esta mensagem já foi visualizada.']);
        }
        chat_json(['ok'=>true,'message'=>(string)$oneTimeMessage['mensagem']]);
    }

    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $messageId = (int)($_POST['message_id'] ?? 0);
        $message = trim((string)($_POST['message'] ?? ''));
        if ($messageId <= 0 || $message === '') {
            chat_json(['ok'=>false,'error'=>'Mensagem inválida.']);
        }
        $length = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);
        if ($length > 2000) {
            chat_json(['ok'=>false,'error'=>'Mensagem muito longa. Limite de 2000 caracteres.']);
        }
        $st = $pdo->prepare("UPDATE chat_mensagens
            SET mensagem=?, editada_em=NOW()
            WHERE id=? AND remetente_id=? AND excluida_em IS NULL
              AND criado_em >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $st->execute([$message, $messageId, $me]);
        if ($st->rowCount() !== 1) {
            chat_json(['ok'=>false,'error'=>'A mensagem só pode ser editada pelo autor durante os primeiros 5 minutos.']);
        }
        chat_json(['ok'=>true]);
    }

    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        check_csrf();
        $messageId = (int)($_POST['message_id'] ?? 0);
        $st = $pdo->prepare("SELECT anexo_url FROM chat_mensagens
            WHERE id=? AND remetente_id=? AND excluida_em IS NULL
              AND criado_em >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1");
        $st->execute([$messageId, $me]);
        $attachmentUrl = $st->fetchColumn();
        if ($attachmentUrl === false) {
            chat_json(['ok'=>false,'error'=>'A mensagem só pode ser apagada pelo autor durante os primeiros 5 minutos.']);
        }
        $st = $pdo->prepare("UPDATE chat_mensagens SET mensagem='', anexo_url=NULL,
            anexo_nome=NULL, anexo_mime=NULL, anexo_tamanho=NULL,
            excluida_em=NOW(), lida_em=COALESCE(lida_em,NOW())
            WHERE id=? AND remetente_id=? AND excluida_em IS NULL
              AND criado_em >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $st->execute([$messageId, $me]);
        if ($attachmentUrl) {
            $chatUploadRoot = realpath(__DIR__ . '/uploads/chat');
            $attachmentPath = realpath(__DIR__ . '/' . ltrim((string)$attachmentUrl, '/\\'));
            if ($chatUploadRoot && $attachmentPath
                && str_starts_with($attachmentPath, $chatUploadRoot . DIRECTORY_SEPARATOR)
                && is_file($attachmentPath)) {
                @unlink($attachmentPath);
            }
        }
        chat_json(['ok'=>true]);
    }

    chat_json(['ok' => false, 'error' => 'Ação inválida.']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($attachment['path']) && is_string($attachment['path']) && is_file($attachment['path'])) {
        @unlink($attachment['path']);
    }

    error_log('[GEOP chat][' . (defined('GEOP_REQUEST_ID') ? GEOP_REQUEST_ID : '-') . '] ' . $e->getMessage());
    http_response_code(500);
    chat_json(['ok' => false, 'error' => 'Não foi possível acessar o chat. Atualize a página ou contate o TI.']);
}
