-- Migration: Add session_id to all participant-related tables
-- Ensures data isolation per event session
-- NOTE: No foreign key constraints to avoid collation/engine mismatches with existing tables.

-- 1. participants table
ALTER TABLE participants
    ADD COLUMN session_id INT NOT NULL DEFAULT 1 AFTER id,
    ADD INDEX idx_participants_session_id (session_id);

-- 2. event_groups table
ALTER TABLE event_groups
    ADD COLUMN session_id INT NOT NULL DEFAULT 1 AFTER id,
    ADD INDEX idx_event_groups_session_id (session_id);

-- 3. event_group_settings table — change from single-row to per-session
ALTER TABLE event_group_settings
    DROP PRIMARY KEY,
    ADD COLUMN session_id INT NOT NULL DEFAULT 1 FIRST,
    ADD PRIMARY KEY (session_id, id);

-- 4. crew table
ALTER TABLE crew
    ADD COLUMN session_id INT NOT NULL DEFAULT 1 AFTER id,
    ADD INDEX idx_crew_session_id (session_id);

-- 5. crew_attendance table
ALTER TABLE crew_attendance
    ADD COLUMN session_id INT NOT NULL DEFAULT 1 AFTER id,
    ADD INDEX idx_crew_attendance_session_id (session_id);

-- 6. group_move_logs table
ALTER TABLE group_move_logs
    ADD COLUMN session_id INT NOT NULL DEFAULT 1 AFTER id,
    ADD INDEX idx_group_move_logs_session_id (session_id);

-- Backfill: All existing data gets session_id = 1 (the default session created in previous migration)
UPDATE participants SET session_id = 1 WHERE session_id = 0;
UPDATE event_groups SET session_id = 1 WHERE session_id = 0;
UPDATE crew SET session_id = 1 WHERE session_id = 0;
UPDATE crew_attendance SET session_id = 1 WHERE session_id = 0;
UPDATE group_move_logs SET session_id = 1 WHERE session_id = 0;