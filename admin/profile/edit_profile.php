<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activeAdminPage = 'profile';
$pageTitle = 'Admin Profile';

if (is_post()) {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'profile') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = normalize_phone($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';

            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Valid name and email are required.');
            }

            $emailCheck = db()->prepare('SELECT id FROM admins WHERE email = :email AND id != :id LIMIT 1');
            $emailCheck->execute([
                'email' => $email,
                'id' => (int) $adminCurrent['id'],
            ]);
            if ($emailCheck->fetch()) {
                throw new RuntimeException('Another admin already uses that email.');
            }

            $profileImage = store_uploaded_file(
                $_FILES['profile_image'] ?? [],
                'admin/uploads/admin_profiles',
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
                'id' => (int) $adminCurrent['id'],
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

            $statement = db()->prepare('UPDATE admins SET ' . implode(', ', $fields) . ' WHERE id = :id');
            $statement->execute($params);
            log_admin_activity((int) $adminCurrent['id'], 'Updated admin profile');
            set_flash('success', 'Admin profile updated successfully.');
        }

        if ($action === 'settings') {
            $appName = trim($_POST['app_name'] ?? '');

            if ($appName === '') {
                throw new RuntimeException('App name is required.');
            }

            $appLogo = store_uploaded_file(
                $_FILES['app_logo'] ?? [],
                'admin/uploads/app',
                ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
            );

            set_app_setting('app_name', $appName);
            if ($appLogo !== null) {
                set_app_setting('app_logo', $appLogo);
            }

            log_admin_activity((int) $adminCurrent['id'], 'Updated app settings');
            set_flash('success', 'App settings updated successfully.');
        }

        admin_redirect('profile/edit_profile.php');
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        admin_redirect('profile/edit_profile.php');
    }
}

$adminCurrent = get_admin_by_id((int) $adminCurrent['id']);

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel admin-hero-panel">
            <div>
                <span class="eyebrow">Admin Profile</span>
                <h2>Edit admin profile and app branding</h2>
                <p>Update your own admin details and the public chat app identity from one professional page.</p>
            </div>
        </section>

        <section class="content-grid admin-content-grid">
            <article class="panel-card">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">Admin Account</span>
                        <h3>Your details</h3>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="form-stack">
                    <input type="hidden" name="action" value="profile">

                    <div class="profile-preview">
                        <?php if (admin_avatar_url($adminCurrent)): ?>
                            <img src="<?= e(admin_avatar_url($adminCurrent)) ?>" alt="<?= e($adminCurrent['name']) ?>" class="avatar-image avatar-xl">
                        <?php else: ?>
                            <div class="avatar-fallback avatar-xl"><?= e(user_initials($adminCurrent['name'])) ?></div>
                        <?php endif; ?>
                        <div>
                            <strong><?= e($adminCurrent['name']) ?></strong>
                            <p><?= e($adminCurrent['email']) ?></p>
                            <span class="status-pill admin-status-pill"><?= e(ucwords(str_replace('_', ' ', $adminCurrent['role']))) ?></span>
                        </div>
                    </div>

                    <div class="input-row">
                        <label class="input-group">
                            <span>Name</span>
                            <input type="text" name="name" value="<?= e($adminCurrent['name']) ?>" required>
                        </label>
                        <label class="input-group">
                            <span>Email</span>
                            <input type="email" name="email" value="<?= e($adminCurrent['email']) ?>" required>
                        </label>
                    </div>

                    <div class="input-row">
                        <label class="input-group">
                            <span>Phone</span>
                            <input type="text" name="phone" value="<?= e((string) $adminCurrent['phone']) ?>">
                        </label>
                        <label class="input-group">
                            <span>Profile image</span>
                            <input type="file" name="profile_image" accept="image/*">
                        </label>
                    </div>

                    <label class="input-group">
                        <span>New password</span>
                        <input type="password" name="password" placeholder="Leave blank to keep current password">
                    </label>

                    <button type="submit" class="btn btn-primary profile-save-btn">Save Admin Profile</button>
                </form>
            </article>

            <article class="panel-card">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">Application Branding</span>
                        <h3>Control app identity</h3>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="form-stack">
                    <input type="hidden" name="action" value="settings">

                    <div class="profile-preview">
                        <?php if (app_logo_url()): ?>
                            <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_name()) ?>" class="avatar-image avatar-xl">
                        <?php else: ?>
                            <div class="brand-icon admin-brand-icon"><?= e(strtoupper(substr(app_name(), 0, 1))) ?></div>
                        <?php endif; ?>
                        <div>
                            <strong><?= e(app_name()) ?></strong>
                            <p>Changes here reflect in the user-side app branding.</p>
                        </div>
                    </div>

                    <label class="input-group">
                        <span>App name</span>
                        <input type="text" name="app_name" value="<?= e(app_name()) ?>" required>
                    </label>

                    <label class="input-group">
                        <span>App logo</span>
                        <input type="file" name="app_logo" accept="image/*">
                    </label>

                    <button type="submit" class="btn btn-primary profile-save-btn">Save App Settings</button>
                </form>
            </article>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
