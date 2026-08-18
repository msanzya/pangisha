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

// Get all properties with related information
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
    ORDER BY p.id DESC
");
$properties = $stmt->fetchAll();

// Get banks for mortgage suggestions
try {
    $banks = $db->query("SELECT * FROM banks ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    // If banks table doesn't exist, create an empty array
    $banks = [];
}

$pageTitle = "Properties Management";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-house-door"></i> Properties Management</h2>
    <div class="btn-toolbar mb-3">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPropertyModal">
            <i class="bi bi-plus-circle"></i> Add New Property
        </button>
    </div>

    <!-- Property Statistics -->
    <div class="stats-grid mb-4">
        <!-- Total Properties -->
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #0CC0DF;">
                <i class="bi bi-house-door-fill"></i>
            </div>
            <div class="stat-content">
                <h3>Total Properties</h3>
                <div class="stat-breakdown">
                    <span><?= count($properties) ?> properties</span>
                </div>
            </div>
        </div>

        <!-- Properties for Rent -->
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #EC7600;">
                <i class="bi bi-key"></i>
            </div>
            <div class="stat-content">
                <h3>For Rent</h3>
                <div class="stat-breakdown">
                    <span><?= $db->query("SELECT COUNT(*) FROM properties WHERE status IN ('vacant', 'rented', 'occupied')")->fetchColumn() ?> properties</span>
                </div>
            </div>
        </div>

        <!-- Properties for Sale -->
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #0CC0DF;">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-content">
                <h3>For Sale</h3>
                <div class="stat-breakdown">
                    <span><?= $db->query("SELECT COUNT(*) FROM properties WHERE status='for_sale'")->fetchColumn() ?> properties</span>
                </div>
            </div>
        </div>

        <!-- Under Maintenance -->
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #EC7600;">
                <i class="bi bi-tools"></i>
            </div>
            <div class="stat-content">
                <h3>Under Maintenance</h3>
                <div class="stat-breakdown">
                    <span><?= $db->query("SELECT COUNT(*) FROM properties WHERE status='maintenance'")->fetchColumn() ?> properties</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Properties Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Property Listings</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Landlord</th>
                            <th>Agent</th>
                            <th>Type</th>
                            <th>Bedrooms</th>
                            <th>Rent (TZS)</th>
                            <th>Sale Price (TZS)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($properties as $property): ?>
                        <tr>
                            <td><?= $property['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($property['title']) ?></strong>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($property['city']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($property['landlord_name']) ?></td>
                            <td><?= htmlspecialchars($property['agent_name'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= ucfirst($property['property_type']) ?>
                                </span>
                            </td>
                            <td><?= $property['bedrooms'] ?? 'N/A' ?></td>
                            <td><?= $property['rent_amount'] ? formatCurrency($property['rent_amount']) : 'N/A' ?></td>
                            <td>
                                <?php if ($property['sale_price'] && $property['status'] == 'for_sale'): ?>
                                    <?= formatCurrency($property['sale_price']) ?>
                                    <button class="btn btn-sm btn-outline-info ms-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#mortgageModal"
                                            data-property="<?= htmlspecialchars($property['title']) ?>"
                                            data-price="<?= $property['sale_price'] ?>">
                                        <i class="bi bi-bank"></i> Mortgage
                                    </button>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($property['status'] == 'for_sale'): ?>
                                    <span class="badge bg-warning">For Sale</span>
                                <?php elseif ($property['status'] == 'rented' || $property['status'] == 'occupied'): ?>
                                    <span class="badge bg-success">Occupied</span>
                                <?php elseif ($property['status'] == 'vacant'): ?>
                                    <span class="badge bg-primary">Available</span>
                                <?php elseif ($property['status'] == 'maintenance'): ?>
                                    <span class="badge bg-secondary">Maintenance</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isTenant()): ?>
                                    <a href="<?= BASE_URL ?>apply_to_rent.php?property_id=<?= $property['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-send"></i> Apply
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (isAdmin() || isAgent()): ?>
                                    <a href="#" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Mortgage Modal -->
<div class="modal fade" id="mortgageModal" tabindex="-1" aria-labelledby="mortgageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mortgageModalLabel">Mortgage Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <h6>Property: <span id="modalPropertyName"></span></h6>
                        <h6>Price: <span id="modalPropertyPrice"></span></h6>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Available Mortgage Providers:</h6>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Bank</th>
                                        <th>Interest Rate</th>
                                        <th>Loan Term</th>
                                        <th>Monthly Payment (Est.)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($banks as $bank): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($bank['name']) ?></strong>
                                            <br>
                                            <small><?= htmlspecialchars($bank['website']) ?></small>
                                        </td>
                                        <td><?= $bank['mortgage_interest_rate_min'] ?>% - <?= $bank['mortgage_interest_rate_max'] ?>%</td>
                                        <td><?= $bank['loan_term_min'] ?> - <?= $bank['loan_term_max'] ?> years</td>
                                        <td>
                                            <!-- Simple mortgage calculation for estimation -->
                                            <?php
                                            // This is a simplified calculation for demonstration
                                            $price = 5000000; // Default value, will be replaced by actual price
                                            $interest_rate = ($bank['mortgage_interest_rate_min'] + $bank['mortgage_interest_rate_max']) / 2;
                                            $loan_term = 20; // Default term in years
                                            $monthly_rate = ($interest_rate / 100) / 12;
                                            $number_of_payments = $loan_term * 12;
                                            $monthly_payment = ($price * $monthly_rate) / (1 - pow(1 + $monthly_rate, -$number_of_payments));
                                            ?>
                                            <span class="estimated-payment" data-bank="<?= $bank['id'] ?>"><?= formatCurrency($monthly_payment) ?></span>
                                        </td>
                                        <td>
                                            <a href="<?= $bank['website'] ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="bi bi-box-arrow-up-right"></i> Visit
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle"></i> Mortgage Information</h6>
                            <p class="mb-0">
                                These are estimated monthly payments. Actual amounts may vary based on your credit score,
                                down payment, and other factors. Please contact the banks directly for detailed mortgage quotes.
                            </p>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Update modal content when mortgage button is clicked
document.addEventListener('DOMContentLoaded', function() {
    var mortgageModal = document.getElementById('mortgageModal');
    mortgageModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var propertyName = button.getAttribute('data-property');
        var propertyPrice = parseFloat(button.getAttribute('data-price'));
        
        document.getElementById('modalPropertyName').textContent = propertyName;
        // Format the price with currency
        var currency = 'TZS'; // This should be dynamic based on system settings
        document.getElementById('modalPropertyPrice').textContent = currency + ' ' + propertyPrice.toLocaleString();
        
        // Update estimated payments
        document.querySelectorAll('.estimated-payment').forEach(function(element) {
            var bankId = element.getAttribute('data-bank');
            // In a real application, you would make an AJAX call to get accurate calculations
            // For now, we'll use a simplified calculation
            var interestRate = 15.0; // Default interest rate for calculation
            var loanTerm = 20; // Default loan term in years
            
            // Calculate monthly payment
            var monthlyRate = (interestRate / 100) / 12;
            var numberOfPayments = loanTerm * 12;
            var monthlyPayment = (propertyPrice * monthlyRate) / (1 - Math.pow(1 + monthlyRate, -numberOfPayments));
            
            // Format with currency
            var currency = 'TZS'; // This should be dynamic based on system settings
            element.textContent = currency + ' ' + monthlyPayment.toLocaleString(undefined, {maximumFractionDigits: 2});
        });
    });
});
</script>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>