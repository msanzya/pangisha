<?php
require_once __DIR__.'/../config/paths.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/session.php';  // This will start the session

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ".BASE_URL."dashboard.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $preferredLogin = $_POST['preferred_login'];
    
    // Check if user already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error = "User with this email already exists";
    } else {
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, preferred_login_method) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $phone, $hashedPassword, $role, $preferredLogin])) {
            // Redirect to login page
            header("Location: ".BASE_URL."login.php?registered=1");
            exit;
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

$pageTitle = "Register";
require_once __DIR__.'/../views/layouts/public_header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Create Account</h2>
            <p>Join Pangisha to manage your properties</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" class="form-control" id="phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="tenant">Tenant</option>
                    <option value="landlord">Landlord</option>
                    <option value="agent">Agent</option>
                </select>
            </div>
            <div class="form-group">
                <label for="preferred_login">Preferred Login Method</label>
                <select class="form-control" id="preferred_login" name="preferred_login" required>
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </form>
        
        <div class="auth-footer">
            <a href="<?= BASE_URL ?>login.php">Already have an account? Login</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__.'/../views/layouts/public_footer.php';
?>