<?php
/**
 * Property Relationship Model
 * Handles all property relationship operations
 */

class PropertyRelationship {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all properties related to a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserProperties($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT pr.*, p.title as property_title, p.address as property_address, 
                       p.city as property_city, p.property_type, p.status as property_status
                FROM property_relationships pr
                JOIN properties p ON pr.property_id = p.id
                WHERE pr.user_id = ?
                ORDER BY pr.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user properties: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get relationship for a specific user and property
     * 
     * @param int $userId
     * @param int $propertyId
     * @return array|null
     */
    public function getUserPropertyRelationship($userId, $propertyId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM property_relationships 
                WHERE user_id = ? AND property_id = ?
            ");
            $stmt->execute([$userId, $propertyId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting user property relationship: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new property relationship
     * 
     * @param int $userId
     * @param int $propertyId
     * @param string $relationshipType
     * @param float $investmentPercentage
     * @param string $startDate
     * @param string $endDate
     * @return bool
     */
    public function createRelationship($userId, $propertyId, $relationshipType, $investmentPercentage = null, $startDate = null, $endDate = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO property_relationships 
                (user_id, property_id, relationship_type, investment_percentage, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([$userId, $propertyId, $relationshipType, $investmentPercentage, $startDate, $endDate]);
        } catch (Exception $e) {
            error_log("Error creating property relationship: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing property relationship
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateRelationship($id, $data) {
        try {
            $fields = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                $fields[] = "$key = ?";
                $values[] = $value;
            }
            
            $values[] = $id;
            
            $stmt = $this->db->prepare("
                UPDATE property_relationships 
                SET " . implode(', ', $fields) . "
                WHERE id = ?
            ");
            return $stmt->execute($values);
        } catch (Exception $e) {
            error_log("Error updating property relationship: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a property relationship
     * 
     * @param int $id
     * @return bool
     */
    public function deleteRelationship($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM property_relationships WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error deleting property relationship: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all relationships for a specific property
     * 
     * @param int $propertyId
     * @return array
     */
    public function getPropertyRelationships($propertyId) {
        try {
            $stmt = $this->db->prepare("
                SELECT pr.*, u.name as user_name, u.email as user_email, u.role as user_role
                FROM property_relationships pr
                JOIN users u ON pr.user_id = u.id
                WHERE pr.property_id = ?
                ORDER BY pr.relationship_type, pr.created_at DESC
            ");
            $stmt->execute([$propertyId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting property relationships: " . $e->getMessage());
            return [];
        }
    }
}