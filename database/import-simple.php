<?php
/**
 * Simple direct import from MySQL to PostgreSQL
 */

// Source: MySQL localhost
$mysql = new mysqli('localhost', 'root', '', 'demolitiontraders');
$mysql->set_charset('utf8mb4');

// Target: Render PostgreSQL
$pgConn = "host=dpg-d4n486a4d50c73f5ksng-a.oregon-postgres.render.com port=5432 dbname=demolitiontraders user=demolition_user password=y0XviqttYTB1D18x0IVrKtJZP6Ck4Hz6 sslmode=require";
$pg = pg_connect($pgConn);

if (!$pg) {
    die("PostgreSQL connection failed\n");
}

echo "✓ Connected to both databases\n\n";

// Get products
$result = $mysql->query("SELECT * FROM products WHERE is_active = 1 ORDER BY id LIMIT 100");

$imported = 0;
$skipped = 0;

echo "Importing products...\n";

while ($row = $result->fetch_assoc()) {
    // Clean text - remove problematic chars
    foreach ($row as $key => $value) {
        if (is_string($value)) {
            $row[$key] = preg_replace('/[^\x20-\x7E\r\n]/', '', $value); // Keep only ASCII + newlines
        }
    }
    
    // Prepare INSERT
    $sql = "INSERT INTO products (
        id, sku, name, slug, description, short_description,
        price, cost_price, compare_at_price, category_id,
        stock_quantity, min_stock_level, weight, dimensions,
        condition_type, is_featured, is_active, show_collection_options,
        created_at, updated_at
    ) VALUES (
        $1, $2, $3, $4, $5, $6, $7, $8, $9, $10,
        $11, $12, $13, $14, $15, $16, $17, $18, $19, $20
    ) ON CONFLICT (sku) DO NOTHING";
    
    $params = [
        $row['id'],
        $row['sku'],
        $row['name'],
        $row['slug'],
        $row['description'],
        $row['short_description'],
        $row['price'],
        $row['cost_price'],
        $row['compare_at_price'],
        $row['category_id'],
        $row['stock_quantity'],
        $row['min_stock_level'],
        $row['weight'],
        $row['dimensions'],
        $row['condition_type'],
        $row['is_featured'] ? 't' : 'f',
        $row['is_active'] ? 't' : 'f',
        $row['show_collection_options'] ? 't' : 'f',
        $row['created_at'],
        $row['updated_at']
    ];
    
    $res = pg_query_params($pg, $sql, $params);
    
    if ($res) {
        $imported++;
        if ($imported % 10 == 0) {
            echo "  Imported $imported products...\n";
        }
    } else {
        $skipped++;
        // echo "  Skipped {$row['sku']}: " . pg_last_error($pg) . "\n";
    }
}

echo "\n✓ Import complete!\n";
echo "  Imported: $imported\n";
echo "  Skipped: $skipped\n";

// Verify
$result = pg_query($pg, "SELECT COUNT(*) FROM products WHERE is_active = TRUE");
$count = pg_fetch_result($result, 0, 0);
echo "\nTotal active products in database: $count\n";

pg_close($pg);
$mysql->close();
