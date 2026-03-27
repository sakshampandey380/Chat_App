<?php

require_once __DIR__ . '/../auth/auth_check.php';

$userId = (int) ($_GET['id'] ?? 0);
$user = get_user_by_id($userId);

if (!$user) {
    set_flash('error', 'User not found.');
    admin_redirect('users/user_list.php');
}

$statement = db()->prepare('DELETE FROM users WHERE id = :id');
$statement->execute(['id' => $userId]);

log_admin_activity((int) $adminCurrent['id'], 'Deleted user #' . $userId);
set_flash('success', 'User deleted successfully.');
admin_redirect('users/user_list.php');
