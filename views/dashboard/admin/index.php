<?php
require CONFIG_PATH.'/paths.php';
require CONFIG_PATH.'/db.php';

// Verify admin
if (!(isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin')) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get all statistics
$stats = [
    'users' => [
        'total' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'tenants' => $db->query("SELECT COUNT(*) FROM tenants")->fetchColumn(),
        'landlords' => $db->query("SELECT COUNT(*) FROM landlords")->fetchColumn(),
        'agents' => $db->query("SELECT COUNT(*) FROM agents")->fetchColumn(),
        'technicians' => $db->query("SELECT COUNT(*) FROM technicians")->fetchColumn()
    ],
    'properties' => [
        'total' => $db->query("SELECT COUNT(*) FROM properties")->fetchColumn(),
        'occupied' => $db->query("SELECT COUNT(*) FROM properties WHERE status='rented' OR status='occupied'")->fetchColumn(),
        'vacant' => $db->query("SELECT COUNT(*) FROM properties WHERE status='vacant'")->fetchColumn(),
        'for_sale' => $db->query("SELECT COUNT(*) FROM properties WHERE is_for_sale=TRUE")->fetchColumn()
    ],
    'finances' => [
        'rent' => $db->query("SELECT SUM(amount) FROM payments WHERE payment_type='rent'")->fetchColumn(),
        'deposits' => $db->query("SELECT SUM(amount) FROM payments WHERE payment_type='deposit'")->fetchColumn(),
        'investments' => $db->query("SELECT SUM(investment_amount) FROM property_investments")->fetchColumn()
    ],
    'issues' => [
        'open' => $db->query("SELECT COUNT(*) FROM maintenance_issues WHERE status IN ('reported', 'assigned', 'in_progress')")->fetchColumn(),
        'resolved' => $db->query("SELECT COUNT(*) FROM maintenance_issues WHERE status='resolved'")->fetchColumn()
    ],
    'marketplace' => [
        'offers' => $db->query("SELECT COUNT(*) FROM financial_offers WHERE is_active=TRUE")->fetchColumn(),
        'investors' => $db->query("SELECT COUNT(DISTINCT investor_id) FROM property_investments")->fetchColumn()
    ]
];

// Get recent activity (without created_at columns)
$activities = $db->query("
    (SELECT 'tenant' as type, CONCAT('New tenant registered: ', u.name) as text, u.id as timestamp
     FROM users u JOIN tenants t ON u.id = t.user_id ORDER BY u.id DESC LIMIT 3)
    UNION ALL
    (SELECT 'property' as type, CONCAT('New property added: ', title) as text, id as timestamp
     FROM properties ORDER BY id DESC LIMIT 3)
    UNION ALL
    (SELECT 'contract' as type, CONCAT('New contract signed for $', monthly_rent) as text, start_date as timestamp
     FROM rent_contracts ORDER BY start_date DESC LIMIT 3)
    UNION ALL
    (SELECT 'issue' as type, CONCAT('Issue reported: ', title) as text, id as timestamp
     FROM maintenance_issues ORDER BY id DESC LIMIT 3)
    ORDER BY timestamp DESC LIMIT 8
")->fetchAll();

// Get top performing agents
$topAgents = $db->query("
    SELECT a.id, u.name as agent_name, 
           COUNT(DISTINCT p.id) as properties_managed,
           COUNT(DISTINCT c.id) as contracts_facilitated
    FROM agents a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN properties p ON a.id = p.agent_id
    LEFT JOIN rent_contracts c ON p.id = c.property_id
    GROUP BY a.id, u.name
    ORDER BY properties_managed DESC, contracts_facilitated DESC
    LIMIT 5
")->fetchAll();

// Get property type distribution
$propertyTypes = $db->query("
    SELECT type, COUNT(*) as count
    FROM properties
    GROUP BY type
    ORDER BY count DESC
")->fetchAll();

// Get recent property sales
$recentSales = $db->query("
    SELECT ps.id, p.title as property_title, u.name as seller_name, ps.sale_date, ps.sale_status
    FROM property_sales ps
    JOIN properties p ON ps.property_id = p.id
    JOIN users u ON ps.seller_id = u.id
    ORDER BY ps.sale_date DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Pangisha</title>
    <link href="<?= ASSETS_URL ?>css/dashboard.css" rel="stylesheet">
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
            <h2><i class="bi bi-speedometer2"></i> Platform Overview</h2>
            <div class="stats-grid">
                <!-- User Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #0CC0DF;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Users</h3>
                        <div class="stat-breakdown">
                            <span>Tenants: <?= $stats['users']['tenants'] ?></span>
                            <span>Landlords: <?= $stats['users']['landlords'] ?></span>
                            <span>Agents: <?= $stats['users']['agents'] ?></span>
                            <span>Technicians: <?= $stats['users']['technicians'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Property Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #EC7600;">
                        <i class="bi bi-house-door-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Properties</h3>
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
                        <h3>Finances</h3>
                        <div class="stat-breakdown">
                            <span>Rent Collected: <?= formatCurrency($stats['finances']['rent'] ?? 0) ?></span>
                            <span>Deposits Held: <?= formatCurrency($stats['finances']['deposits'] ?? 0) ?></span>
                            <span>Investments: <?= formatCurrency($stats['finances']['investments'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Statistics -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #6f42c1;">
                        <i class="bi bi-tools"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Maintenance</h3>
                        <div class="stat-breakdown">
                            <span>Open Issues: <?= $stats['issues']['open'] ?></span>
                            <span>Resolved: <?= $stats['issues']['resolved'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Business Insights -->
        <section class="dashboard-section">
            <h2><i class="bi bi-bar-chart-line-fill"></i> Business Insights</h2>
            <div class="row">
                <!-- Top Performing Agents -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Top Performing Agents</h5>
                        </div>
                        <div class="card-body">
                            <?php if(count($topAgents) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Agent</th>
                                            <th>Properties</th>
                                            <th>Contracts</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($topAgents as $agent): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($agent['agent_name']) ?></td>
                                            <td><?= $agent['properties_managed'] ?></td>
                                            <td><?= $agent['contracts_facilitated'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                No agent performance data available.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Property Type Distribution -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Property Type Distribution</h5>
                        </div>
                        <div class="card-body">
                            <?php if(count($propertyTypes) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Count</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($propertyTypes as $type): ?>
                                        <tr>
                                            <td><?= ucfirst(htmlspecialchars($type['type'])) ?></td>
                                            <td><?= $type['count'] ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">
                                No property type data available.
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Recent Activity -->
        <section class="dashboard-section">
            <h2><i class="bi bi-clock-history"></i> Recent Activity</h2>
            <div class="activity-feed">
                <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php switch($activity['type']) {
                            case 'tenant': echo '<i class="bi bi-person-plus" style="color: #0CC0DF;"></i>'; break;
                            case 'property': echo '<i class="bi bi-house-add" style="color: #EC7600;"></i>'; break;
                            case 'contract': echo '<i class="bi bi-file-earmark-text" style="color: #28a745;"></i>'; break;
                            case 'issue': echo '<i class="bi bi-exclamation-triangle" style="color: #6f42c1;"></i>'; break;
                        } ?>
                    </div>
                    <div class="activity-details">
                        <p><?= $activity['text'] ?></p>
                        <small><?= date('M j, Y g:i A', strtotime($activity['timestamp'])) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Marketplace Statistics -->
        <section class="dashboard-section">
            <h2><i class="bi bi-shop"></i> Marketplace Overview</h2>
            <div class="stats-grid">
                <!-- Properties For Sale -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #6f42c1;">
                        <i class="bi bi-tag"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Properties For Sale</h3>
                        <div class="stat-breakdown">
                            <span>Total: <?= $stats['properties']['for_sale'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Investment Platform -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #20c997;">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Investment Platform</h3>
                        <div class="stat-breakdown">
                            <span>Total Investments: <?= formatCurrency($stats['finances']['investments'] ?? 0) ?></span>
                            <span>Active Investors: <?= $stats['marketplace']['investors'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Financial Offers -->
                <div class="stat-card">
                    <div class="stat-icon" style="background-color: #fd7e14;">
                        <i class="bi bi-bank"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Financial Offers</h3>
                        <div class="stat-breakdown">
                            <span>Active Offers: <?= $stats['marketplace']['offers'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Property Sales -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Property Sales</h5>
                </div>
                <div class="card-body">
                    <?php if(count($recentSales) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Property</th>
                                    <th>Seller</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentSales as $sale): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sale['property_title']) ?></td>
                                    <td><?= htmlspecialchars($sale['seller_name']) ?></td>
                                    <td><?= date('M j, Y', strtotime($sale['sale_date'])) ?></td>
                                    <td>
                                        <?php if ($sale['sale_status'] == 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($sale['sale_status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Cancelled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        No recent property sales.
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
            <li><a href="<?= BASE_URL ?>users.php"><i class="bi bi-people"></i> Users</a></li>
            <li><a href="<?= BASE_URL ?>properties.php"><i class="bi bi-house-door"></i> Properties</a></li>
            <li><a href="<?= BASE_URL ?>contracts.php"><i class="bi bi-file-text"></i> Contracts</a></li>
            <li><a href="<?= BASE_URL ?>payments.php"><i class="bi bi-cash-stack"></i> Payments</a></li>
            <li><a href="<?= BASE_URL ?>issues.php"><i class="bi bi-tools"></i> Maintenance</a></li>
            <li><a href="<?= BASE_URL ?>reports.php"><i class="bi bi-graph-up"></i> Reports</a></li>
        </ul>
    </nav>
</body>
</html>