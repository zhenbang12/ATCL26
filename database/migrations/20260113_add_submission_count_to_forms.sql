-- Migration: Add submission_count column to forms table
-- Run: mysql -u root -p atcl26 < database/migrations/20260113_add_submission_count_to_forms.sql

ALTER TABLE forms ADD COLUMN submission_count INT NOT NULL DEFAULT 0 AFTER is_active;

-- Initialize submission_count based on existing submissions
UPDATE forms f
SET submission_count = (
    SELECT COUNT(*) 
    FROM form_submissions fs 
    WHERE fs.form_id = f.id
);
