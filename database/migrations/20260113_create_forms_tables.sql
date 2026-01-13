-- Migration: Create tables for form management system
-- Run: mysql -u root -p atcl26 < database/migrations/20260113_create_forms_tables.sql

CREATE TABLE IF NOT EXISTS forms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    target_audience ENUM('participant','crew','committee','all') NOT NULL DEFAULT 'all',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS form_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    field_label VARCHAR(255) NOT NULL,
    field_type ENUM('text','textarea','number','email','select','radio','checkbox','date','rating') NOT NULL DEFAULT 'text',
    field_options TEXT, -- JSON for select/radio/checkbox options
    is_required TINYINT(1) NOT NULL DEFAULT 0,
    field_order INT NOT NULL DEFAULT 0,
    placeholder VARCHAR(255),
    validation_pattern VARCHAR(255),
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    submitted_by VARCHAR(255), -- Name or identifier of submitter
    submitted_by_type ENUM('participant','crew','committee') NOT NULL,
    submitted_by_id INT, -- Optional: link to participant/crew ID
    submission_data JSON NOT NULL, -- Store all field responses as JSON
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
);
