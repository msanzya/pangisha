<?php
/**
 * Financial Offer Model
 * Handles all financial offer operations
 */

class FinancialOffer {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get financial offers for a user based on their role
     * 
     * @param int $userId
     * @return array
     */
    public function getUserOffers($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    u.role as user_role,
                    fo.*,
                    uo.eligibility_score
                FROM users u
                JOIN user_offers uo ON u.id = uo.user_id
                JOIN financial_offers fo ON uo.offer_id = fo.id
                WHERE u.id = ? AND fo.is_active = TRUE
                ORDER BY uo.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user offers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all active financial offers
     * 
     * @return array
     */
    public function getActiveOffers() {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM financial_offers
                WHERE is_active = TRUE
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting active financial offers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get financial offers by type
     * 
     * @param string $offerType
     * @return array
     */
    public function getOffersByType($offerType) {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM financial_offers
                WHERE offer_type = ? AND is_active = TRUE
                ORDER BY created_at DESC
            ");
            $stmt->execute([$offerType]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting financial offers by type: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create a new financial offer
     * 
     * @param string $providerName
     * @param string $offerType
     * @param string $targetUserType
     * @param array $eligibilityCriteria
     * @param array $terms
     * @param bool $isActive
     * @return bool
     */
    public function createOffer($providerName, $offerType, $targetUserType, $eligibilityCriteria, $terms, $isActive = true) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO financial_offers 
                (provider_name, offer_type, target_user_type, eligibility_criteria, terms, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $providerName, 
                $offerType, 
                $targetUserType, 
                json_encode($eligibilityCriteria), 
                json_encode($terms), 
                $isActive
            ]);
        } catch (Exception $e) {
            error_log("Error creating financial offer: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Assign offer to user
     * 
     * @param int $userId
     * @param int $offerId
     * @param float $eligibilityScore
     * @return bool
     */
    public function assignOfferToUser($userId, $offerId, $eligibilityScore) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_offers 
                (user_id, offer_id, eligibility_score)
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$userId, $offerId, $eligibilityScore]);
        } catch (Exception $e) {
            error_log("Error assigning offer to user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get eligibility score for user and offer
     * 
     * @param int $userId
     * @param int $offerId
     * @return float|null
     */
    public function getEligibilityScore($userId, $offerId) {
        try {
            $stmt = $this->db->prepare("
                SELECT eligibility_score
                FROM user_offers
                WHERE user_id = ? AND offer_id = ?
            ");
            $stmt->execute([$userId, $offerId]);
            $result = $stmt->fetch();
            return $result['eligibility_score'] ?? null;
        } catch (Exception $e) {
            error_log("Error getting eligibility score: " . $e->getMessage());
            return null;
        }
    }
}