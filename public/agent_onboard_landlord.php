<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';
require __DIR__.'/../includes/agent_functions.php';

// Verify agent
if (!isAgent()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get agent information
$agent = getAgentByUserId($_SESSION['user_id'], $db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $landlordData = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? ''
    ];
    
    // Validate input
    $errors = [];
    if (empty($landlordData['name'])) {
        $errors[] = "Landlord name is required";
    }
    if (empty($landlordData['email'])) {
        $errors[] = "Email is required";
    } elseif (!filter_var($landlordData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    if (empty($landlordData['password'])) {
        $errors[] = "Password is required";
    } elseif (strlen($landlordData['password']) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$landlordData['email']]);
        if ($stmt->fetch()) {
            $errors[] = "Email already exists";
        }
    }
    
    // Create landlord if no errors
    if (empty($errors)) {
        // Check if agent exists before trying to onboard landlord
        if ($agent && isset($agent['id'])) {
            $landlordId = onboardLandlordByAgent($landlordData, $agent['id'], $db);
            if ($landlordId) {
                $success = "Landlord successfully onboarded!";
            } else {
                $errors[] = "Failed to onboard landlord. Please try again.";
            }
        } else {
            $errors[] = "Agent information not found. Please try logging in again.";
        }
    }
}

$pageTitle = "Onboard Landlord";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-person-plus"></i> Onboard New Landlord</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Landlord Information</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Password must be at least 6 characters</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>
                
                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus"></i> Onboard Landlord
                    </button>
                    <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>