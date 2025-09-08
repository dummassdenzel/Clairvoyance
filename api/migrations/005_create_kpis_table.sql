-- Migration: 005_create_kpis_table.sql
-- Description: Create kpis table with RAG thresholds and formatting options
-- Date: 2024-01-XX

-- Drop table if exists (for development only)
DROP TABLE IF EXISTS kpis;

-- Create kpis table
CREATE TABLE kpis (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    direction ENUM('higher_is_better', 'lower_is_better') NOT NULL DEFAULT 'higher_is_better',
    target DECIMAL(10,2) NOT NULL,
    rag_red DECIMAL(10,2) NOT NULL COMMENT 'Red threshold value',
    rag_amber DECIMAL(10,2) NOT NULL COMMENT 'Amber threshold value',
    format_prefix VARCHAR(10) DEFAULT '',
    format_suffix VARCHAR(10) DEFAULT '',
    user_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    INDEX idx_direction (direction),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
