CREATE TABLE IF NOT EXISTS anomaly_constraints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL DEFAULT 1,
    constraint_type ENUM('email_pattern', 'field_contains', 'field_equals', 'field_empty', 'field_not_empty', 'field_regex') NOT NULL,
    field_name VARCHAR(100) NOT NULL DEFAULT 'student_email',
    pattern VARCHAR(500) NOT NULL DEFAULT '',
    description VARCHAR(500) NOT NULL DEFAULT '',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_anomaly_constraints_session_id (session_id),
    INDEX idx_anomaly_constraints_enabled (is_enabled),
    CONSTRAINT fk_anomaly_constraints_session FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;