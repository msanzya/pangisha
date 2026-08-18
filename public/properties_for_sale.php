<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../models/PropertySale.php';

// Verify user is logged in
if (!isLoggedIn()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Initialize property sale model
$propertySale = new PropertySale($db);

// Get properties for sale
$propertiesForSale = $propertySale->getPropertiesForSale();

$pageTitle = "Properties For Sale";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-house-door"></i> Properties For Sale</h2>
    
    <?php if(count($propertiesForSale) > 0): ?>
    <div class="row">
        <?php foreach($propertiesForSale as $property): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card property-card">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>
                    <p class="card-text"><?= htmlspecialchars($property['description'] ?? '') ?></p>
                    <ul class="list-unstyled">
                        <li><strong>Location:</strong> <?= htmlspecialchars($property['city']) ?></li>
                        <li><strong>Type:</strong> <?= ucfirst($property['property_type']) ?></li>
                        <li><strong>Price:</strong> <?= formatCurrency($property['sale_price']) ?></li>
                        <li><strong>Owner:</strong> <?= htmlspecialchars($property['owner_name']) ?></li>
                    </ul>
                    <a href="<?= BASE_URL ?>property_details.php?id=<?= $property['id'] ?>" class="btn btn-primary">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        No properties are currently for sale.
    </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>