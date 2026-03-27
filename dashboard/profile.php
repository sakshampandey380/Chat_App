<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activePage = 'profile';
$pageTitle = 'Profile';

if (is_post()) {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = normalize_phone($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '') {
            throw new RuntimeException('Name is required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid email address.');
        }

        $emailCheck = db()->prepare('SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1');
        $emailCheck->execute([
            'email' => $email,
            'id' => (int) $currentUser['id'],
        ]);

        if ($emailCheck->fetch()) {
            throw new RuntimeException('That email is already used by another account.');
        }

        if ($phone !== null) {
            $phoneCheck = db()->prepare('SELECT id FROM users WHERE phone = :phone AND id != :id LIMIT 1');
            $phoneCheck->execute([
                'phone' => $phone,
                'id' => (int) $currentUser['id'],
            ]);

            if ($phoneCheck->fetch()) {
                throw new RuntimeException('That phone number is already used by another account.');
            }
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
        ];

        $params = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'id' => (int) $currentUser['id'],
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

        set_flash('success', 'Your profile has been updated.');
        redirect('dashboard/profile.php');
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        redirect('dashboard/profile.php');
    }
}

$currentUser = get_user_by_id((int) $currentUser['id']);

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel compact-panel">
            <div>
                <span class="eyebrow">Your Profile</span>
                <h2>Edit your account</h2>
                <p>Update your image, username, email, phone number, and password from one place.</p>
            </div>
        </section>

        <section class="content-grid profile-grid">
            <article class="panel-card">
                <div class="profile-preview">
                    <?php if (user_avatar_url($currentUser)): ?>
                        <img src="<?= e(user_avatar_url($currentUser)) ?>" alt="<?= e($currentUser['name']) ?>" class="avatar-image avatar-xl">
                    <?php else: ?>
                        <div class="avatar-fallback avatar-xl"><?= e(user_initials($currentUser['name'])) ?></div>
                    <?php endif; ?>

                    <div>
                        <strong><?= e($currentUser['name']) ?></strong>
                        <p><?= e($currentUser['email']) ?></p>
                        <?php if (!empty($currentUser['phone'])): ?>
                            <p><?= e($currentUser['phone']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

            <article class="panel-card">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">Edit Details</span>
                        <h3>Change anything you want</h3>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="form-stack">
                    <div class="input-row">
                        <label class="input-group">
                            <span>User name</span>
                            <input type="text" name="name" value="<?= e($currentUser['name']) ?>" required>
                        </label>

                        <label class="input-group">
                            <span>Email</span>
                            <input type="email" name="email" value="<?= e($currentUser['email']) ?>" required>
                        </label>
                    </div>

                    <div class="input-row">
                        <label class="input-group">
                            <span>Phone number</span>
                            <input type="text" name="phone" value="<?= e((string) $currentUser['phone']) ?>" placeholder="Optional">
                        </label>

                        <label class="input-group">
                            <span>Profile image</span>
                            <input type="file" name="profile_image" accept="image/*">
                        </label>
                    </div>

                    <label class="input-group">
                        <span>New password</span>
                        <input type="password" name="password" placeholder="Leave empty to keep current password">
                    </label>

                    <button type="submit" class="btn btn-primary profile-save-btn">Save Changes</button>
                </form>
            </article>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
