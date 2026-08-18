<?php
// Test script to verify authentication UI changes
require_once __DIR__.'/../config/paths.php';
require_once __DIR__.'/../config/db.php';

echo "<h1>Authentication UI Test</h1>";

// Test 1: Check if users table has new columns
echo "<h2>Test 1: Database Schema Check</h2>";
try {
    $stmt = $db->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['phone', 'phone_verified', 'preferred_login_method'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $missingColumns[] = $column;
        }
    }
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✓ All required columns exist in users table</p>";
    } else {
        echo "<p style='color: red;'>✗ Missing columns: " . implode(', ', $missingColumns) . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking database schema: " . $e->getMessage() . "</p>";
}

// Test 2: Check if CSS files are accessible
echo "<h2>Test 2: CSS Files Check</h2>";
$cssFiles = [
    __DIR__.'/../public/assets/css/landing.css',
    __DIR__.'/../public/assets/css/auth.css'
];

foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file is accessible</p>";
    } else {
        echo "<p style='color: red;'>✗ $file is not accessible</p>";
    }
}

// Test 3: Check if registration page has been updated
echo "<h2>Test 3: Registration Page Check</h2>";
$regPage = file_get_contents(__DIR__.'/../public/register.php');
if ($regPage) {
    if (strpos($regPage, 'auth-container') !== false) {
        echo "<p style='color: green;'>✓ Registration page uses new styling</p>";
    } else {
        echo "<p style='color: red;'>✗ Registration page does not use new styling</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Could not load registration page</p>";
}

echo "<p><a href='../public/register.php'>View Registration Page</a></p>";
echo "<p><a href='../public/login.php'>View Login Page</a></p>";
?>