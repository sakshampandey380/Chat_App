<?php
$activeAdminPage = $activeAdminPage ?? '';
$adminCurrent = $adminCurrent ?? current_admin();
?>
<aside class="sidebar-card admin-sidebar">
    <div class="brand-mark">
        <?php if (app_logo_url()): ?>
            <img src="<?= e(app_logo_url()) ?>" alt="<?= e(app_name()) ?>" class="brand-image">
        <?php else: ?>
            <div class="brand-icon"><?= e(strtoupper(substr(app_name(), 0, 1))) ?></div>
        <?php endif; ?>
        <div>
            <p class="eyebrow">Admin Console</p>
            <h1><?= e(app_name()) ?></h1>
        </div>
    </div>

    <div class="profile-tile admin-profile-tile">
        <?php if (admin_avatar_url($adminCurrent)): ?>
            <img src="<?= e(admin_avatar_url($adminCurrent)) ?>" alt="<?= e($adminCurrent['name']) ?>" class="avatar-image avatar-large">
        <?php else: ?>
            <div class="avatar-fallback avatar-large"><?= e(user_initials($adminCurrent['name'])) ?></div>
        <?php endif; ?>
        <div>
            <strong><?= e($adminCurrent['name']) ?></strong>
            <p><?= e($adminCurrent['email']) ?></p>
            <span class="status-pill admin-status-pill"><?= e(ucwords(str_replace('_', ' ', $adminCurrent['role'] ?? 'admin'))) ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="<?= e(admin_base_path('dashboard.php')) ?>" class="nav-link <?= $activeAdminPage === 'dashboard' ? 'is-active' : '' ?>">Dashboard</a>
        <a href="<?= e(admin_base_path('users/user_list.php')) ?>" class="nav-link <?= $activeAdminPage === 'users' ? 'is-active' : '' ?>">Users</a>
        <a href="<?= e(admin_base_path('messages/conversations.php')) ?>" class="nav-link <?= $activeAdminPage === 'messages' ? 'is-active' : '' ?>">Messages</a>
        <a href="<?= e(admin_base_path('profile/edit_profile.php')) ?>" class="nav-link <?= $activeAdminPage === 'profile' ? 'is-active' : '' ?>">Profile</a>
        <a href="<?= e(admin_base_path('auth/logout.php')) ?>" class="nav-link nav-link-muted">Logout</a>
    </nav>
</aside>
