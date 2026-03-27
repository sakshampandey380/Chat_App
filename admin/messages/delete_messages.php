<?php

require_once __DIR__ . '/../auth/auth_check.php';

$messageId = (int) ($_GET['id'] ?? 0);
$conversationId = (int) ($_GET['conversation_id'] ?? 0);

$statement = db()->prepare('DELETE FROM messages WHERE id = :id');
$statement->execute(['id' => $messageId]);

$latestMessageStmt = db()->prepare(
    'SELECT message, created_at
     FROM messages
     WHERE conversation_id = :conversation_id
     ORDER BY created_at DESC, id DESC
     LIMIT 1'
);
$latestMessageStmt->execute(['conversation_id' => $conversationId]);
$latestMessage = $latestMessageStmt->fetch();

$updateConversation = db()->prepare(
    'UPDATE conversations
     SET last_message = :last_message, last_message_time = :last_message_time
     WHERE id = :id'
);
$updateConversation->execute([
    'last_message' => $latestMessage['message'] ?? null,
    'last_message_time' => $latestMessage['created_at'] ?? null,
    'id' => $conversationId,
]);

log_admin_activity((int) $adminCurrent['id'], 'Deleted message #' . $messageId . ' from conversation #' . $conversationId);
set_flash('success', 'Message deleted successfully.');
admin_redirect('messages/view_chat.php?id=' . $conversationId);
