<?php
/**
 * Agent-specific functions
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../models/AgentWallet.php';

/**
 * Get agent information by user ID
 * 
 * @param int $userId
 * @param PDO $db
 * @return array|null
 */
function getAgentByUserId($userId, $db) {
    try {
        $stmt = $db->prepare("
            SELECT a.*, u.name as agent_name, u.email as agent_email
            FROM agents a
            JOIN users u ON a.user_id = u.id
            WHERE a.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting agent by user ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get agent information by agent ID
 * 
 * @param int $agentId
 * @param PDO $db
 * @return array|null
 */
function getAgentById($agentId, $db) {
    try {
        $stmt = $db->prepare("
            SELECT a.*, u.name as agent_name, u.email as agent_email
            FROM agents a
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting agent by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Create a new agent
 *
 * @param array $agentData
 * @param PDO $db
 * @return int|false
 */
function createAgent($agentData, $db) {
    try {
        $db->beginTransaction();
        
        // Create user account
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, phone, role)
            VALUES (?, ?, ?, ?, 'agent')
        ");
        $stmt->execute([
            $agentData['name'],
            $agentData['email'],
            password_hash($agentData['password'], PASSWORD_DEFAULT),
            $agentData['phone'] ?? null
        ]);
        $userId = $db->lastInsertId();
        
        // Create agent profile
        $stmt = $db->prepare("
            INSERT INTO agents (user_id)
            VALUES (?)
        ");
        $stmt->execute([$userId]);
        $agentId = $db->lastInsertId();
        
        // Create agent wallet
        $wallet = new AgentWallet($db);
        $wallet->createWallet($agentId);
        
        $db->commit();
        return $agentId;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error creating agent: " . $e->getMessage());
        return false;
    }
}

/**
 * Onboard a new landlord by agent
 *
 * @param array $landlordData
 * @param int $agentId
 * @param PDO $db
 * @return int|false
 */
function onboardLandlordByAgent($landlordData, $agentId, $db) {
    try {
        $db->beginTransaction();
        
        // Create user account for landlord
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, phone, role)
            VALUES (?, ?, ?, ?, 'landlord')
        ");
        $stmt->execute([
            $landlordData['name'],
            $landlordData['email'],
            password_hash($landlordData['password'], PASSWORD_DEFAULT),
            $landlordData['phone'] ?? null
        ]);
        $userId = $db->lastInsertId();
        
        // Create landlord profile
        $stmt = $db->prepare("
            INSERT INTO landlords (user_id, agent_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $agentId]);
        $landlordId = $db->lastInsertId();
        
        $db->commit();
        return $landlordId;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error onboarding landlord: " . $e->getMessage());
        return false;
    }
}

/**
 * Onboard a new tenant by agent
 *
 * @param array $tenantData
 * @param int $agentId
 * @param PDO $db
 * @return int|false
 */
function onboardTenantByAgent($tenantData, $agentId, $db) {
    try {
        $db->beginTransaction();
        
        // Create user account for tenant
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, phone, role)
            VALUES (?, ?, ?, ?, 'tenant')
        ");
        $stmt->execute([
            $tenantData['name'],
            $tenantData['email'],
            password_hash($tenantData['password'], PASSWORD_DEFAULT),
            $tenantData['phone'] ?? null
        ]);
        $userId = $db->lastInsertId();
        
        // Create tenant profile
        $stmt = $db->prepare("
            INSERT INTO tenants (user_id, agent_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $agentId]);
        $tenantId = $db->lastInsertId();
        
        $db->commit();
        return $tenantId;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error onboarding tenant: " . $e->getMessage());
        return false;
    }
}

/**
 * Link property to agent
 * 
 * @param int $propertyId
 * @param int $agentId
 * @param PDO $db
 * @return bool
 */
function linkPropertyToAgent($propertyId, $agentId, $db) {
    try {
        $stmt = $db->prepare("
            UPDATE properties 
            SET agent_id = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$agentId, $propertyId]);
    } catch (Exception $e) {
        error_log("Error linking property to agent: " . $e->getMessage());
        return false;
    }
}

/**
 * Get properties managed by agent
 * 
 * @param int $agentId
 * @param PDO $db
 * @return array
 */
function getPropertiesByAgent($agentId, $db) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, l_u.name as landlord_name
            FROM properties p
            JOIN landlords l ON p.landlord_id = l.id
            JOIN users l_u ON l.user_id = l_u.id
            WHERE p.agent_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting properties by agent: " . $e->getMessage());
        return [];
    }
}

/**
 * Get tenants linked to agent
 * 
 * @param int $agentId
 * @param PDO $db
 * @return array
 */
function getTenantsByAgent($agentId, $db) {
    try {
        $stmt = $db->prepare("
            SELECT t.*, u.name as tenant_name, u.email as tenant_email, p.title as property_title
            FROM tenants t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN properties p ON t.property_id = p.id
            WHERE t.agent_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting tenants by agent: " . $e->getMessage());
        return [];
    }
}