<?php
/**
 * Fix PostgreSQL Sequences
 * Run this once after importing data to reset auto-increment sequences
 */

require_once __DIR__ . '/../backend/config/database.php';

echo "Fixing PostgreSQL sequences...\n\n";

$db = Database::getInstance();

$sequences = [
    'orders' => 'orders_id_seq',
    'order_items' => 'order_items_id_seq',
    'users' => 'users_id_seq',
    'products' => 'products_id_seq',
    'product_images' => 'product_images_id_seq',
    'categories' => 'categories_id_seq',
    'cart_items' => 'cart_items_id_seq',
    'wishlist_items' => 'wishlist_items_id_seq',
    'addresses' => 'addresses_id_seq',
];

foreach ($sequences as $table => $sequence) {
    try {
        // Get max ID from table
        $result = $db->fetchOne("SELECT COALESCE(MAX(id), 0) as max_id FROM {$table}");
        $maxId = $result['max_id'];
        
        // Reset sequence
        $db->query("SELECT setval('{$sequence}', {$maxId})");
        
        // Verify
        $result = $db->fetchOne("SELECT last_value FROM {$sequence}");
        $lastValue = $result['last_value'];
        
        echo "✓ {$table}: max_id={$maxId}, sequence={$lastValue}\n";
    } catch (Exception $e) {
        echo "✗ {$table}: Error - " . $e->getMessage() . "\n";
    }
}

echo "\nDone! All sequences have been reset.\n";
echo "You can now create new orders without duplicate key errors.\n";
