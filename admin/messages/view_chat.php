<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activeAdminPage = 'messages';
$pageTitle = 'View Chat';
$conversationId = (int) ($_GET['id'] ?? 0);
$conversationStatement = db()->prepare(
    'SELECT
        c.*,
        u1.name AS user_one_name,
        u2.name AS user_two_name,
        g.name AS group_name
     FROM conversations c
     LEFT JOIN users u1 ON u1.id = c.user_one
     LEFT JOIN users u2 ON u2.id = c.user_two
     LEFT JOIN chat_groups g ON g.id = c.group_id
     WHERE c.id = :id
     LIMIT 1'
);
$conversationStatement->execute(['id' => $conversationId]);
$conversation = $conversationStatement->fetch();

if (!$conversation) {
    set_flash('error', 'Conversation not found.');
    admin_redirect('messages/conversations.php');
}

$messages = get_messages_for_conversation($conversationId);

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel compact-panel">
            <div>
                <span class="eyebrow">Conversation Review</span>
                <h2><?= e(admin_conversation_label($conversation)) ?></h2>
                <p>Inspect message history and remove content when necessary.</p>
            </div>
            <a href="<?= e(admin_base_path('messages/conversations.php')) ?>" class="btn btn-secondary">Back</a>
        </section>

        <section class="panel-card">
            <div class="admin-message-list">
                <?php if ($messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <article class="admin-message-item">
                            <div>
                                <strong><?= e($message['sender_name']) ?></strong>
                                <?php if (!empty($message['media'])): ?>
                                    <img src="<?= e(base_path($message['media'])) ?>" alt="Shared media" class="message-media">
                                <?php endif; ?>
                                <?php if (!empty($message['message'])): ?>
                                    <p><?= nl2br(e($message['message'])) ?></p>
                                <?php endif; ?>
                                <span><?= e(format_datetime($message['created_at'])) ?></span>
                            </div>
                            <a href="<?= e(admin_base_path('messages/delete_messages.php?id=' . (int) $message['id'] . '&conversation_id=' . $conversationId)) ?>" class="btn btn-secondary btn-small">Delete</a>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-card slim">
                        <h3>No messages yet</h3>
                        <p>This conversation is still empty.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
