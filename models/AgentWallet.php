<?php
/**
 * Agent Wallet Model
 * Handles all wallet operations for agents
 */

class AgentWallet {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get agent wallet information
     * 
     * @param int $agentId
     * @return array|null
     */
    public function getWallet($agentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM agents_wallet 
                WHERE agent_id = ?
            ");
            $stmt->execute([$agentId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting agent wallet: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create wallet for agent if it doesn't exist
     * 
     * @param int $agentId
     * @return bool
     */
    public function createWallet($agentId) {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO agents_wallet (agent_id, balance, total_earnings, total_payouts)
                VALUES (?, 0.00, 0.00, 0.00)
            ");
            return $stmt->execute([$agentId]);
        } catch (Exception $e) {
            error_log("Error creating agent wallet: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Add funds to agent wallet (credit)
     * 
     * @param int $agentId
     * @param float $amount
     * @param string $description
     * @param int|null $referenceId
     * @param string|null $referenceType
     * @return bool
     */
    public function credit($agentId, $amount, $description, $referenceId = null, $referenceType = null) {
        try {
            // Start transaction
            $this->db->beginTransaction();
            
            // Update wallet balance and total earnings
            $stmt = $this->db->prepare("
                UPDATE agents_wallet 
                SET balance = balance + ?, total_earnings = total_earnings + ?
                WHERE agent_id = ?
            ");
            $stmt->execute([$amount, $amount, $agentId]);
            
            // Record transaction
            $stmt = $this->db->prepare("
                INSERT INTO agents_wallet_transactions 
                (agent_id, amount, transaction_type, description, reference_id, reference_type)
                VALUES (?, ?, 'credit', ?, ?, ?)
            ");
            $stmt->execute([$agentId, $amount, $description, $referenceId, $referenceType]);
            
            // Commit transaction
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            error_log("Error crediting agent wallet: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Deduct funds from agent wallet (debit)
     * 
     * @param int $agentId
     * @param float $amount
     * @param string $description
     * @param int|null $referenceId
     * @param string|null $referenceType
     * @return bool
     */
    public function debit($agentId, $amount, $description, $referenceId = null, $referenceType = null) {
        try {
            // Check if sufficient balance
            $wallet = $this->getWallet($agentId);
            if (!$wallet || $wallet['balance'] < $amount) {
                return false; // Insufficient funds
            }
            
            // Start transaction
            $this->db->beginTransaction();
            
            // Update wallet balance and total payouts
            $stmt = $this->db->prepare("
                UPDATE agents_wallet 
                SET balance = balance - ?, total_payouts = total_payouts + ?
                WHERE agent_id = ?
            ");
            $stmt->execute([$amount, $amount, $agentId]);
            
            // Record transaction
            $stmt = $this->db->prepare("
                INSERT INTO agents_wallet_transactions 
                (agent_id, amount, transaction_type, description, reference_id, reference_type)
                VALUES (?, ?, 'debit', ?, ?, ?)
            ");
            $stmt->execute([$agentId, $amount, $description, $referenceId, $referenceType]);
            
            // Commit transaction
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction
            $this->db->rollback();
            error_log("Error debiting agent wallet: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get transaction history for agent
     *
     * @param int $agentId
     * @param int $limit
     * @return array
     */
    public function getTransactionHistory($agentId, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM agents_wallet_transactions
                WHERE agent_id = ?
                ORDER BY created_at DESC
                LIMIT " . intval($limit) . "
            ");
            $stmt->execute([$agentId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting transaction history: " . $e->getMessage());
            return [];
        }
    }
}