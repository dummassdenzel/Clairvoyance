-- Migration: 003_create_dashboard_access_table.sql
-- Description: Create dashboard_access table for many-to-many viewer relationships
-- Date: 2024-01-XX

-- Drop table if exists (for development only)
DROP TABLE IF EXISTS dashboard_access;

-- Create dashboard_access table
CREATE TABLE dashboard_access (
    dashboard_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (dashboard_id, user_id),
    INDEX idx_dashboard_id (dashboard_id),
    INDEX idx_user_id (user_id),
    FOREIGN KEY (dashboard_id) REFERENCES dashboards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
