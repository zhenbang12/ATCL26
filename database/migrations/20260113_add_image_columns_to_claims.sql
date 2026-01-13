-- Migration: Add receipt_image and items_image columns to claims table
-- Run: mysql -u root -p atcl26 < database/migrations/20260113_add_image_columns_to_claims.sql

ALTER TABLE claims 
ADD COLUMN receipt_image VARCHAR(500) NULL AFTER description,
ADD COLUMN items_image VARCHAR(500) NULL AFTER receipt_image;
