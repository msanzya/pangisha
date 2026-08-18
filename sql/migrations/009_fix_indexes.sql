-- Migration script to fix missing indexes

-- Add indexes for property_relationships table
CREATE INDEX idx_property_relationships_user ON property_relationships(user_id);
CREATE INDEX idx_property_relationships_property ON property_relationships(property_id);

-- Add indexes for financial marketplace tables
CREATE INDEX idx_financial_offers_type ON financial_offers(offer_type);
CREATE INDEX idx_financial_offers_active ON financial_offers(is_active);
CREATE INDEX idx_user_offers_user ON user_offers(user_id);
CREATE INDEX idx_user_offers_offer ON user_offers(offer_id);