<?php
require __DIR__.'/../config/paths.php';
require __DIR__.'/../config/db.php';
require __DIR__.'/../config/session.php';
require __DIR__.'/../includes/auth.php';
require __DIR__.'/../includes/agent_functions.php';
require_once __DIR__.'/../models/AgentWallet.php';

// Verify agent
if (!isAgent()) {
    header("Location: ".BASE_URL."login.php");
    exit;
}

// Get agent information
$agent = getAgentByUserId($_SESSION['user_id'], $db);

// Initialize wallet
$wallet = new AgentWallet($db);
$walletInfo = $wallet->getWallet($agent['id']);
if (!$walletInfo) {
    $wallet->createWallet($agent['id']);
    $walletInfo = $wallet->getWallet($agent['id']);
}

// Get transaction history
$transactions = $wallet->getTransactionHistory($agent['id'], 20);

$pageTitle = "Agent Wallet";
require_once __DIR__.'/../views/layouts/header.php';
?>

<section class="dashboard-section">
    <h2><i class="bi bi-wallet2"></i> Agent Wallet</h2>
    
    <!-- Wallet Summary -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #28a745;">
                <i class="bi bi-currency-exchange"></i>
            </div>
            <div class="stat-content">
                <h3>Current Balance</h3>
                <div class="stat-breakdown">
                    <span><?= formatCurrency($walletInfo['balance'] ?? 0) ?></span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #0CC0DF;">
                <i class="bi bi-graph-up"></i>
            </div>
            <div class="stat-content">
                <h3>Total Earnings</h3>
                <div class="stat-breakdown">
                    <span><?= formatCurrency($walletInfo['total_earnings'] ?? 0) ?></span>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #EC7600;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div class="stat-content">
                <h3>Total Payouts</h3>
                <div class="stat-breakdown">
                    <span><?= formatCurrency($walletInfo['total_payouts'] ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction History -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Transaction History</h5>
        </div>
        <div class="card-body">
            <?php if(count($transactions) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th>Amount</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transactions as $transaction): ?>
                        <tr>
                            <td><?= date('M j, Y g:i A', strtotime($transaction['created_at'])) ?></td>
                            <td><?= htmlspecialchars($transaction['description']) ?></td>
                            <td>
                                <?php if($transaction['reference_id']): ?>
                                    <?php if($transaction['reference_type'] == 'payment'): ?>
                                        Payment #<?= $transaction['reference_id'] ?>
                                    <?php elseif($transaction['reference_type'] == 'contract'): ?>
                                        Contract #<?= $transaction['reference_id'] ?>
                                    <?php else: ?>
                                        #<?= $transaction['reference_id'] ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= formatCurrency($transaction['amount']) ?></td>
                            <td>
                                <?php if ($transaction['transaction_type'] == 'credit'): ?>
                                    <span class="badge bg-success">Credit</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Debit</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                No transactions found.
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
require_once __DIR__.'/../views/layouts/footer.php';
?>