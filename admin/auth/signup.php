<?php

require_once __DIR__ . '/../includes/admin_function.php';

require_admin_guest();

if (is_post()) {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = normalize_phone($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            throw new RuntimeException('Name, valid email, and password are required.');
        }

        if ($password !== $confirmPassword) {
            throw new RuntimeException('Password confirmation does not match.');
        }

        if (strlen($password) < 6) {
            throw new RuntimeException('Password must be at least 6 characters.');
        }

        if (get_admin_by_email($email)) {
            throw new RuntimeException('That admin email already exists.');
        }

        if ($phone !== null) {
            $phoneCheck = db()->prepare('SELECT id FROM admins WHERE phone = :phone LIMIT 1');
            $phoneCheck->execute(['phone' => $phone]);
            if ($phoneCheck->fetch()) {
                throw new RuntimeException('That phone number already exists.');
            }
        }

        $profileImage = store_uploaded_file(
            $_FILES['profile_image'] ?? [],
            'admin/uploads/admin_profiles',
            ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
        );

        $statement = db()->prepare(
            'INSERT INTO admins (name, email, phone, password, profile_image, role, status, created_at)
             VALUES (:name, :email, :phone, :password, :profile_image, :role, :status, NOW())'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'profile_image' => $profileImage,
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $admin = get_admin_by_id((int) db()->lastInsertId());
        login_admin($admin);
        set_flash('success', 'Admin account created successfully.');
        admin_redirect('dashboard.php');
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
    }
}

$pageTitle = 'Admin Signup';
include __DIR__ . '/../includes/header.php';
?>
<main class="auth-layout admin-auth-layout">
    <section class="auth-copy admin-auth-copy">
        <span class="eyebrow">Secure onboarding</span>
        <h1>Create the admin account that controls your chat platform.</h1>
        <p>This account manages app branding, user records, message review, and activity logs through the dedicated admin interface.</p>
    </section>

    <section class="auth-card auth-card-wide">
        <div class="auth-card-header">
            <span class="eyebrow">Create admin</span>
            <h2>Set up administrator profile</h2>
        </div>

        <form method="post" enctype="multipart/form-data" class="form-stack">
            <div class="input-row">
                <label class="input-group">
                    <span>Full name</span>
                    <input type="text" name="name" required>
                </label>
                <label class="input-group">
                    <span>Email</span>
                    <input type="email" name="email" required>
                </label>
            </div>

            <div class="input-row">
                <label class="input-group">
                    <span>Phone</span>
                    <input type="text" name="phone">
                </label>
                <label class="input-group">
                    <span>Profile image</span>
                    <input type="file" name="profile_image" accept="image/*">
                </label>
            </div>

            <div class="input-row">
                <label class="input-group">
                    <span>Password</span>
                    <input type="password" name="password" required>
                </label>
                <label class="input-group">
                    <span>Confirm password</span>
                    <input type="password" name="confirm_password" required>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Create Admin</button>
        </form>

        <p class="auth-switch">
            Already have admin access?
            <a href="<?= e(admin_base_path('auth/login.php')) ?>">Login here</a>
        </p>

        <p class="auth-switch">
            Need the regular app instead?
            <a href="<?= e(base_path('auth/register.php')) ?>">User signup</a>
        </p>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
