-- Add 'rejected' status to claims table
ALTER TABLE claims MODIFY COLUMN status ENUM('submitted','verified','approved','rejected','paid') NOT NULL DEFAULT 'submitted';
