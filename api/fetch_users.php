<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activeUserId = isset($_GET['active_user_id']) ? (int) $_GET['active_user_id'] : 0;
$activeChatType = $_GET['active_chat_type'] ?? 'direct';
$roster = array_map('roster_payload', get_chat_roster((int) $currentUser['id']));

json_response([
    'success' => true,
    'active_chat_type' => $activeChatType,
    'active_user_id' => $activeUserId,
    'users' => $roster,
]);
