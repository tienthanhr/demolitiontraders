<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== PHP Environment Test ===\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Current Directory: " . __DIR__ . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

echo "=== File Existence Check ===\n";
$files = [
    '../config/database.php',
    '../config/error-handler.php',
    'bootstrap.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    echo "$file: " . (file_exists($path) ? "EXISTS" : "NOT FOUND") . "\n";
}

echo "\n=== Environment Variables ===\n";
$envVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'];
foreach ($envVars as $var) {
    $value = getenv($var);
    echo "$var: " . ($value ? (strlen($value) . " characters") : "NOT SET") . "\n";
}

echo "\n=== Try loading database.php ===\n";
try {
    require_once __DIR__ . '/../config/database.php';
    echo "✓ database.php loaded successfully\n";
    
    echo "\n=== Try connecting to database ===\n";
    $db = Database::getInstance();
    echo "✓ Database instance created\n";
    
    $conn = $db->getConnection();
    echo "✓ Database connection established\n";
    
    // Try a simple query
    $stmt = $conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✓ Test query successful: " . json_encode($result) . "\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
