<?php

require_once __DIR__ . '/../auth/auth_check.php';

$chatType = $_POST['type'] ?? $_GET['type'] ?? 'direct';
$targetId = (int) ($_POST['id'] ?? $_GET['id'] ?? $_POST['user_id'] ?? $_GET['user_id'] ?? 0);

if ($targetId <= 0) {
    json_response([
        'success' => false,
        'message' => 'No conversation selected.',
    ], 422);
}

$selectedChat = get_chat_view((int) $currentUser['id'], $chatType, $targetId);
$conversation = $selectedChat && !empty($selectedChat['conversation_id'])
    ? get_conversation_by_id((int) $selectedChat['conversation_id'])
    : null;

if ($conversation) {
    mark_messages_seen((int) $conversation['id'], (int) $currentUser['id']);
}

json_response([
    'success' => true,
]);
