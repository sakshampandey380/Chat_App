<?php
$activePage = $activePage ?? '';
$currentUser = $currentUser ?? current_user();
?>
<aside class="sidebar-card">
    <div class="brand-mark">
        <?php if (app_logo_url()): ?>
            <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_name()) ?>" class="brand-image">
        <?php else: ?>
            <div class="brand-icon"><?= e(strtoupper(substr(app_name(), 0, 1))) ?></div>
        <?php endif; ?>
        <div>
            <h1><?= e(app_name()) ?></h1>
        </div>
    </div>

    <div class="profile-tile">
        <?php if (user_avatar_url($currentUser)): ?>
            <img src="<?= e(user_avatar_url($currentUser)) ?>" alt="<?= e($currentUser['name']) ?>" class="avatar-image avatar-large">
        <?php else: ?>
            <div class="avatar-fallback avatar-large"><?= e(user_initials($currentUser['name'])) ?></div>
        <?php endif; ?>

        <div>
            <strong><?= e($currentUser['name']) ?></strong>
            <p><?= e($currentUser['email']) ?></p>
            <span class="status-pill is-online">Online</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= e(base_path('dashboard/home.php')) ?>" class="nav-link <?= $activePage === 'home' ? 'is-active' : '' ?>">Overview</a>
        <a href="<?= e(base_path('dashboard/users.php')) ?>" class="nav-link <?= $activePage === 'users' ? 'is-active' : '' ?>">People</a>
        <a href="<?= e(base_path('dashboard/chat.php')) ?>" class="nav-link <?= $activePage === 'chat' ? 'is-active' : '' ?>">Messages</a>
        <a href="<?= e(base_path('dashboard/profile.php')) ?>" class="nav-link <?= $activePage === 'profile' ? 'is-active' : '' ?>">Profile</a>
        <a href="<?= e(base_path('auth/logout.php')) ?>" class="nav-link nav-link-muted">Logout</a>
    </nav>
</aside>
