-- Setup Category Hierarchy for Demolition Traders
-- Maps subcategories to their parent categories

-- PLYWOOD (ID 4) with children
UPDATE categories SET parent_id = 4 WHERE id IN (12, 18, 32, 40);  -- Untreated, Treated, MDF, ACP/Seratone

-- DOORS (ID 1) with children  
UPDATE categories SET parent_id = 1 WHERE id IN (11, 16, 26, 34, 36, 61, 63, 65, 73, 74, 76, 78);
-- New Alum Single, New Alum French, Recycled Interior, Recycled Single, Bi-fold, New Interior, New Alum Entrance, New Exterior, Recycled, Recycled French, Recycled Wooden, Garage

-- WINDOWS (ID 2) with children
UPDATE categories SET parent_id = 2 WHERE id IN (13, 57, 35, 68, 44);  
-- Aluminium, Wooden, Bi-fold/Sliding, New Aluminium, Leadlights/Stained Glass

-- SLIDING DOORS (ID 71) with children
UPDATE categories SET parent_id = 71 WHERE id IN (15, 17);  
-- New Aluminium Sliding, Recycled Sliding

-- TIMBER (ID 21) with children
UPDATE categories SET parent_id = 21 WHERE id IN (20, 22, 25, 19);  
-- Pine, Native Timber, Mouldings, Railway Sleepers

-- CLADDING (ID 24 Weatherboard) with children
UPDATE categories SET parent_id = 24 WHERE id IN (64);  
-- Cement Board

-- LANDSCAPING (ID 54) - typically no direct subcategories
-- UPDATE categories SET parent_id = 54 WHERE id IN (...);

-- ROOFING (ID 7) with children
UPDATE categories SET parent_id = 7 WHERE id IN (23, 55, 70, 42);  
-- Iron Roofing, Roof Tiles, Downpipe, Flashing & Drainage

-- KITCHENS (ID 5) with children
UPDATE categories SET parent_id = 5 WHERE id IN (31, 46, 67, 58);  
-- Kitchenettes, Complete, Cabinets, Bench Tops

-- GENERAL (ID 48) with children - catch-all for remaining categories
UPDATE categories SET parent_id = 48 WHERE id IN (39, 28, 27, 29, 33, 37, 41, 43, 49, 50, 51, 52, 59, 60, 62, 69, 72, 75, 79, 80);
-- General Hardware, Fence/Gates, Clearlite, Gypsum Board, Utility Clad, Laundry Tubs, Showers, Character Items, Blocks, Building Paper, Wire/Netting, Electrical Fittings, Glass Blocks, Hot Water, Toilets, Insulation, Recycled Aluminium, Bricks, Netting, Spouting

-- Verify the hierarchy
SELECT COUNT(*) as total_with_parent FROM categories WHERE parent_id IS NOT NULL;
SELECT parent_id, COUNT(*) as count FROM categories WHERE parent_id IS NOT NULL GROUP BY parent_id ORDER BY parent_id;
