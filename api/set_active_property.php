<?php
/**
 * API endpoint to set the active property for a user
 */

// Include necessary files
require_once __DIR__.'/../config/paths.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../includes/auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['property_id'])) {
        $propertyId = (int)$data['property_id'];
        
        // In a real implementation, we would verify that the user has access to this property
        // For now, we'll just set it in the session
        $_SESSION['active_property_id'] = $propertyId;
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Active property set successfully']);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Property ID not provided']);
        exit;
    }
}

// Handle GET request (for testing purposes)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['property_id'])) {
    $propertyId = (int)$_GET['property_id'];
    $_SESSION['active_property_id'] = $propertyId;
    
    // Redirect back to the referring page or dashboard
    $redirect = $_SERVER['HTTP_REFERER'] ?? BASE_URL.'dashboard.php';
    header("Location: $redirect");
    exit;
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request method']);