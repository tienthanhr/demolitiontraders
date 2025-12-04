-- Add position column to categories table (MySQL)
ALTER TABLE categories ADD COLUMN position INT DEFAULT 0 AFTER name;
ALTER TABLE categories ADD KEY idx_position (position);

-- Update categories to have sequential positions
SET @pos := 0;
UPDATE categories SET position = (@pos := @pos + 1) WHERE is_deleted = 0 OR is_deleted IS NULL ORDER BY id ASC;
