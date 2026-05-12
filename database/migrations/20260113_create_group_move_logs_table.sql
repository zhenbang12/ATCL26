CREATE TABLE IF NOT EXISTS group_move_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    participant_name VARCHAR(255) NOT NULL,
    from_group_code VARCHAR(20) NULL,
    to_group_code VARCHAR(20) NULL,
    moved_by VARCHAR(255) NOT NULL,
    action_type ENUM('move','undo') NOT NULL DEFAULT 'move',
    moved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_group_move_logs_moved_at (moved_at),
    INDEX idx_group_move_logs_participant_id (participant_id)
);
