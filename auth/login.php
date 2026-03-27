<?php

require_once __DIR__ . '/../includes/functions.php';

require_guest();

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = get_user_by_email($email);

    if (!$user || !password_verify($password, $user['password'])) {
        set_flash('error', 'Invalid email or password.');
    } else {
        login_user($user);
        set_flash('success', 'Welcome back, ' . $user['name'] . '.');
        redirect('dashboard/home.php');
    }
}

$pageTitle = 'Login';
$bodyClass = 'auth-page';
include __DIR__ . '/../includes/header.php';
?>
<main class="auth-layout">
    <section class="auth-copy">
        <span class="eyebrow">Modern chat workspace</span>
        <h1>Talk beautifully. Share instantly. Keep every conversation alive.</h1>
        <p><?= e(app_name()) ?> is your polished PHP chat project with elegant motion, glowing panels, and a UI that feels premium from the first screen.</p>

        <div class="feature-grid">
            <article class="feature-card">
                <h2>Fluid conversations</h2>
                <p>Live fetching, seen state, and responsive message bubbles built for everyday use.</p>
            </article>
            <article class="feature-card">
                <h2>Visual atmosphere</h2>
                <p>Layered gradients, floating lights, and confident button styling instead of flat default pages.</p>
            </article>
        </div>
    </section>

    <section class="auth-card">
        <div class="auth-card-header">
            <span class="eyebrow">Welcome back</span>
            <h2>Log into your account</h2>
        </div>

        <form method="post" class="form-stack">
            <label class="input-group">
                <span>Email address</span>
                <input type="email" name="email" placeholder="you@example.com" required>
            </label>

            <label class="input-group">
                <span>Password</span>
                <input type="password" name="password" placeholder="Enter your password" required>
            </label>

            <button type="submit" class="btn btn-primary btn-full">Login</button>
        </form>

        <p class="auth-switch">
            New here?
            <a href="<?= e(base_path('auth/register.php')) ?>">Create your account</a>
        </p>

        <p class="auth-switch">
            Need admin access?
            <a href="<?= e(base_path('admin/auth/login.php')) ?>">Open admin portal</a>
        </p>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
