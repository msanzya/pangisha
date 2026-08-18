<?php
// Include the standard paths configuration
require __DIR__.'/../../../config/paths.php';
require __DIR__.'/../../../config/db.php';
require __DIR__.'/../../../config/session.php';
require_once __DIR__.'/../../../includes/auth.php';

if(!isTenant()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get tenant information
$tenantStmt = $db->prepare("SELECT id FROM tenants WHERE user_id = ?");
$tenantStmt->execute([$_SESSION['user_id']]);
$tenantId = $tenantStmt->fetchColumn();

// Get tenant stats
$stats = [
    'payments' => [
        'total' => 0,
        'rent' => 0,
        'deposits' => 0
    ],
    'contracts' => [
        'active' => 0,
        'total' => 0
    ],
    'viewings' => [
        'scheduled' => 0,
        'completed' => 0
    ]
];

if ($tenantId) {
    // Payment statistics
    $payStmt = $db->prepare("SELECT COUNT(*) FROM payments WHERE payer_id = ?");
    $payStmt->execute([$_SESSION['user_id']]);
    $stats['payments']['total'] = $payStmt->fetchColumn();
    
    $payStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payment_type='rent'");
    $payStmt->execute([$_SESSION['user_id']]);
    $stats['payments']['rent'] = $payStmt->fetchColumn();
    
    $payStmt = $db->prepare("SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payment_type='deposit'");
    $payStmt->execute([$_SESSION['user_id']]);
    $stats['payments']['deposits'] = $payStmt->fetchColumn();
    
    // Contract statistics
    $contStmt = $db->prepare("SELECT COUNT(*) FROM rent_contracts WHERE tenant_id = ? AND status='active'");
    $contStmt->execute([$tenantId]);
    $stats['contracts']['active'] = $contStmt->fetchColumn();
    
    $contStmt = $db->prepare("SELECT COUNT(*) FROM rent_contracts WHERE tenant_id = ?");
    $contStmt->execute([$tenantId]);
    $stats['contracts']['total'] = $contStmt->fetchColumn();
    
    // Viewing statistics
    $viewStmt = $db->prepare("SELECT COUNT(*) FROM property_viewings WHERE tenant_id = ? AND status='scheduled'");
    $viewStmt->execute([$tenantId]);
    $stats['viewings']['scheduled'] = $viewStmt->fetchColumn();
    
    $viewStmt = $db->prepare("SELECT COUNT(*) FROM property_viewings WHERE tenant_id = ? AND status='completed'");
    $viewStmt->execute([$tenantId]);
    $stats['viewings']['completed'] = $viewStmt->fetchColumn();
}

// Get tenant's active contracts
$activeContracts = [];
if ($tenantId) {
    $contracts = $db->prepare("
        SELECT rc.*, p.title as property_title, l_u.name as landlord_name
        FROM rent_contracts rc
        JOIN properties p ON rc.property_id = p.id
        JOIN landlords l ON p.landlord_id = l.id
        JOIN users l_u ON l.user_id = l_u.id
        WHERE rc.tenant_id = ? AND rc.status = 'active'
        ORDER BY rc.start_date DESC
    ");
    $contracts->execute([$tenantId]);
    $activeContracts = $contracts->fetchAll();
}

// Get recent payments
$recentPayments = [];
if ($tenantId) {
    $payments = $db->prepare("
        SELECT p.*, prop.title as property_title
        FROM payments p
        JOIN rent_contracts rc ON p.contract_id = rc.id
        JOIN properties prop ON rc.property_id = prop.id
        WHERE p.payer_id = ?
        ORDER BY p.payment_date DESC
        LIMIT 5
    ");
    $payments->execute([$_SESSION['user_id']]);
    $recentPayments = $payments->fetchAll();
}

// Get upcoming viewings
$upcomingViewings = [];
if ($tenantId) {
    $viewings = $db->prepare("
        SELECT v.*, p.title as property_title, a_u.name as agent_name
        FROM property_viewings v
        JOIN properties p ON v.property_id = p.id
        LEFT JOIN agents a ON p.agent_id = a.id
        LEFT JOIN users a_u ON a.user_id = a_u.id
        WHERE v.tenant_id = ?
        AND v.scheduled_date >= CURDATE()
        ORDER BY v.scheduled_date ASC
        LIMIT 5
    ");
    $viewings->execute([$tenantId]);
    $upcomingViewings = $viewings->fetchAll();
}

// Get maintenance issues reported by tenant
$maintenanceIssues = [];
if ($tenantId) {
    $issues = $db->prepare("
        SELECT mi.*, p.title as property_title
        FROM maintenance_issues mi
        JOIN properties p ON mi.property_id = p.id
        WHERE mi.reported_by = ?
        ORDER BY mi.created_at DESC
        LIMIT 5
    ");
    $issues->execute([$_SESSION['user_id']]);
    $maintenanceIssues = $issues->fetchAll();
}

$pageTitle = "Tenant Dashboard";
?>

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
    <!-- Simplified Header -->
    <header class="dashboard-header">
        <div class="header-content">
            <img src="<?= ASSETS_URL ?>img/pangisha-logo.png" alt="Pangisha" class="logo">
            <div class="user-controls">
                <span class="welcome">Welcome, <?= $_SESSION['user_name'] ?></span>
                <a href="<?= BASE_URL ?>logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="dashboard-container">
        <!-- Platform Overview -->
        <section class="dashboard-section">
            <h2><i class="bi bi-speedometer2"></i> Tenant Dashboard</h2>
            <div class="stats-grid">
                <!-- Payment Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #28a745;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-content">
                        <h3>My Payments</h3>
                        <div class="stat-breakdown">
                            <span>Total Payments: <?= $stats['payments']['total'] ?></span>
                            <span>Rent Paid: <?= formatCurrency($stats['payments']['rent'] ?? 0) ?></span>
                            <span>Deposits Paid: <?= formatCurrency($stats['payments']['deposits'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Contract Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #0CC0DF;">
                        <i class="bi bi-file-text"></i>
                    </div>
                    <div class="stat-content">
                        <h3>My Contracts</h3>
                        <div class="stat-breakdown">
                            <span>Active Contracts: <?= $stats['contracts']['active'] ?></span>
                            <span>Total Contracts: <?= $stats['contracts']['total'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Viewing Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #6f42c1;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Property Viewings</h3>
                        <div class="stat-breakdown">
                            <span>Scheduled: <?= $stats['viewings']['scheduled'] ?></span>
                            <span>Completed: <?= $stats['viewings']['completed'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Active Contracts -->
        <section class="dashboard-section">
            <h2><i class="bi bi-file-text"></i> Active Contracts</h2>
            <div class="card">
                <div class="card-body">
                    <?php if(count($activeContracts) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Landlord</th>
                                    <th>Rent</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($activeContracts as $contract): ?>
                                <tr>
                                    <td><?= htmlspecialchars($contract['property_title']) ?></td>
                                    <td><?= htmlspecialchars($contract['landlord_name']) ?></td>
                                    <td><?= formatCurrency($contract['monthly_rent']) ?></td>
                                    <td><?= date('M j, Y', strtotime($contract['start_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($contract['end_date'])) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>view_contract.php?id=<?= $contract['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        You don't have any active contracts.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Recent Payments -->
        <section class="dashboard-section">
            <h2><i class="bi bi-currency-exchange"></i> Recent Payments</h2>
            <div class="card">
                <div class="card-body">
                    <?php if(count($recentPayments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Amount</th>
                                    <th>Payment Type</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentPayments as $payment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['property_title']) ?></td>
                                    <td><?= formatCurrency($payment['amount']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($payment['payment_type'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td>
                                        <?php if ($payment['status'] == 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($payment['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($payment['status'] == 'failed'): ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Refunded</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No recent payments found.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Upcoming Viewings -->
        <section class="dashboard-section">
            <h2><i class="bi bi-calendar-check"></i> Upcoming Viewings</h2>
            <div class="card">
                <div class="card-body">
                    <?php if(count($upcomingViewings) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Agent</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($upcomingViewings as $viewing): ?>
                                <tr>
                                    <td><?= htmlspecialchars($viewing['property_title']) ?></td>
                                    <td><?= htmlspecialchars($viewing['agent_name'] ?? 'N/A') ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($viewing['scheduled_date'])) ?></td>
                                    <td>
                                        <?php if ($viewing['status'] == 'confirmed'): ?>
                                            <span class="badge bg-success">Confirmed</span>
                                        <?php elseif ($viewing['status'] == 'scheduled'): ?>
                                            <span class="badge bg-warning">Scheduled</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>viewing.php?id=<?= $viewing['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No upcoming viewings scheduled.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Maintenance Issues -->
        <section class="dashboard-section">
            <h2><i class="bi bi-tools"></i> My Maintenance Issues</h2>
            <div class="card">
                <div class="card-body">
                    <?php if(count($maintenanceIssues) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Title</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Reported On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($maintenanceIssues as $issue): ?>
                                <tr>
                                    <td><?= htmlspecialchars($issue['property_title']) ?></td>
                                    <td><?= htmlspecialchars($issue['title']) ?></td>
                                    <td>
                                        <?php if ($issue['priority'] == 'urgent'): ?>
                                            <span class="badge bg-danger">Urgent</span>
                                        <?php elseif ($issue['priority'] == 'high'): ?>
                                            <span class="badge bg-warning">High</span>
                                        <?php elseif ($issue['priority'] == 'medium'): ?>
                                            <span class="badge bg-info">Medium</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Low</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($issue['status'] == 'resolved'): ?>
                                            <span class="badge bg-success">Resolved</span>
                                        <?php elseif ($issue['status'] == 'in_progress'): ?>
                                            <span class="badge bg-info">In Progress</span>
                                        <?php elseif ($issue['status'] == 'assigned'): ?>
                                            <span class="badge bg-primary">Assigned</span>
                                        <?php elseif ($issue['status'] == 'reported'): ?>
                                            <span class="badge bg-warning">Reported</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Closed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($issue['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No maintenance issues reported.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <!-- Navigation Menu (Simplified) -->
    <nav class="dashboard-nav">
        <ul>
            <li><a href="<?= BASE_URL ?>dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>contracts.php"><i class="bi bi-file-text"></i> Contracts</a></li>
            <li><a href="<?= BASE_URL ?>payments.php"><i class="bi bi-cash-stack"></i> Payments</a></li>
            <li><a href="<?= BASE_URL ?>viewings.php"><i class="bi bi-calendar-check"></i> Viewings</a></li>
            <li><a href="<?= BASE_URL ?>issues.php"><i class="bi bi-tools"></i> Maintenance</a></li>
        </ul>
    </nav>
</div>
</div>

</body>
</html>

<?php
require_once __DIR__.'/../../../views/layouts/footer.php';