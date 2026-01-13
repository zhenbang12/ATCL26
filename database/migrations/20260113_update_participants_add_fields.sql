-- Migration: Update participants table to new registration fields
-- Run this after you already have the original participants table.
-- Example:
--   mysql -u root -p atcl26 < database/migrations/20260113_update_participants_add_fields.sql

ALTER TABLE participants
    ADD COLUMN ic_passport_no VARCHAR(50) NULL AFTER full_name,
    ADD COLUMN student_id VARCHAR(50) NULL AFTER ic_passport_no,
    ADD COLUMN student_email VARCHAR(255) NULL AFTER student_id,
    ADD COLUMN programme VARCHAR(255) NULL AFTER student_email,
    ADD COLUMN faculty VARCHAR(255) NULL AFTER programme,
    ADD COLUMN gender VARCHAR(20) NULL AFTER faculty,
    ADD COLUMN contact_no VARCHAR(50) NULL AFTER gender,
    ADD COLUMN emergency_contact_no VARCHAR(50) NULL AFTER contact_no,
    ADD COLUMN emergency_contact_relationship VARCHAR(100) NULL AFTER emergency_contact_no,
    ADD COLUMN preferred_language VARCHAR(50) NULL AFTER emergency_contact_relationship;

-- Optional: migrate existing data from older columns, if they exist
-- (If your table never had these columns, MySQL will error; comment out if not needed.)
UPDATE participants
SET student_email = COALESCE(student_email, email)
WHERE email IS NOT NULL AND email <> '';

UPDATE participants
SET programme = COALESCE(programme, school)
WHERE school IS NOT NULL AND school <> '';

-- Optional: drop legacy columns once you are confident data is migrated
 ALTER TABLE participants
     DROP COLUMN email,
     DROP COLUMN school,
     DROP COLUMN medical_notes,
     DROP COLUMN dietary_notes;

