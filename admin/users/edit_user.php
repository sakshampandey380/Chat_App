<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activeAdminPage = 'users';
$pageTitle = 'Edit User';
$userId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$user = get_user_by_id($userId);

if (!$user) {
    set_flash('error', 'User not found.');
    admin_redirect('users/user_list.php');
}

if (is_post()) {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = normalize_phone($_POST['phone'] ?? '');
        $status = trim($_POST['status'] ?? 'offline');
        $password = $_POST['password'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Valid name and email are required.');
        }

        $emailCheck = db()->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $emailCheck->execute([
            'email' => $email,
            'id' => $userId,
        ]);
        if ($emailCheck->fetch()) {
            throw new RuntimeException('Another user already uses that email.');
        }

        $profileImage = store_uploaded_file(
            $_FILES['profile_image'] ?? [],
            'uploads/profile',
            ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
        );

        $fields = [
            'name = :name',
            'email = :email',
            'phone = :phone',
            'status = :status',
        ];
        $params = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'status' => in_array($status, ['online', 'away', 'offline'], true) ? $status : 'offline',
            'id' => $userId,
        ];

        if ($profileImage !== null) {
            $fields[] = 'profile_image = :profile_image';
            $params['profile_image'] = $profileImage;
        }

        if ($password !== '') {
            if (strlen($password) < 6) {
                throw new RuntimeException('Password must be at least 6 characters.');
            }
            $fields[] = 'password = :password';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $statement = db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $statement->execute($params);

        log_admin_activity((int) $adminCurrent['id'], 'Edited user #' . $userId);
        set_flash('success', 'User updated successfully.');
        admin_redirect('users/edit_user.php?id=' . $userId);
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        admin_redirect('users/edit_user.php?id=' . $userId);
    }
}

$user = get_user_by_id($userId);
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel compact-panel">
            <div>
                <span class="eyebrow">Edit User</span>
                <h2><?= e($user['name']) ?></h2>
                <p>Update user details, image, password, and account status.</p>
            </div>
        </section>

        <section class="panel-card">
            <form method="post" enctype="multipart/form-data" class="form-stack">
                <input type="hidden" name="id" value="<?= e((string) $userId) ?>">

                <div class="input-row">
                    <label class="input-group">
                        <span>Name</span>
                        <input type="text" name="name" value="<?= e($user['name']) ?>" required>
                    </label>
                    <label class="input-group">
                        <span>Email</span>
                        <input type="email" name="email" value="<?= e($user['email']) ?>" required>
                    </label>
                </div>

                <div class="input-row">
                    <label class="input-group">
                        <span>Phone</span>
                        <input type="text" name="phone" value="<?= e((string) $user['phone']) ?>">
                    </label>
                    <label class="input-group">
                        <span>Status</span>
                        <select name="status">
                            <option value="online" <?= $user['status'] === 'online' ? 'selected' : '' ?>>Online</option>
                            <option value="away" <?= $user['status'] === 'away' ? 'selected' : '' ?>>Away</option>
                            <option value="offline" <?= $user['status'] === 'offline' ? 'selected' : '' ?>>Offline</option>
                        </select>
                    </label>
                </div>

                <div class="input-row">
                    <label class="input-group">
                        <span>Profile image</span>
                        <input type="file" name="profile_image" accept="image/*">
                    </label>
                    <label class="input-group">
                        <span>New password</span>
                        <input type="password" name="password" placeholder="Leave blank to keep current password">
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Save User Changes</button>
            </form>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
