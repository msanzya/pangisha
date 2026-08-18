<?php
// Include the standard paths configuration
require __DIR__.'/../../../config/paths.php';
require __DIR__.'/../../../config/db.php';
require __DIR__.'/../../../config/session.php';
require_once __DIR__.'/../../../includes/auth.php';

if(!isLandlord()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get landlord information
$landlordStmt = $db->prepare("SELECT id FROM landlords WHERE user_id = ?");
$landlordStmt->execute([$_SESSION['user_id']]);
$landlordId = $landlordStmt->fetchColumn();

// Get landlord stats
$stats = [
    'properties' => [
        'total' => 0,
        'occupied' => 0,
        'vacant' => 0,
        'for_sale' => 0
    ],
    'finances' => [
        'rent' => 0,
        'deposits' => 0,
        'maintenance' => 0
    ],
    'issues' => [
        'open' => 0,
        'resolved' => 0
    ]
];

if ($landlordId) {
    // Property statistics
    $propStmt = $db->prepare("SELECT COUNT(*) FROM properties WHERE landlord_id = ?");
    $propStmt->execute([$landlordId]);
    $stats['properties']['total'] = $propStmt->fetchColumn();
    
    $propStmt = $db->prepare("SELECT COUNT(*) FROM properties WHERE landlord_id = ? AND status IN ('rented', 'occupied')");
    $propStmt->execute([$landlordId]);
    $stats['properties']['occupied'] = $propStmt->fetchColumn();
    
    $propStmt = $db->prepare("SELECT COUNT(*) FROM properties WHERE landlord_id = ? AND status='vacant'");
    $propStmt->execute([$landlordId]);
    $stats['properties']['vacant'] = $propStmt->fetchColumn();
    
    $propStmt = $db->prepare("SELECT COUNT(*) FROM properties WHERE landlord_id = ? AND status='for_sale'");
    $propStmt->execute([$landlordId]);
    $stats['properties']['for_sale'] = $propStmt->fetchColumn();
    
    // Financial statistics
    $finStmt = $db->prepare("SELECT SUM(p.amount) FROM payments p JOIN rent_contracts rc ON p.contract_id = rc.id WHERE rc.landlord_id = ? AND p.payment_type='rent'");
    $finStmt->execute([$landlordId]);
    $stats['finances']['rent'] = $finStmt->fetchColumn();
    
    $finStmt = $db->prepare("SELECT SUM(p.amount) FROM payments p JOIN rent_contracts rc ON p.contract_id = rc.id WHERE rc.landlord_id = ? AND p.payment_type='deposit'");
    $finStmt->execute([$landlordId]);
    $stats['finances']['deposits'] = $finStmt->fetchColumn();
    
    $finStmt = $db->prepare("SELECT SUM(p.amount) FROM payments p JOIN properties prop ON p.property_id = prop.id WHERE prop.landlord_id = ? AND p.payment_type='maintenance'");
    $finStmt->execute([$landlordId]);
    $stats['finances']['maintenance'] = $finStmt->fetchColumn();
    
    // Maintenance issues
    $issueStmt = $db->prepare("SELECT COUNT(*) FROM maintenance_issues mi JOIN properties p ON mi.property_id = p.id WHERE p.landlord_id = ? AND mi.status IN ('reported', 'assigned', 'in_progress')");
    $issueStmt->execute([$landlordId]);
    $stats['issues']['open'] = $issueStmt->fetchColumn();
    
    $issueStmt = $db->prepare("SELECT COUNT(*) FROM maintenance_issues mi JOIN properties p ON mi.property_id = p.id WHERE p.landlord_id = ? AND mi.status='resolved'");
    $issueStmt->execute([$landlordId]);
    $stats['issues']['resolved'] = $issueStmt->fetchColumn();
}

// Get landlord's properties
$landlordProperties = [];
if ($landlordId) {
    $properties = $db->prepare("
        SELECT p.*, 
               CASE 
                   WHEN p.status = 'vacant' THEN 'Available'
                   WHEN p.status IN ('rented', 'occupied') THEN 'Occupied'
                   WHEN p.status = 'maintenance' THEN 'Maintenance'
                   WHEN p.status = 'for_sale' THEN 'For Sale'
                   ELSE 'Unknown'
               END as display_status
        FROM properties p
        WHERE p.landlord_id = ?
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $properties->execute([$landlordId]);
    $landlordProperties = $properties->fetchAll();
}

// Get recent contracts
$recentContracts = [];
if ($landlordId) {
    $contracts = $db->prepare("
        SELECT rc.*, p.title as property_title, t_u.name as tenant_name
        FROM rent_contracts rc
        JOIN properties p ON rc.property_id = p.id
        JOIN tenants t ON rc.tenant_id = t.id
        JOIN users t_u ON t.user_id = t_u.id
        WHERE p.landlord_id = ?
        ORDER BY rc.created_at DESC
        LIMIT 5
    ");
    $contracts->execute([$landlordId]);
    $recentContracts = $contracts->fetchAll();
}

// Get recent payments
$recentPayments = [];
if ($landlordId) {
    $payments = $db->prepare("
        SELECT p.*, prop.title as property_title
        FROM payments p
        JOIN rent_contracts rc ON p.contract_id = rc.id
        JOIN properties prop ON rc.property_id = prop.id
        WHERE prop.landlord_id = ?
        ORDER BY p.payment_date DESC
        LIMIT 5
    ");
    $payments->execute([$landlordId]);
    $recentPayments = $payments->fetchAll();
}

// Get maintenance issues
$maintenanceIssues = [];
if ($landlordId) {
    $issues = $db->prepare("
        SELECT mi.*, p.title as property_title, t.name as technician_name
        FROM maintenance_issues mi
        JOIN properties p ON mi.property_id = p.id
        LEFT JOIN technicians t ON mi.assigned_to = t.id
        WHERE p.landlord_id = ?
        ORDER BY mi.created_at DESC
        LIMIT 5
    ");
    $issues->execute([$landlordId]);
    $maintenanceIssues = $issues->fetchAll();
}

$pageTitle = "Landlord Dashboard";
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
            <h2><i class="bi bi-speedometer2"></i> Landlord Dashboard</h2>
            <div class="stats-grid">
                <!-- Property Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #0CC0DF;">
                        <i class="bi bi-house-door-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3>My Properties</h3>
                        <div class="stat-breakdown">
                            <span>Total: <?= $stats['properties']['total'] ?></span>
                            <span>Occupied: <?= $stats['properties']['occupied'] ?></span>
                            <span>Vacant: <?= $stats['properties']['vacant'] ?></span>
                            <span>For Sale: <?= $stats['properties']['for_sale'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Financial Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #28a745;">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div class="stat-content">
                        <h3>My Earnings</h3>
                        <div class="stat-breakdown">
                            <span>Rent Collected: <?= formatCurrency($stats['finances']['rent'] ?? 0) ?></span>
                            <span>Deposits Held: <?= formatCurrency($stats['finances']['deposits'] ?? 0) ?></span>
                            <span>Maintenance Costs: <?= formatCurrency($stats['finances']['maintenance'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #6f42c1;">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Maintenance Issues</h3>
                        <div class="stat-breakdown">
                            <span>Open Issues: <?= $stats['issues']['open'] ?></span>
                            <span>Resolved: <?= $stats['issues']['resolved'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- My Properties -->
        <section class="dashboard-section">
            <h2><i class="bi bi-house-door"></i> My Properties</h2>
            <div class="card">
                <div class="card-body">
                    <?php if(count($landlordProperties) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Type</th>
                                    <th>Rent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($landlordProperties as $property): ?>
                                <tr>
                                    <td><?= htmlspecialchars($property['title']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($property['property_type'])) ?></td>
                                    <td><?= formatCurrency($property['rent_amount'] ?? 0) ?></td>
                                    <td>
                                        <?php if ($property['display_status'] == 'Available'): ?>
                                            <span class="badge bg-primary">Available</span>
                                        <?php elseif ($property['display_status'] == 'Occupied'): ?>
                                            <span class="badge bg-success">Occupied</span>
                                        <?php elseif ($property['display_status'] == 'Maintenance'): ?>
                                            <span class="badge bg-warning">Maintenance</span>
                                        <?php elseif ($property['display_status'] == 'For Sale'): ?>
                                            <span class="badge bg-info">For Sale</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Unknown</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>property_details.php?id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-primary">
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
                        You don't have any properties listed yet.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Recent Contracts -->
        <section class="dashboard-section">
            <h2><i class="bi bi-file-text"></i> Recent Contracts</h2>
            <div class="card">
                <div class="card-body">
                    <?php if(count($recentContracts) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Tenant</th>
                                    <th>Rent</th>
                                    <th>Start Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentContracts as $contract): ?>
                                <tr>
                                    <td><?= htmlspecialchars($contract['property_title']) ?></td>
                                    <td><?= htmlspecialchars($contract['tenant_name']) ?></td>
                                    <td><?= formatCurrency($contract['monthly_rent']) ?></td>
                                    <td><?= date('M j, Y', strtotime($contract['start_date'])) ?></td>
                                    <td>
                                        <?php if ($contract['status'] == 'active'): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($contract['status'] == 'expired'): ?>
                                            <span class="badge bg-secondary">Expired</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Terminated</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No recent contracts found.
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

        <!-- Maintenance Issues -->
        <section class="dashboard-section">
            <h2><i class="bi bi-tools"></i> Maintenance Issues</h2>
            <div class="card">
                <div class="card-body">
                    <?php if(count($maintenanceIssues) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Title</th>
                                    <th>Assigned To</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($maintenanceIssues as $issue): ?>
                                <tr>
                                    <td><?= htmlspecialchars($issue['property_title']) ?></td>
                                    <td><?= htmlspecialchars($issue['title']) ?></td>
                                    <td><?= htmlspecialchars($issue['technician_name'] ?? 'Unassigned') ?></td>
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
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No maintenance issues found.
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
            <li><a href="<?= BASE_URL ?>properties.php"><i class="bi bi-house-door"></i> Properties</a></li>
            <li><a href="<?= BASE_URL ?>contracts.php"><i class="bi bi-file-text"></i> Contracts</a></li>
            <li><a href="<?= BASE_URL ?>payments.php"><i class="bi bi-cash-stack"></i> Payments</a></li>
            <li><a href="<?= BASE_URL ?>issues.php"><i class="bi bi-tools"></i> Maintenance</a></li>
        </ul>
    </nav>
</div>
</div>

</body>
</html>

<?php
require_once __DIR__.'/../../../views/layouts/footer.php';