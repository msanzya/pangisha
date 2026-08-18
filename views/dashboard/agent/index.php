<?php
// Include the standard paths configuration
require __DIR__.'/../../../config/paths.php';
require __DIR__.'/../../../config/db.php';
require __DIR__.'/../../../config/session.php';
require_once __DIR__.'/../../../includes/auth.php';
require_once __DIR__.'/../../../includes/agent_functions.php';
require_once __DIR__.'/../../../models/AgentWallet.php';

if(!isAgent()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get agent information
$agent = getAgentByUserId($_SESSION['user_id'], $db);

// Initialize wallet
$wallet = new AgentWallet($db);
$walletInfo = null;
if ($agent) {
    $walletInfo = $wallet->getWallet($agent['id']);
    if (!$walletInfo) {
        $wallet->createWallet($agent['id']);
        $walletInfo = $wallet->getWallet($agent['id']);
    }
}

// Get agent stats
$stmt = $db->prepare("
    SELECT
        COUNT(DISTINCT p.id) as properties,
        COUNT(DISTINCT l.id) as landlords,
        COUNT(DISTINCT v.id) as viewings,
        COUNT(DISTINCT c.id) as contracts,
        COALESCE(SUM(c.monthly_rent), 0) as expected_rent
    FROM agents a
    LEFT JOIN properties p ON a.id = p.agent_id
    LEFT JOIN landlords l ON a.id = l.agent_id
    LEFT JOIN property_viewings v ON p.id = v.property_id
    LEFT JOIN rent_contracts c ON p.id = c.property_id
    WHERE a.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get upcoming viewings
$agentId = null;
if ($agent) {
    $agentId = $agent['id'];
    $viewings = $db->prepare("
        SELECT v.*, p.title as property_title, u.name as tenant_name
        FROM property_viewings v
        JOIN properties p ON v.property_id = p.id
        JOIN tenants t ON v.tenant_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE p.agent_id = ?
        AND v.scheduled_date >= CURDATE()
        ORDER BY v.scheduled_date ASC, v.scheduled_date ASC
        LIMIT 5
    ");
    $viewings->execute([$agentId]);
    $upcomingViewings = $viewings->fetchAll();
} else {
    $upcomingViewings = [];
}

// Get recent wallet transactions
$recentTransactions = [];
if ($agent) {
    $recentTransactions = $wallet->getTransactionHistory($agent['id'], 5);
}

// Get agent's properties
$agentProperties = [];
if ($agent) {
    $properties = $db->prepare("
        SELECT p.*, l_u.name as landlord_name,
               CASE 
                   WHEN p.status = 'vacant' THEN 'Available'
                   WHEN p.status IN ('rented', 'occupied') THEN 'Occupied'
                   WHEN p.status = 'maintenance' THEN 'Maintenance'
                   WHEN p.status = 'for_sale' THEN 'For Sale'
                   ELSE 'Unknown'
               END as display_status
        FROM properties p
        JOIN landlords l ON p.landlord_id = l.id
        JOIN users l_u ON l.user_id = l_u.id
        WHERE p.agent_id = ?
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $properties->execute([$agentId]);
    $agentProperties = $properties->fetchAll();
}

// Get recent contracts
$recentContracts = [];
if ($agent) {
    $contracts = $db->prepare("
        SELECT rc.*, p.title as property_title, t_u.name as tenant_name
        FROM rent_contracts rc
        JOIN properties p ON rc.property_id = p.id
        JOIN tenants t ON rc.tenant_id = t.id
        JOIN users t_u ON t.user_id = t_u.id
        WHERE p.agent_id = ?
        ORDER BY rc.created_at DESC
        LIMIT 5
    ");
    $contracts->execute([$agentId]);
    $recentContracts = $contracts->fetchAll();
}

$pageTitle = "Agent Dashboard";
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
            <h2><i class="bi bi-speedometer2"></i> Agent Dashboard</h2>
            <div class="stats-grid">
                <!-- Wallet Balance -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #28a745;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Wallet Balance</h3>
                        <div class="stat-breakdown">
                            <span><?= formatCurrency($walletInfo['balance'] ?? 0) ?></span>
                            <?php if ($agent): ?>
                                <span><a href="<?= BASE_URL ?>agent_wallet.php" class="btn btn-sm btn-outline-primary">View Wallet</a></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Properties Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #0CC0DF;">
                        <i class="bi bi-house-door-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Properties</h3>
                        <div class="stat-breakdown">
                            <span>Total: <?= $stats['properties'] ?></span>
                            <span><a href="<?= BASE_URL ?>properties.php" class="btn btn-sm btn-outline-primary">Manage</a></span>
                        </div>
                    </div>
                </div>

                <!-- Landlords Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #EC7600;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Landlords</h3>
                        <div class="stat-breakdown">
                            <span>Total: <?= $stats['landlords'] ?></span>
                            <span><a href="<?= BASE_URL ?>landlords.php" class="btn btn-sm btn-outline-primary">View All</a></span>
                        </div>
                    </div>
                </div>

                <!-- Expected Monthly Rent -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #20c997;">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Expected Monthly Rent</h3>
                        <div class="stat-breakdown">
                            <span><?= formatCurrency($stats['expected_rent'] ?? 0) ?></span>
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
                    <?php if(count($agentProperties) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Landlord</th>
                                    <th>Type</th>
                                    <th>Rent</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($agentProperties as $property): ?>
                                <tr>
                                    <td><?= htmlspecialchars($property['title']) ?></td>
                                    <td><?= htmlspecialchars($property['landlord_name']) ?></td>
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
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        You don't have any properties assigned yet.
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
                                    <th>Tenant</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($upcomingViewings as $viewing): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($viewing['property_title']); ?></td>
                                    <td><?php echo htmlspecialchars($viewing['tenant_name']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($viewing['scheduled_date'])); ?></td>
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
                                        <a href="<?= BASE_URL ?>viewing.php?id=<?php echo $viewing['id']; ?>" class="btn btn-sm btn-outline-primary">
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

        <!-- Quick Actions -->
        <section class="dashboard-section">
            <h2><i class="bi bi-lightning"></i> Quick Actions</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-house-add" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Add Property</h5>
                            <a href="<?= BASE_URL ?>properties.php?action=add" class="btn btn-primary">Add</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-person-plus" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Add Landlord</h5>
                            <a href="<?= BASE_URL ?>landlords.php?action=add" class="btn btn-primary">Add</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-plus" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Schedule Viewing</h5>
                            <a href="<?= BASE_URL ?>viewings.php?action=schedule" class="btn btn-primary">Schedule</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-person-plus" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Onboard Landlord</h5>
                            <a href="<?= BASE_URL ?>agent_onboard_landlord.php" class="btn btn-primary">Onboard</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="bi bi-person-plus" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Onboard Tenant</h5>
                            <a href="<?= BASE_URL ?>agent_onboard_tenant.php" class="btn btn-primary">Onboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Recent Wallet Transactions -->
        <section class="dashboard-section">
            <h2><i class="bi bi-currency-exchange"></i> Recent Wallet Transactions</h2>
            <div class="card">
                <div class="card-body">
                    <?php if(count($recentTransactions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($transaction['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($transaction['description']) ?></td>
                                    <td><?= formatCurrency($transaction['amount']) ?></td>
                                    <td>
                                        <?php if ($transaction['transaction_type'] == 'credit'): ?>
                                            <span class="badge bg-success">Credit</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Debit</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No recent wallet transactions.
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
            <li><a href="<?= BASE_URL ?>landlords.php"><i class="bi bi-person-badge"></i> Landlords</a></li>
            <li><a href="<?= BASE_URL ?>viewings.php"><i class="bi bi-calendar-check"></i> Viewings</a></li>
            <li><a href="<?= BASE_URL ?>contracts.php"><i class="bi bi-file-text"></i> Contracts</a></li>
        </ul>
    </nav>
</div>
</div>

</body>
</html>

<?php
require_once __DIR__.'/../../../views/layouts/footer.php';