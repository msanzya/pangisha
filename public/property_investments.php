<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../models/PropertyInvestment.php';

// Verify user is logged in
if (!isLoggedIn()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Initialize property investment model
$propertyInvestment = new PropertyInvestment($db);

// Get user's investments
$userInvestments = $propertyInvestment->getUserInvestments($_SESSION['user_id']);

$pageTitle = "My Investments";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-currency-exchange"></i> My Property Investments</h2>
    
    <?php if(count($userInvestments) > 0): ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Investment Amount</th>
                            <th>Ownership %</th>
                            <th>Investment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($userInvestments as $investment): ?>
                        <tr>
                            <td><?= htmlspecialchars($investment['property_title']) ?></td>
                            <td><?= formatCurrency($investment['investment_amount']) ?></td>
                            <td><?= $investment['ownership_percentage'] ?>%</td>
                            <td><?= date('M j, Y', strtotime($investment['investment_date'])) ?></td>
                            <td><span class="badge bg-success">Active</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        You have not made any property investments yet.
    </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>