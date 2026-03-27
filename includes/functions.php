<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function app_config(): array
{
    static $config;

    if ($config === null) {
        $config = require __DIR__ . '/../config/database.php';
        date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
    }

    return $config;
}

function db(): PDO
{
    static $pdo;

    if ($pdo === null) {
        $config = app_config()['database'];
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        ensure_schema($pdo);
    }

    return $pdo;
}

function app_name(): string
{
    return (string) app_setting('app_name', app_config()['app']['name'] ?? 'Chat App');
}

function app_logo_url(): ?string
{
    $logoPath = trim((string) app_setting('app_logo', ''));

    if ($logoPath === '') {
        return null;
    }

    return base_path(ltrim($logoPath, '/'));
}

function base_path(string $path = ''): string
{
    $base = rtrim(app_config()['app']['base_path'] ?? '', '/');

    if ($path === '') {
        return $base ?: '';
    }

    return ($base ? $base . '/' : '/') . ltrim($path, '/');
}

function asset(string $path): string
{
    return base_path($path);
}

function redirect(string $path): void
{
    header('Location: ' . base_path($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function get_user_by_id(int $userId): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    return $user ?: null;
}

function get_user_by_email(string $email): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch();

    return $user ?: null;
}

function current_user(): ?array
{
    $userId = current_user_id();

    if (!$userId) {
        return null;
    }

    return get_user_by_id($userId);
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    update_user_status((int) $user['id'], 'online');
}

function logout_user(): void
{
    $userId = current_user_id();

    if ($userId) {
        update_user_status($userId, 'offline');
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function require_login(): array
{
    $user = current_user();

    if (!$user) {
        set_flash('error', 'Please log in to continue.');
        redirect('auth/login.php');
    }

    touch_user_activity((int) $user['id']);

    return $user;
}

function require_guest(): void
{
    if (current_user()) {
        redirect('dashboard/home.php');
    }
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function touch_user_activity(int $userId): void
{
    $statement = db()->prepare('UPDATE users SET status = :status, last_seen = NOW() WHERE id = :id');
    $statement->execute([
        'status' => 'online',
        'id' => $userId,
    ]);
}

function update_user_status(int $userId, string $status): void
{
    $statement = db()->prepare('UPDATE users SET status = :status, last_seen = NOW() WHERE id = :id');
    $statement->execute([
        'status' => $status,
        'id' => $userId,
    ]);
}

function user_avatar_url(?array $user): ?string
{
    if (!$user || empty($user['profile_image'])) {
        return null;
    }

    return base_path(ltrim($user['profile_image'], '/'));
}

function user_initials(?string $name): string
{
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $initials = '';

    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    return $initials ?: 'PC';
}

function app_setting(string $key, ?string $default = null): ?string
{
    static $settings = null;

    if (!empty($GLOBALS['__app_settings_cache_reset'])) {
        $settings = null;
        unset($GLOBALS['__app_settings_cache_reset']);
    }

    if ($settings === null) {
        $settings = [];

        if (table_exists(db(), 'app_settings')) {
            $statement = db()->query('SELECT setting_key, setting_value FROM app_settings');
            foreach ($statement->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    return array_key_exists($key, $settings) ? $settings[$key] : $default;
}

function set_app_setting(string $key, ?string $value): void
{
    $statement = db()->prepare(
        'INSERT INTO app_settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $statement->execute([
        'setting_key' => $key,
        'setting_value' => $value,
    ]);

    $GLOBALS['__app_settings_cache_reset'] = true;
}

function format_datetime(?string $value, string $format = 'M d, Y h:i A'): string
{
    if (!$value) {
        return 'Just now';
    }

    return date($format, strtotime($value));
}

function time_ago(?string $value): string
{
    if (!$value) {
        return 'Just now';
    }

    $timestamp = strtotime($value);
    $delta = time() - $timestamp;

    if ($delta < 60) {
        return 'Just now';
    }

    if ($delta < 3600) {
        return floor($delta / 60) . ' min ago';
    }

    if ($delta < 86400) {
        return floor($delta / 3600) . ' hr ago';
    }

    if ($delta < 604800) {
        return floor($delta / 86400) . ' days ago';
    }

    return date('M d', $timestamp);
}

function user_status_text(array $user): string
{
    if (($user['status'] ?? 'offline') === 'online') {
        return 'Online now';
    }

    return 'Last seen ' . time_ago($user['last_seen'] ?? null);
}

function normalize_phone(?string $phone): ?string
{
    $phone = trim((string) $phone);
    return $phone === '' ? null : $phone;
}

function ensure_upload_folder(string $folder): string
{
    $fullPath = dirname(__DIR__) . '/' . trim($folder, '/');

    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0777, true);
    }

    return $fullPath;
}

function store_uploaded_file(array $file, string $folder, array $allowedMimeTypes): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed. Please try again.');
    }

    $tmpPath = $file['tmp_name'];
    $mimeType = mime_content_type($tmpPath) ?: '';

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        throw new RuntimeException('Unsupported file type uploaded.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid('upload_', true) . '.' . $extension;
    $relativePath = trim($folder, '/') . '/' . $fileName;
    $destination = ensure_upload_folder($folder) . '/' . $fileName;

    if (!move_uploaded_file($tmpPath, $destination)) {
        throw new RuntimeException('Unable to save uploaded file.');
    }

    return $relativePath;
}

function table_exists(PDO $pdo, string $table): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        throw new RuntimeException('Invalid table name.');
    }

    $statement = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));

    return (bool) $statement->fetchColumn();
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        throw new RuntimeException('Invalid schema identifier.');
    }

    $statement = $pdo->query(sprintf(
        'SHOW COLUMNS FROM `%s` LIKE %s',
        $table,
        $pdo->quote($column)
    ));

    return (bool) $statement->fetchColumn();
}

function ensure_schema(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS app_settings (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(120) NOT NULL,
            setting_value TEXT DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY app_settings_setting_key_unique (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(30) DEFAULT NULL,
            password VARCHAR(255) NOT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'super_admin',
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY admins_email_unique (email),
            UNIQUE KEY admins_phone_unique (phone)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_login_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            admin_id INT UNSIGNED NOT NULL,
            login_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(64) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY admin_login_logs_admin_idx (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            admin_id INT UNSIGNED NOT NULL,
            action VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY admin_activity_logs_admin_idx (admin_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS chat_groups (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id INT UNSIGNED NOT NULL,
            name VARCHAR(140) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            created_by INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY chat_groups_conversation_unique (conversation_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS chat_group_members (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            group_id INT UNSIGNED NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY chat_group_members_unique (group_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    if (!column_exists($pdo, 'conversations', 'conversation_type')) {
        $pdo->exec("ALTER TABLE conversations ADD COLUMN conversation_type ENUM('direct','group') NOT NULL DEFAULT 'direct' AFTER user_two");
    }

    if (!column_exists($pdo, 'conversations', 'group_id')) {
        $pdo->exec('ALTER TABLE conversations ADD COLUMN group_id INT UNSIGNED DEFAULT NULL AFTER conversation_type');
    }

    $seedSetting = $pdo->prepare(
        'INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value)'
    );
    $seedSetting->execute([
        'setting_key' => 'app_name',
        'setting_value' => app_config()['app']['name'] ?? 'Chat App',
    ]);
    $seedSetting->execute([
        'setting_key' => 'app_logo',
        'setting_value' => '',
    ]);

    $checked = true;
}

function build_chat_href(string $chatType, int $targetId): string
{
    return base_path('dashboard/chat.php?type=' . urlencode($chatType) . '&id=' . $targetId);
}

function get_or_create_conversation(int $currentUserId, int $otherUserId): array
{
    $conversation = find_conversation($currentUserId, $otherUserId);

    if ($conversation) {
        return $conversation;
    }

    $insert = db()->prepare(
        'INSERT INTO conversations (user_one, user_two, conversation_type, created_at)
         VALUES (:user_one, :user_two, :conversation_type, NOW())'
    );
    $insert->execute([
        'user_one' => $currentUserId,
        'user_two' => $otherUserId,
        'conversation_type' => 'direct',
    ]);

    return get_conversation_by_id((int) db()->lastInsertId());
}

function find_conversation(int $currentUserId, int $otherUserId): ?array
{
    $statement = db()->prepare(
        'SELECT * FROM conversations
         WHERE conversation_type = :conversation_type
           AND (
                (user_one = :current_a AND user_two = :other_a)
             OR (user_one = :other_b AND user_two = :current_b)
           )
         LIMIT 1'
    );
    $statement->execute([
        'conversation_type' => 'direct',
        'current_a' => $currentUserId,
        'other_a' => $otherUserId,
        'other_b' => $otherUserId,
        'current_b' => $currentUserId,
    ]);
    $conversation = $statement->fetch();

    return $conversation ?: null;
}

function get_conversation_by_id(int $conversationId): ?array
{
    $statement = db()->prepare('SELECT * FROM conversations WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $conversationId]);
    $conversation = $statement->fetch();

    return $conversation ?: null;
}

function get_messages_for_conversation(int $conversationId): array
{
    $statement = db()->prepare(
        'SELECT m.*, u.name AS sender_name, u.profile_image AS sender_profile
         FROM messages m
         INNER JOIN users u ON u.id = m.sender_id
         WHERE m.conversation_id = :conversation_id
         ORDER BY m.created_at ASC, m.id ASC'
    );
    $statement->execute(['conversation_id' => $conversationId]);

    return $statement->fetchAll();
}

function mark_messages_seen(int $conversationId, int $viewerId): void
{
    $messageStatement = db()->prepare(
        'SELECT id FROM messages
         WHERE conversation_id = :conversation_id
           AND sender_id != :viewer_id
           AND is_seen = 0'
    );
    $messageStatement->execute([
        'conversation_id' => $conversationId,
        'viewer_id' => $viewerId,
    ]);

    $messageIds = array_column($messageStatement->fetchAll(), 'id');

    if (!$messageIds) {
        return;
    }

    $update = db()->prepare(
        'UPDATE messages
         SET is_seen = 1
         WHERE conversation_id = :conversation_id
           AND sender_id != :viewer_id
           AND is_seen = 0'
    );
    $update->execute([
        'conversation_id' => $conversationId,
        'viewer_id' => $viewerId,
    ]);

    $insertSeen = db()->prepare(
        'INSERT IGNORE INTO message_seen (message_id, user_id, seen_at) VALUES (:message_id, :user_id, NOW())'
    );

    foreach ($messageIds as $messageId) {
        $insertSeen->execute([
            'message_id' => $messageId,
            'user_id' => $viewerId,
        ]);
    }
}

function send_chat_message(int $conversationId, int $senderId, string $messageText = '', ?string $mediaPath = null): array
{
    $messageText = trim($messageText);

    if ($messageText === '' && !$mediaPath) {
        throw new RuntimeException('Enter a message or attach an image.');
    }

    $messageType = $mediaPath ? 'image' : 'text';

    $insert = db()->prepare(
        'INSERT INTO messages (conversation_id, sender_id, message, message_type, media, is_seen, created_at)
         VALUES (:conversation_id, :sender_id, :message, :message_type, :media, 0, NOW())'
    );
    $insert->execute([
        'conversation_id' => $conversationId,
        'sender_id' => $senderId,
        'message' => $messageText !== '' ? $messageText : null,
        'message_type' => $messageType,
        'media' => $mediaPath,
    ]);
    $messageId = (int) db()->lastInsertId();

    $preview = $messageText !== '' ? $messageText : 'Sent an image';

    $updateConversation = db()->prepare(
        'UPDATE conversations SET last_message = :last_message, last_message_time = NOW() WHERE id = :id'
    );
    $updateConversation->execute([
        'last_message' => $preview,
        'id' => $conversationId,
    ]);

    $message = get_message_by_id($messageId);

    if (!$message) {
        throw new RuntimeException('Unable to load the sent message.');
    }

    return $message;
}

function get_message_by_id(int $messageId): ?array
{
    $statement = db()->prepare(
        'SELECT m.*, u.name AS sender_name, u.profile_image AS sender_profile
         FROM messages m
         INNER JOIN users u ON u.id = m.sender_id
         WHERE m.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $messageId]);
    $message = $statement->fetch();

    return $message ?: null;
}

function get_people_directory(int $currentUserId): array
{
    $statement = db()->prepare(
        'SELECT
            u.id,
            u.name,
            u.email,
            u.phone,
            u.profile_image,
            u.status,
            u.last_seen,
            c.id AS conversation_id
         FROM users u
         LEFT JOIN conversations c
           ON c.conversation_type = :conversation_type
          AND (
                (c.user_one = :current_a AND c.user_two = u.id)
                OR
                (c.user_two = :current_b AND c.user_one = u.id)
              )
         WHERE u.id != :current_d
         ORDER BY u.name ASC'
    );
    $statement->execute([
        'conversation_type' => 'direct',
        'current_a' => $currentUserId,
        'current_b' => $currentUserId,
        'current_d' => $currentUserId,
    ]);

    $people = [];

    foreach ($statement->fetchAll() as $person) {
        $people[] = [
            'id' => (int) $person['id'],
            'conversation_id' => $person['conversation_id'] ? (int) $person['conversation_id'] : null,
            'name' => $person['name'],
            'email' => $person['email'],
            'phone' => $person['phone'],
            'avatar_url' => user_avatar_url($person),
            'initials' => user_initials($person['name']),
            'status' => $person['status'],
            'status_text' => user_status_text($person),
            'chat_href' => build_chat_href('direct', (int) $person['id']),
        ];
    }

    return $people;
}

function get_group_members(int $groupId): array
{
    $statement = db()->prepare(
        'SELECT u.*
         FROM chat_group_members gm
         INNER JOIN users u ON u.id = gm.user_id
         WHERE gm.group_id = :group_id
         ORDER BY u.name ASC'
    );
    $statement->execute(['group_id' => $groupId]);

    return $statement->fetchAll();
}

function user_is_group_member(int $groupId, int $userId): bool
{
    $statement = db()->prepare(
        'SELECT id FROM chat_group_members WHERE group_id = :group_id AND user_id = :user_id LIMIT 1'
    );
    $statement->execute([
        'group_id' => $groupId,
        'user_id' => $userId,
    ]);

    return (bool) $statement->fetchColumn();
}

function get_group_by_id(int $groupId): ?array
{
    $statement = db()->prepare(
        'SELECT
            g.*,
            c.last_message,
            c.last_message_time,
            c.id AS conversation_id
         FROM chat_groups g
         INNER JOIN conversations c ON c.id = g.conversation_id
         WHERE g.id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => $groupId]);
    $group = $statement->fetch();

    if (!$group) {
        return null;
    }

    $group['members'] = get_group_members($groupId);

    return $group;
}

function get_group_avatar_url(?array $group): ?string
{
    if (!$group || empty($group['image'])) {
        return null;
    }

    return base_path(ltrim($group['image'], '/'));
}

function create_group_chat(int $creatorId, string $groupName, array $memberIds, ?string $imagePath = null): array
{
    $groupName = trim($groupName);
    $memberIds = array_values(array_unique(array_filter(array_map('intval', $memberIds))));
    $memberIds = array_values(array_filter($memberIds, fn(int $memberId): bool => $memberId !== $creatorId));

    if ($groupName === '') {
        throw new RuntimeException('Group name is required.');
    }

    if (count($memberIds) < 2) {
        throw new RuntimeException('Select at least two users to form a group.');
    }

    $selectedUsers = [];

    foreach ($memberIds as $memberId) {
        $user = get_user_by_id($memberId);
        if ($user) {
            $selectedUsers[] = $user;
        }
    }

    if (count($selectedUsers) < 2) {
        throw new RuntimeException('Selected users could not be validated.');
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        $conversationStatement = $pdo->prepare(
            'INSERT INTO conversations (user_one, user_two, conversation_type, created_at)
             VALUES (:user_one, :user_two, :conversation_type, NOW())'
        );
        $conversationStatement->execute([
            'user_one' => $creatorId,
            'user_two' => $creatorId,
            'conversation_type' => 'group',
        ]);
        $conversationId = (int) $pdo->lastInsertId();

        $groupStatement = $pdo->prepare(
            'INSERT INTO chat_groups (conversation_id, name, image, created_by, created_at)
             VALUES (:conversation_id, :name, :image, :created_by, NOW())'
        );
        $groupStatement->execute([
            'conversation_id' => $conversationId,
            'name' => $groupName,
            'image' => $imagePath,
            'created_by' => $creatorId,
        ]);
        $groupId = (int) $pdo->lastInsertId();

        $pdo->prepare('UPDATE conversations SET group_id = :group_id WHERE id = :id')->execute([
            'group_id' => $groupId,
            'id' => $conversationId,
        ]);

        $memberStatement = $pdo->prepare(
            'INSERT INTO chat_group_members (group_id, user_id, joined_at) VALUES (:group_id, :user_id, NOW())'
        );

        $memberStatement->execute([
            'group_id' => $groupId,
            'user_id' => $creatorId,
        ]);

        foreach ($memberIds as $memberId) {
            $memberStatement->execute([
                'group_id' => $groupId,
                'user_id' => $memberId,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }

    $group = get_group_by_id($groupId);

    if (!$group) {
        throw new RuntimeException('Unable to create the group.');
    }

    return $group;
}

function get_direct_chat_roster(int $currentUserId): array
{
    $statement = db()->prepare(
        'SELECT
            u.id,
            u.name,
            u.email,
            u.phone,
            u.profile_image,
            u.status,
            u.last_seen,
            c.id AS conversation_id,
            c.last_message,
            c.last_message_time,
            (
                SELECT COUNT(*)
                FROM messages m
                WHERE m.conversation_id = c.id
                  AND m.sender_id != :current_c
                  AND m.is_seen = 0
            ) AS unread_count
         FROM users u
         LEFT JOIN conversations c
           ON c.conversation_type = :conversation_type
          AND (
                (c.user_one = :current_a AND c.user_two = u.id)
                OR
                (c.user_two = :current_b AND c.user_one = u.id)
              )
         WHERE u.id != :current_d
         ORDER BY u.name ASC'
    );
    $statement->execute([
        'conversation_type' => 'direct',
        'current_a' => $currentUserId,
        'current_b' => $currentUserId,
        'current_c' => $currentUserId,
        'current_d' => $currentUserId,
    ]);

    $items = [];

    foreach ($statement->fetchAll() as $user) {
        $items[] = [
            'chat_type' => 'direct',
            'target_id' => (int) $user['id'],
            'conversation_id' => $user['conversation_id'] ? (int) $user['conversation_id'] : null,
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'avatar_url' => user_avatar_url($user),
            'initials' => user_initials($user['name']),
            'status' => $user['status'],
            'status_text' => user_status_text($user),
            'subtitle' => $user['email'],
            'last_message' => $user['last_message'] ?: 'Start a new conversation',
            'last_message_time' => $user['last_message_time'],
            'time_label' => $user['last_message_time'] ? time_ago($user['last_message_time']) : 'New contact',
            'unread_count' => (int) ($user['unread_count'] ?? 0),
            'chat_href' => build_chat_href('direct', (int) $user['id']),
        ];
    }

    return $items;
}

function get_group_chat_roster(int $currentUserId): array
{
    if (!table_exists(db(), 'chat_groups')) {
        return [];
    }

    $statement = db()->prepare(
        'SELECT
            g.id,
            g.name,
            g.image,
            g.conversation_id,
            c.last_message,
            c.last_message_time,
            (
                SELECT COUNT(*)
                FROM messages m
                WHERE m.conversation_id = c.id
                  AND m.sender_id != :current_user
                  AND m.is_seen = 0
            ) AS unread_count,
            (
                SELECT COUNT(*)
                FROM chat_group_members gm2
                WHERE gm2.group_id = g.id
            ) AS member_count
         FROM chat_groups g
         INNER JOIN conversations c ON c.id = g.conversation_id
         INNER JOIN chat_group_members gm ON gm.group_id = g.id
         WHERE gm.user_id = :user_id
         ORDER BY g.name ASC'
    );
    $statement->execute([
        'current_user' => $currentUserId,
        'user_id' => $currentUserId,
    ]);

    $items = [];

    foreach ($statement->fetchAll() as $group) {
        $items[] = [
            'chat_type' => 'group',
            'target_id' => (int) $group['id'],
            'conversation_id' => (int) $group['conversation_id'],
            'name' => $group['name'],
            'email' => null,
            'phone' => null,
            'avatar_url' => get_group_avatar_url($group),
            'initials' => user_initials($group['name']),
            'status' => 'group',
            'status_text' => (int) $group['member_count'] . ' members',
            'subtitle' => (int) $group['member_count'] . ' members',
            'last_message' => $group['last_message'] ?: 'Group created',
            'last_message_time' => $group['last_message_time'],
            'time_label' => $group['last_message_time'] ? time_ago($group['last_message_time']) : 'New group',
            'unread_count' => (int) ($group['unread_count'] ?? 0),
            'chat_href' => build_chat_href('group', (int) $group['id']),
        ];
    }

    return $items;
}

function get_chat_roster(int $currentUserId): array
{
    $items = array_merge(
        get_group_chat_roster($currentUserId),
        get_direct_chat_roster($currentUserId)
    );

    usort($items, function (array $left, array $right): int {
        $leftTime = $left['last_message_time'] ? strtotime($left['last_message_time']) : 0;
        $rightTime = $right['last_message_time'] ? strtotime($right['last_message_time']) : 0;

        if ($leftTime === $rightTime) {
            return strcasecmp($left['name'], $right['name']);
        }

        return $rightTime <=> $leftTime;
    });

    return $items;
}

function get_dashboard_stats(int $currentUserId): array
{
    $totalUsers = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();

    $conversationCount = count(get_chat_roster($currentUserId));

    $messageStatement = db()->prepare(
        'SELECT COUNT(*)
         FROM messages m
         INNER JOIN conversations c ON c.id = m.conversation_id
         LEFT JOIN chat_group_members gm ON gm.group_id = c.group_id AND gm.user_id = :group_user
         WHERE
            (c.conversation_type = :direct_type AND (c.user_one = :direct_user_one OR c.user_two = :direct_user_two))
            OR
            (c.conversation_type = :group_type AND gm.user_id IS NOT NULL)'
    );
    $messageStatement->execute([
        'group_user' => $currentUserId,
        'direct_type' => 'direct',
        'direct_user_one' => $currentUserId,
        'direct_user_two' => $currentUserId,
        'group_type' => 'group',
    ]);

    $onlineUsers = (int) db()->query("SELECT COUNT(*) FROM users WHERE status = 'online'")->fetchColumn();

    return [
        'total_users' => max(0, $totalUsers - 1),
        'total_conversations' => $conversationCount,
        'total_messages' => (int) $messageStatement->fetchColumn(),
        'online_users' => max(0, $onlineUsers - 1),
    ];
}

function get_direct_chat_view(int $currentUserId, int $targetUserId): ?array
{
    $user = get_user_by_id($targetUserId);

    if (!$user || $targetUserId === $currentUserId) {
        return null;
    }

    $conversation = find_conversation($currentUserId, $targetUserId);

    return [
        'chat_type' => 'direct',
        'target_id' => $targetUserId,
        'conversation_id' => $conversation['id'] ?? null,
        'name' => $user['name'],
        'avatar_url' => user_avatar_url($user),
        'initials' => user_initials($user['name']),
        'status_text' => user_status_text($user),
        'subtitle' => $user['email'],
        'status' => $user['status'],
    ];
}

function get_group_chat_view(int $currentUserId, int $groupId): ?array
{
    if (!user_is_group_member($groupId, $currentUserId)) {
        return null;
    }

    $group = get_group_by_id($groupId);

    if (!$group) {
        return null;
    }

    return [
        'chat_type' => 'group',
        'target_id' => $groupId,
        'conversation_id' => $group['conversation_id'],
        'name' => $group['name'],
        'avatar_url' => get_group_avatar_url($group),
        'initials' => user_initials($group['name']),
        'status_text' => count($group['members']) . ' members',
        'subtitle' => count($group['members']) . ' members',
        'status' => 'group',
        'members' => $group['members'],
    ];
}

function get_chat_view(int $currentUserId, string $chatType, int $targetId): ?array
{
    if ($chatType === 'group') {
        return get_group_chat_view($currentUserId, $targetId);
    }

    return get_direct_chat_view($currentUserId, $targetId);
}

function message_payload(array $message, int $currentUserId): array
{
    return [
        'id' => (int) $message['id'],
        'conversation_id' => (int) $message['conversation_id'],
        'sender_id' => (int) $message['sender_id'],
        'sender_name' => $message['sender_name'],
        'message' => $message['message'] ?? '',
        'message_type' => $message['message_type'],
        'media_url' => !empty($message['media']) ? base_path($message['media']) : null,
        'is_seen' => (bool) $message['is_seen'],
        'created_at' => $message['created_at'],
        'time_label' => date('h:i A', strtotime($message['created_at'])),
        'is_mine' => (int) $message['sender_id'] === $currentUserId,
    ];
}

function roster_payload(array $user): array
{
    return $user;
}
