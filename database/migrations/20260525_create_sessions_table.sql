-- Migration: Create sessions table for per-session data isolation
-- Each session represents a distinct event (e.g., "Testing", "November 2026")

CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed a default session so existing data has a home
INSERT IGNORE INTO sessions (id, name, description, is_active) VALUES
    (1, 'Default Session', 'Auto-created session for existing data', 1);