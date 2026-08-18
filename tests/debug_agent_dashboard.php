<?php
// Debug script for agent dashboard issues

require __DIR__.'/../config/db.php';
require __DIR__.'/../includes/agent_functions.php';

echo "Debugging agent dashboard issues...\n\n";

// Simulate a user ID (you would replace this with actual session data)
$userId = 6; // This should be the user ID of an agent

echo "1. Checking if user exists...\n";
$stmt = $db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if ($user) {
    echo "   User found: " . $user['name'] . " (" . $user['email'] . ") - Role: " . $user['role'] . "\n";
} else {
    echo "   User not found\n";
    exit(1);
}

echo "2. Checking if user is an agent...\n";
if ($user['role'] === 'agent') {
    echo "   User is an agent\n";
} else {
    echo "   User is not an agent (role: " . $user['role'] . ")\n";
    exit(1);
}

echo "3. Getting agent information...\n";
$agent = getAgentByUserId($userId, $db);
if ($agent) {
    echo "   Agent found: " . $agent['agent_name'] . " (" . $agent['agent_email'] . ")\n";
    echo "   Agent ID: " . $agent['id'] . "\n";
} else {
    echo "   No agent record found for user ID " . $userId . "\n";
    
    echo "4. Checking if agent record exists in agents table...\n";
    $stmt = $db->prepare("SELECT id FROM agents WHERE user_id = ?");
    $stmt->execute([$userId]);
    $agentRecord = $stmt->fetch();
    if ($agentRecord) {
        echo "   Agent record exists with ID: " . $agentRecord['id'] . "\n";
    } else {
        echo "   No agent record exists in agents table\n";
    }
    
    exit(1);
}

echo "\nDebug completed successfully!\n";
?>