-- Add 'draft' status to claims table
ALTER TABLE claims MODIFY COLUMN status ENUM('draft','submitted','verified','approved','rejected','paid') NOT NULL DEFAULT 'draft';

-- Add 'draft' status to buying_requests table
ALTER TABLE buying_requests MODIFY COLUMN status ENUM('draft','pending','approved','rejected','purchased') NOT NULL DEFAULT 'draft';
