-- Migration: Change item_condition to pickup_date
-- Run this to update the sell_to_us_submissions table

-- Rename item_condition to pickup_date and change type
ALTER TABLE sell_to_us_submissions 
CHANGE COLUMN item_condition pickup_date DATE NULL;

-- If you want to see the updated structure
-- SHOW COLUMNS FROM sell_to_us_submissions;
