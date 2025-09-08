-- Migration: 004_create_dashboard_share_tokens_table.sql
-- Description: Create dashboard_share_tokens table for token-based sharing
-- Date: 2024-01-XX

-- Drop table if exists (for development only)
DROP TABLE IF EXISTS dashboard_share_tokens;

-- Create dashboard_share_tokens table
CREATE TABLE dashboard_share_tokens (
    id INT(11) NOT NULL AUTO_INCREMENT,
    dashboard_id INT(11) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_dashboard_id (dashboard_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (dashboard_id) REFERENCES dashboards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
