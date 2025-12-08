<?php
// backend/api/test_db_connection.php

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain');

echo "Testing Database Connection...\n";
echo "----------------------------\n";

$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$port = getenv('DB_PORT');

echo "Host: " . ($host ?: 'Not Set') . "\n";
echo "Port: " . ($port ?: 'Not Set') . "\n";
echo "Database: " . ($dbname ?: 'Not Set') . "\n";
echo "User: " . ($user ?: 'Not Set') . "\n";
echo "Pass: " . ($pass ? '******' : 'Not Set') . "\n";

echo "----------------------------\n";

if (!$host) {
    die("ERROR: DB_HOST is not set in environment variables.\n");
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    echo "Attempting connection to: $dsn\n";
    
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "CONNECTION SUCCESSFUL!\n";
    
    // Check for tables
    echo "----------------------------\n";
    echo "Checking for tables...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "WARNING: No tables found in database '$dbname'.\n";
        echo "You may need to import your SQL dump.\n";
    } else {
        echo "Found " . count($tables) . " tables:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    }
    
} catch (PDOException $e) {
    echo "CONNECTION FAILED: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
