<?php
/**
 * Property Sale Model
 * Handles all property sale operations
 */

class PropertySale {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get properties that are for sale
     * 
     * @return array
     */
    public function getPropertiesForSale() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    u.name as owner_name
                FROM properties p
                JOIN landlords l ON p.landlord_id = l.id
                JOIN users u ON l.user_id = u.id
                WHERE p.is_for_sale = TRUE AND p.sale_price IS NOT NULL
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting properties for sale: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get property sale by property ID
     * 
     * @param int $propertyId
     * @return array|null
     */
    public function getPropertySale($propertyId) {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM property_sales
                WHERE property_id = ?
            ");
            $stmt->execute([$propertyId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting property sale: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new property sale listing
     * 
     * @param int $propertyId
     * @param int $sellerId
     * @param float $salePrice
     * @return bool
     */
    public function createSaleListing($propertyId, $sellerId, $salePrice) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO property_sales 
                (property_id, seller_id, sale_price, sale_status, sale_date)
                VALUES (?, ?, ?, 'pending', CURDATE())
            ");
            return $stmt->execute([$propertyId, $sellerId, $salePrice]);
        } catch (Exception $e) {
            error_log("Error creating sale listing: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark property as for sale
     * 
     * @param int $propertyId
     * @param bool $isForSale
     * @return bool
     */
    public function markPropertyForSale($propertyId, $isForSale = true) {
        try {
            $stmt = $this->db->prepare("
                UPDATE properties 
                SET is_for_sale = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$isForSale, $propertyId]);
        } catch (Exception $e) {
            error_log("Error marking property for sale: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's properties for sale
     * 
     * @param int $userId
     * @return array
     */
    public function getUserPropertiesForSale($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.*,
                    ps.sale_status
                FROM properties p
                JOIN landlords l ON p.landlord_id = l.id
                JOIN property_sales ps ON p.id = ps.property_id
                WHERE l.user_id = ? AND p.is_for_sale = TRUE
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user properties for sale: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Complete a property sale
     * 
     * @param int $saleId
     * @param int $buyerId
     * @return bool
     */
    public function completeSale($saleId, $buyerId) {
        try {
            // Update property sale record
            $stmt = $this->db->prepare("
                UPDATE property_sales 
                SET buyer_id = ?, sale_status = 'completed', sale_date = CURDATE(), updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([$buyerId, $saleId]);
            
            if ($result) {
                // Update property status
                $stmt2 = $this->db->prepare("
                    UPDATE properties p
                    JOIN property_sales ps ON p.id = ps.property_id
                    SET p.status = 'occupied'
                    WHERE ps.id = ?
                ");
                $stmt2->execute([$saleId]);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error completing property sale: " . $e->getMessage());
            return false;
        }
    }
}