-- Migration script to enhance properties table for sale and investment features
-- Also create tables for property sales and investments

-- Extend properties table
ALTER TABLE properties 
ADD COLUMN is_for_sale BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN allows_fractional_investment BOOLEAN DEFAULT FALSE AFTER sale_price,
ADD COLUMN investment_offering_percentage DECIMAL(5,2) CHECK (investment_offering_percentage BETWEEN 0 AND 100) AFTER allows_fractional_investment;

-- Create property_sales table
CREATE TABLE IF NOT EXISTS property_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    seller_id INT NOT NULL,
    buyer_id INT,
    sale_status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    sale_price DECIMAL(12,2),
    sale_date DATE,
    tenant_notification_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (seller_id) REFERENCES users(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id)
);

-- Create property_investments table
CREATE TABLE IF NOT EXISTS property_investments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    investor_id INT NOT NULL,
    investment_amount DECIMAL(12,2),
    ownership_percentage DECIMAL(5,2),
    investment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (investor_id) REFERENCES users(id)
);

-- Add indexes for performance
CREATE INDEX idx_properties_for_sale ON properties(is_for_sale);
CREATE INDEX idx_properties_fractional_investment ON properties(allows_fractional_investment);
CREATE INDEX idx_property_sales_property ON property_sales(property_id);
CREATE INDEX idx_property_sales_seller ON property_sales(seller_id);
CREATE INDEX idx_property_sales_buyer ON property_sales(buyer_id);
CREATE INDEX idx_property_sales_status ON property_sales(sale_status);
CREATE INDEX idx_property_investments_property ON property_investments(property_id);
CREATE INDEX idx_property_investments_investor ON property_investments(investor_id);