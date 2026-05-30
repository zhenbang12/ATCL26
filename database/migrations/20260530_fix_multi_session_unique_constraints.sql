-- Migration: Fix unique constraints to support multiple event sessions
-- Changes unique constraints from global to per-session (composite indexes)

-- 1. participants table
ALTER TABLE participants DROP INDEX idx_unique_student_id;
ALTER TABLE participants ADD UNIQUE INDEX idx_unique_student_session (session_id, student_id);

-- 2. event_groups table
ALTER TABLE event_groups DROP INDEX uq_event_groups_code;
ALTER TABLE event_groups ADD UNIQUE INDEX uq_event_groups_code_session (session_id, group_code);
