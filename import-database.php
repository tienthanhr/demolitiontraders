<?php
/**
 * Auto Import Database Schema
 * This will import database/schema.sql automatically
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes

echo "<h1>Database Schema Import</h1>";

// Load config
require_once __DIR__ . '/backend/config/config.php';

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'demolitiontraders';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

$schemaFile = __DIR__ . '/database/schema.sql';

// Check if schema file exists
if (!file_exists($schemaFile)) {
    die("<p style='color: red;'>✗ Schema file not found: {$schemaFile}</p>");
}

echo "<p>Found schema file: <code>{$schemaFile}</code></p>";
echo "<p>File size: " . number_format(filesize($schemaFile)) . " bytes</p>";

try {
    // Connect to database
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p style='color: green;'>✓ Connected to database: <strong>{$dbname}</strong></p>";
    
    // Read SQL file
    $sql = file_get_contents($schemaFile);
    
    // Remove comments and split by semicolon
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt);
        }
    );
    
    echo "<p>Found " . count($statements) . " SQL statements to execute...</p>";
    echo "<hr>";
    
    $successCount = 0;
    $errorCount = 0;
    
    // Execute each statement
    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            
            // Get statement type
            preg_match('/^\s*(CREATE|INSERT|ALTER|DROP)\s+(\w+)/i', $statement, $matches);
            $action = $matches[1] ?? '';
            $object = $matches[2] ?? '';
            
            echo "<p style='color: green;'>✓ " . ($index + 1) . ". {$action} {$object}</p>";
            $successCount++;
            
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠ " . ($index + 1) . ". Error: " . $e->getMessage() . "</p>";
            $errorCount++;
        }
    }
    
    echo "<hr>";
    echo "<h2>Import Summary:</h2>";
    echo "<p style='color: green; font-size: 18px;'>✓ <strong>{$successCount}</strong> statements executed successfully</p>";
    
    if ($errorCount > 0) {
        echo "<p style='color: orange;'>⚠ <strong>{$errorCount}</strong> statements had errors (may be duplicates, this is OK)</p>";
    }
    
    // Check tables
    echo "<h2>Database Tables:</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Table Name</th><th>Records</th></tr>";
    
    foreach ($tables as $table) {
        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
        $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<tr>";
        echo "<td><strong>{$table}</strong></td>";
        echo "<td>{$count}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<h2>Sample Products:</h2>";
    $products = $pdo->query("SELECT id, name, price, stock_quantity, condition_type FROM products LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($products)) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Condition</th></tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td>\${$product['price']}</td>";
            echo "<td>{$product['stock_quantity']}</td>";
            echo "<td>{$product['condition_type']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Sample Categories:</h2>";
    $categories = $pdo->query("SELECT id, name, slug FROM categories ORDER BY display_order LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($categories as $cat) {
        echo "<li><strong>{$cat['name']}</strong> (slug: {$cat['slug']})</li>";
    }
    echo "</ul>";
    
    // Show admin account
    echo "<h2>Admin Account:</h2>";
    $admin = $pdo->query("SELECT email, first_name, last_name, role FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px;'>";
        echo "<p><strong>Email:</strong> {$admin['email']}</p>";
        echo "<p><strong>Password:</strong> admin123 <span style='color: red;'>(CHANGE THIS!)</span></p>";
        echo "<p><strong>Role:</strong> {$admin['role']}</p>";
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✓ Database Import Complete!</h2>";
    
    echo "<h3>🎉 Next Steps - Your Website is Ready!</h3>";
    echo "<ol style='font-size: 16px; line-height: 2;'>";
    echo "<li><a href='frontend/index.php' target='_blank' style='font-weight: bold; color: #1976d2;'>→ Open Homepage</a></li>";
    echo "<li><a href='frontend/shop.php' target='_blank' style='font-weight: bold; color: #1976d2;'>→ Open Shop Page</a></li>";
    echo "<li><a href='api/products' target='_blank' style='font-weight: bold; color: #1976d2;'>→ Test Products API</a></li>";
    echo "<li><a href='api/categories' target='_blank' style='font-weight: bold; color: #1976d2;'>→ Test Categories API</a></li>";
    echo "<li><a href='test-db.php' target='_blank' style='font-weight: bold; color: #1976d2;'>→ Full Database Test</a></li>";
    echo "</ol>";
    
    echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3>⚙️ Configure IdealPOS Integration:</h3>";
    echo "<p>Edit <code>.env</code> file and add your IdealPOS credentials:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>";
    echo "IDEALPOS_API_KEY=your-api-key-here\n";
    echo "IDEALPOS_STORE_ID=your-store-id-here";
    echo "</pre>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database Error:</p>";
    echo "<pre style='background: #ffe6e6; padding: 15px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}
?>
