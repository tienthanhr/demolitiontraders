-- Update users table: Remove staff role from ENUM
-- Only allow customer and admin roles

ALTER TABLE users 
MODIFY COLUMN role ENUM('customer', 'admin') DEFAULT 'customer';

-- Update any existing staff users to customer
UPDATE users SET role = 'customer' WHERE role = 'staff';

COMMIT;
