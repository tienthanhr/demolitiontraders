-- Update users table to remove 'staff' role
-- Run this SQL to update existing database

ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'admin') DEFAULT 'customer';

-- Update any existing staff users to customer
UPDATE users SET role = 'customer' WHERE role = 'staff';
