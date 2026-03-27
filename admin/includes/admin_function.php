<?php

require_once __DIR__ . '/../../includes/functions.php';

function admin_base_path(string $path = ''): string
{
    $base = rtrim(base_path('admin'), '/');

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function admin_redirect(string $path): void
{
    header('Location: ' . admin_base_path($path));
    exit;
}

function current_admin_id(): ?int
{
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
}

function get_admin_by_id(int $adminId): ?array
{
    $statement = db()->prepare('SELECT * FROM admins WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $adminId]);
    $admin = $statement->fetch();

    return $admin ?: null;
}

function get_admin_by_email(string $email): ?array
{
    $statement = db()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $admin = $statement->fetch();

    return $admin ?: null;
}

function current_admin(): ?array
{
    $adminId = current_admin_id();

    if (!$adminId) {
        return null;
    }

    return get_admin_by_id($adminId);
}

function admin_avatar_url(?array $admin): ?string
{
    if (!$admin || empty($admin['profile_image'])) {
        return null;
    }

    return base_path(ltrim($admin['profile_image'], '/'));
}

function login_admin(array $admin): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $admin['id'];

    $statement = db()->prepare('UPDATE admins SET status = :status WHERE id = :id');
    $statement->execute([
        'status' => 'active',
        'id' => (int) $admin['id'],
    ]);

    log_admin_login((int) $admin['id']);
    log_admin_activity((int) $admin['id'], 'Admin logged in');
}

function logout_admin(): void
{
    $adminId = current_admin_id();

    if ($adminId) {
        $statement = db()->prepare('UPDATE admins SET status = :status WHERE id = :id');
        $statement->execute([
            'status' => 'inactive',
            'id' => $adminId,
        ]);

        log_admin_activity($adminId, 'Admin logged out');
    }

    unset($_SESSION['admin_id']);
}

function require_admin_login(): array
{
    $admin = current_admin();

    if (!$admin) {
        set_flash('error', 'Please log in as admin.');
        admin_redirect('auth/login.php');
    }

    return $admin;
}

function require_admin_guest(): void
{
    if (current_admin()) {
        admin_redirect('dashboard.php');
    }
}

function admin_request_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function log_admin_login(int $adminId): void
{
    $statement = db()->prepare(
        'INSERT INTO admin_login_logs (admin_id, login_time, ip_address) VALUES (:admin_id, NOW(), :ip_address)'
    );
    $statement->execute([
        'admin_id' => $adminId,
        'ip_address' => admin_request_ip(),
    ]);
}

function log_admin_activity(int $adminId, string $action): void
{
    $statement = db()->prepare(
        'INSERT INTO admin_activity_logs (admin_id, action, created_at) VALUES (:admin_id, :action, NOW())'
    );
    $statement->execute([
        'admin_id' => $adminId,
        'action' => $action,
    ]);
}

function admin_stats(): array
{
    return [
        'admins' => (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn(),
        'users' => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'conversations' => (int) db()->query('SELECT COUNT(*) FROM conversations')->fetchColumn(),
        'messages' => (int) db()->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
        'groups' => table_exists(db(), 'chat_groups') ? (int) db()->query('SELECT COUNT(*) FROM chat_groups')->fetchColumn() : 0,
    ];
}

function admin_recent_activity(int $limit = 8): array
{
    $statement = db()->prepare(
        'SELECT aal.*, a.name AS admin_name
         FROM admin_activity_logs aal
         INNER JOIN admins a ON a.id = aal.admin_id
         ORDER BY aal.created_at DESC
         LIMIT ' . (int) $limit
    );
    $statement->execute();

    return $statement->fetchAll();
}

function admin_recent_logins(int $limit = 8): array
{
    $statement = db()->prepare(
        'SELECT allogs.*, a.name AS admin_name
         FROM admin_login_logs allogs
         INNER JOIN admins a ON a.id = allogs.admin_id
         ORDER BY allogs.login_time DESC
         LIMIT ' . (int) $limit
    );
    $statement->execute();

    return $statement->fetchAll();
}

function admin_conversation_label(array $conversation): string
{
    if (($conversation['conversation_type'] ?? 'direct') === 'group' && !empty($conversation['group_name'])) {
        return $conversation['group_name'];
    }

    $parts = array_filter([
        $conversation['user_one_name'] ?? null,
        $conversation['user_two_name'] ?? null,
    ]);

    return $parts ? implode(' and ', $parts) : 'Conversation #' . (int) $conversation['id'];
}
