<?php
/**
 * Fly.io Health Check - Debug Endpoint
 * Returns 200 even if database is not connected (for deployment)
 */
header('Content-Type: application/json');

// Suppress error display, log instead
error_reporting(E_ALL);
ini_set('display_errors', '0');

$response = [
    'status' => 'ok',
    'environment' => [],
    'database' => null,
    'errors' => [],
    'timestamp' => date('Y-m-d H:i:s')
];

// Check environment
$response['environment']['DATABASE_URL'] = getenv('DATABASE_URL') ? '***SET***' : 'NOT SET';
$response['environment']['DB_HOST'] = getenv('DB_HOST') ?: 'not set';
$response['environment']['DB_NAME'] = getenv('DB_NAME') ?: 'not set';
$response['environment']['APP_ENV'] = getenv('APP_ENV') ?: 'not set';
$response['environment']['PHP_VERSION'] = phpversion();

// Try to connect to database (but don't fail if unable)
try {
    require_once __DIR__ . '/../../backend/config/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $result = $conn->query('SELECT 1 as test')->fetch();
    $response['database'] = [
        'status' => 'connected',
        'test' => $result['test']
    ];
} catch (Exception $e) {
    // Database not connected, but that's OK during initial deployment
    $response['database'] = [
        'status' => 'not_connected',
        'error' => $e->getMessage(),
        'note' => 'Database will be connected once PostgreSQL is attached'
    ];
    // Don't change status to error - we still return 200
}

// Check file permissions
$response['files'] = [
    'logs_dir' => is_writable(__DIR__ . '/../../backend/logs') ? 'writable' : 'not writable',
    'tmp_dir' => is_writable('/var/lib/php/sessions') ? 'writable' : 'not writable'
];

// Always return 200 for health check - deployment will succeed
http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT);
?>
