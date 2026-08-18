<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';

// Verify user is logged in
if (!isLoggedIn()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get user role
$role = $_SESSION['user_role'];

// Only agents can access this page
if ($role !== 'agent') {
    header("Location: ".BASE_URL."dashboard.php");
    exit;
}

// Get agent information
$stmt = $db->prepare("
    SELECT u.*, a.phone, a.address, a.agency_name, a.license_number
    FROM users u
    JOIN agents a ON u.id = a.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$agent = $stmt->fetch();

// Get agent's properties
$stmt = $db->prepare("
    SELECT p.*, l_u.name as landlord_name,
           COUNT(rc.id) as tenant_count
    FROM properties p
    JOIN landlords l ON p.landlord_id = l.id
    JOIN users l_u ON l.user_id = l_u.id
    LEFT JOIN rent_contracts rc ON p.id = rc.property_id AND rc.status = 'active'
    WHERE p.agent_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$properties = $stmt->fetchAll();

// Get agent's deals history
$stmt = $db->prepare("
    SELECT COUNT(*) as total_deals,
           SUM(CASE WHEN rc.status = 'active' THEN 1 ELSE 0 END) as active_rentals,
           SUM(CASE WHEN rc.status = 'expired' THEN 1 ELSE 0 END) as completed_rentals
    FROM properties p
    JOIN rent_contracts rc ON p.id = rc.property_id
    WHERE p.agent_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$dealsStats = $stmt->fetch();

// Get agent's viewing history
$stmt = $db->prepare("
    SELECT pv.*, prop.title as property_title, t_u.name as tenant_name
    FROM property_viewings pv
    JOIN properties prop ON pv.property_id = prop.id
    JOIN tenants t ON pv.tenant_id = t.id
    JOIN users t_u ON t.user_id = t_u.id
    WHERE pv.agent_id = ?
    ORDER BY pv.scheduled_date DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$viewingHistory = $stmt->fetchAll();

$pageTitle = "Agent Profile";
require_once __DIR__.'/../views/layouts/header.php';
?>

<div class="container mt-4">
    <!-- Agent Profile Header -->
    <div class="hero agent-hero">
        <img src="https://via.placeholder.com/100" class="profile-pic mb-2" alt="Agent">
        <h3><?= htmlspecialchars($agent['name']) ?> <span class="badge badge-role">Agent</span></h3>
        <small>Licensed • <?= htmlspecialchars($agent['agency_name'] ?? 'Independent Agent') ?> • <?= htmlspecialchars($agent['address'] ?? 'Dar es Salaam, Tanzania') ?></small>
    </div>
    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Deals Closed</h6>
                <canvas id="agentDealsChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Client Satisfaction</h6>
                <h3>90%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Response Time</h6>
                <h3>1h avg</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Fairness</h6>
                <span class="badge rounded-pill" style="background-color:var(--primary-color); color:white;">★★★★★</span>
            </div>
        </div>
    </div>
    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Affordable Housing Contribution</h6>
                <h3>75%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Green Agent Badge</h6>
                <span class="badge rounded-pill" style="background-color:#28a745">★★★★☆</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Deals Trend</h6>
                <canvas id="agentTrendChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Community Trust</h6>
                <span class="badge rounded-pill" style="background-color:#ffc107">★★★★☆</span>
            </div>
        </div>
    </div>
    
    <!-- Properties Managed -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Properties Managed</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Landlord</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Rent</th>
                            <th>Status</th>
                            <th>Tenants</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $property): ?>
                        <tr>
                            <td><?= htmlspecialchars($property['title']) ?></td>
                            <td><?= htmlspecialchars($property['landlord_name']) ?></td>
                            <td><?= ucfirst($property['property_type']) ?></td>
                            <td><?= htmlspecialchars($property['city'] ?? 'N/A') ?></td>
                            <td><?= formatCurrency($property['rent_amount']) ?></td>
                            <td>
                                <?php if ($property['status'] == 'vacant'): ?>
                                    <span class="badge bg-primary">Available</span>
                                <?php elseif ($property['status'] == 'rented' || $property['status'] == 'occupied'): ?>
                                    <span class="badge bg-success">Occupied</span>
                                <?php elseif ($property['status'] == 'maintenance'): ?>
                                    <span class="badge bg-warning">Maintenance</span>
                                <?php elseif ($property['status'] == 'for_sale'): ?>
                                    <span class="badge bg-info">For Sale</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $property['tenant_count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Viewing History -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Viewing History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Property</th>
                            <th>Tenant</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewingHistory as $viewing): ?>
                        <tr>
                            <td><?= date('M j, Y g:i A', strtotime($viewing['scheduled_date'])) ?></td>
                            <td><?= htmlspecialchars($viewing['property_title']) ?></td>
                            <td><?= htmlspecialchars($viewing['tenant_name']) ?></td>
                            <td>
                                <?php if ($viewing['status'] == 'scheduled'): ?>
                                    <span class="badge bg-info">Scheduled</span>
                                <?php elseif ($viewing['status'] == 'completed'): ?>
                                    <span class="badge bg-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Cancelled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Agent Deals Chart
document.addEventListener('DOMContentLoaded', function() {
    // Sample data for demonstration
    var dealsData = [20, 35, 50, 70, 90, 120, 150];
    var dealsLabels = ['2016', '2017', '2018', '2019', '2020', '2021', '2022'];
    
    new Chart(document.getElementById('agentDealsChart'), {
        type: 'line',
        data: {
            labels: dealsLabels,
            datasets: [{
                label: 'Deals Closed',
                data: dealsData,
                borderColor: '#EC7600',
                backgroundColor: 'rgba(236,118,0,0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Agent Deals Trend Chart
    var trendData = [5, 10, 20, 35, 50, 65, 75];
    var trendLabels = ['2016', '2017', '2018', '2019', '2020', '2021', '2022'];
    
    new Chart(document.getElementById('agentTrendChart'), {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Deals Trend',
                data: trendData,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40,167,69,0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>