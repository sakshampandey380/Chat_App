<?php

require_once __DIR__ . '/../includes/admin_function.php';

require_admin_guest();

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $admin = get_admin_by_email($email);

    if (!$admin || !password_verify($password, $admin['password'])) {
        set_flash('error', 'Invalid admin email or password.');
    } else {
        login_admin($admin);
        set_flash('success', 'Welcome back, ' . $admin['name'] . '.');
        admin_redirect('dashboard.php');
    }
}

$pageTitle = 'Admin Login';
include __DIR__ . '/../includes/header.php';
?>
<main class="auth-layout admin-auth-layout">
    <section class="auth-copy admin-auth-copy">
        <span class="eyebrow">Professional control room</span>
        <h1>Manage users, branding, and chat activity from one admin workspace.</h1>
        <p>Use the dedicated admin section to oversee the application professionally with logs, user editing, message review, and branding controls.</p>
    </section>

    <section class="auth-card">
        <div class="auth-card-header">
            <span class="eyebrow">Admin Access</span>
            <h2>Login to admin panel</h2>
        </div>

        <form method="post" class="form-stack">
            <label class="input-group">
                <span>Admin email</span>
                <input type="email" name="email" placeholder="admin@example.com" required>
            </label>

            <label class="input-group">
                <span>Password</span>
                <input type="password" name="password" placeholder="Enter your password" required>
            </label>

            <button type="submit" class="btn btn-primary btn-full">Admin Login</button>
        </form>

        <p class="auth-switch">
            Need an admin account?
            <a href="<?= e(admin_base_path('auth/signup.php')) ?>">Create admin</a>
        </p>

        <p class="auth-switch">
            Back to user app?
            <a href="<?= e(base_path('auth/login.php')) ?>">User login</a>
        </p>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
