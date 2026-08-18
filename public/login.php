<?php
// Enable error reporting for debugging (optional, remove on production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/../config/paths.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/session.php';  // This will start the session
require_once __DIR__.'/../includes/auth.php';

if(isLoggedIn()) {
    header("Location: ".BASE_URL."dashboard.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['email']; // This could be email or phone
    $password = $_POST['password'];
    
    // Check if login is email or phone
    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        // Email login
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$login]);
    } else {
        // Phone login
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$login]);
    }
    
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Check if there's a redirect parameter
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect) {
            header("Location: ".BASE_URL.$redirect);
        } else {
            header("Location: ".BASE_URL."dashboard.php"); // Redirect to dashboard after login
        }
        exit;
    } else {
        $error = "Invalid email/phone or password";
    }
}

$pageTitle = "Login";
require_once __DIR__.'/../views/layouts/public_header.php';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Sign in to your Pangisha account</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="auth-form">
            <div class="form-group">
                <label for="email">Email or Phone</label>
                <input type="text" class="form-control" id="email" name="email" placeholder="Enter email or phone number" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        
        <div class="auth-footer">
            <a href="<?= BASE_URL ?>register.php">Create an account</a>
        </div>
    </div>
</div>

<?php
require_once __DIR__.'/../views/layouts/public_footer.php';
?>