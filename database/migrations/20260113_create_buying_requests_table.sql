-- Migration: Create buying_requests table
-- Run: mysql -u root -p atcl26 < database/migrations/20260113_create_buying_requests_table.sql

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
    status ENUM('pending','approved','rejected','purchased') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
