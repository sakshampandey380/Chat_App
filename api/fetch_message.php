<?php

require_once __DIR__ . '/../auth/auth_check.php';

$chatType = $_GET['type'] ?? 'direct';
$targetId = (int) ($_GET['id'] ?? $_GET['user_id'] ?? 0);

if ($targetId <= 0) {
    json_response([
        'success' => false,
        'message' => 'Invalid conversation selected.',
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
$messages = [];

if ($conversation) {
    mark_messages_seen((int) $conversation['id'], (int) $currentUser['id']);
    $messages = array_map(
        fn(array $message): array => message_payload($message, (int) $currentUser['id']),
        get_messages_for_conversation((int) $conversation['id'])
    );
}

json_response([
    'success' => true,
    'conversation_id' => $conversation ? (int) $conversation['id'] : null,
    'selected_user' => $selectedChat,
    'messages' => $messages,
]);
