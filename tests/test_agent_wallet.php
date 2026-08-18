<?php
// Simple test script for agent wallet functionality

require __DIR__.'/../config/db.php';
require __DIR__.'/../models/AgentWallet.php';

echo "Testing Agent Wallet functionality...\n\n";

// Create a test agent wallet
$wallet = new AgentWallet($db);

// Test creating wallet
echo "1. Creating wallet for agent ID 1...\n";
$result = $wallet->createWallet(1);
echo "   Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test getting wallet
echo "2. Getting wallet for agent ID 1...\n";
$walletInfo = $wallet->getWallet(1);
if ($walletInfo) {
    echo "   Wallet Balance: " . $walletInfo['balance'] . "\n";
    echo "   Total Earnings: " . $walletInfo['total_earnings'] . "\n";
    echo "   Total Payouts: " . $walletInfo['total_payouts'] . "\n";
} else {
    echo "   Result: FAILED - Wallet not found\n";
}

// Test crediting wallet
echo "3. Crediting wallet with 10000...\n";
$result = $wallet->credit(1, 10000, "Test commission payment", 1, "contract");
echo "   Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test debiting wallet
echo "4. Debiting wallet with 2000...\n";
$result = $wallet->debit(1, 2000, "Payout to agent", 1, "payout");
echo "   Result: " . ($result ? "SUCCESS" : "FAILED") . "\n";

// Test getting updated wallet
echo "5. Getting updated wallet...\n";
$walletInfo = $wallet->getWallet(1);
if ($walletInfo) {
    echo "   Wallet Balance: " . $walletInfo['balance'] . "\n";
    echo "   Total Earnings: " . $walletInfo['total_earnings'] . "\n";
    echo "   Total Payouts: " . $walletInfo['total_payouts'] . "\n";
} else {
    echo "   Result: FAILED - Wallet not found\n";
}

// Test transaction history
echo "6. Getting transaction history...\n";
$transactions = $wallet->getTransactionHistory(1, 10);
echo "   Found " . count($transactions) . " transactions\n";
foreach ($transactions as $transaction) {
    echo "   - " . $transaction['transaction_type'] . ": " . $transaction['amount'] . " (" . $transaction['description'] . ")\n";
}

echo "\nTest completed.\n";
?>