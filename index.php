<?php
require __DIR__.'/config/paths.php';
require __DIR__.'/config/session.php';
require __DIR__.'/includes/auth.php';

if (isLoggedIn()) {
    header("Location: ".BASE_URL."dashboard.php");
    exit;
}

if (file_exists(PUBLIC_PATH.'/login.php')) {
    header("Location: ".BASE_URL."login.php");
} else {
    die("System not properly configured");
}
exit;