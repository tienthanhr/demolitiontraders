<?php
/**
 * Export Products from MySQL to PostgreSQL format
 * Run: php database/export-products-postgresql.php > database/products-data.sql
 */

// MySQL connection (localhost)
$mysqli = new mysqli('localhost', 'root', '', 'demolitiontraders');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset to UTF-8
$mysqli->set_charset("utf8mb4");

// Helper function to clean and escape strings for PostgreSQL
function cleanString($mysqli, $value) {
    if ($value === null) return null;
    
    // Force to UTF-8 and remove any invalid sequences
    $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
    
    // Remove control characters
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', '', $value);
    
    // Replace common problematic chars
    $value = preg_replace('/[''`´]/u', "'", $value); // Various apostrophes
    $value = preg_replace('/[""]/u', '"', $value); // Various quotes
    $value = preg_replace('/[—–-]/u', '-', $value); // Various dashes
    
    return $mysqli->real_escape_string($value);
}

echo "-- Products Export for PostgreSQL\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Export products
$result = $mysqli->query("
    SELECT id, sku, name, slug, description, short_description, 
           price, cost_price, compare_at_price, category_id,
           stock_quantity, min_stock_level, weight, dimensions,
           condition_type, is_featured, is_active, show_collection_options,
           idealpos_product_id, last_synced_at, meta_title, meta_description,
           created_at, updated_at
    FROM products 
    WHERE is_active = 1
    ORDER BY id
    LIMIT 1000
");

if ($result->num_rows > 0) {
    echo "-- Inserting {$result->num_rows} products\n";
    echo "INSERT INTO products (id, sku, name, slug, description, short_description, price, cost_price, compare_at_price, category_id, stock_quantity, min_stock_level, weight, dimensions, condition_type, is_featured, is_active, show_collection_options, idealpos_product_id, last_synced_at, meta_title, meta_description, created_at, updated_at) VALUES\n";
    
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if (!$first) echo ",\n";
        $first = false;
        
        // Escape and format values for PostgreSQL
        $values = [
            $row['id'],
            "'" . cleanString($mysqli, $row['sku']) . "'",
            "'" . cleanString($mysqli, $row['name']) . "'",
            "'" . cleanString($mysqli, $row['slug']) . "'",
            $row['description'] ? "'" . cleanString($mysqli, $row['description']) . "'" : 'NULL',
            $row['short_description'] ? "'" . cleanString($mysqli, $row['short_description']) . "'" : 'NULL',
            $row['price'],
            $row['cost_price'] ?: 'NULL',
            $row['compare_at_price'] ?: 'NULL',
            $row['category_id'] ?: 'NULL',
            $row['stock_quantity'] ?: 0,
            $row['min_stock_level'] ?: 0,
            $row['weight'] ?: 'NULL',
            $row['dimensions'] ? "'" . cleanString($mysqli, $row['dimensions']) . "'" : 'NULL',
            "'" . $row['condition_type'] . "'",
            $row['is_featured'] ? 'TRUE' : 'FALSE',
            $row['is_active'] ? 'TRUE' : 'FALSE',
            $row['show_collection_options'] ? 'TRUE' : 'FALSE',
            $row['idealpos_product_id'] ? "'" . cleanString($mysqli, $row['idealpos_product_id']) . "'" : 'NULL',
            $row['last_synced_at'] ? "'" . $row['last_synced_at'] . "'" : 'NULL',
            $row['meta_title'] ? "'" . cleanString($mysqli, $row['meta_title']) . "'" : 'NULL',
            $row['meta_description'] ? "'" . cleanString($mysqli, $row['meta_description']) . "'" : 'NULL',
            "'" . $row['created_at'] . "'",
            "'" . $row['updated_at'] . "'"
        ];
        
        echo "(" . implode(', ', $values) . ")";
    }
    
    echo "\nON CONFLICT (sku) DO UPDATE SET\n";
    echo "    name = EXCLUDED.name,\n";
    echo "    price = EXCLUDED.price,\n";
    echo "    stock_quantity = EXCLUDED.stock_quantity,\n";
    echo "    updated_at = CURRENT_TIMESTAMP;\n\n";
}

// Export product images
$result = $mysqli->query("
    SELECT pi.* 
    FROM product_images pi
    JOIN products p ON pi.product_id = p.id
    WHERE p.is_active = 1
    ORDER BY pi.product_id, pi.display_order
    LIMIT 5000
");

if ($result->num_rows > 0) {
    echo "-- Inserting {$result->num_rows} product images\n";
    echo "INSERT INTO product_images (product_id, image_url, alt_text, display_order, is_primary, created_at) VALUES\n";
    
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if (!$first) echo ",\n";
        $first = false;
        
        echo "(";
        echo $row['product_id'] . ", ";
        echo "'" . cleanString($mysqli, $row['image_url']) . "', ";
        echo $row['alt_text'] ? "'" . cleanString($mysqli, $row['alt_text']) . "'" : 'NULL';
        echo ", " . ($row['display_order'] ?: 0);
        echo ", " . ($row['is_primary'] ? 'TRUE' : 'FALSE');
        echo ", '" . $row['created_at'] . "'";
        echo ")";
    }
    
    echo "\nON CONFLICT DO NOTHING;\n\n";
}

echo "-- Update product sequence\n";
echo "SELECT setval('products_id_seq', (SELECT MAX(id) FROM products));\n";
echo "SELECT setval('product_images_id_seq', (SELECT MAX(id) FROM product_images));\n";

$mysqli->close();
echo "\n-- Export complete!\n";
