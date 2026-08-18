-- Migration script to enhance authentication and registration
-- Add phone verification and preferred login method columns to users table

ALTER TABLE users 
ADD COLUMN phone VARCHAR(20) NULL AFTER email,
ADD COLUMN phone_verified BOOLEAN DEFAULT FALSE AFTER phone,
ADD COLUMN preferred_login_method VARCHAR(20) DEFAULT 'email' AFTER phone_verified;

-- Add indexes for better performance
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_phone_verified ON users(phone_verified);
CREATE INDEX idx_users_preferred_login ON users(preferred_login_method);