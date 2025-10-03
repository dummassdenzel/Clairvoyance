-- Migration: 002_create_dashboards_table.sql
-- Description: Create dashboards table with JSON layout storage
-- Date: 2024-01-XX

-- Drop table if exists (for development only)
DROP TABLE IF EXISTS dashboards;

-- Create dashboards table
CREATE TABLE dashboards (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    layout LONGTEXT NOT NULL COMMENT 'JSON array of widget configurations',
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
