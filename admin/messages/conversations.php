<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activeAdminPage = 'messages';
$pageTitle = 'Conversations';
$statement = db()->query(
    'SELECT
        c.*,
        u1.name AS user_one_name,
        u2.name AS user_two_name,
        g.name AS group_name
     FROM conversations c
     LEFT JOIN users u1 ON u1.id = c.user_one
     LEFT JOIN users u2 ON u2.id = c.user_two
     LEFT JOIN chat_groups g ON g.id = c.group_id
     ORDER BY c.last_message_time DESC, c.created_at DESC'
);
$conversations = $statement->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel compact-panel">
            <div>
                <span class="eyebrow">Messages</span>
                <h2>Conversation oversight</h2>
                <p>Review all chat threads in the application and inspect message histories.</p>
            </div>
        </section>

        <section class="panel-card">
            <div class="admin-table">
                <?php foreach ($conversations as $conversation): ?>
                    <article class="admin-table-row">
                        <div>
                            <strong><?= e(admin_conversation_label($conversation)) ?></strong>
                            <p><?= e($conversation['last_message'] ?: 'No messages yet') ?></p>
                        </div>
                        <div class="admin-table-meta">
                            <span><?= e($conversation['conversation_type']) ?></span>
                            <span><?= e($conversation['last_message_time'] ? time_ago($conversation['last_message_time']) : 'New') ?></span>
                        </div>
                        <div class="admin-table-actions">
                            <a href="<?= e(admin_base_path('messages/view_chat.php?id=' . (int) $conversation['id'])) ?>" class="btn btn-primary btn-small">View Chat</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
