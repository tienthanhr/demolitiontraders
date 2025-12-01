<?php
/**
 * Import Products from CSV
 * Imports products from export_table_products CSV file
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);
ini_set('memory_limit', '512M');

require_once __DIR__ . '/backend/config/database.php';

$db = Database::getInstance()->getConnection();

// CSV file path
$csvFile = __DIR__ . '/export_table_products_25Nov2025_09_43.csv';

if (!file_exists($csvFile)) {
    die("CSV file not found: $csvFile");
}

echo "<h2>Product Import Process</h2>";
echo "<p>Starting import from: " . basename($csvFile) . "</p>";
echo "<hr>";

// Read CSV file
$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Could not open CSV file");
}

// Get header row
$headers = fgetcsv($handle);
echo "<p>CSV Headers found: " . count($headers) . " columns</p>";
echo "<pre>Headers: " . implode(', ', array_slice($headers, 0, 10)) . "...</pre>";

// Map CSV columns to database fields
$columnMap = [
    'Part Number' => 'part_number',
    'Title' => 'title',
    'Description' => 'description',
    'Price' => 'price',
    'Special Offer Price' => 'special_price',
    'Category' => 'category',
    'Section' => 'section',
    'Live Stock' => 'stock',
    'Main Image' => 'main_image',
    'New Product' => 'is_new',
    'Recycled' => 'is_recycled',
    'Featured' => 'is_featured',
    'Show Online' => 'show_online',
    'Weight (in grams)' => 'weight',
    'Meta Title' => 'meta_title',
    'Meta Description' => 'meta_description'
];

// Find column indices
$columnIndices = [];
foreach ($columnMap as $csvColumn => $dbField) {
    $index = array_search($csvColumn, $headers);
    if ($index !== false) {
        $columnIndices[$dbField] = $index;
    }
}

echo "<p>Mapped " . count($columnIndices) . " columns</p>";
echo "<hr>";

// Categories cache
$categoriesCache = [];

// Function to get or create category
function getOrCreateCategory($db, $categoryName, &$cache) {
    if (empty($categoryName)) {
        return null;
    }
    
    $categoryName = trim($categoryName);
    
    if (isset($cache[$categoryName])) {
        return $cache[$categoryName];
    }
    
    // Check if category exists
    $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$categoryName]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $cache[$categoryName] = $result['id'];
        return $result['id'];
    }
    
    // Create new category
    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $categoryName));
    $slug = trim($slug, '-');
    
    // Ensure unique slug
    $baseSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    
    $stmt = $db->prepare("INSERT INTO categories (name, slug, is_active) VALUES (?, ?, 1)");
    $stmt->execute([$categoryName, $slug]);
    $categoryId = $db->lastInsertId();
    
    $cache[$categoryName] = $categoryId;
    return $categoryId;
}

// Function to generate slug
function generateSlug($db, $text, $id = null) {
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');
    
    if (empty($slug)) {
        $slug = 'product-' . uniqid();
    }
    
    // Ensure unique slug
    $baseSlug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id ?: 0]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

// Function to get random placeholder image
function getPlaceholderImage($index) {
    $colors = ['FF6B6B', '4ECDC4', '45B7D1', 'FFA07A', '98D8C8', 'F7DC6F', 'BB8FCE', '85C1E2'];
    $color = $colors[$index % count($colors)];
    return "https://via.placeholder.com/400x300/$color/FFFFFF?text=Product+Image";
}

// Statistics
$stats = [
    'total' => 0,
    'imported' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0
];

echo "<p>Processing products...</p>";
echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #f5f5f5;'>";

$lineNumber = 1;
while (($row = fgetcsv($handle)) !== false) {
    $lineNumber++;
    $stats['total']++;
    
    try {
        // Extract data from CSV
        $partNumber = isset($columnIndices['part_number']) ? trim($row[$columnIndices['part_number']]) : '';
        $title = isset($columnIndices['title']) ? trim($row[$columnIndices['title']]) : '';
        $description = isset($columnIndices['description']) ? trim($row[$columnIndices['description']]) : '';
        $price = isset($columnIndices['price']) ? floatval($row[$columnIndices['price']]) : 0;
        $specialPrice = isset($columnIndices['special_price']) ? floatval($row[$columnIndices['special_price']]) : 0;
        $categoryName = isset($columnIndices['category']) ? trim($row[$columnIndices['category']]) : '';
        $stock = isset($columnIndices['stock']) ? intval($row[$columnIndices['stock']]) : 0;
        $mainImage = isset($columnIndices['main_image']) ? trim($row[$columnIndices['main_image']]) : '';
        $isNew = isset($columnIndices['is_new']) ? (strtoupper(trim($row[$columnIndices['is_new']])) === 'Y') : false;
        $isRecycled = isset($columnIndices['is_recycled']) ? (strtoupper(trim($row[$columnIndices['is_recycled']])) === 'Y') : false;
        $isFeatured = isset($columnIndices['is_featured']) ? (strtoupper(trim($row[$columnIndices['is_featured']])) === 'Y') : false;
        $showOnline = isset($columnIndices['show_online']) ? (strtoupper(trim($row[$columnIndices['show_online']])) === 'Y') : false;
        $weight = isset($columnIndices['weight']) ? floatval($row[$columnIndices['weight']]) : 0;
        $metaTitle = isset($columnIndices['meta_title']) ? trim($row[$columnIndices['meta_title']]) : '';
        $metaDescription = isset($columnIndices['meta_description']) ? trim($row[$columnIndices['meta_description']]) : '';
        
        // Skip if no part number or (no title and no description)
        if (empty($partNumber) || (empty($title) && empty($description))) {
            $stats['skipped']++;
            if ($stats['total'] % 1000 == 0) {
                echo "<div>Skipped line $lineNumber (incomplete data)</div>";
            }
            continue;
        }
        
        // Use title, or fallback to description if no title
        $productName = !empty($title) ? $title : substr($description, 0, 100);
        if (empty($productName)) {
            $productName = $partNumber;
        }
        
        // Get short description (first 500 chars of description)
        $shortDescription = !empty($description) ? substr($description, 0, 500) : '';
        
        // Get category ID
        $categoryId = getOrCreateCategory($db, $categoryName, $categoriesCache);
        
        // Generate SKU (use part_number or generate one)
        $sku = !empty($partNumber) ? 'DT-' . $partNumber : 'DT-' . uniqid();
        $sku = preg_replace('/[^A-Za-z0-9-_]/', '', $sku);
        
        // Determine condition
        $condition = $isRecycled ? 'recycled' : 'new';
        
        // Use special price if available, otherwise regular price
        $finalPrice = $specialPrice > 0 ? $specialPrice : $price;
        $comparePrice = $specialPrice > 0 ? $price : null;
        
        // Check if product already exists
        $stmt = $db->prepare("SELECT id, slug FROM products WHERE sku = ?");
        $stmt->execute([$sku]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing product
            $slug = $existing['slug'];
            
            $stmt = $db->prepare("
                UPDATE products SET
                    name = ?,
                    description = ?,
                    short_description = ?,
                    price = ?,
                    compare_at_price = ?,
                    category_id = ?,
                    stock_quantity = ?,
                    weight = ?,
                    condition_type = ?,
                    is_featured = ?,
                    is_active = ?,
                    meta_title = ?,
                    meta_description = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $stmt->execute([
                $productName,
                $description,
                $shortDescription,
                $finalPrice,
                $comparePrice,
                $categoryId,
                $stock,
                $weight > 0 ? $weight : null,
                $condition,
                $isFeatured ? 1 : 0,
                $showOnline ? 1 : 0,
                $metaTitle ?: $productName,
                $metaDescription ?: $shortDescription,
                $existing['id']
            ]);
            
            $productId = $existing['id'];
            $stats['updated']++;
            
        } else {
            // Insert new product
            $slug = generateSlug($db, $productName);
            
            $stmt = $db->prepare("
                INSERT INTO products (
                    sku, name, slug, description, short_description, price, compare_at_price,
                    category_id, stock_quantity, weight, condition_type, is_featured, is_active,
                    meta_title, meta_description, show_collection_options
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $sku,
                $productName,
                $slug,
                $description,
                $shortDescription,
                $finalPrice,
                $comparePrice,
                $categoryId,
                $stock,
                $weight > 0 ? $weight : null,
                $condition,
                $isFeatured ? 1 : 0,
                $showOnline ? 1 : 0,
                $metaTitle ?: $productName,
                $metaDescription ?: $shortDescription,
                isset($showCollectionOptions) ? (int)$showCollectionOptions : 0
            ]);
            
            $productId = $db->lastInsertId();
            $stats['imported']++;
        }
        
        // Handle product image
        if (!empty($mainImage)) {
            // Check if it's a URL or filename
            if (filter_var($mainImage, FILTER_VALIDATE_URL)) {
                $imageUrl = $mainImage;
            } else {
                // Assume it's a filename in uploads directory
                $imageUrl = '/demolitiontraders/uploads/' . $mainImage;
            }
        } else {
            // Use placeholder
            $imageUrl = getPlaceholderImage($stats['imported']);
        }
        
        // Delete old images and insert new one
        $stmt = $db->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$productId]);
        
        $stmt = $db->prepare("
            INSERT INTO product_images (product_id, image_url, alt_text, is_primary, display_order)
            VALUES (?, ?, ?, 1, 0)
        ");
        $stmt->execute([$productId, $imageUrl, $productName]);
        
        // Log progress every 1000 records
        if ($stats['total'] % 1000 == 0) {
            echo "<div><strong>Progress:</strong> Processed {$stats['total']} rows - Imported: {$stats['imported']}, Updated: {$stats['updated']}, Skipped: {$stats['skipped']}</div>";
            flush();
        }
        
    } catch (Exception $e) {
        $stats['errors']++;
        if ($stats['errors'] < 10) {
            echo "<div style='color: red;'>Error on line $lineNumber: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

fclose($handle);

echo "</div>";
echo "<hr>";
echo "<h3>Import Complete!</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><td><strong>Total Rows:</strong></td><td>{$stats['total']}</td></tr>";
echo "<tr><td><strong>New Products:</strong></td><td style='color: green;'>{$stats['imported']}</td></tr>";
echo "<tr><td><strong>Updated Products:</strong></td><td style='color: blue;'>{$stats['updated']}</td></tr>";
echo "<tr><td><strong>Skipped Rows:</strong></td><td style='color: orange;'>{$stats['skipped']}</td></tr>";
echo "<tr><td><strong>Errors:</strong></td><td style='color: red;'>{$stats['errors']}</td></tr>";
echo "</table>";

// Show category statistics
echo "<hr>";
echo "<h3>Categories Created/Used:</h3>";
echo "<p>Total categories: " . count($categoriesCache) . "</p>";
echo "<ul>";
foreach ($categoriesCache as $name => $id) {
    echo "<li>" . htmlspecialchars($name) . " (ID: $id)</li>";
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='frontend/shop.php'>View Products</a> | <a href='frontend/admin-dashboard.php'>Admin Dashboard</a></p>";
