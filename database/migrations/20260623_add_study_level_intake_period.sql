ALTER TABLE participants
    ADD COLUMN study_level VARCHAR(20) NULL AFTER intake,
    ADD COLUMN intake_period VARCHAR(50) NULL AFTER study_level;