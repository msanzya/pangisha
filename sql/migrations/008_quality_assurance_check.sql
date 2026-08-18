-- Quality Assurance Check Script
-- This script validates database standards and backward compatibility

-- Check for proper indexing on foreign keys and frequently queried columns
-- Users table indexes
SHOW INDEX FROM users WHERE Column_name IN ('phone', 'phone_verified', 'preferred_login_method');

-- Properties table indexes
SHOW INDEX FROM properties WHERE Column_name IN ('is_for_sale', 'allows_fractional_investment');

-- Property relationships table indexes
SHOW INDEX FROM property_relationships WHERE Column_name IN ('user_id', 'property_id', 'relationship_type', 'start_date', 'end_date');

-- Property sales table indexes
SHOW INDEX FROM property_sales WHERE Column_name IN ('property_id', 'seller_id', 'buyer_id', 'sale_status');

-- Property investments table indexes
SHOW INDEX FROM property_investments WHERE Column_name IN ('property_id', 'investor_id');

-- Financial offers table indexes
SHOW INDEX FROM financial_offers WHERE Column_name IN ('offer_type', 'target_user_type', 'is_active');

-- User offers table indexes
SHOW INDEX FROM user_offers WHERE Column_name IN ('user_id', 'offer_id', 'eligibility_score');

-- Check foreign key constraints
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE REFERENCED_TABLE_SCHEMA = 'pangisha'
AND TABLE_NAME IN ('property_relationships', 'property_sales', 'property_investments', 'user_offers')
ORDER BY TABLE_NAME, COLUMN_NAME;

-- Check data types and constraints
DESCRIBE users;
DESCRIBE property_relationships;
DESCRIBE property_sales;
DESCRIBE property_investments;
DESCRIBE financial_offers;
DESCRIBE user_offers;

-- Check for audit fields (created_at, updated_at)
SELECT 
    TABLE_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'pangisha'
AND COLUMN_NAME IN ('created_at', 'updated_at')
AND TABLE_NAME IN ('property_relationships', 'property_sales', 'property_investments', 'financial_offers', 'user_offers')
ORDER BY TABLE_NAME, COLUMN_NAME;

-- Check for soft delete implementation
SELECT 
    TABLE_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'pangisha'
AND COLUMN_NAME IN ('is_deleted', 'deleted_at')
AND TABLE_NAME IN ('users', 'properties', 'property_relationships', 'property_sales', 'property_investments', 'financial_offers', 'user_offers');

-- Validate data integrity after migration
-- Check that all landlords have owner relationships
SELECT COUNT(*) as landlords_without_ownership
FROM landlords l
JOIN users u ON l.user_id = u.id
JOIN properties p ON l.id = p.landlord_id
WHERE NOT EXISTS (
    SELECT 1 FROM property_relationships pr 
    WHERE pr.user_id = l.user_id AND pr.property_id = p.id AND pr.relationship_type = 'owner'
);

-- Check that all tenants have tenant relationships
SELECT COUNT(*) as tenants_without_relationships
FROM tenants t
JOIN users u ON t.user_id = u.id
JOIN rent_contracts rc ON t.id = rc.tenant_id
WHERE rc.status = 'active'
AND NOT EXISTS (
    SELECT 1 FROM property_relationships pr 
    WHERE pr.user_id = t.user_id AND pr.property_id = rc.property_id AND pr.relationship_type = 'tenant'
);

-- Check that all agents have manager relationships
SELECT COUNT(*) as agents_without_management
FROM agents a
JOIN users u ON a.user_id = u.id
JOIN properties p ON a.id = p.agent_id
WHERE NOT EXISTS (
    SELECT 1 FROM property_relationships pr 
    WHERE pr.user_id = a.user_id AND pr.property_id = p.id AND pr.relationship_type = 'manager'
);