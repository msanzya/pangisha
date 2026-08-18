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

// Only tenants can access this page
if ($role !== 'tenant') {
    header("Location: ".BASE_URL."dashboard.php");
    exit;
}

// Get tenant information
$stmt = $db->prepare("
    SELECT u.*, t.phone, t.address
    FROM users u
    JOIN tenants t ON u.id = t.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$tenant = $stmt->fetch();

// Get tenant's payment history
$stmt = $db->prepare("
    SELECT p.*, prop.title as property_title
    FROM payments p
    JOIN rent_contracts rc ON p.contract_id = rc.id
    JOIN properties prop ON rc.property_id = prop.id
    WHERE p.payer_id = ?
    ORDER BY p.payment_date DESC
    LIMIT 12
");
$stmt->execute([$_SESSION['user_id']]);
$paymentHistory = $stmt->fetchAll();

// Get tenant's viewing history
$stmt = $db->prepare("
    SELECT pv.*, prop.title as property_title
    FROM property_viewings pv
    JOIN properties prop ON pv.property_id = prop.id
    WHERE pv.tenant_id = ?
    ORDER BY pv.scheduled_date DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$viewingHistory = $stmt->fetchAll();

// Get tenant's contract history
$stmt = $db->prepare("
    SELECT rc.*, prop.title as property_title, l_u.name as landlord_name
    FROM rent_contracts rc
    JOIN properties prop ON rc.property_id = prop.id
    JOIN landlords l ON prop.landlord_id = l.id
    JOIN users l_u ON l.user_id = l_u.id
    WHERE rc.tenant_id = ?
    ORDER BY rc.start_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$contractHistory = $stmt->fetchAll();

$pageTitle = "Tenant Profile";
require_once __DIR__.'/../views/layouts/header.php';
?>

<div class="container mt-4">
    <!-- Tenant Profile Header -->
    <div class="hero tenant-hero">
        <img src="https://via.placeholder.com/100" class="profile-pic mb-2" alt="Tenant">
        <h3><?= htmlspecialchars($tenant['name']) ?> <span class="badge badge-role">Tenant</span></h3>
        <small>Verified • Tenant since <?= date('Y', strtotime($tenant['created_at'])) ?> • <?= htmlspecialchars($tenant['address'] ?? 'Dar es Salaam, Tanzania') ?></small>
    </div>
    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Spend Over Time</h6>
                <canvas id="tenantSpendChart" height="100"></canvas>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>On-Time Payments</h6>
                <h3>92%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Kindness & Communication</h6>
                <span class="badge rounded-pill" style="background-color:var(--secondary-color)">★★★★★</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Green Score</h6>
                <div class="progress">
                    <div class="progress-bar" style="width:75%"></div>
                </div>
                <small>Mortgage-Ready</small>
            </div>
        </div>
    </div>
    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Utility Payment Consistency</h6>
                <h3>88%</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Savings-to-Rent Ratio</h6>
                <h3>1.8x</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Environmental Responsibility</h6>
                <span class="badge rounded-pill" style="background-color:#28a745">★★★★☆</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card-dashboard">
                <h6>Community Trust</h6>
                <span class="badge rounded-pill" style="background-color:#ffc107">★★★★☆</span>
            </div>
        </div>
    </div>
    
    <!-- Payment History -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Payment History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Property</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentHistory as $payment): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                            <td><?= htmlspecialchars($payment['property_title']) ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', $payment['payment_type'])) ?></td>
                            <td><?= formatCurrency($payment['amount']) ?></td>
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
        </div>
    </div>
    
    <!-- Contract History -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Contract History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Landlord</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Rent</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contractHistory as $contract): ?>
                        <tr>
                            <td><?= htmlspecialchars($contract['property_title']) ?></td>
                            <td><?= htmlspecialchars($contract['landlord_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($contract['start_date'])) ?></td>
                            <td><?= date('M j, Y', strtotime($contract['end_date'])) ?></td>
                            <td><?= formatCurrency($contract['monthly_rent']) ?></td>
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
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewingHistory as $viewing): ?>
                        <tr>
                            <td><?= date('M j, Y g:i A', strtotime($viewing['scheduled_date'])) ?></td>
                            <td><?= htmlspecialchars($viewing['property_title']) ?></td>
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
// Tenant Spend Chart
document.addEventListener('DOMContentLoaded', function() {
    // Get payment data for chart
    var paymentData = <?php 
        $monthlyPayments = [];
        foreach ($paymentHistory as $payment) {
            $month = date('Y-m', strtotime($payment['payment_date']));
            if (!isset($monthlyPayments[$month])) {
                $monthlyPayments[$month] = 0;
            }
            $monthlyPayments[$month] += $payment['amount'];
        }
        echo json_encode(array_values($monthlyPayments));
    ?>;
    
    var paymentLabels = <?php 
        $labels = [];
        foreach ($paymentHistory as $payment) {
            $labels[] = date('Y-m', strtotime($payment['payment_date']));
        }
        echo json_encode(array_unique($labels));
    ?>;
    
    new Chart(document.getElementById('tenantSpendChart'), {
        type: 'line',
        data: {
            labels: paymentLabels,
            datasets: [{
                label: 'Rent Payments',
                data: paymentData,
                borderColor: '#0CC0DF',
                backgroundColor: 'rgba(12,192,223,0.1)',
                fill: true,
                tension: 0.4
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