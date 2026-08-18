<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';

// Verify admin or agent
if (!(isAdmin() || isAgent())) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get all payments with related information
$stmt = $db->query("
    SELECT p.*,
           prop.title as property_title,
           rc.id as contract_id
    FROM payments p
    LEFT JOIN rent_contracts rc ON p.contract_id = rc.id
    LEFT JOIN properties prop ON rc.property_id = prop.id
    ORDER BY p.id DESC
");
$payments = $stmt->fetchAll();

$pageTitle = "Payments Management";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-cash-stack"></i> Payments Management</h2>
    <div class="btn-toolbar mb-3">
        <button type="button" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Record Payment
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #0CC0DF;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-content">
                <h3>Total Payments</h3>
                <div class="stat-breakdown">
                    <span><?= count($payments) ?> payments recorded</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #EC7600;">
                <i class="bi bi-currency-exchange"></i>
            </div>
            <div class="stat-content">
                <h3>Total Amount</h3>
                <div class="stat-breakdown">
                    <?php
                    $totalAmount = array_sum(array_column($payments, 'amount'));
                    ?>
                    <span><?= formatCurrency($totalAmount) ?></span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #0CC0DF;">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3>Completed</h3>
                <div class="stat-breakdown">
                    <?php
                    $completedPayments = array_filter($payments, function($p) { return $p['status'] == 'completed'; });
                    ?>
                    <span><?= count($completedPayments) ?> payments</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Property</th>
                            <th>Amount (TZS)</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= $payment['id'] ?></td>
                            <td><?= htmlspecialchars($payment['property_title'] ?? 'N/A') ?></td>
                            <td><?= formatCurrency($payment['amount']) ?></td>
                            <td>
                                <span class="badge bg-info"><?= ucfirst($payment['payment_type']) ?></span>
                            </td>
                            <td><?= ucfirst(str_replace('_', ' ', $payment['payment_method'])) ?></td>
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
                            <td>
                                <a href="#" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-receipt"></i> Receipt
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>