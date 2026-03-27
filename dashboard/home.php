<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activePage = 'home';
$pageTitle = 'Dashboard';
$stats = get_dashboard_stats((int) $currentUser['id']);
$roster = array_slice(get_chat_roster((int) $currentUser['id']), 0, 4);
$showFlash = false;

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel">
            <div>
                <span class="eyebrow">Control center</span>
                <h2>Welcome, <?= e($currentUser['name']) ?></h2>
                <p>Your chat project is now structured with secure auth, polished screens, and a visual system that already feels production-ready.</p>
            </div>
            <div class="hero-actions">
                <a href="<?= e(base_path('dashboard/chat.php')) ?>" class="btn btn-primary">Open Messages</a>
                <a href="<?= e(base_path('dashboard/users.php')) ?>" class="btn btn-secondary">Browse People</a>
            </div>
        </section>

        <section class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Contacts</span>
                <strong><?= e((string) $stats['total_users']) ?></strong>
                <p>People ready to chat with you.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Conversations</span>
                <strong><?= e((string) $stats['total_conversations']) ?></strong>
                <p>Threaded discussions tracked in your database.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Messages</span>
                <strong><?= e((string) $stats['total_messages']) ?></strong>
                <p>Total messages across your conversations.</p>
            </article>
            <article class="stat-card">
                <span class="stat-label">Online</span>
                <strong><?= e((string) $stats['online_users']) ?></strong>
                <p>Active people available right now.</p>
            </article>
        </section>

        <section class="content-grid">
            <article class="panel-card">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">Recent chats</span>
                        <h3>Continue where you left off</h3>
                    </div>
                    <a href="<?= e(base_path('dashboard/chat.php')) ?>" class="text-link">See all</a>
                </div>

                <?php if ($roster): ?>
                    <div class="contact-list">
                        <?php foreach ($roster as $person): ?>
                            <a href="<?= e($person['chat_href']) ?>" class="contact-row">
                                <div class="contact-identity">
                                    <?php if (!empty($person['avatar_url'])): ?>
                                        <img src="<?= e($person['avatar_url']) ?>" alt="<?= e($person['name']) ?>" class="avatar-image">
                                    <?php else: ?>
                                        <div class="avatar-fallback"><?= e($person['initials']) ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= e($person['name']) ?></strong>
                                        <p><?= e($person['last_message'] ?: 'Start the first message') ?></p>
                                    </div>
                                </div>
                                <span class="contact-time"><?= e($person['last_message_time'] ? time_ago($person['last_message_time']) : 'New') ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-card">
                        <h3>No conversations yet</h3>
                        <p>Create a second account, then open the People page to start your first chat.</p>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
