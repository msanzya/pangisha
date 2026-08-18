-- Migration script to add authentication columns to users table

-- Add phone_verified column to users table
ALTER TABLE users 
ADD COLUMN phone_verified BOOLEAN DEFAULT FALSE AFTER phone;

-- Add preferred_login_method column to users table
ALTER TABLE users 
ADD COLUMN preferred_login_method VARCHAR(20) DEFAULT 'email' AFTER phone_verified;

-- Add indexes for better performance
CREATE INDEX idx_users_phone_verified ON users(phone_verified);