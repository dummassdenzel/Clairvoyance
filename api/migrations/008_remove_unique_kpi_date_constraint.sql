-- Migration: 008_remove_unique_kpi_date_constraint.sql
-- Description: Remove unique constraint on kpi_id, date to allow multiple entries per date
-- Date: 2024-01-XX

-- Remove the unique constraint that prevents multiple entries for the same date
ALTER TABLE kpi_entries DROP INDEX unique_kpi_date;
