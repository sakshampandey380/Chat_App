<?php

require_once __DIR__ . '/../auth/auth_check.php';

$status = trim($_POST['status'] ?? 'online');
$allowedStatuses = ['online', 'away', 'offline'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = 'online';
}

update_user_status((int) $currentUser['id'], $status);

json_response([
    'success' => true,
    'status' => $status,
]);
