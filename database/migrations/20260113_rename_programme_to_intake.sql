-- Migration: rename participants.programme to intake
-- Run:
--   mysql -u root -p atcl26 < database/migrations/20260113_rename_programme_to_intake.sql

ALTER TABLE participants
    CHANGE COLUMN programme intake VARCHAR(255) NULL;

