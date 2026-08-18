<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/session.php';

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ".BASE_URL."login.php");
exit;