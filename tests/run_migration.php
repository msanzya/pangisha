<?php
// Script to run database migrations
require_once __DIR__.'/../config/db.php';

echo "<h1>Running Database Migration</h1>";

$migrationFile = __DIR__.'/../sql/migrations/003_add_auth_columns.sql';

if (!file_exists($migrationFile)) {
    echo "<p style='color: red;'>Migration file not found: $migrationFile</p>";
    exit;
}

$sql = file_get_contents($migrationFile);

try {
    $db->exec($sql);
    echo "<p style='color: green;'>Migration executed successfully!</p>";
    
    // Verify the columns were added
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
    echo "<p style='color: red;'>Error executing migration: " . $e->getMessage() . "</p>";
}
?>