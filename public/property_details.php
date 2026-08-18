<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';
// functions.php is already included in db.php

// Get property ID from URL
$propertyId = $_GET['id'] ?? null;

if (!$propertyId) {
    header("Location: ".BASE_URL."public_properties.php");
    exit;
}

// Get property details
$stmt = $db->prepare("
    SELECT p.*,
           l_u.name as landlord_name,
           l_u.email as landlord_email,
           l.phone as landlord_phone,
           a_u.name as agent_name,
           a_u.email as agent_email,
           a.phone as agent_phone,
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
    WHERE p.id = ?
");

$stmt->execute([$propertyId]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: ".BASE_URL."public_properties.php");
    exit;
}

$pageTitle = $property['title'] . " | Pangisha";
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
                <li><a href="<?= BASE_URL ?>public_properties.php" class="active">Properties</a></li>
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

    <!-- Property Details Section -->
    <section class="properties">
        <div class="container">
            <div class="row">
                <div class="col-12 mb-4">
                    <a href="<?= BASE_URL ?>public_properties.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Properties
                    </a>
                </div>
            </div>
            
            <div class="row">
                <!-- Property Images -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="property-image" style="height: 400px;">
                            Property Image
                        </div>
                    </div>
                    
                    <!-- Property Description -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3>Description</h3>
                            <p><?= htmlspecialchars($property['description'] ?? 'No description available.') ?></p>
                        </div>
                    </div>
                    
                    <!-- Property Features -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3>Features</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Bedrooms: <?= $property['bedrooms'] ?? 'N/A' ?></li>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Bathrooms: <?= $property['bathrooms'] ?? 'N/A' ?></li>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Size: <?= $property['size_sqft'] ?? 'N/A' ?> sq.ft</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled">
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Property Type: <?= ucfirst($property['property_type']) ?></li>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Status: <?= $property['display_status'] ?></li>
                                        <li><i class="bi bi-check-circle-fill text-success me-2"></i> Location: <?= htmlspecialchars($property['city']) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Property Info and Actions -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title"><?= htmlspecialchars($property['title']) ?></h3>
                            <div class="property-location mb-3">
                                <i class="bi bi-geo-alt"></i>
                                <span><?= htmlspecialchars($property['city']) ?><?= $property['address'] ? ', ' . htmlspecialchars($property['address']) : '' ?></span>
                            </div>
                            
                            <div class="property-price text-center mb-4">
                                <?php if ($property['status'] == 'for_sale' && $property['sale_price']): ?>
                                    <h2 class="text-primary"><?= formatCurrency($property['sale_price']) ?></h2>
                                <?php elseif ($property['rent_amount']): ?>
                                    <h2 class="text-primary"><?= formatCurrency($property['rent_amount']) ?>/month</h2>
                                <?php else: ?>
                                    <h2 class="text-primary">Price on request</h2>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Contact Info -->
                            <div class="mb-4">
                                <h5>Contact Information</h5>
                                <?php if ($property['agent_name']): ?>
                                    <p><strong>Agent:</strong> <?= htmlspecialchars($property['agent_name']) ?></p>
                                    <?php if ($property['agent_phone']): ?>
                                        <p><i class="bi bi-telephone"></i> <?= htmlspecialchars($property['agent_phone']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($property['agent_email']): ?>
                                        <p><i class="bi bi-envelope"></i> <?= htmlspecialchars($property['agent_email']) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p><strong>Landlord:</strong> <?= htmlspecialchars($property['landlord_name']) ?></p>
                                    <?php if ($property['landlord_phone']): ?>
                                        <p><i class="bi bi-telephone"></i> <?= htmlspecialchars($property['landlord_phone']) ?></p>
                                    <?php endif; ?>
                                    <?php if ($property['landlord_email']): ?>
                                        <p><i class="bi bi-envelope"></i> <?= htmlspecialchars($property['landlord_email']) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <?php if (isLoggedIn()): ?>
                                    <?php if (isTenant()): ?>
                                        <a href="<?= BASE_URL ?>book_viewing.php?property_id=<?= $property['id'] ?>" class="btn btn-primary btn-lg">
                                            <i class="bi bi-calendar-check"></i> Book a Viewing
                                        </a>
                                    <?php elseif (isAgent() || isLandlord()): ?>
                                        <button class="btn btn-primary btn-lg" disabled>
                                            <i class="bi bi-calendar-check"></i> Book a Viewing
                                        </button>
                                        <small class="text-muted">Only tenants can book viewings</small>
                                    <?php else: ?>
                                        <button class="btn btn-primary btn-lg" disabled>
                                            <i class="bi bi-calendar-check"></i> Book a Viewing
                                        </button>
                                        <small class="text-muted">You don't have permission to book viewings</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>login.php?redirect=book_viewing.php?property_id=<?= $property['id'] ?>" class="btn btn-primary btn-lg">
                                        <i class="bi bi-calendar-check"></i> Login to Book Viewing
                                    </a>
                                    <a href="<?= BASE_URL ?>register.php?role=tenant" class="btn btn-outline-primary btn-lg">
                                        <i class="bi bi-person-plus"></i> Register as Tenant
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($property['status'] == 'for_sale' && $property['sale_price']): ?>
                                    <a href="#" class="btn btn-outline-info btn-lg mt-2" data-bs-toggle="modal" data-bs-target="#mortgageModal">
                                        <i class="bi bi-bank"></i> Mortgage Calculator
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mortgage Modal -->
    <div class="modal fade" id="mortgageModal" tabindex="-1" aria-labelledby="mortgageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mortgageModalLabel">Mortgage Calculator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Property: <?= htmlspecialchars($property['title']) ?></h6>
                            <h6>Price: <?= formatCurrency($property['sale_price']) ?></h6>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="downPayment" class="form-label">Down Payment (20%)</label>
                                <input type="text" class="form-control" id="downPayment" value="<?= formatCurrency($property['sale_price'] * 0.2) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="loanAmount" class="form-label">Loan Amount</label>
                                <input type="text" class="form-control" id="loanAmount" value="<?= formatCurrency($property['sale_price'] * 0.8) ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="interestRate" class="form-label">Interest Rate (15%)</label>
                                <input type="text" class="form-control" id="interestRate" value="15%" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="loanTerm" class="form-label">Loan Term (20 years)</label>
                                <input type="text" class="form-control" id="loanTerm" value="20 years" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <h6>Estimated Monthly Payment</h6>
                                <h3 class="text-center">
                                    <?php
                                    // Simple mortgage calculation
                                    $loanAmount = $property['sale_price'] * 0.8;
                                    $interestRate = 15;
                                    $loanTerm = 20;
                                    $monthlyRate = ($interestRate / 100) / 12;
                                    $numberOfPayments = $loanTerm * 12;
                                    $monthlyPayment = ($loanAmount * $monthlyRate) / (1 - pow(1 + $monthlyRate, -$numberOfPayments));
                                    echo formatCurrency($monthlyPayment);
                                    ?>/month
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

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
                    <li><a href="<?= BASE_URL ?>public_properties.php">Properties</a></li>
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