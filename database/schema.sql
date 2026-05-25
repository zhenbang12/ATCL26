-- Base SQL schema for Camp Management System (MySQL-compatible)

CREATE TABLE IF NOT EXISTS participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    ic_passport_no VARCHAR(50),
    student_id VARCHAR(50),
    student_email VARCHAR(255),
    intake VARCHAR(255),
    programme_name VARCHAR(255),
    faculty VARCHAR(255),
    gender VARCHAR(20),
    contact_no VARCHAR(50),
    emergency_contact_no VARCHAR(50),
    emergency_contact_relationship VARCHAR(100),
    preferred_language VARCHAR(50),
    registration_type ENUM('pre_register','walk_in') NOT NULL DEFAULT 'pre_register',
    qr_code VARCHAR(64) UNIQUE,
    group_code VARCHAR(20),
    blacklisted TINYINT(1) NOT NULL DEFAULT 0,
    duplicate_of INT NULL,
    checked_in_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_unique_student_id (student_id)
);

CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source ENUM('participant','crew','committee') NOT NULL,
    name VARCHAR(255),
    role_or_group VARCHAR(100),
    rating TINYINT,
    comments TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS landing_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slot ENUM('hero', 'feature_1', 'feature_2') NOT NULL,
    filename VARCHAR(255) NULL,
    alt_text VARCHAR(500) NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_landing_images_slot (slot)
);

INSERT IGNORE INTO landing_images (slot, filename, alt_text) VALUES
    ('hero', NULL, ''),
    ('feature_1', NULL, ''),
    ('feature_2', NULL, '');

CREATE TABLE IF NOT EXISTS landing_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logo_1_filename VARCHAR(255) NULL,
    logo_1_alt_text VARCHAR(500) NOT NULL DEFAULT '',
    logo_2_filename VARCHAR(255) NULL,
    logo_2_alt_text VARCHAR(500) NOT NULL DEFAULT '',
    logo_3_filename VARCHAR(255) NULL,
    logo_3_alt_text VARCHAR(500) NOT NULL DEFAULT '',
    background_color VARCHAR(7) NOT NULL DEFAULT '#ffffff',
    main_title VARCHAR(255) NOT NULL DEFAULT 'Welcome to Adjustment To Campus Life',
    main_caption TEXT NOT NULL,
    section_1_title VARCHAR(255) NOT NULL DEFAULT 'What is it?',
    section_1_caption TEXT NOT NULL,
    section_2_title VARCHAR(255) NOT NULL DEFAULT 'What to expect',
    section_2_caption TEXT NOT NULL,
    section_3_title VARCHAR(255) NOT NULL DEFAULT 'Before you arrive',
    section_3_caption TEXT NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO landing_settings (
    logo_1_filename, logo_1_alt_text,
    logo_2_filename, logo_2_alt_text,
    logo_3_filename, logo_3_alt_text,
    background_color, main_title, main_caption,
    section_1_title, section_1_caption,
    section_2_title, section_2_caption,
    section_3_title, section_3_caption
) VALUES (
    NULL, '',
    NULL, '',
    NULL, '',
    '#ffffff',
    'Welcome to Adjustment To Campus Life',
    'A few days of games, teamwork, and community built for TAR UMT students to connect, learn, and make memories together.',
    'What is it?',
    'ATCL is our annual camp-style programme. You will join a small group, take part in station games and activities, and get to know facilitators and participants from across programmes. It is run by student leaders and advisors with safety and inclusion in mind.',
    'What to expect',
    'Icebreakers and group challenges across the event. Meals, briefings, and evening segments with your group. Check-in on arrival using the QR code you receive after registering. Language-friendly grouping so you can participate comfortably.',
    'Before you arrive',
    'Pre-register with your student details so we can prepare your QR for check-in and place you in a group when you arrive. If you already registered, you can retrieve your QR any time.'
);

CREATE TABLE IF NOT EXISTS registration_settings (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    pre_register_enabled TINYINT(1) NOT NULL DEFAULT 1,
    walk_in_enabled TINYINT(1) NOT NULL DEFAULT 1,
    theme VARCHAR(20) NOT NULL DEFAULT 'violet',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO registration_settings (id, pre_register_enabled, walk_in_enabled, theme)
VALUES (1, 1, 1, 'violet');

CREATE TABLE IF NOT EXISTS event_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_code VARCHAR(20) NOT NULL,
    language_pool ENUM('english', 'mandarin') NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_event_groups_code (group_code)
);

CREATE TABLE IF NOT EXISTS event_group_settings (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    max_per_group INT NOT NULL DEFAULT 0
);

INSERT IGNORE INTO event_group_settings (id, max_per_group) VALUES (1, 0);

CREATE TABLE IF NOT EXISTS crew (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role VARCHAR(100),
    assigned_group_code VARCHAR(20),
    is_medic TINYINT(1) NOT NULL DEFAULT 0,
    is_facilitator TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS crew_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crew_id INT NOT NULL,
    session_label VARCHAR(100) NOT NULL,
    attended TINYINT(1) NOT NULL DEFAULT 0,
    marked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (crew_id) REFERENCES crew(id)
);

CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    station_code VARCHAR(50),
    description TEXT
);

CREATE TABLE IF NOT EXISTS scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    group_code VARCHAR(20) NOT NULL,
    score INT NOT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(id)
);

CREATE TABLE IF NOT EXISTS rotations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_label VARCHAR(100) NOT NULL,
    group_code VARCHAR(20) NOT NULL,
    station_code VARCHAR(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claimant_name VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    description TEXT,
    receipt_image VARCHAR(500) NULL,
    items_image VARCHAR(500) NULL,
    amount_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('draft','submitted','verified','approved','rejected','paid') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    allocated_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    spent_amount DECIMAL(10,2) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS buying_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_name VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    item_description TEXT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    estimated_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    justification TEXT,
    vendor_preference VARCHAR(255),
    reference_image VARCHAR(500) NULL,
    status ENUM('draft','pending','approved','rejected','purchased') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_info VARCHAR(255),
    notes TEXT
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('pending','in_progress','done','blocked') NOT NULL DEFAULT 'pending',
    due_date DATE,
    depends_on_task_id INT NULL,
    FOREIGN KEY (depends_on_task_id) REFERENCES tasks(id)
);

CREATE TABLE IF NOT EXISTS proposals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    owner_name VARCHAR(255) NOT NULL,
    status ENUM('draft','submitted','advisor_review','approved','rejected') NOT NULL DEFAULT 'draft',
    document_url VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    related_type ENUM('task','proposal') NOT NULL,
    related_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    due_at DATETIME NOT NULL,
    sent TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_audience ENUM('participant','crew','committee','all') NOT NULL DEFAULT 'all',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    submission_count INT NOT NULL DEFAULT 0,
    created_by VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS form_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    field_label VARCHAR(255) NOT NULL,
    field_type ENUM('text','textarea','number','email','select','radio','checkbox','date','rating') NOT NULL DEFAULT 'text',
    field_options TEXT,
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    field_order INT NOT NULL DEFAULT 0,
    placeholder VARCHAR(255),
    validation_pattern VARCHAR(255),
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    submitted_by VARCHAR(255),
    submitted_by_type ENUM('participant','crew','committee') NOT NULL,
    submitted_by_id INT,
    submission_data JSON NOT NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255),
    capacity INT
);

CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venue_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    department VARCHAR(100),
    FOREIGN KEY (venue_id) REFERENCES venues(id)
);

CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    quantity_available INT NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    direction ENUM('out','in') NOT NULL,
    taken_by VARCHAR(255),
    note VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reported_by VARCHAR(255),
    type ENUM('injury','lost_item','other') NOT NULL DEFAULT 'other',
    description TEXT,
    related_group_code VARCHAR(20),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'committee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

