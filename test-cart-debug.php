<?php
session_start();
require_once 'backend/config/database.php';

header('Content-Type: text/plain');

echo "=== CART DEBUG ===\n\n";

echo "Session ID: " . session_id() . "\n";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not logged in') . "\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check cart table structure
    echo "=== CART TABLE STRUCTURE ===\n";
    $stmt = $db->query("DESCRIBE cart");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    echo "\n=== ALL CART ITEMS ===\n";
    $stmt = $db->query("SELECT * FROM cart LIMIT 10");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($items)) {
        echo "No items in cart table!\n";
    } else {
        print_r($items);
    }
    
    echo "\n=== PRODUCTS TABLE CHECK ===\n";
    $stmt = $db->query("SELECT id, name, status FROM products LIMIT 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($products);
    
    echo "\n=== PRODUCT_IMAGES TABLE CHECK ===\n";
    $stmt = $db->query("DESCRIBE product_images");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    echo "\n=== SAMPLE IMAGES DATA ===\n";
    $stmt = $db->query("SELECT id, product_id, image_url, is_primary FROM product_images WHERE product_id IN (SELECT product_id FROM cart WHERE user_id = 1) LIMIT 5");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($images);
    
    echo "\n=== FULL QUERY TEST (what API returns) ===\n";
    $stmt = $db->prepare("
        SELECT c.product_id, c.quantity, p.name, p.price, 
               pi.image_url as image
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE c.user_id = 1
        LIMIT 3
    ");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($result);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
