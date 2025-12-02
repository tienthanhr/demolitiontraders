<?php
// Test database connection and display detailed error info
header('Content-Type: application/json');

try {
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'demolitiontraders';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS') ?: '';
    $port = getenv('DB_PORT') ?: '3306';
    
    echo json_encode([
        'attempting_connection' => [
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'username' => $username,
            'password_set' => !empty($password)
        ]
    ], JSON_PRETTY_PRINT);
    
    echo "\n\n";
    
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful!',
        'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
