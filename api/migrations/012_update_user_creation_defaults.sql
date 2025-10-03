-- Migration: 012_update_user_creation_defaults.sql
-- Description: Update user creation defaults and add constraints for new role system
-- Date: 2024-01-XX

-- Update the users table to ensure proper defaults and constraints
ALTER TABLE users 
MODIFY COLUMN role ENUM('editor', 'admin') NOT NULL DEFAULT 'editor';

-- Add a check constraint to ensure we don't accidentally create invalid roles
-- (MySQL doesn't support CHECK constraints in older versions, so we'll rely on ENUM)
-- Add a comment to document the role system
ALTER TABLE users 
COMMENT = 'Users table - editor (universal role for creating/viewing), admin (system administration)';

-- Ensure all existing users have valid roles
UPDATE users SET role = 'editor' WHERE role NOT IN ('editor', 'admin');
