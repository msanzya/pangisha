<?php
// Debug script for transaction history

require __DIR__.'/../config/db.php';

echo "Debugging transaction history...\n\n";

$agentId = 1;

echo "1. Checking if agent exists...\n";
$stmt = $db->prepare("SELECT id FROM agents WHERE id = ?");
$stmt->execute([$agentId]);
$agent = $stmt->fetch();
if ($agent) {
    echo "   Agent exists\n";
} else {
    echo "   Agent does not exist\n";
    exit(1);
}

echo "2. Checking transactions in database...\n";
$stmt = $db->prepare("SELECT COUNT(*) as count FROM agents_wallet_transactions WHERE agent_id = ?");
$stmt->execute([$agentId]);
$count = $stmt->fetch();
echo "   Found " . $count['count'] . " transactions in database\n";

echo "3. Getting transactions with direct query...\n";
$stmt = $db->prepare("SELECT * FROM agents_wallet_transactions WHERE agent_id = ? ORDER BY created_at DESC");
$stmt->execute([$agentId]);
$transactions = $stmt->fetchAll();
echo "   Direct query found " . count($transactions) . " transactions\n";

echo "4. Getting transactions with limit...\n";
$stmt = $db->prepare("SELECT * FROM agents_wallet_transactions WHERE agent_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$agentId]);
$transactions = $stmt->fetchAll();
echo "   Query with limit found " . count($transactions) . " transactions\n";

echo "\nDebug completed.\n";
?>