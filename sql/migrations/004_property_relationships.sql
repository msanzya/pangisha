-- Migration script to create property relationships table
-- This table will store the relationship between users and properties

CREATE TABLE IF NOT EXISTS property_relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    relationship_type ENUM('owner', 'tenant', 'manager', 'investor') NOT NULL,
    investment_percentage DECIMAL(5,2) CHECK (investment_percentage BETWEEN 0 AND 100),
    start_date DATE,
    end_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- Add indexes for performance
CREATE INDEX idx_property_relationships_user ON property_relationships(user_id);
CREATE INDEX idx_property_relationships_property ON property_relationships(property_id);
CREATE INDEX idx_property_relationships_type ON property_relationships(relationship_type);
CREATE INDEX idx_property_relationships_dates ON property_relationships(start_date, end_date);