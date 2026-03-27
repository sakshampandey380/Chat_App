<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activePage = 'chat';
$pageTitle = 'Messages';
$roster = get_chat_roster((int) $currentUser['id']);

$selectedType = $_GET['type'] ?? (isset($_GET['user']) ? 'direct' : '');
$selectedId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_GET['user']) ? (int) $_GET['user'] : 0);

if (!$selectedType && $roster) {
    $selectedType = $roster[0]['chat_type'];
    $selectedId = (int) $roster[0]['target_id'];
}

$selectedChat = $selectedId > 0 ? get_chat_view((int) $currentUser['id'], $selectedType, $selectedId) : null;
$initialMessages = [];

if ($selectedChat && !empty($selectedChat['conversation_id'])) {
    $initialMessages = get_messages_for_conversation((int) $selectedChat['conversation_id']);
    mark_messages_seen((int) $selectedChat['conversation_id'], (int) $currentUser['id']);
}

$includeAjaxScript = true;
$includeChatScript = true;
$showFlash = false;
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main
        class="dashboard-main"
        data-chat-app
        data-current-user-id="<?= e((string) $currentUser['id']) ?>"
        data-selected-chat-type="<?= e($selectedChat['chat_type'] ?? '') ?>"
        data-selected-target-id="<?= e($selectedChat ? (string) $selectedChat['target_id'] : '') ?>"
        data-chat-page="<?= e(base_path('dashboard/chat.php')) ?>"
        data-users-endpoint="<?= e(base_path('api/fetch_users.php')) ?>"
        data-messages-endpoint="<?= e(base_path('api/fetch_message.php')) ?>"
        data-send-endpoint="<?= e(base_path('api/send_message.php')) ?>"
        data-seen-endpoint="<?= e(base_path('api/seen_status.php')) ?>"
    >
        <section class="chat-shell">
            <aside class="chat-sidebar">
                <div class="chat-sidebar-head">
                    <div>
                        <h2>Inbox</h2>
                    </div>
                    <a href="<?= e(base_path('dashboard/users.php')) ?>" class="btn btn-secondary btn-small">New chat</a>
                </div>

                <div class="chat-list" id="conversationRoster">
                    <?php if ($roster): ?>
                        <?php foreach ($roster as $chat): ?>
                            <button
                                type="button"
                                class="chat-user <?= $selectedChat && $selectedChat['chat_type'] === $chat['chat_type'] && (int) $selectedChat['target_id'] === (int) $chat['target_id'] ? 'is-active' : '' ?>"
                                data-chat-type="<?= e($chat['chat_type']) ?>"
                                data-chat-target="<?= e((string) $chat['target_id']) ?>"
                            >
                                <div class="contact-identity">
                                    <?php if (!empty($chat['avatar_url'])): ?>
                                        <img src="<?= e($chat['avatar_url']) ?>" alt="<?= e($chat['name']) ?>" class="avatar-image">
                                    <?php else: ?>
                                        <div class="avatar-fallback"><?= e($chat['initials']) ?></div>
                                    <?php endif; ?>

                                    <div class="chat-user-copy">
                                        <strong><?= e($chat['name']) ?></strong>
                                        <p><?= e($chat['last_message'] ?: 'Start a new conversation') ?></p>
                                    </div>
                                </div>
                                <div class="chat-user-meta">
                                    <span><?= e($chat['last_message_time'] ? time_ago($chat['last_message_time']) : 'New') ?></span>
                                    <?php if ((int) ($chat['unread_count'] ?? 0) > 0): ?>
                                        <span class="unread-pill"><?= e((string) $chat['unread_count']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </button>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-card slim">
                            <h3>No contacts available</h3>
                            <p>Create one more account first, then return here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="chat-panel">
                <?php if ($selectedChat): ?>
                    <header class="chat-header" id="chatHeader">
                        <div class="contact-identity">
                            <?php if (!empty($selectedChat['avatar_url'])): ?>
                                <img src="<?= e($selectedChat['avatar_url']) ?>" alt="<?= e($selectedChat['name']) ?>" class="avatar-image avatar-large">
                            <?php else: ?>
                                <div class="avatar-fallback avatar-large"><?= e($selectedChat['initials']) ?></div>
                            <?php endif; ?>

                            <div>
                                <h2><?= e($selectedChat['name']) ?></h2>
                                <p><?= e($selectedChat['status_text']) ?></p>
                            </div>
                        </div>
                    </header>

                    <div class="messages-board" id="messagesBoard">
                        <?php if ($initialMessages): ?>
                            <?php foreach ($initialMessages as $message): ?>
                                <?php $mine = (int) $message['sender_id'] === (int) $currentUser['id']; ?>
                                <article class="message-row <?= $mine ? 'is-mine' : '' ?>">
                                    <div class="message-bubble">
                                        <?php if (!empty($message['media'])): ?>
                                            <img src="<?= e(base_path($message['media'])) ?>" alt="Shared media" class="message-media">
                                        <?php endif; ?>
                                        <?php if (!empty($message['message'])): ?>
                                            <p><?= nl2br(e($message['message'])) ?></p>
                                        <?php endif; ?>
                                        <span><?= e(date('h:i A', strtotime($message['created_at']))) ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-chat" id="emptyChat">
                                <span class="eyebrow">Conversation ready</span>
                                <h3>Send the first message</h3>
                                <p>This thread is connected to your database and will start filling as soon as you send a text or image.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form id="messageForm" class="composer" enctype="multipart/form-data">
                        <input type="hidden" name="chat_type" value="<?= e($selectedChat['chat_type']) ?>">
                        <input type="hidden" name="target_id" value="<?= e((string) $selectedChat['target_id']) ?>">
                        <label class="composer-attach">
                            <input type="file" name="media" id="mediaInput" accept="image/*">
                            <span data-attach-label>Attach image</span>
                        </label>
                        <textarea name="message" id="messageInput" rows="1" placeholder="Write your message"></textarea>
                        <button type="submit" class="btn btn-primary">Send</button>
                    </form>
                    <p class="composer-hint" id="composerHint" aria-live="polite"></p>
                <?php else: ?>
                    <div class="empty-chat large">
                        <span class="eyebrow">No conversation selected</span>
                        <h3>Open a contact or group to begin</h3>
                        <p>Create another account or form a group from the People page to start chatting.</p>
                    </div>
                <?php endif; ?>
            </section>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
