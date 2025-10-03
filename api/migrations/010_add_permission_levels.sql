-- Migration: 010_add_permission_levels.sql
-- Description: Add permission levels to dashboard_access table for access-based sharing
-- Date: 2024-01-XX

-- Add permission_level column to dashboard_access table
ALTER TABLE dashboard_access 
ADD COLUMN permission_level ENUM('owner', 'editor', 'viewer') NOT NULL DEFAULT 'viewer' 
AFTER user_id;

-- Add index for permission_level for better query performance
ALTER TABLE dashboard_access 
ADD INDEX idx_permission_level (permission_level);

-- Update existing dashboard_access records to have 'viewer' permission by default
-- (This maintains current behavior for existing shared dashboards)
UPDATE dashboard_access SET permission_level = 'viewer' WHERE permission_level IS NULL;

-- Add a comment to document the permission levels
ALTER TABLE dashboard_access 
COMMENT = 'Dashboard access control with permission levels: owner (full control), editor (can edit), viewer (read-only)';
