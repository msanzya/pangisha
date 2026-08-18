<?php
// Load config
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';
// functions.php is already included in db.php

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to appropriate dashboard
    switch($_SESSION['user_role']) {
        case 'admin':
            header("Location: ".BASE_URL."dashboard.php");
            break;
        case 'landlord':
            header("Location: ".BASE_URL."dashboard.php");
            break;
        case 'agent':
            header("Location: ".BASE_URL."dashboard.php");
            break;
        case 'tenant':
            header("Location: ".BASE_URL."dashboard.php");
            break;
        default:
            header("Location: ".BASE_URL."dashboard.php");
    }
    exit;
}

// Get some properties for the catalog
try {
    $stmt = $db->query("
        SELECT p.*, 
               l_u.name as landlord_name,
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
        WHERE p.status IN ('vacant', 'for_sale')
        ORDER BY p.id DESC
        LIMIT 6
    ");
    $properties = $stmt->fetchAll();
} catch (PDOException $e) {
    $properties = [];
}

// Get cities for search dropdown
try {
    $stmt = $db->query("SELECT DISTINCT city FROM properties WHERE city IS NOT NULL AND city != '' ORDER BY city");
    $cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $cities = [];
}

$pageTitle = "Find Your Perfect Home | Pangisha";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?php echo ASSETS_URL; ?>css/landing.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="landing-header">
        <div class="header-content">
            <img src="<?= ASSETS_URL ?>img/pangisha-logo.png" alt="Pangisha" class="logo">
            <ul class="nav-menu">
                <li><a href="#" class="active">Home</a></li>
                <li><a href="#properties">Properties</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="<?= BASE_URL ?>login.php">For Landlords</a></li>
                <li><a href="<?= BASE_URL ?>login.php">For Agents</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="<?= BASE_URL ?>login.php" class="btn btn-outline">Login</a>
                <a href="<?= BASE_URL ?>register.php" class="btn btn-primary">Register</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Find Your Perfect Home with Pangisha</h1>
            <p>Discover the easiest way to rent or buy properties. We connect tenants, landlords, and agents in one seamless platform.</p>
            <div class="hero-buttons">
                <a href="#properties" class="btn btn-primary">Browse Properties</a>
                <a href="<?= BASE_URL ?>register.php?role=landlord" class="btn btn-outline">List Your Property</a>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="search-container">
            <form class="search-form" method="GET" action="<?= BASE_URL ?>public_properties.php">
                <input type="text" name="search" class="search-input" placeholder="Search by location, street, or property name...">
                <select name="city" class="search-input">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="type" class="search-input">
                    <option value="">All Property Types</option>
                    <option value="apartment">Apartment</option>
                    <option value="house">House</option>
                    <option value="commercial">Commercial</option>
                    <option value="land">Land</option>
                </select>
                <button type="submit" class="search-button">Search</button>
            </form>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-title">
            <h2>Why Choose Pangisha?</h2>
            <p>We provide a comprehensive solution for all your property needs</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-search"></i>
                </div>
                <h3>Easy Search</h3>
                <p>Find properties quickly with our advanced search filters by location, price, and property type.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3>Verified Listings</h3>
                <p>All properties are verified by our team to ensure accuracy and quality.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <h3>Secure Payments</h3>
                <p>Manage rent payments and deposits securely through our integrated payment system.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Our dedicated support team is always ready to assist you with any questions.</p>
            </div>
        </div>
    </section>

    <!-- Properties Section -->
    <section class="properties" id="properties">
        <div class="properties-header">
            <div class="section-title">
                <h2>Featured Properties</h2>
                <p>Discover our latest property listings</p>
            </div>
            <a href="<?= BASE_URL ?>public_properties.php" class="btn btn-outline">View All Properties</a>
        </div>
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
                        <a href="<?= BASE_URL ?>public_properties.php" class="btn btn-primary">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No properties available at the moment. Check back later!</p>
            <?php endif; ?>
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
                    <li><a href="#">Home</a></li>
                    <li><a href="#properties">Properties</a></li>
                    <li><a href="#features">Features</a></li>
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

    <script>
        // Simple JavaScript for smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>