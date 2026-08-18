-- Migration script to create tables for stakeholder marketplace integration

-- Create financial_offers table
CREATE TABLE IF NOT EXISTS financial_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_name VARCHAR(100) NOT NULL,
    offer_type ENUM('mortgage', 'insurance', 'loan') NOT NULL,
    target_user_type ENUM('tenant', 'landlord', 'investor') NOT NULL,
    eligibility_criteria JSON,
    terms JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user_offers table
CREATE TABLE IF NOT EXISTS user_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    offer_id INT NOT NULL,
    eligibility_score DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (offer_id) REFERENCES financial_offers(id)
);

-- Add indexes for performance
CREATE INDEX idx_financial_offers_type ON financial_offers(offer_type);
CREATE INDEX idx_financial_offers_target ON financial_offers(target_user_type);
CREATE INDEX idx_financial_offers_active ON financial_offers(is_active);
CREATE INDEX idx_user_offers_user ON user_offers(user_id);
CREATE INDEX idx_user_offers_offer ON user_offers(offer_id);
CREATE INDEX idx_user_offers_score ON user_offers(eligibility_score);