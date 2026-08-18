<?php
/**
 * Property Investment Model
 * Handles all property investment operations
 */

class PropertyInvestment {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get user's property investments
     * 
     * @param int $userId
     * @return array
     */
    public function getUserInvestments($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    pi.*,
                    p.title as property_title
                FROM property_investments pi
                JOIN properties p ON pi.property_id = p.id
                WHERE pi.investor_id = ?
                ORDER BY pi.investment_date DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user investments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get property investments by property ID
     * 
     * @param int $propertyId
     * @return array
     */
    public function getPropertyInvestments($propertyId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    pi.*,
                    u.name as investor_name
                FROM property_investments pi
                JOIN users u ON pi.investor_id = u.id
                WHERE pi.property_id = ?
                ORDER BY pi.investment_date DESC
            ");
            $stmt->execute([$propertyId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting property investments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a new property investment
     * 
     * @param int $propertyId
     * @param int $investorId
     * @param float $investmentAmount
     * @param float $ownershipPercentage
     * @param string $investmentDate
     * @return bool
     */
    public function createInvestment($propertyId, $investorId, $investmentAmount, $ownershipPercentage, $investmentDate) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO property_investments 
                (property_id, investor_id, investment_amount, ownership_percentage, investment_date)
                VALUES (?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$propertyId, $investorId, $investmentAmount, $ownershipPercentage, $investmentDate]);
        } catch (Exception $e) {
            error_log("Error creating property investment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get total investment amount for a property
     * 
     * @param int $propertyId
     * @return float
     */
    public function getTotalInvestmentForProperty($propertyId) {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(investment_amount) as total_investment
                FROM property_investments
                WHERE property_id = ?
            ");
            $stmt->execute([$propertyId]);
            $result = $stmt->fetch();
            return $result['total_investment'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting total investment for property: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total ownership percentage for a property
     * 
     * @param int $propertyId
     * @return float
     */
    public function getTotalOwnershipPercentage($propertyId) {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(ownership_percentage) as total_percentage
                FROM property_investments
                WHERE property_id = ?
            ");
            $stmt->execute([$propertyId]);
            $result = $stmt->fetch();
            return $result['total_percentage'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting total ownership percentage: " . $e->getMessage());
            return 0;
        }
    }
}