-- Migration script to extend properties table for sale/investment features and create related tables

-- Extend properties table for sale/investment features
ALTER TABLE properties 
ADD COLUMN is_for_sale BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN sale_price DECIMAL(12,2) AFTER is_for_sale,
ADD COLUMN allows_fractional_investment BOOLEAN DEFAULT FALSE AFTER sale_price,
ADD COLUMN investment_offering_percentage DECIMAL(5,2) AFTER allows_fractional_investment;

-- Create NEW tables for enhanced features
CREATE TABLE IF NOT EXISTS property_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    seller_id INT NOT NULL,
    buyer_id INT,
    sale_status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    sale_date DATE,
    tenant_notification_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS property_investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    investor_id INT NOT NULL,
    investment_amount DECIMAL(12,2) NOT NULL,
    ownership_percentage DECIMAL(5,2) NOT NULL,
    investment_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (investor_id) REFERENCES users(id)
);

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_properties_for_sale ON properties(is_for_sale);
CREATE INDEX IF NOT EXISTS idx_property_sales_property ON property_sales(property_id);
CREATE INDEX IF NOT EXISTS idx_property_sales_seller ON property_sales(seller_id);
CREATE INDEX IF NOT EXISTS idx_property_investments_property ON property_investments(property_id);
CREATE INDEX IF NOT EXISTS idx_property_investments_investor ON property_investments(investor_id);