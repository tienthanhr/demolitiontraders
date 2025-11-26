<?php
// Fix stock issues
require_once 'backend/config/database.php';

try {
    $db = Database::getInstance();
    
    // Clear old cart items
    $db->execute("DELETE FROM cart WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    echo "✓ Cleared old cart items\n";
    
    // Update products with zero or null stock to have some stock
    $db->execute("UPDATE products SET stock_quantity = 100 WHERE stock_quantity IS NULL OR stock_quantity <= 0");
    echo "✓ Updated products with low/null stock\n";
    
    // Show cart items
    $cartItems = $db->fetchAll("SELECT c.*, p.name, p.stock_quantity FROM cart c JOIN products p ON c.product_id = p.id");
    echo "\nCurrent cart items:\n";
    foreach ($cartItems as $item) {
        echo "- {$item['name']}: Requested {$item['quantity']}, Available {$item['stock_quantity']}\n";
    }
    
    // Show sample products
    $products = $db->fetchAll("SELECT id, name, stock_quantity FROM products LIMIT 5");
    echo "\nSample products:\n";
    foreach ($products as $product) {
        echo "- ID {$product['id']}: {$product['name']} (Stock: {$product['stock_quantity']})\n";
    }
    
    echo "\n✓ Done! Try adding to cart again.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
