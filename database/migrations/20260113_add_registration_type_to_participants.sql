ALTER TABLE participants
ADD COLUMN registration_type ENUM('pre_register','walk_in') NOT NULL DEFAULT 'pre_register'
AFTER preferred_language;
