-- Migration script to update tenants table with missing columns

-- Add property_id and agent_id to tenants table
ALTER TABLE tenants 
ADD COLUMN property_id INT NULL AFTER user_id,
ADD COLUMN agent_id INT NULL AFTER property_id,
ADD FOREIGN KEY (property_id) REFERENCES properties(id),
ADD FOREIGN KEY (agent_id) REFERENCES agents(id);

-- Create agents_wallet table for tracking agent balances and transactions
CREATE TABLE IF NOT EXISTS agents_wallet (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    balance DECIMAL(12, 2) DEFAULT 0.00,
    total_earnings DECIMAL(12, 2) DEFAULT 0.00,
    total_payouts DECIMAL(12, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id)
);

-- Create agents_wallet_transactions table for tracking individual transactions
CREATE TABLE IF NOT EXISTS agents_wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    transaction_type ENUM('credit', 'debit') NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_id INT NULL,
    reference_type VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES agents(id)
);

-- Add indexes for better performance
CREATE INDEX idx_tenants_property_id ON tenants(property_id);
CREATE INDEX idx_tenants_agent_id ON tenants(agent_id);
CREATE INDEX idx_wallet_agent_id ON agents_wallet(agent_id);
CREATE INDEX idx_wallet_transactions_agent_id ON agents_wallet_transactions(agent_id);
CREATE INDEX idx_wallet_transactions_created_at ON agents_wallet_transactions(created_at);