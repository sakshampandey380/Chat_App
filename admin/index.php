<?php

require_once __DIR__ . '/includes/admin_function.php';

if (current_admin()) {
    admin_redirect('dashboard.php');
}

admin_redirect('auth/login.php');
