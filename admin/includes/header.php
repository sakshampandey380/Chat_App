<?php

require_once __DIR__ . '/admin_function.php';

$pageTitle = $pageTitle ?? 'Admin';
$adminBodyClass = $adminBodyClass ?? 'admin-page';
$adminCurrent = $adminCurrent ?? current_admin();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(app_name()) ?> Admin</title>
    <link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/chat.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('assets/css/responsive.css')) ?>">
</head>
<body class="<?= e($adminBodyClass) ?>">
    <div class="ambient-scene" aria-hidden="true">
        <span class="ambient-orb ambient-orb-one"></span>
        <span class="ambient-orb ambient-orb-two"></span>
        <span class="ambient-orb ambient-orb-three"></span>
        <span class="ambient-grid"></span>
    </div>

    <div class="page-shell">
        <?php if ($flash): ?>
            <div class="flash flash-<?= e($flash['type']) ?>">
                <span><?= e($flash['message']) ?></span>
                <button type="button" class="flash-close" data-flash-close>Dismiss</button>
            </div>
        <?php endif; ?>
