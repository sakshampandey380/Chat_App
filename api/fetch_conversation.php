<?php

require_once __DIR__ . '/../auth/auth_check.php';

$chatType = $_GET['type'] ?? 'direct';
$targetId = (int) ($_GET['id'] ?? $_GET['user_id'] ?? 0);

if ($targetId <= 0) {
    json_response([
        'success' => false,
        'message' => 'Invalid chat selected.',
    ], 422);
}

$selectedChat = get_chat_view((int) $currentUser['id'], $chatType, $targetId);

if (!$selectedChat) {
    json_response([
        'success' => false,
        'message' => 'Chat target not found.',
    ], 404);
}
$conversation = !empty($selectedChat['conversation_id']) ? get_conversation_by_id((int) $selectedChat['conversation_id']) : null;

json_response([
    'success' => true,
    'conversation' => [
        'id' => $conversation ? (int) $conversation['id'] : null,
        'last_message' => $conversation['last_message'] ?? null,
        'last_message_time' => $conversation['last_message_time'] ?? null,
    ],
    'selected_user' => $selectedChat,
]);
