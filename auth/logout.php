<?php

require_once __DIR__ . '/../includes/functions.php';

logout_user();
session_start();
set_flash('success', 'You have been logged out.');
redirect('auth/login.php');
