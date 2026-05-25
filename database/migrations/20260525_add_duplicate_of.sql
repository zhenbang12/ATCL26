ALTER TABLE participants ADD COLUMN duplicate_of INT NULL AFTER blacklisted;
ALTER TABLE participants ADD INDEX idx_duplicate_of (duplicate_of);
