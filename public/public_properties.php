<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';
// functions.php is already included in db.php

// Get all properties with related information (public view)
$stmt = $db->query("
    SELECT p.*,
           l_u.name as landlord_name,
           a_u.name as agent_name,
           CASE
               WHEN p.status = 'for_sale' THEN 'For Sale'
               WHEN p.status = 'rented' OR p.status = 'occupied' THEN 'Occupied'
               WHEN p.status = 'vacant' THEN 'Available for Rent'
               WHEN p.status = 'maintenance' THEN 'Under Maintenance'
               ELSE 'Unknown'
           END as display_status
    FROM properties p
    JOIN landlords l ON p.landlord_id = l.id
    JOIN users l_u ON l.user_id = l_u.id
    LEFT JOIN agents a ON p.agent_id = a.id
    LEFT JOIN users a_u ON a.user_id = a_u.id
    WHERE p.status IN ('vacant', 'for_sale')
    ORDER BY p.id DESC
");

$properties = $stmt->fetchAll();

// Get cities for filter
try {
    $stmt = $db->query("SELECT DISTINCT city FROM properties WHERE city IS NOT NULL AND city != '' ORDER BY city");
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $cities = [];
}

// Get property types for filter
try {
    $stmt = $db->query("SELECT DISTINCT property_type FROM properties WHERE property_type IS NOT NULL AND property_type != '' ORDER BY property_type");
    $propertyTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $propertyTypes = [];
}

$pageTitle = "Browse Properties | Pangisha";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>css/landing.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="landing-header">
        <div class="header-content">
            <img src="<?= ASSETS_URL ?>img/pangisha-logo.png" alt="Pangisha" class="logo">
            <ul class="nav-menu">
                <li><a href="<?= BASE_URL ?>">Home</a></li>
                <li><a href="<?= BASE_URL ?>#properties" class="active">Properties</a></li>
                <li><a href="<?= BASE_URL ?>#features">Features</a></li>
                <li><a href="<?= BASE_URL ?>login.php">For Landlords</a></li>
                <li><a href="<?= BASE_URL ?>login.php">For Agents</a></li>
            </ul>
            <div class="auth-buttons">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-outline">Dashboard</a>
                    <a href="<?= BASE_URL ?>logout.php" class="btn btn-primary">Logout</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-outline">Login</a>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Properties Section -->
    <section class="properties">
        <div class="container">
            <div class="properties-header">
                <div class="section-title">
                    <h2>Browse Properties</h2>
                    <p>Discover your perfect home or investment property</p>
                </div>
            </div>
            
            <!-- Search and Filter Form -->
            <div class="search-section">
                <div class="search-container">
                    <form class="search-form" method="GET">
                        <input type="text" name="search" class="search-input" placeholder="Search by location, street, or property name..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        <select name="city" class="search-input">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?= htmlspecialchars($city) ?>" <?= (($_GET['city'] ?? '') == $city) ? 'selected' : '' ?>><?= htmlspecialchars($city) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="type" class="search-input">
                            <option value="">All Property Types</option>
                            <?php foreach ($propertyTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>" <?= (($_GET['type'] ?? '') == $type) ? 'selected' : '' ?>><?= htmlspecialchars(ucfirst($type)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="search-button">Filter</button>
                    </form>
                </div>
            </div>
            
            <!-- Properties Grid -->
            <div class="properties-grid">
                <?php if (!empty($properties)): ?>
                    <?php foreach ($properties as $property): ?>
                    <div class="property-card">
                        <div class="property-image">
                            Property Image
                        </div>
                        <div class="property-details">
                            <h3><?= htmlspecialchars($property['title']) ?></h3>
                            <div class="property-location">
                                <i class="bi bi-geo-alt"></i>
                                <span><?= htmlspecialchars($property['city']) ?><?= $property['address'] ? ', ' . htmlspecialchars($property['address']) : '' ?></span>
                            </div>
                            <div class="property-meta">
                                <span>
                                    <i class="bi bi-door-open"></i>
                                    <span><?= $property['bedrooms'] ?? 'N/A' ?></span>
                                    <small>Bedrooms</small>
                                </span>
                                <span>
                                    <i class="bi bi-droplet"></i>
                                    <span><?= $property['bathrooms'] ?? 'N/A' ?></span>
                                    <small>Bathrooms</small>
                                </span>
                                <span>
                                    <i class="bi bi-rulers"></i>
                                    <span><?= $property['size_sqft'] ?? 'N/A' ?></span>
                                    <small>sq.ft</small>
                                </span>
                            </div>
                            <div class="property-price">
                                <?php if ($property['status'] == 'for_sale' && $property['sale_price']): ?>
                                    <?= formatCurrency($property['sale_price']) ?>
                                <?php elseif ($property['rent_amount']): ?>
                                    <?= formatCurrency($property['rent_amount']) ?>/month
                                <?php else: ?>
                                    Price on request
                                <?php endif; ?>
                            </div>
                            <a href="<?= BASE_URL ?>property_details.php?id=<?= $property['id'] ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <p class="text-center">No properties found matching your criteria. Please try different search terms.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="footer-content">
            <div class="footer-column">
                <h3>Pangisha</h3>
                <p>Your trusted partner for property rentals and sales in Tanzania.</p>
                <div class="social-links">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-twitter"></i></a>
                    <a href="#"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?= BASE_URL ?>">Home</a></li>
                    <li><a href="<?= BASE_URL ?>#properties">Properties</a></li>
                    <li><a href="<?= BASE_URL ?>#features">Features</a></li>
                    <li><a href="<?= BASE_URL ?>login.php">For Landlords</a></li>
                    <li><a href="<?= BASE_URL ?>login.php">For Agents</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Support</h3>
                <ul>
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contact Info</h3>
                <ul>
                    <li><i class="bi bi-geo-alt"></i> Dar es Salaam, Tanzania</li>
                    <li><i class="bi bi-telephone"></i> +255 123 456 789</li>
                    <li><i class="bi bi-envelope"></i> info@pangisha.com</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Pangisha. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>