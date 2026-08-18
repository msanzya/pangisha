<?php
// Test script to verify admin dashboard functionality
require_once __DIR__.'/../config/paths.php';
require_once __DIR__.'/../config/db.php';

echo "<h1>Admin Dashboard Test</h1>";

// Simulate admin session
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';

// Try to include the admin dashboard and capture any errors
ob_start();
try {
    include __DIR__.'/../views/dashboard/admin/index.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    if ($output) {
        echo "<p style='color: green;'>✓ Admin dashboard loaded successfully</p>";
        
        // Check if it contains the expected elements
        if (strpos($output, 'Platform Overview') !== false) {
            echo "<p style='color: green;'>✓ Platform Overview section found</p>";
        } else {
            echo "<p style='color: red;'>✗ Platform Overview section not found</p>";
        }
        
        if (strpos($output, 'Marketplace Overview') !== false) {
            echo "<p style='color: green;'>✓ Marketplace Overview section found</p>";
        } else {
            echo "<p style='color: red;'>✗ Marketplace Overview section not found</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Admin dashboard failed to load</p>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>✗ Error loading admin dashboard: " . $e->getMessage() . "</p>";
}

// Test specific queries that were causing issues
echo "<h2>Testing Specific Queries</h2>";

try {
    // Test the query that was causing the error
    $stmt = $db->query("SELECT COUNT(*) FROM properties WHERE is_for_sale=TRUE");
    $count = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Properties for sale query successful: $count properties</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Properties for sale query failed: " . $e->getMessage() . "</p>";
}

try {
    // Test financial investments query
    $stmt = $db->query("SELECT COUNT(*) FROM property_investments");
    $count = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Property investments query successful: $count records</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Property investments query failed: " . $e->getMessage() . "</p>";
}

try {
    // Test financial offers query
    $stmt = $db->query("SELECT COUNT(*) FROM financial_offers WHERE is_active=TRUE");
    $count = $stmt->fetchColumn();
    echo "<p style='color: green;'>✓ Financial offers query successful: $count records</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Financial offers query failed: " . $e->getMessage() . "</p>";
}

echo "<p style='color: blue; font-weight: bold;'>Admin dashboard test completed!</p>";
?>