<?php
// Script to run all database migrations
require_once __DIR__.'/../config/db.php';

echo "<h1>Running All Database Migrations</h1>";

$migrationFiles = [
    __DIR__.'/../sql/migrations/003_add_auth_columns.sql',
    __DIR__.'/../sql/migrations/004_create_relationship_tables.sql',
    __DIR__.'/../sql/migrations/005_create_property_sales_investments.sql',
    __DIR__.'/../sql/migrations/006_create_financial_marketplace.sql'
];

foreach ($migrationFiles as $migrationFile) {
    echo "<h2>Running: " . basename($migrationFile) . "</h2>";
    
    if (!file_exists($migrationFile)) {
        echo "<p style='color: red;'>Migration file not found: $migrationFile</p>";
        continue;
    }

    $sql = file_get_contents($migrationFile);

    try {
        $db->exec($sql);
        echo "<p style='color: green;'>Migration executed successfully!</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>Note: " . $e->getMessage() . " (This might be OK if the tables/columns already exist)</p>";
    }
}

echo "<h2>Verifying Database Structure</h2>";

// Verify the columns were added
try {
    $stmt = $db->prepare("DESCRIBE properties");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (in_array('is_for_sale', $columns)) {
        echo "<p style='color: green;'>✓ Properties table has is_for_sale column</p>";
    } else {
        echo "<p style='color: red;'>✗ Properties table missing is_for_sale column</p>";
    }
    
    // Check if required tables exist
    $requiredTables = ['property_relationships', 'property_sales', 'property_investments', 'financial_offers', 'user_offers'];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $db->prepare("SELECT 1 FROM $table LIMIT 1");
            $stmt->execute();
            echo "<p style='color: green;'>✓ Table $table exists</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Table $table does not exist: " . $e->getMessage() . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error verifying database structure: " . $e->getMessage() . "</p>";
}

echo "<p style='color: blue; font-weight: bold;'>All migrations completed!</p>";
?>