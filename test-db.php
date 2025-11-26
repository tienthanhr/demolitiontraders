<?php
/**
 * Test Database Connection
 * Access: http://localhost/demolitiontraders/test-db.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

// Load config
require_once 'backend/config/config.php';
require_once 'backend/config/database.php';

try {
    // Test connection
    $db = Database::getInstance();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Test tables
    $tables = [
        'users', 
        'products', 
        'categories', 
        'orders', 
        'cart',
        'idealpos_sync_log'
    ];
    
    echo "<h2>Tables Status:</h2>";
    echo "<ul>";
    
    foreach ($tables as $table) {
        if ($db->tableExists($table)) {
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM {$table}")['count'];
            echo "<li style='color: green;'>✓ {$table} - {$count} records</li>";
        } else {
            echo "<li style='color: red;'>✗ {$table} - NOT FOUND</li>";
        }
    }
    
    echo "</ul>";
    
    // Test sample product
    echo "<h2>Sample Products:</h2>";
    $products = $db->fetchAll("SELECT id, name, price, stock_quantity FROM products LIMIT 5");
    
    if (empty($products)) {
        echo "<p style='color: orange;'>⚠ No products found. Import database/schema.sql first!</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>\${$product['price']}</td>";
            echo "<td>{$product['stock_quantity']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test categories
    echo "<h2>Categories:</h2>";
    $categories = $db->fetchAll("SELECT name FROM categories ORDER BY display_order");
    echo "<ul>";
    foreach ($categories as $cat) {
        echo "<li>{$cat['name']}</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Go to <a href='frontend/index.php'>Homepage</a></li>";
    echo "<li>Go to <a href='frontend/shop.php'>Shop Page</a></li>";
    echo "<li>Test API: <a href='api/products'>Products API</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Solution:</strong> Import database/schema.sql using phpMyAdmin</p>";
}
