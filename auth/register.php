<?php

require_once __DIR__ . '/../includes/functions.php';

require_guest();

if (is_post()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = normalize_phone($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    try {
        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException('Name, email, and password are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Enter a valid email address.');
        }

        if ($password !== $confirmPassword) {
            throw new RuntimeException('Password confirmation does not match.');
        }

        if (strlen($password) < 6) {
            throw new RuntimeException('Use at least 6 characters for the password.');
        }

        if (get_user_by_email($email)) {
            throw new RuntimeException('That email is already registered.');
        }

        if ($phone !== null) {
            $phoneCheck = db()->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
            $phoneCheck->execute(['phone' => $phone]);
            if ($phoneCheck->fetch()) {
                throw new RuntimeException('That phone number is already registered.');
            }
        }

        $profileImage = store_uploaded_file(
            $_FILES['profile_image'] ?? [],
            'uploads/profile',
            ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
        );

        $insert = db()->prepare(
            'INSERT INTO users (name, email, phone, password, profile_image, status, last_seen, created_at)
             VALUES (:name, :email, :phone, :password, :profile_image, :status, NOW(), NOW())'
        );
        $insert->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'profile_image' => $profileImage,
            'status' => 'online',
        ]);

        $user = get_user_by_id((int) db()->lastInsertId());
        login_user($user);
        set_flash('success', 'Your account is ready. Start chatting now.');
        redirect('dashboard/home.php');
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
    }
}

$pageTitle = 'Register';
$bodyClass = 'auth-page';
include __DIR__ . '/../includes/header.php';
?>
<main class="auth-layout">
    <section class="auth-copy">
        <span class="eyebrow">Create a polished chat identity</span>
        <h1>Build your profile and step into a smooth messaging experience.</h1>
        <p>This version includes secure login, profile photos, responsive messaging, and a dashboard that feels intentionally designed instead of auto-generated.</p>

        <div class="feature-grid">
            <article class="feature-card">
                <h2>Profile setup</h2>
                <p>Add your name, email, phone, and avatar so the people list already feels complete.</p>
            </article>
            <article class="feature-card">
                <h2>Ready for growth</h2>
                <p>The folder structure stays close to your screenshots so future changes remain easy to manage.</p>
            </article>
        </div>
    </section>

    <section class="auth-card auth-card-wide">
        <div class="auth-card-header">
            <span class="eyebrow">Start here</span>
            <h2>Create your account</h2>
        </div>

        <form method="post" enctype="multipart/form-data" class="form-stack">
            <div class="input-row">
                <label class="input-group">
                    <span>Full name</span>
                    <input type="text" name="name" placeholder="Your name" required>
                </label>

                <label class="input-group">
                    <span>Email address</span>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </label>
            </div>

            <div class="input-row">
                <label class="input-group">
                    <span>Phone number</span>
                    <input type="text" name="phone" placeholder="Optional">
                </label>

                <label class="input-group">
                    <span>Profile image</span>
                    <input type="file" name="profile_image" accept="image/*">
                </label>
            </div>

            <div class="input-row">
                <label class="input-group">
                    <span>Password</span>
                    <input type="password" name="password" placeholder="Minimum 6 characters" required>
                </label>

                <label class="input-group">
                    <span>Confirm password</span>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-full">Create account</button>
        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="<?= e(base_path('auth/login.php')) ?>">Login now</a>
        </p>

        <p class="auth-switch">
            Creating an administrator?
            <a href="<?= e(base_path('admin/auth/signup.php')) ?>">Open admin signup</a>
        </p>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
