-- Public registration availability controls

CREATE TABLE IF NOT EXISTS registration_settings (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    pre_register_enabled TINYINT(1) NOT NULL DEFAULT 1,
    walk_in_enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO registration_settings (id, pre_register_enabled, walk_in_enabled)
VALUES (1, 1, 1);
