<?php
// Debug script for agent creation

require __DIR__.'/../config/db.php';

echo "Debugging agent creation...\n\n";

// Create a test agent first
echo "1. Creating test agent...\n";
$agentData = [
    'name' => 'Test Agent',
    'email' => 'testagent@example.com',
    'password' => 'password123',
    'phone' => '1234567890',
    'agency_name' => 'Test Agency',
    'license_number' => 'LICENSE123'
];

try {
    $db->beginTransaction();
    
    // Create user account
    echo "   Creating user account...\n";
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, phone, role)
        VALUES (?, ?, ?, ?, 'agent')
    ");
    $stmt->execute([
        $agentData['name'],
        $agentData['email'],
        password_hash($agentData['password'], PASSWORD_DEFAULT),
        $agentData['phone'] ?? null
    ]);
    $userId = $db->lastInsertId();
    echo "   User created with ID: " . $userId . "\n";
    
    // Create agent profile
    echo "   Creating agent profile...\n";
    $stmt = $db->prepare("
        INSERT INTO agents (user_id)
        VALUES (?)
    ");
    $stmt->execute([$userId]);
    $agentId = $db->lastInsertId();
    echo "   Agent profile created with ID: " . $agentId . "\n";
    
    $db->commit();
    echo "   Agent creation successful!\n";
    echo "   Agent ID: " . $agentId . "\n";
} catch (Exception $e) {
    $db->rollback();
    echo "   Error creating agent: " . $e->getMessage() . "\n";
    echo "   Error code: " . $e->getCode() . "\n";
}
?>