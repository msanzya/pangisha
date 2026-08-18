-- Migration script to migrate existing role data to new relationship model
-- This script will populate the property_relationships table based on existing data

-- Migrate landlord ownership relationships
-- Each landlord owns all their properties
INSERT INTO property_relationships (user_id, property_id, relationship_type, start_date, created_at, updated_at)
SELECT l.user_id, p.id, 'owner', CURDATE(), NOW(), NOW()
FROM landlords l
JOIN properties p ON l.id = p.landlord_id
WHERE NOT EXISTS (
    SELECT 1 FROM property_relationships pr 
    WHERE pr.user_id = l.user_id AND pr.property_id = p.id AND pr.relationship_type = 'owner'
);

-- Migrate tenant relationships
-- Each tenant rents a property through their contract
INSERT INTO property_relationships (user_id, property_id, relationship_type, start_date, end_date, created_at, updated_at)
SELECT t.user_id, rc.property_id, 'tenant', rc.start_date, rc.end_date, NOW(), NOW()
FROM tenants t
JOIN rent_contracts rc ON t.id = rc.tenant_id
WHERE rc.status = 'active'
AND NOT EXISTS (
    SELECT 1 FROM property_relationships pr 
    WHERE pr.user_id = t.user_id AND pr.property_id = rc.property_id AND pr.relationship_type = 'tenant'
);

-- Migrate agent relationships
-- Each agent manages properties they're assigned to
INSERT INTO property_relationships (user_id, property_id, relationship_type, start_date, created_at, updated_at)
SELECT a.user_id, p.id, 'manager', CURDATE(), NOW(), NOW()
FROM agents a
JOIN properties p ON a.id = p.agent_id
WHERE NOT EXISTS (
    SELECT 1 FROM property_relationships pr 
    WHERE pr.user_id = a.user_id AND pr.property_id = p.id AND pr.relationship_type = 'manager'
);

-- Update properties that are already marked as for_sale
-- Link them to property_sales table
INSERT INTO property_sales (property_id, seller_id, sale_status, sale_price, sale_date, created_at, updated_at)
SELECT p.id, l.user_id, 'pending', p.sale_price, CURDATE(), NOW(), NOW()
FROM properties p
JOIN landlords l ON p.landlord_id = l.id
WHERE p.status = 'for_sale' AND p.sale_price IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM property_sales ps WHERE ps.property_id = p.id
);