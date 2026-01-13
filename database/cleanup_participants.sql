-- SQL script to remove all participants from the database
-- WARNING: This will delete ALL participants!
-- Run: mysql -u root -p atcl26 < database/cleanup_participants.sql

-- Option 1: Delete all participants (keeps table structure)
DELETE FROM participants;

-- Option 2: Reset the table completely (removes all data and resets auto-increment)
-- TRUNCATE TABLE participants;

-- Option 3: Delete only participants created after a certain date (if you want to keep real data)
-- DELETE FROM participants WHERE created_at >= '2026-01-13';
