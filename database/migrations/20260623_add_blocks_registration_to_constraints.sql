-- Create anomaly_constraints table with blocks_registration column if it doesn't exist yet.
-- If the table already exists (from the create migration), this is a no-op and the
-- controller's ensureAnomalyConstraintsTable() handles adding the column via ALTER TABLE.
CREATE TABLE IF NOT EXISTS anomaly_constraints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL DEFAULT 1,
    constraint_type ENUM('email_pattern', 'field_contains', 'field_equals', 'field_empty', 'field_not_empty', 'field_regex') NOT NULL,
    field_name VARCHAR(100) NOT NULL DEFAULT 'student_email',
    pattern VARCHAR(500) NOT NULL DEFAULT '',
    description VARCHAR(500) NOT NULL DEFAULT '',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    blocks_registration TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_anomaly_constraints_session_id (session_id),
    INDEX idx_anomaly_constraints_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;