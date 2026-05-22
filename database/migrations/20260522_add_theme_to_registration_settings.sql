-- Migration: Add theme column to registration_settings
-- Run: mysql -u root -p atcl26 < database/migrations/20260522_add_theme_to_registration_settings.sql

ALTER TABLE registration_settings ADD COLUMN theme VARCHAR(20) NOT NULL DEFAULT 'violet';
