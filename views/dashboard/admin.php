<?php
require_once __DIR__.'/../../../includes/auth.php';

if(!isAdmin()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

$pageTitle = "Admin Dashboard";
$bodyClass = "dashboard";
require_once __DIR__.'/../../../views/layouts/header.php';

// Get stats
$users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$properties = $db->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$payments = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'success'")->fetchColumn();
?>

<div class="dashboard-container">
    <div class="sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="<?= BASE_URL ?>dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>users.php">
                    <i class="bi bi-people"></i> Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>properties.php">
                    <i class="bi bi-house"></i> Properties
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>payments.php">
                    <i class="bi bi-cash-coin"></i> Payments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>reports.php">
                    <i class="bi bi-graph-up"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?= BASE_URL ?>settings.php">
                    <i class="bi bi-gear"></i> Settings
                </a>
            </li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <div class="welcome-message">
                Welcome back, <?php echo $_SESSION['user_name']; ?>
            </div>
        </div>
        
        <div class="stats-cards">
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2 class="stat-number"><?php echo $users; ?></h2>
                    <a href="<?= BASE_URL ?>users.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
            </div>
            
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Properties</h5>
                    <h2 class="stat-number"><?php echo $properties; ?></h2>
                    <a href="<?= BASE_URL ?>properties.php" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
            </div>
            
            <div class="card stat-card">
                <div class="card-body">
                    <h5 class="card-title">Total Revenue</h5>
                    <h2 class="stat-number">KSh <?php echo number_format($payments, 2); ?></h2>
                    <a href="<?= BASE_URL ?>payments.php" class="btn btn-sm btn-outline-primary">View</a>
                </div>
            </div>
        </div>
        
        <div class="recent-activity mt-4">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="activity-item">
                        <div class="activity-icon bg-primary">
                            <i class="bi bi-house"></i>
                        </div>
                        <div class="activity-content">
                            <span class="activity-time">2 mins ago</span>
                            <p>New property added by Agent Kamau</p>
                        </div>
                    </div>
                    <!-- More activity items -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
require_once __DIR__.'/../../../views/layouts/footer.php';