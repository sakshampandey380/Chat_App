<?php

require_once __DIR__ . '/auth/auth_check.php';

$activeAdminPage = 'dashboard';
$pageTitle = 'Admin Dashboard';
$stats = admin_stats();
$recentActivity = admin_recent_activity();
$recentLogins = admin_recent_logins();

include __DIR__ . '/includes/header.php';
?>
<div class="dashboard-layout admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel admin-hero-panel">
            <div>
                <span class="eyebrow">Administration</span>
                <h2>Control the chat platform professionally</h2>
                <p>Review users, conversations, branding, and admin activity from one dedicated workspace.</p>
            </div>
            <div class="hero-actions">
                <a href="<?= e(admin_base_path('users/user_list.php')) ?>" class="btn btn-primary">Manage Users</a>
                <a href="<?= e(admin_base_path('profile/edit_profile.php')) ?>" class="btn btn-secondary">Edit Profile</a>
            </div>
        </section>

        <section class="stats-grid admin-stats-grid">
            <article class="stat-card">
                <span class="stat-label">Admins</span>
                <strong><?= e((string) $stats['admins']) ?></strong>
                <p>Total administrator accounts.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Users</span>
                <strong><?= e((string) $stats['users']) ?></strong>
                <p>Registered user accounts.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Conversations</span>
                <strong><?= e((string) $stats['conversations']) ?></strong>
                <p>All direct and group conversations.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Messages</span>
                <strong><?= e((string) $stats['messages']) ?></strong>
                <p>Total messages stored in the app.</p>
            </article>
        </section>

        <section class="content-grid admin-content-grid">
            <article class="panel-card">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">Recent Activity</span>
                        <h3>Admin actions</h3>
                    </div>
                </div>

                <div class="admin-log-list">
                    <?php if ($recentActivity): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="admin-log-item">
                                <strong><?= e($activity['admin_name']) ?></strong>
                                <p><?= e($activity['action']) ?></p>
                                <span><?= e(time_ago($activity['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-card slim">
                            <h3>No activity yet</h3>
                            <p>Admin actions will appear here after the first changes are made.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="panel-card">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">Login Tracking</span>
                        <h3>Recent admin logins</h3>
                    </div>
                </div>

                <div class="admin-log-list">
                    <?php if ($recentLogins): ?>
                        <?php foreach ($recentLogins as $login): ?>
                            <div class="admin-log-item">
                                <strong><?= e($login['admin_name']) ?></strong>
                                <p><?= e($login['ip_address'] ?: 'IP unavailable') ?></p>
                                <span><?= e(format_datetime($login['login_time'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-card slim">
                            <h3>No login logs yet</h3>
                            <p>Admin login history will appear here after sign in.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </main>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
