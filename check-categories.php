<?php
/**
 * Check category hierarchy
 */
require_once __DIR__ . '/backend/config/database.php';

try {
    $db = Database::getInstance();
    $categories = $db->fetchAll("SELECT id, name, parent_id FROM categories ORDER BY parent_id, id");
    
    echo "Total categories: " . count($categories) . "\n\n";
    
    // Group by parent
    $byParent = [];
    foreach ($categories as $cat) {
        $parentId = $cat['parent_id'] ?: 'null';
        if (!isset($byParent[$parentId])) {
            $byParent[$parentId] = [];
        }
        $byParent[$parentId][] = $cat;
    }
    
    echo "Main Categories (no parent):\n";
    if (isset($byParent['null'])) {
        foreach ($byParent['null'] as $cat) {
            echo "- ID {$cat['id']}: {$cat['name']}\n";
        }
    }
    
    echo "\n\nCategories with children:\n";
    foreach ($byParent as $parentId => $cats) {
        if ($parentId !== 'null') {
            $parentName = null;
            foreach ($categories as $c) {
                if ($c['id'] == $parentId) {
                    $parentName = $c['name'];
                    break;
                }
            }
            echo "\nParent ID $parentId ($parentName):\n";
            foreach ($cats as $cat) {
                echo "  - ID {$cat['id']}: {$cat['name']}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
