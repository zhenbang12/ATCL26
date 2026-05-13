-- Landing page settings (logos, background color, and editable text content)

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
    logo_1_filename,
    logo_1_alt_text,
    logo_2_filename,
    logo_2_alt_text,
    logo_3_filename,
    logo_3_alt_text,
    background_color,
    main_title,
    main_caption,
    section_1_title,
    section_1_caption,
    section_2_title,
    section_2_caption,
    section_3_title,
    section_3_caption
) VALUES (
    NULL,
    '',
    NULL,
    '',
    NULL,
    '',
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
