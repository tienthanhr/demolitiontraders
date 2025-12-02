-- Fix PostgreSQL Boolean Comparisons
-- Convert all boolean comparisons from integer (1/0) to boolean (TRUE/FALSE)

-- This script is safe to run multiple times (idempotent)

-- Note: PostgreSQL BOOLEAN columns store true/false, not 1/0
-- PHP code comparing with 1/0 will fail

-- Categories
-- Already correct - using CHECK constraint

-- Products  
-- Already correct - using CHECK constraint

-- The issue is in PHP code, not database
-- We need to either:
-- 1. Change PHP queries to use TRUE/FALSE instead of 1/0
-- 2. OR cast boolean to integer in queries

-- Temporary fix: Add a compatibility function
CREATE OR REPLACE FUNCTION bool_to_int(b BOOLEAN) RETURNS INTEGER AS $$
BEGIN
    RETURN CASE WHEN b THEN 1 ELSE 0 END;
END;
$$ LANGUAGE plpgsql IMMUTABLE;

-- Create indexes that might help with performance
CREATE INDEX IF NOT EXISTS idx_products_active_featured ON products(is_active, is_featured) WHERE is_active = TRUE AND is_featured = TRUE;
CREATE INDEX IF NOT EXISTS idx_categories_active ON categories(is_active) WHERE is_active = TRUE;
CREATE INDEX IF NOT EXISTS idx_product_images_primary ON product_images(product_id, is_primary) WHERE is_primary = TRUE;
