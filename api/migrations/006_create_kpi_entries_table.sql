-- Migration: 006_create_kpi_entries_table.sql
-- Description: Create kpi_entries table for time-series KPI data
-- Date: 2024-01-XX

-- Drop table if exists (for development only)
DROP TABLE IF EXISTS kpi_entries;

-- Create kpi_entries table
CREATE TABLE kpi_entries (
    id INT(11) NOT NULL AUTO_INCREMENT,
    kpi_id INT(11) NOT NULL,
    date DATE NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_kpi_id (kpi_id),
    INDEX idx_date (date),
    INDEX idx_kpi_date (kpi_id, date),
    UNIQUE KEY unique_kpi_date (kpi_id, date),
    FOREIGN KEY (kpi_id) REFERENCES kpis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
