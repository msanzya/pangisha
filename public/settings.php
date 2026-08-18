<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';

// Verify admin
if (!isAdmin()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update currency setting
    if (isset($_POST['currency'])) {
        $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'currency'");
        $stmt->execute([$_POST['currency']]);
    }
    
    // Update language setting
    if (isset($_POST['language'])) {
        $stmt = $db->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'language'");
        $stmt->execute([$_POST['language']]);
    }
    
    // Set success message
    $success = "Settings updated successfully!";
}

// Get current settings
$currency = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'currency'")->fetchColumn();
$language = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'language'")->fetchColumn();

$pageTitle = "System Settings";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-gear"></i> System Settings</h2>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">System Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <select class="form-select" id="currency" name="currency" required>
                                <option value="TZS" <?= $currency == 'TZS' ? 'selected' : '' ?>>Tanzanian Shilling (TZS)</option>
                                <option value="USD" <?= $currency == 'USD' ? 'selected' : '' ?>>US Dollar (USD)</option>
                                <option value="KES" <?= $currency == 'KES' ? 'selected' : '' ?>>Kenyan Shilling (KES)</option>
                                <option value="UGX" <?= $currency == 'UGX' ? 'selected' : '' ?>>Ugandan Shilling (UGX)</option>
                            </select>
                            <div class="form-text">Select the default currency for the system</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="language" class="form-label">Language</label>
                            <select class="form-select" id="language" name="language" required>
                                <option value="en" <?= $language == 'en' ? 'selected' : '' ?>>English</option>
                                <option value="sw" <?= $language == 'sw' ? 'selected' : '' ?>>Kiswahili</option>
                            </select>
                            <div class="form-text">Select the default language for the system</div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Settings Information</h5>
                </div>
                <div class="card-body">
                    <p>Configure system-wide settings that affect all users of the application.</p>
                    <ul>
                        <li><strong>Currency:</strong> Default currency used for all financial transactions</li>
                        <li><strong>Language:</strong> Default language for the user interface</li>
                    </ul>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Note: Changes to these settings will affect all users of the system.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>