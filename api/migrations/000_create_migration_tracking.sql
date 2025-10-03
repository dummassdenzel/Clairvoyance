-- Migration: 000_create_migration_tracking.sql
-- Description: Create migration tracking table to track which migrations have been run
-- Date: 2024-01-XX

-- Create migration tracking table
CREATE TABLE IF NOT EXISTS migrations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    migration_name VARCHAR(255) NOT NULL UNIQUE,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_migration_name (migration_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
