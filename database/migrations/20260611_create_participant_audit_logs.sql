CREATE TABLE IF NOT EXISTS participant_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    participant_id INT NOT NULL,
    participant_name VARCHAR(255) NOT NULL,
    action VARCHAR(50) NOT NULL,
    changed_fields TEXT NULL,
    performed_by VARCHAR(255) NOT NULL,
    performed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_participant_audit_logs_session_id (session_id),
    INDEX idx_participant_audit_logs_participant_id (participant_id),
    CONSTRAINT fk_participant_audit_logs_session FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
