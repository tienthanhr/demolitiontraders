<?php
/**
 * Setup Category Hierarchy
 * Run this once to set parent_id for all subcategories
 */
require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance();
    
    echo "Setting up category hierarchy...\n\n";
    
    // PLYWOOD (ID 4)
    $db->query("UPDATE categories SET parent_id = 4 WHERE id IN (12, 18, 32, 40)");
    echo "✓ PLYWOOD setup (ID 4)\n";
    
    // DOORS (ID 1)
    $db->query("UPDATE categories SET parent_id = 1 WHERE id IN (11, 16, 26, 34, 36, 61, 63, 65, 73, 74, 76, 78)");
    echo "✓ DOORS setup (ID 1)\n";
    
    // WINDOWS (ID 2)
    $db->query("UPDATE categories SET parent_id = 2 WHERE id IN (13, 57, 35, 68, 44)");
    echo "✓ WINDOWS setup (ID 2)\n";
    
    // SLIDING DOORS (ID 71)
    $db->query("UPDATE categories SET parent_id = 71 WHERE id IN (15, 17)");
    echo "✓ SLIDING DOORS setup (ID 71)\n";
    
    // TIMBER (ID 21)
    $db->query("UPDATE categories SET parent_id = 21 WHERE id IN (20, 22, 25, 19)");
    echo "✓ TIMBER setup (ID 21)\n";
    
    // CLADDING (ID 24)
    $db->query("UPDATE categories SET parent_id = 24 WHERE id IN (64)");
    echo "✓ CLADDING setup (ID 24)\n";
    
    // ROOFING (ID 7)
    $db->query("UPDATE categories SET parent_id = 7 WHERE id IN (23, 55, 70, 42)");
    echo "✓ ROOFING setup (ID 7)\n";
    
    // KITCHENS (ID 5)
    $db->query("UPDATE categories SET parent_id = 5 WHERE id IN (31, 46, 67, 58)");
    echo "✓ KITCHENS setup (ID 5)\n";
    
    // GENERAL (ID 48)
    $db->query("UPDATE categories SET parent_id = 48 WHERE id IN (39, 28, 27, 29, 33, 37, 41, 43, 49, 50, 51, 52, 59, 60, 62, 69, 72, 75, 79, 80)");
    echo "✓ GENERAL setup (ID 48)\n";
    
    echo "\n✓ Category hierarchy setup complete!\n";
    
    // Show results
    $results = $db->fetchAll("SELECT parent_id, COUNT(*) as count FROM categories WHERE parent_id IS NOT NULL GROUP BY parent_id ORDER BY parent_id");
    echo "\nSummary:\n";
    foreach ($results as $row) {
        $parent = $db->fetchOne("SELECT name FROM categories WHERE id = ?", [$row['parent_id']]);
        echo "- {$parent['name']} ({$row['parent_id']}): {$row['count']} subcategories\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}
?>
