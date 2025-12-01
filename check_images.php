<?php
require_once 'backend/config/database.php';

$db = Database::getInstance();
$products = $db->fetchAll('SELECT id, name, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image FROM products p LIMIT 20');

echo "Product Images Check:\n\n";
foreach($products as $p) {
    $imagePath = $p['image'] ?? 'NULL';
    echo "ID: " . $p['id'] . "\n";
    echo "Name: " . substr($p['name'], 0, 50) . "\n";
    echo "Image: " . $imagePath . "\n";
    
    if ($imagePath && $imagePath != 'NULL') {
        $fullPath = __DIR__ . '/' . ltrim($imagePath, '/');
        echo "Full Path: " . $fullPath . "\n";
        echo "Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    }
    echo "---\n";
}
