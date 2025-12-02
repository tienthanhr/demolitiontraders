<?php
/**
 * Import products via Render API endpoint
 * This script uploads and executes SQL on Render server
 */

require_once __DIR__ . '/../backend/config/database.php';

header('Content-Type: application/json');

// Check if running from command line or via web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    // If accessed via web, require admin authentication
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        die(json_encode(['error' => 'Admin access required']));
    }
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Read SQL file
    $sqlFile = __DIR__ . '/products-data.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    if ($isCLI) echo "Reading SQL file...\n";
    $sql = file_get_contents($sqlFile);
    
    if (!$sql) {
        throw new Exception("Failed to read SQL file");
    }
    
    // Split into individual statements (simple approach)
    // For large files, we'll execute in one go
    if ($isCLI) echo "Executing SQL statements...\n";
    
    $startTime = microtime(true);
    
    // Disable foreign key checks for faster import (PostgreSQL way)
    if ($db->isPostgreSQL()) {
        $conn->exec("SET session_replication_role = 'replica';");
    } else {
        $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
    }
    
    // Execute SQL
    $conn->exec($sql);
    
    // Re-enable foreign key checks
    if ($db->isPostgreSQL()) {
        $conn->exec("SET session_replication_role = 'origin';");
    } else {
        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }
    
    $duration = round(microtime(true) - $startTime, 2);
    
    // Verify import
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = " . ($db->isPostgreSQL() ? 'TRUE' : '1'));
    $productCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM product_images");
    $imageCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $result = [
        'success' => true,
        'duration' => $duration,
        'products_imported' => $productCount,
        'images_imported' => $imageCount,
        'message' => "Successfully imported $productCount products and $imageCount images in {$duration}s"
    ];
    
    if ($isCLI) {
        echo "\n✓ Import successful!\n";
        echo "  Products: $productCount\n";
        echo "  Images: $imageCount\n";
        echo "  Duration: {$duration}s\n";
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    $error = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    if ($isCLI) {
        echo "\n✗ Import failed: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode($error);
    }
}
