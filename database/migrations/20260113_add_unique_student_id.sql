-- Migration: Add unique constraint to student_id to prevent duplicates
-- Run: mysql -u root -p atcl26 < database/migrations/20260113_add_unique_student_id.sql

-- First, remove any duplicate student_ids (keep the first one)
-- This is a safety measure - adjust as needed for your data
-- DELETE p1 FROM participants p1
-- INNER JOIN participants p2 
-- WHERE p1.id > p2.id AND p1.student_id = p2.student_id AND p1.student_id IS NOT NULL AND p1.student_id != '';

-- Add unique index on student_id (only for non-empty values)
-- Note: MySQL allows multiple NULL values in a unique index
ALTER TABLE participants
    ADD UNIQUE INDEX idx_unique_student_id (student_id);
