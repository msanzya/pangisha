<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Rental System'; ?> | Pangisha</title>
    <link href="<?php echo ASSETS_URL; ?>css/dashboard.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="admin-dashboard">
    <header class="dashboard-header">
        <div class="header-content">
            <img src="<?= ASSETS_URL ?>img/pangisha-logo.png" alt="Pangisha" class="logo">
            <div class="user-controls">
                <span class="welcome">Welcome, <?php echo $_SESSION['user_name'] ?? 'Guest'; ?></span>
                <a href="<?= BASE_URL ?>logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </header>
    
    <nav class="dashboard-nav">
        <ul>
            <li><a href="<?= BASE_URL ?>dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>users.php"><i class="bi bi-people"></i> Users</a></li>
            <li><a href="<?= BASE_URL ?>properties.php"><i class="bi bi-house-door"></i> Properties</a></li>
            <li><a href="<?= BASE_URL ?>contracts.php"><i class="bi bi-file-text"></i> Contracts</a></li>
            <li><a href="<?= BASE_URL ?>payments.php"><i class="bi bi-cash-stack"></i> Payments</a></li>
            <li><a href="<?= BASE_URL ?>issues.php"><i class="bi bi-tools"></i> Maintenance</a></li>
            <li><a href="<?= BASE_URL ?>reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
            <li><a href="<?= BASE_URL ?>settings.php"><i class="bi bi-gear"></i> Settings</a></li>
        </ul>
    </nav>
    
    <div class="dashboard-container">