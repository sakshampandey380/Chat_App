    </div>

    <?php if ($includeAjaxScript): ?>
        <script src="<?= e(asset('assets/js/ajax.js')) ?>"></script>
    <?php endif; ?>
    <?php if ($includeMainScript): ?>
        <script src="<?= e(asset('assets/js/main.js')) ?>"></script>
    <?php endif; ?>
    <?php if ($includeUsersScript): ?>
        <script src="<?= e(asset('assets/js/users.js')) ?>"></script>
    <?php endif; ?>
    <?php if ($includeChatScript): ?>
        <script src="<?= e(asset('assets/js/chat.js')) ?>"></script>
    <?php endif; ?>
</body>
</html>
