<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activeAdminPage = 'users';
$pageTitle = 'View User';
$userId = (int) ($_GET['id'] ?? 0);
$user = get_user_by_id($userId);

if (!$user) {
    set_flash('error', 'User not found.');
    admin_redirect('users/user_list.php');
}

$messageCountStmt = db()->prepare('SELECT COUNT(*) FROM messages WHERE sender_id = :id');
$messageCountStmt->execute(['id' => $userId]);
$messageCount = (int) $messageCountStmt->fetchColumn();

$conversationCountStmt = db()->prepare(
    'SELECT COUNT(*) FROM conversations WHERE user_one = :id OR user_two = :id'
);
$conversationCountStmt->execute(['id' => $userId]);
$conversationCount = (int) $conversationCountStmt->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel compact-panel">
            <div>
                <span class="eyebrow">User Profile</span>
                <h2><?= e($user['name']) ?></h2>
                <p>Review this user's account details, usage, and account status.</p>
            </div>
            <div class="hero-actions">
                <a href="<?= e(admin_base_path('users/edit_user.php?id=' . $userId)) ?>" class="btn btn-primary">Edit User</a>
                <a href="<?= e(admin_base_path('users/user_list.php')) ?>" class="btn btn-secondary">Back To List</a>
            </div>
        </section>

        <section class="content-grid admin-content-grid">
            <article class="panel-card">
                <div class="profile-preview">
                    <?php if (user_avatar_url($user)): ?>
                        <img src="<?= e(user_avatar_url($user)) ?>" alt="<?= e($user['name']) ?>" class="avatar-image avatar-xl">
                    <?php else: ?>
                        <div class="avatar-fallback avatar-xl"><?= e(user_initials($user['name'])) ?></div>
                    <?php endif; ?>
                    <div>
                        <strong><?= e($user['name']) ?></strong>
                        <p><?= e($user['email']) ?></p>
                        <p><?= e($user['phone'] ?: 'No phone added') ?></p>
                        <span class="status-pill <?= $user['status'] === 'online' ? 'is-online' : 'is-offline' ?>"><?= e(ucfirst($user['status'])) ?></span>
                    </div>
                </div>
            </article>

            <article class="panel-card">
                <div class="stats-grid admin-mini-stats">
                    <article class="stat-card">
                        <span class="stat-label">Joined</span>
                        <strong><?= e(date('d', strtotime($user['created_at']))) ?></strong>
                        <p><?= e(date('M Y', strtotime($user['created_at']))) ?></p>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">Messages</span>
                        <strong><?= e((string) $messageCount) ?></strong>
                        <p>Total sent messages.</p>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">Chats</span>
                        <strong><?= e((string) $conversationCount) ?></strong>
                        <p>Total conversations.</p>
                    </article>
                </div>
            </article>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
