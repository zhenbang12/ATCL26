-- Lost and Found feature tables

CREATE TABLE IF NOT EXISTS lost_and_found_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL DEFAULT 1,
    photo_filename VARCHAR(255) NULL,
    caption VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status ENUM('unclaimed', 'claimed') NOT NULL DEFAULT 'unclaimed',
    claimed_by_name VARCHAR(255) NULL,
    claimed_by_phone VARCHAR(50) NULL,
    claimed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lost_found_session_id (session_id),
    INDEX idx_lost_found_status (status),
    CONSTRAINT fk_lost_found_session FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;