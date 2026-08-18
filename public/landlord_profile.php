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

// Only landlords can access this page
if ($role !== 'landlord') {
    header("Location: ".BASE_URL."dashboard.php");
    exit;
}

// Get landlord information
$stmt = $db->prepare("
    SELECT u.*, l.phone, l.address, l.agency_name
    FROM users u
    JOIN landlords l ON u.id = l.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$landlord = $stmt->fetch();

// Get landlord's properties
$stmt = $db->prepare("
    SELECT p.*, 
           COUNT(rc.id) as tenant_count,
           SUM(rc.monthly_rent) as total_rent
    FROM properties p
    LEFT JOIN rent_contracts rc ON p.id = rc.property_id AND rc.status = 'active'
    WHERE p.landlord_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$properties = $stmt->fetchAll();

// Get landlord's income history
$stmt = $db->prepare("
    SELECT SUM(p.amount) as monthly_income, DATE_FORMAT(p.payment_date, '%Y-%m') as month
    FROM payments p
    JOIN rent_contracts rc ON p.contract_id = rc.id
    JOIN properties prop ON rc.property_id = prop.id
    WHERE prop.landlord_id = ? AND p.payment_type = 'rent'
    GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$stmt->execute([$_SESSION['user_id']]);
$incomeHistory = $stmt->fetchAll();

// Get landlord's maintenance issues
$stmt = $db->prepare("
    SELECT mi.*, prop.title as property_title, u.name as reported_by_name
    FROM maintenance_issues mi
    JOIN properties prop ON mi.property_id = prop.id
    JOIN users u ON mi.reported_by = u.id
    WHERE prop.landlord_id = ?
    ORDER BY mi.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$maintenanceIssues = $stmt->fetchAll();

$pageTitle = "Landlord Profile";
require_once __DIR__.'/../views/layouts/header.php';
?>

<div class="container mt-4">
    <!-- Landlord Profile Header -->
    <div class="hero landlord-hero">
        <img src="https://via.placeholder.com/100" class="profile-pic mb-2" alt="Landlord">
        <h3><?= htmlspecialchars($landlord['name']) ?> <span class="badge badge-role">Landlord</span></h3>
        <small>Verified • <?= count($properties) ?> Properties Listed • <?= htmlspecialchars($landlord['address'] ?? 'Dar es Salaam, Tanzania') ?></small>
    </div>
    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Rental Income</h6>
                <canvas id="landlordIncomeChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Tenant Retention</h6>
                <h3>85%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Dispute Resolution</h6>
                <h3>90%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Property Occupancy</h6>
                <h3>95%</h3>
            </div>
        </div>
    </div>
    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Fairness Score</h6>
                <span class="badge rounded-pill" style="background-color:var(--secondary-color)">★★★★☆</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Green Property Index</h6>
                <div class="progress">
                    <div class="progress-bar" style="width:80%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Understanding Score</h6>
                <span class="badge rounded-pill" style="background-color:var(--primary-color); color:white;">★★★★☆</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Communication Score</h6>
                <span class="badge rounded-pill" style="background-color:var(--secondary-color); color:white;">★★★★★</span>
            </div>
        </div>
    </div>
    
    <!-- Properties -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">My Properties</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Property</th>
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
    
    <!-- Maintenance Issues -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Maintenance Issues</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Issue</th>
                            <th>Reported By</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Reported On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenanceIssues as $issue): ?>
                        <tr>
                            <td><?= htmlspecialchars($issue['property_title']) ?></td>
                            <td><?= htmlspecialchars($issue['title']) ?></td>
                            <td><?= htmlspecialchars($issue['reported_by_name']) ?></td>
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
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Landlord Income Chart
document.addEventListener('DOMContentLoaded', function() {
    // Get income data for chart
    var incomeData = <?php 
        $monthlyIncome = [];
        foreach ($incomeHistory as $income) {
            $monthlyIncome[] = $income['monthly_income'];
        }
        echo json_encode(array_values($monthlyIncome));
    ?>;
    
    var incomeLabels = <?php 
        $labels = [];
        foreach ($incomeHistory as $income) {
            $labels[] = $income['month'];
        }
        echo json_encode(array_values($labels));
    ?>;
    
    new Chart(document.getElementById('landlordIncomeChart'), {
        type: 'bar',
        data: {
            labels: incomeLabels,
            datasets: [{
                label: 'Income (<?= getSystemSetting('currency', 'TZS') ?>)',
                data: incomeData,
                backgroundColor: '#0CC0DF'
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