<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';

// Helper function to safely execute prepared statements and fetch single values
function getPreparedValue($db, $sql, $params = []) {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get user role
$userRole = $_SESSION['user_role'] ?? '';

// Get statistics for reports based on user role
$stats = [];

switch ($userRole) {
    case 'admin':
        // Admin sees all statistics
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
                'for_sale' => $db->query("SELECT COUNT(*) FROM properties WHERE status='for_sale'")->fetchColumn()
            ],
            'finances' => [
                'rent' => $db->query("SELECT SUM(amount) FROM payments WHERE payment_type='rent'")->fetchColumn(),
                'deposits' => $db->query("SELECT SUM(amount) FROM payments WHERE payment_type='deposit'")->fetchColumn(),
                'maintenance' => $db->query("SELECT SUM(amount) FROM payments WHERE payment_type='maintenance'")->fetchColumn()
            ],
            'issues' => [
                'open' => $db->query("SELECT COUNT(*) FROM maintenance_issues WHERE status IN ('reported', 'assigned', 'in_progress')")->fetchColumn(),
                'resolved' => $db->query("SELECT COUNT(*) FROM maintenance_issues WHERE status='resolved'")->fetchColumn()
            ]
        ];
        break;
        
    case 'landlord':
        // Landlord sees statistics for their properties only
        $landlordStmt = $db->prepare("SELECT id FROM landlords WHERE user_id = ?");
        $landlordStmt->execute([$_SESSION['user_id']]);
        $landlordId = $landlordStmt->fetchColumn();
        $stats = [
            'properties' => [
                'total' => getPreparedValue($db, "SELECT COUNT(*) FROM properties WHERE landlord_id = ?", [$landlordId]),
                'occupied' => getPreparedValue($db, "SELECT COUNT(*) FROM properties WHERE landlord_id = ? AND status IN ('rented', 'occupied')", [$landlordId]),
                'vacant' => getPreparedValue($db, "SELECT COUNT(*) FROM properties WHERE landlord_id = ? AND status='vacant'", [$landlordId]),
                'for_sale' => getPreparedValue($db, "SELECT COUNT(*) FROM properties WHERE landlord_id = ? AND status='for_sale'", [$landlordId])
            ],
            'finances' => [
                'rent' => getPreparedValue($db, "SELECT SUM(p.amount) FROM payments p JOIN rent_contracts rc ON p.contract_id = rc.id WHERE rc.landlord_id = ? AND p.payment_type='rent'", [$landlordId]),
                'deposits' => getPreparedValue($db, "SELECT SUM(p.amount) FROM payments p JOIN rent_contracts rc ON p.contract_id = rc.id WHERE rc.landlord_id = ? AND p.payment_type='deposit'", [$landlordId]),
                'maintenance' => getPreparedValue($db, "SELECT SUM(p.amount) FROM payments p JOIN properties prop ON p.property_id = prop.id WHERE prop.landlord_id = ? AND p.payment_type='maintenance'", [$landlordId])
            ],
            'issues' => [
                'open' => getPreparedValue($db, "SELECT COUNT(*) FROM maintenance_issues mi JOIN properties p ON mi.property_id = p.id WHERE p.landlord_id = ? AND mi.status IN ('reported', 'assigned', 'in_progress')", [$landlordId]),
                'resolved' => getPreparedValue($db, "SELECT COUNT(*) FROM maintenance_issues mi JOIN properties p ON mi.property_id = p.id WHERE p.landlord_id = ? AND mi.status='resolved'", [$landlordId])
            ]
        ];
        break;
        
    case 'agent':
        // Agent sees statistics for properties they manage
        $agentStmt = $db->prepare("SELECT id FROM agents WHERE user_id = ?");
        $agentStmt->execute([$_SESSION['user_id']]);
        $agentId = $agentStmt->fetchColumn();
        $stats = [
            'properties' => [
                'total' => getPreparedValue($db, "SELECT COUNT(*) FROM properties WHERE agent_id = ?", [$agentId]),
                'occupied' => getPreparedValue($db, "SELECT COUNT(*) FROM properties WHERE agent_id = ? AND status IN ('rented', 'occupied')", [$agentId]),
                'vacant' => getPreparedValue($db, "SELECT COUNT(*) FROM properties WHERE agent_id = ? AND status='vacant'", [$agentId]),
                'for_sale' => getPreparedValue($db, "SELECT COUNT(*) FROM properties WHERE agent_id = ? AND status='for_sale'", [$agentId])
            ],
            'finances' => [
                'rent' => getPreparedValue($db, "SELECT SUM(p.amount) FROM payments p JOIN rent_contracts rc ON p.contract_id = rc.id JOIN properties prop ON rc.property_id = prop.id WHERE prop.agent_id = ? AND p.payment_type='rent'", [$agentId]),
                'deposits' => getPreparedValue($db, "SELECT SUM(p.amount) FROM payments p JOIN rent_contracts rc ON p.contract_id = rc.id JOIN properties prop ON rc.property_id = prop.id WHERE prop.agent_id = ? AND p.payment_type='deposit'", [$agentId])
            ],
            'issues' => [
                'open' => getPreparedValue($db, "SELECT COUNT(*) FROM maintenance_issues mi JOIN properties p ON mi.property_id = p.id WHERE p.agent_id = ? AND mi.status IN ('reported', 'assigned', 'in_progress')", [$agentId]),
                'resolved' => getPreparedValue($db, "SELECT COUNT(*) FROM maintenance_issues mi JOIN properties p ON mi.property_id = p.id WHERE p.agent_id = ? AND mi.status='resolved'", [$agentId])
            ]
        ];
        break;
        
    case 'tenant':
        // Tenant sees their payment history and contract details
        $tenantId = getPreparedValue($db, "SELECT id FROM tenants WHERE user_id = ?", [$_SESSION['user_id']]);
        $stats = [
            'payments' => [
                'total' => getPreparedValue($db, "SELECT COUNT(*) FROM payments WHERE payer_id = ?", [$_SESSION['user_id']]),
                'rent' => getPreparedValue($db, "SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payment_type='rent'", [$_SESSION['user_id']]),
                'deposits' => getPreparedValue($db, "SELECT SUM(amount) FROM payments WHERE payer_id = ? AND payment_type='deposit'", [$_SESSION['user_id']])
            ],
            'contracts' => [
                'active' => getPreparedValue($db, "SELECT COUNT(*) FROM rent_contracts WHERE tenant_id = ? AND status='active'", [$tenantId]),
                'total' => getPreparedValue($db, "SELECT COUNT(*) FROM rent_contracts WHERE tenant_id = ?", [$tenantId])
            ]
        ];
        break;
        
    case 'technician':
        // Technician sees their assigned maintenance issues
        $technicianId = $_SESSION['user_id'];
        $stats = [
            'issues' => [
                'assigned' => getPreparedValue($db, "SELECT COUNT(*) FROM maintenance_issues WHERE assigned_to = ? AND status IN ('assigned', 'in_progress')", [$technicianId]),
                'completed' => getPreparedValue($db, "SELECT COUNT(*) FROM maintenance_issues WHERE assigned_to = ? AND status='resolved'", [$technicianId]),
                'total' => getPreparedValue($db, "SELECT COUNT(*) FROM maintenance_issues WHERE assigned_to = ?", [$technicianId])
            ]
        ];
        break;
        
    default:
        // Default to minimal statistics
        $stats = [];
}

$pageTitle = "Reports & Analytics";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
    <div class="btn-toolbar mb-3">
        <button type="button" class="btn btn-outline-secondary">
            <i class="bi bi-download"></i> Export Report
        </button>
    </div>

    <?php if ($userRole == 'admin'): ?>
        <!-- Admin Reports -->
        <!-- Summary Cards -->
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
                <div class="stat-icon" style="background-color: #0CC0DF;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-content">
                    <h3>Finances</h3>
                    <div class="stat-breakdown">
                        <span>Rent Collected: <?= formatCurrency($stats['finances']['rent'] ?? 0) ?></span>
                        <span>Deposits Held: <?= formatCurrency($stats['finances']['deposits'] ?? 0) ?></span>
                        <span>Maintenance: <?= formatCurrency($stats['finances']['maintenance'] ?? 0) ?></span>
                    </div>
                </div>
            </div>

            <!-- Maintenance Statistics -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #EC7600;">
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

        <!-- Detailed Reports -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">User Distribution</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Tenants
                                <span class="badge bg-primary rounded-pill"><?= $stats['users']['tenants'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Landlords
                                <span class="badge bg-success rounded-pill"><?= $stats['users']['landlords'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Agents
                                <span class="badge bg-warning rounded-pill"><?= $stats['users']['agents'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Technicians
                                <span class="badge bg-info rounded-pill"><?= $stats['users']['technicians'] ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Property Status</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Occupied
                                <span class="badge bg-success rounded-pill"><?= $stats['properties']['occupied'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Vacant
                                <span class="badge bg-primary rounded-pill"><?= $stats['properties']['vacant'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                For Sale
                                <span class="badge bg-warning rounded-pill"><?= $stats['properties']['for_sale'] ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Financial Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Rent Collected
                                <span class="text-success"><?= formatCurrency($stats['finances']['rent'] ?? 0) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Security Deposits
                                <span class="text-primary"><?= formatCurrency($stats['finances']['deposits'] ?? 0) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Maintenance Payments
                                <span class="text-info"><?= formatCurrency($stats['finances']['maintenance'] ?? 0) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
    <?php elseif ($userRole == 'landlord'): ?>
        <!-- Landlord Reports -->
        <div class="stats-grid">
            <!-- Property Statistics -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #EC7600;">
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
                <div class="stat-icon" style="background-color: #0CC0DF;">
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
                <div class="stat-icon" style="background-color: #EC7600;">
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
        
    <?php elseif ($userRole == 'agent'): ?>
        <!-- Agent Reports -->
        <div class="stats-grid">
            <!-- Property Statistics -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #EC7600;">
                    <i class="bi bi-house-door-fill"></i>
                </div>
                <div class="stat-content">
                    <h3>Managed Properties</h3>
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
                <div class="stat-icon" style="background-color: #0CC0DF;">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div class="stat-content">
                    <h3>Rental Income</h3>
                    <div class="stat-breakdown">
                        <span>Rent Collected: <?= formatCurrency($stats['finances']['rent'] ?? 0) ?></span>
                        <span>Deposits Held: <?= formatCurrency($stats['finances']['deposits'] ?? 0) ?></span>
                    </div>
                </div>
            </div>

            <!-- Maintenance Statistics -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #EC7600;">
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
        
    <?php elseif ($userRole == 'tenant'): ?>
        <!-- Tenant Reports -->
        <div class="stats-grid">
            <!-- Payment Statistics -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #0CC0DF;">
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
                <div class="stat-icon" style="background-color: #EC7600;">
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
        </div>
        
    <?php elseif ($userRole == 'technician'): ?>
        <!-- Technician Reports -->
        <div class="stats-grid">
            <!-- Issue Statistics -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: #0CC0DF;">
                    <i class="bi bi-tools"></i>
                </div>
                <div class="stat-content">
                    <h3>My Jobs</h3>
                    <div class="stat-breakdown">
                        <span>Assigned: <?= $stats['issues']['assigned'] ?></span>
                        <span>Completed: <?= $stats['issues']['completed'] ?></span>
                        <span>Total: <?= $stats['issues']['total'] ?></span>
                    </div>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Default/Minimal Reports -->
        <div class="alert alert-info">
            <h4>Welcome to Reports & Analytics</h4>
            <p>Your role-specific reports will appear here once you have the appropriate permissions.</p>
        </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>