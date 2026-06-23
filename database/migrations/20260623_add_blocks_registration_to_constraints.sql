ALTER TABLE anomaly_constraints
    ADD COLUMN blocks_registration TINYINT(1) NOT NULL DEFAULT 0 AFTER is_enabled;