<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Rental System'; ?> | Pangisha</title>
    <link href="<?php echo ASSETS_URL; ?>css/landing.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>css/auth.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <header class="landing-header">
        <div class="header-content">
            <img src="<?= ASSETS_URL ?>img/pangisha-logo.png" alt="Pangisha" class="logo">
            <div class="auth-buttons">
                <a href="<?= BASE_URL ?>login.php" class="btn btn-outline">Login</a>
                <a href="<?= BASE_URL ?>register.php" class="btn btn-primary">Register</a>
            </div>
        </div>
    </header>
    
    <div class="container">