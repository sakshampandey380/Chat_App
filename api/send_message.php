<?php

require_once __DIR__ . '/../auth/auth_check.php';

if (!is_post()) {
    json_response([
        'success' => false,
        'message' => 'Method not allowed.',
    ], 405);
}

$chatType = $_POST['chat_type'] ?? 'direct';
$targetId = (int) ($_POST['target_id'] ?? $_POST['receiver_id'] ?? 0);
$messageText = trim($_POST['message'] ?? '');

if ($targetId <= 0) {
    json_response([
        'success' => false,
        'message' => 'Choose a valid chat target.',
    ], 422);
}

try {
    $mediaPath = store_uploaded_file(
        $_FILES['media'] ?? [],
        'uploads/media',
        ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
    );

    if ($chatType === 'group') {
        if (!user_is_group_member($targetId, (int) $currentUser['id'])) {
            throw new RuntimeException('You are not a member of this group.');
        }

        $group = get_group_by_id($targetId);
        if (!$group) {
            throw new RuntimeException('Group not found.');
        }

        $conversation = get_conversation_by_id((int) $group['conversation_id']);
    } else {
        if ($targetId === (int) $currentUser['id']) {
            throw new RuntimeException('Choose a valid receiver.');
        }

        $receiver = get_user_by_id($targetId);
        if (!$receiver) {
            throw new RuntimeException('Receiver not found.');
        }

        $conversation = get_or_create_conversation((int) $currentUser['id'], $targetId);
    }

    if (!$conversation) {
        throw new RuntimeException('Conversation could not be resolved.');
    }

    $message = send_chat_message((int) $conversation['id'], (int) $currentUser['id'], $messageText, $mediaPath);

    json_response([
        'success' => true,
        'message' => message_payload($message, (int) $currentUser['id']),
        'conversation_id' => (int) $conversation['id'],
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 422);
}
