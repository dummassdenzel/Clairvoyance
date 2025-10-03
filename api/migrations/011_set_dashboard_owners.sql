-- Migration: 011_set_dashboard_owners.sql
-- Description: Set dashboard owners in dashboard_access table for access-based system
-- Date: 2024-01-XX

-- Insert dashboard owners into dashboard_access table
-- This ensures that dashboard creators have 'owner' permissions
INSERT IGNORE INTO dashboard_access (dashboard_id, user_id, permission_level, created_at)
SELECT 
    d.id as dashboard_id,
    d.user_id as user_id,
    'owner' as permission_level,
    d.created_at as created_at
FROM dashboards d
WHERE NOT EXISTS (
    SELECT 1 FROM dashboard_access da 
    WHERE da.dashboard_id = d.id AND da.user_id = d.user_id
);

-- Add a comment to document the owner relationship
ALTER TABLE dashboards 
COMMENT = 'Dashboard table - owners are automatically added to dashboard_access with owner permission';
