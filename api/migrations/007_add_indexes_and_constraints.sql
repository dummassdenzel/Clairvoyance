-- Migration: 007_add_indexes_and_constraints.sql
-- Description: Add additional indexes and constraints for performance optimization
-- Date: 2024-01-XX

-- Add composite indexes for common queries
ALTER TABLE dashboards ADD INDEX idx_user_created (user_id, created_at);
ALTER TABLE kpi_entries ADD INDEX idx_date_range (date, kpi_id);
ALTER TABLE dashboard_share_tokens ADD INDEX idx_active_tokens (expires_at, created_at);

-- Add check constraints for data validation
ALTER TABLE kpis ADD CONSTRAINT chk_rag_values CHECK (rag_red != rag_amber);
ALTER TABLE kpi_entries ADD CONSTRAINT chk_positive_value CHECK (value >= 0);

-- Add triggers for automatic timestamp updates (if not already handled by MySQL)
-- Note: MySQL 5.6+ handles ON UPDATE CURRENT_TIMESTAMP automatically
