<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../models/FinancialOffer.php';

// Verify user is logged in
if (!isLoggedIn()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Initialize financial offer model
$financialOffer = new FinancialOffer($db);

// Get user-specific offers
$userOffers = $financialOffer->getUserOffers($_SESSION['user_id']);

$pageTitle = "Financial Offers";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-bank"></i> Financial Offers</h2>
    
    <?php if(count($userOffers) > 0): ?>
    <div class="row">
        <?php foreach($userOffers as $offer): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card offer-card">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($offer['provider_name']) ?></h5>
                    <p class="card-text">
                        <span class="badge bg-primary"><?= ucfirst($offer['offer_type']) ?></span>
                        <span class="badge bg-secondary"><?= ucfirst($offer['target_user_type']) ?></span>
                    </p>
                    <p class="card-text">Eligibility Score: <?= $offer['eligibility_score'] ?>/100</p>
                    <a href="#" class="btn btn-primary">View Offer Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        No financial offers are currently available for you.
    </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>