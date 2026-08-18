<?php
// Complete test script for agent wallet functionality

require __DIR__.'/../config/db.php';
require __DIR__.'/../models/AgentWallet.php';
require __DIR__.'/../includes/agent_functions.php';

echo "Testing complete agent wallet functionality...\n\n";

// Create a test agent first
echo "1. Creating test agent...\n";
// First check if agent already exists
$stmt = $db->prepare("SELECT id FROM agents LIMIT 1");
$stmt->execute();
$agent = $stmt->fetch();

if ($agent) {
    $agentId = $agent['id'];
    echo "   Using existing agent with ID: " . $agentId . "\n";
} else {
    echo "   No existing agent found, creating new one...\n";
    // Create a test agent first
    $agentData = [
        'name' => 'Test Agent',
        'email' => 'testagent' . time() . '@example.com', // Use unique email
        'password' => 'password123',
        'phone' => '1234567890'
    ];

    $agentId = createAgent($agentData, $db);
    if ($agentId) {
        echo "   Agent created with ID: " . $agentId . "\n";
    } else {
        echo "   Failed to create agent\n";
        exit(1);
    }
}

// Test creating wallet
echo "2. Creating wallet for agent ID " . $agentId . "...\n";
$wallet = new AgentWallet($db);
$result = $wallet->createWallet($agentId);
echo "   Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test getting wallet
echo "3. Getting wallet for agent ID " . $agentId . "...\n";
$walletInfo = $wallet->getWallet($agentId);
if ($walletInfo) {
    echo "   Wallet Balance: " . $walletInfo['balance'] . "\n";
    echo "   Total Earnings: " . $walletInfo['total_earnings'] . "\n";
    echo "   Total Payouts: " . $walletInfo['total_payouts'] . "\n";
} else {
    echo "   Result: FAILED - Wallet not found\n";
}

// Test crediting wallet
echo "4. Crediting wallet with 10000...\n";
$result = $wallet->credit($agentId, 10000, "Test commission payment", 1, "contract");
echo "   Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test debiting wallet
echo "5. Debiting wallet with 2000...\n";
$result = $wallet->debit($agentId, 2000, "Payout to agent", 1, "payout");
echo "   Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test getting updated wallet
echo "6. Getting updated wallet...\n";
$walletInfo = $wallet->getWallet($agentId);
if ($walletInfo) {
    echo "   Wallet Balance: " . $walletInfo['balance'] . "\n";
    echo "   Total Earnings: " . $walletInfo['total_earnings'] . "\n";
    echo "   Total Payouts: " . $walletInfo['total_payouts'] . "\n";
} else {
    echo "   Result: FAILED - Wallet not found\n";
}

// Test transaction history
echo "7. Getting transaction history...\n";
$transactions = $wallet->getTransactionHistory($agentId);
echo "   Found " . count($transactions) . " transactions\n";
foreach ($transactions as $transaction) {
    echo "   - " . $transaction['transaction_type'] . ": " . $transaction['amount'] . " (" . $transaction['description'] . ")\n";
}

echo "\nTest completed successfully!\n";
?>