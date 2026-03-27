<?php

require_once __DIR__ . '/../includes/admin_function.php';

logout_admin();
set_flash('success', 'Admin logged out successfully.');
admin_redirect('auth/login.php');
