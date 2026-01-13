-- Migration: add programme_name column for participants to enter their specific programme
-- Run:
--   mysql -u root -p atcl26 < database/migrations/20260113_add_programme_name_column.sql

ALTER TABLE participants
    ADD COLUMN programme_name VARCHAR(255) NULL AFTER programme;

