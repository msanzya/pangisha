-- Rollback script for all migrations
-- This script reverses the changes made by the enhancement migrations

-- Rollback migration 008 (quality assurance check) - No changes to rollback

-- Rollback migration 007 (data migration) - No changes to rollback
-- Data migration is additive and doesn't modify existing data

-- Rollback migration 006 (stakeholder marketplace)
DROP TABLE IF EXISTS user_offers;
DROP TABLE IF EXISTS financial_offers;

-- Rollback migration 005 (property sale and investment)
-- Remove indexes
DROP INDEX idx_properties_for_sale ON properties;
DROP INDEX idx_properties_fractional_investment ON properties;
DROP INDEX idx_property_sales_property ON property_sales;
DROP INDEX idx_property_sales_seller ON property_sales;
DROP INDEX idx_property_sales_buyer ON property_sales;
DROP INDEX idx_property_sales_status ON property_sales;
DROP INDEX idx_property_investments_property ON property_investments;
DROP INDEX idx_property_investments_investor ON property_investments;

-- Remove columns from properties table
ALTER TABLE properties 
DROP COLUMN is_for_sale,
DROP COLUMN allows_fractional_investment,
DROP COLUMN investment_offering_percentage;

-- Remove tables
DROP TABLE IF EXISTS property_investments;
DROP TABLE IF EXISTS property_sales;

-- Rollback migration 004 (property relationships)
-- Remove indexes
DROP INDEX idx_property_relationships_user ON property_relationships;
DROP INDEX idx_property_relationships_property ON property_relationships;
DROP INDEX idx_property_relationships_type ON property_relationships;
DROP INDEX idx_property_relationships_dates ON property_relationships;

-- Remove table
DROP TABLE IF EXISTS property_relationships;

-- Rollback migration 003 (enhanced authentication)
-- Remove indexes
DROP INDEX idx_users_phone ON users;
DROP INDEX idx_users_phone_verified ON users;
DROP INDEX idx_users_preferred_login ON users;

-- Remove columns from users table
ALTER TABLE users 
DROP COLUMN phone,
DROP COLUMN phone_verified,
DROP COLUMN preferred_login_method;

-- Rollback migration 002 (update tenants table)
-- Remove indexes
DROP INDEX idx_tenants_property_id ON tenants;
DROP INDEX idx_tenants_agent_id ON tenants;

-- Remove columns from tenants table
ALTER TABLE tenants 
DROP COLUMN property_id,
DROP COLUMN agent_id;

-- Rollback migration 001 (agent wallet and property extensions)
-- Remove indexes
DROP INDEX idx_properties_agent_id ON properties;
DROP INDEX idx_wallet_agent_id ON agents_wallet;
DROP INDEX idx_wallet_transactions_agent_id ON agents_wallet_transactions;
DROP INDEX idx_wallet_transactions_created_at ON agents_wallet_transactions;

-- Remove columns from properties table
ALTER TABLE properties 
DROP COLUMN agent_id;

-- Remove tables
DROP TABLE IF EXISTS agents_wallet_transactions;
DROP TABLE IF EXISTS agents_wallet;