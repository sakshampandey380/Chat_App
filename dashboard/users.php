<?php

require_once __DIR__ . '/../auth/auth_check.php';

$activePage = 'users';
$pageTitle = 'People';
$showFlash = true;

if (is_post()) {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_group') {
            $groupName = trim($_POST['group_name'] ?? '');
            $memberIds = $_POST['member_ids'] ?? [];
            $groupImage = store_uploaded_file(
                $_FILES['group_image'] ?? [],
                'uploads/groups',
                ['image/jpeg', 'image/png', 'image/webp', 'image/gif']
            );

            $group = create_group_chat((int) $currentUser['id'], $groupName, $memberIds, $groupImage);
            set_flash('success', 'Group created successfully.');
            redirect('dashboard/chat.php?type=group&id=' . (int) $group['id']);
        }
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        redirect('dashboard/users.php');
    }
}

$people = get_people_directory((int) $currentUser['id']);
$includeUsersScript = true;

include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-main">
        <section class="hero-panel compact-panel">
            <div>
                <span class="eyebrow">People directory</span>
                <h2>Everyone available to message</h2>
                <p>Use this page to discover users, check who is online, and jump directly into a conversation.</p>
            </div>
            <button type="button" class="btn btn-primary" data-open-group-modal>Form Group</button>
        </section>

        <section class="user-grid">
            <?php if ($people): ?>
                <?php foreach ($people as $person): ?>
                    <article class="user-card">
                        <div class="user-card-head">
                            <?php if (!empty($person['avatar_url'])): ?>
                                <img src="<?= e($person['avatar_url']) ?>" alt="<?= e($person['name']) ?>" class="avatar-image avatar-large">
                            <?php else: ?>
                                <div class="avatar-fallback avatar-large"><?= e($person['initials']) ?></div>
                            <?php endif; ?>
                            <span class="status-pill <?= $person['status'] === 'online' ? 'is-online' : 'is-offline' ?>">
                                <?= e($person['status'] === 'online' ? 'Online' : 'Offline') ?>
                            </span>
                        </div>

                        <div class="user-card-copy">
                            <h3><?= e($person['name']) ?></h3>
                            <p class="user-card-email"><?= e($person['email']) ?></p>
                            <?php if (!empty($person['phone'])): ?>
                                <p><?= e($person['phone']) ?></p>
                            <?php endif; ?>
                            <small><?= e($person['status_text']) ?></small>
                        </div>

                        <div class="user-card-actions">
                            <a href="<?= e($person['chat_href']) ?>" class="btn btn-primary btn-full">
                                <?= $person['conversation_id'] ? 'Continue Chat' : 'Start Chat' ?>
                            </a>
                            <button
                                type="button"
                                class="btn btn-secondary btn-full"
                                data-quick-group
                                data-member-id="<?= e((string) $person['id']) ?>"
                            >
                                Add To Group
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-card">
                    <h3>No other users yet</h3>
                    <p>Create another account so the chat directory can fill up and the real-time screens become usable.</p>
                </div>
            <?php endif; ?>
        </section>

        <div class="modal-shell" data-group-modal hidden>
            <div class="modal-backdrop" data-close-group-modal></div>
            <section class="modal-card">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">Create Group</span>
                        <h3>Choose members, name, and image</h3>
                    </div>
                    <button type="button" class="btn btn-secondary btn-small" data-close-group-modal>Close</button>
                </div>

                <form method="post" enctype="multipart/form-data" class="form-stack">
                    <input type="hidden" name="action" value="create_group">

                    <div class="input-row">
                        <label class="input-group">
                            <span>Group name</span>
                            <input type="text" name="group_name" placeholder="Weekend Crew" required>
                        </label>

                        <label class="input-group">
                            <span>Group image</span>
                            <input type="file" name="group_image" accept="image/*">
                        </label>
                    </div>

                    <div class="group-member-list">
                        <?php foreach ($people as $person): ?>
                            <label class="group-member-item">
                                <input type="checkbox" name="member_ids[]" value="<?= e((string) $person['id']) ?>">
                                <div class="contact-identity">
                                    <?php if (!empty($person['avatar_url'])): ?>
                                        <img src="<?= e($person['avatar_url']) ?>" alt="<?= e($person['name']) ?>" class="avatar-image">
                                    <?php else: ?>
                                        <div class="avatar-fallback"><?= e($person['initials']) ?></div>
                                    <?php endif; ?>
                                    <div class="chat-user-copy">
                                        <strong><?= e($person['name']) ?></strong>
                                        <p><?= e($person['email']) ?></p>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Create Group</button>
                </form>
            </section>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
