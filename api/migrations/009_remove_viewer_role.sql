-- Migration: 009_remove_viewer_role.sql
-- Description: Remove viewer role from users table and update existing viewers to editors
-- Date: 2024-01-XX

-- First, update any existing viewers to editors
UPDATE users SET role = 'editor' WHERE role = 'viewer';

-- Modify the role enum to remove 'viewer' and set default to 'editor'
ALTER TABLE users MODIFY COLUMN role ENUM('editor', 'admin') NOT NULL DEFAULT 'editor';

-- Update the default admin user to ensure it's still admin (in case it was affected)
UPDATE users SET role = 'admin' WHERE email = 'admin@clairvoyance.com';
