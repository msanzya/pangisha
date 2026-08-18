<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';  // This will start the session
require __DIR__.'/../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

$role = $_SESSION['user_role'];
$dashboardFile = VIEWS_PATH."/dashboard/$role/index.php";

if (file_exists($dashboardFile)) {
    require $dashboardFile;
} else {
    header("HTTP/1.1 404 Not Found");
    die("Dashboard not found for role: $role");
}