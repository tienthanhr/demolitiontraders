<?php
/**
 * Import products-data.sql to Render PostgreSQL
 * Run: php database/import-to-render.php
 */

// Render PostgreSQL connection details
$host = 'dpg-d4n486a4d50c73f5ksng-a.oregon-postgres.render.com';
$port = '5432';
$dbname = 'demolitiontraders';
$user = 'demolition_user';
$password = 'y0XviqttYTB1D18x0IVrKtJZP6Ck4Hz6';

echo "=== Import Products to Render PostgreSQL ===\n\n";

// Connect to Render PostgreSQL
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✓ Connected to Render PostgreSQL\n";
    
} catch (PDOException $e) {
    die("✗ Connection failed: " . $e->getMessage() . "\n");
}

// Read SQL file
$sqlFile = __DIR__ . '/products-data.sql';
if (!file_exists($sqlFile)) {
    die("✗ File not found: $sqlFile\n");
}

echo "✓ Reading SQL file...\n";
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("✗ Failed to read SQL file\n");
}

// Count estimated inserts
$productInserts = substr_count($sql, "INSERT INTO products");
$imageInserts = substr_count($sql, "INSERT INTO product_images");

echo "✓ Found $productInserts product statements\n";
echo "✓ Found $imageInserts product_images statements\n\n";

// Execute SQL
echo "Importing data (this may take a few minutes)...\n";

try {
    // Disable foreign key checks temporarily for faster import
    $pdo->exec("SET session_replication_role = 'replica';");
    
    $startTime = microtime(true);
    
    // Execute the entire SQL file
    $pdo->exec($sql);
    
    // Re-enable foreign key checks
    $pdo->exec("SET session_replication_role = 'origin';");
    
    $duration = round(microtime(true) - $startTime, 2);
    
    echo "\n✓ Import completed in {$duration}s\n\n";
    
    // Verify import
    echo "=== Verification ===\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = TRUE");
    $productCount = $stmt->fetch()['count'];
    echo "Active products: $productCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_images");
    $imageCount = $stmt->fetch()['count'];
    echo "Product images: $imageCount\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_featured = TRUE");
    $featuredCount = $stmt->fetch()['count'];
    echo "Featured products: $featuredCount\n";
    
    $stmt = $pdo->query("SELECT c.name, COUNT(p.id) as count 
                         FROM categories c 
                         LEFT JOIN products p ON p.category_id = c.id 
                         GROUP BY c.id, c.name 
                         ORDER BY count DESC 
                         LIMIT 5");
    
    echo "\nTop 5 categories:\n";
    while ($row = $stmt->fetch()) {
        echo "  - {$row['name']}: {$row['count']} products\n";
    }
    
    echo "\n✓ Import successful!\n";
    echo "\nNext: Visit https://demolitiontraders.onrender.com to see products\n";
    
} catch (PDOException $e) {
    echo "\n✗ Import failed: " . $e->getMessage() . "\n";
    echo "\nError details:\n";
    print_r($pdo->errorInfo());
    exit(1);
}
