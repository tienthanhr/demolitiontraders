<?php
/**
 * Fly.io Health Check - Debug Endpoint
 */
header('Content-Type: application/json');

// Suppress error display, log instead
error_reporting(E_ALL);
ini_set('display_errors', '0');

$response = [
    'status' => 'ok',
    'environment' => [],
    'database' => null,
    'errors' => []
];

// Check environment
$response['environment']['DATABASE_URL'] = getenv('DATABASE_URL') ? '***SET***' : 'NOT SET';
$response['environment']['DB_HOST'] = getenv('DB_HOST') ?: 'not set';
$response['environment']['DB_NAME'] = getenv('DB_NAME') ?: 'not set';
$response['environment']['APP_ENV'] = getenv('APP_ENV') ?: 'not set';
$response['environment']['PHP_VERSION'] = phpversion();

// Try to connect to database
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $result = $conn->query('SELECT 1 as test')->fetch();
    $response['database'] = [
        'status' => 'connected',
        'test' => $result['test']
    ];
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['database'] = [
        'status' => 'failed',
        'error' => $e->getMessage()
    ];
    $response['errors'][] = $e->getMessage();
}

// Check file permissions
$response['files'] = [
    'logs_dir' => is_writable(__DIR__ . '/logs') ? 'writable' : 'not writable',
    'tmp_dir' => is_writable('/tmp') ? 'writable' : 'not writable'
];

http_response_code($response['status'] === 'error' ? 500 : 200);
echo json_encode($response, JSON_PRETTY_PRINT);
?>
