-- Migration Script to Update sell_to_us_submissions Table
-- Run this to add new columns to existing table

-- Add item_name column
ALTER TABLE sell_to_us_submissions 
ADD COLUMN item_name VARCHAR(255) NOT NULL DEFAULT '' AFTER location;

-- Add pickup_delivery column
ALTER TABLE sell_to_us_submissions 
ADD COLUMN pickup_delivery VARCHAR(50) NOT NULL DEFAULT '' AFTER item_condition;

-- Update quantity to be NOT NULL
ALTER TABLE sell_to_us_submissions 
MODIFY COLUMN quantity VARCHAR(100) NOT NULL;

-- Update item_condition to be NOT NULL
ALTER TABLE sell_to_us_submissions 
MODIFY COLUMN item_condition VARCHAR(100) NOT NULL;

-- Update description to be NOT NULL (should already be)
ALTER TABLE sell_to_us_submissions 
MODIFY COLUMN description TEXT NOT NULL;

-- Remove defaults after migration is complete
-- Run this after testing to ensure data integrity
-- ALTER TABLE sell_to_us_submissions 
-- MODIFY COLUMN item_name VARCHAR(255) NOT NULL,
-- MODIFY COLUMN pickup_delivery VARCHAR(50) NOT NULL;
