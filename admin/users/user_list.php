<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activeAdminPage = 'users';
$pageTitle = 'User List';
$users = db()->query(
    'SELECT
        u.*,
        (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id) AS message_count
     FROM users u
     ORDER BY u.created_at DESC'
)->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel compact-panel">
            <div>
                <span class="eyebrow">Users</span>
                <h2>Manage app members</h2>
                <p>Review user profiles, edit their details, or open deeper user records.</p>
            </div>
        </section>

        <section class="panel-card">
            <div class="panel-head">
                <div>
                    <span class="eyebrow">Directory</span>
                    <h3>Registered users</h3>
                </div>
            </div>

            <div class="admin-table">
                <?php foreach ($users as $user): ?>
                    <article class="admin-table-row">
                        <div class="contact-identity">
                            <?php if (user_avatar_url($user)): ?>
                                <img src="<?= e(user_avatar_url($user)) ?>" alt="<?= e($user['name']) ?>" class="avatar-image">
                            <?php else: ?>
                                <div class="avatar-fallback"><?= e(user_initials($user['name'])) ?></div>
                            <?php endif; ?>
                            <div class="chat-user-copy">
                                <strong><?= e($user['name']) ?></strong>
                                <p><?= e($user['email']) ?></p>
                            </div>
                        </div>

                        <div class="admin-table-meta">
                            <span><?= e($user['status']) ?></span>
                            <span><?= e((string) $user['message_count']) ?> messages</span>
                            <span><?= e(format_datetime($user['created_at'], 'M d, Y')) ?></span>
                        </div>

                        <div class="admin-table-actions">
                            <a href="<?= e(admin_base_path('users/view_user.php?id=' . (int) $user['id'])) ?>" class="btn btn-secondary btn-small">View</a>
                            <a href="<?= e(admin_base_path('users/edit_user.php?id=' . (int) $user['id'])) ?>" class="btn btn-primary btn-small">Edit</a>
                            <a href="<?= e(admin_base_path('users/delete_user.php?id=' . (int) $user['id'])) ?>" class="btn btn-secondary btn-small">Delete</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
