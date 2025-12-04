<?php
/**
 * Fix category hierarchy to match header layout
 * Main categories: PLYWOOD, DOORS, WINDOWS, SLIDING DOORS, TIMBER, CLADDING, LANDSCAPING, ROOFING, KITCHENS, BATHROOM & LAUNDRY, GENERAL
 */
require_once __DIR__ . '/backend/config/database.php';

$db = Database::getInstance();

// First, reset all parent_id to NULL
$db->query("UPDATE categories SET parent_id = NULL");

// Define main categories and their subcategories
$mainCategories = [
    4 => [12, 18, 32, 40],  // PLYWOOD
    1 => [11, 16, 26, 34, 36, 61, 63, 65, 73, 74, 76, 78],  // DOORS
    2 => [13, 57, 35, 68, 44],  // WINDOWS
    71 => [75, 79],  // SLIDING DOORS
    21 => [22, 23, 25, 27],  // TIMBER
    24 => [62],  // WEATHERBOARD/CLADDING
    54 => [55, 60],  // LANDSCAPING
    7 => [37, 43, 69, 70],  // ROOFING
    5 => [39, 50, 51, 52],  // KITCHENS
    53 => [56, 59, 64, 77],  // BATHROOM & LAUNDRY
    48 => [6, 9, 10, 14, 15, 17, 19, 20, 28, 29, 30, 33, 38, 41, 42, 45, 46, 47, 49, 58, 66, 67, 72, 80]  // GENERAL (catch-all)
];

echo "Fixing category hierarchy...\n\n";

$totalUpdates = 0;
foreach ($mainCategories as $mainId => $subIds) {
    foreach ($subIds as $subId) {
        $db->query(
            "UPDATE categories SET parent_id = :parent_id WHERE id = :id",
            ['parent_id' => $mainId, 'id' => $subId]
        );
        $totalUpdates++;
        echo "✓ Set category $subId as subcategory of $mainId\n";
    }
}

// Set positions for main categories
$mainCategoryOrder = [4, 1, 2, 71, 21, 24, 54, 7, 5, 53, 48];
foreach ($mainCategoryOrder as $pos => $catId) {
    $db->query(
        "UPDATE categories SET position = :position WHERE id = :id",
        ['position' => $pos, 'id' => $catId]
    );
    echo "✓ Set position $pos for main category $catId\n";
}

echo "\n✓ Fixed $totalUpdates subcategories\n";
echo "✓ Set positions for " . count($mainCategoryOrder) . " main categories\n";
echo "\nDone! Now use admin panel to adjust further.\n";
?>
