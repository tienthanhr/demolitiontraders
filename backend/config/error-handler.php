<?php
/**
 * Production Error Handler
 * Disable error display and log to file instead
 */

// Disable error display in production
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Enable error logging (prefer writable dir)
ini_set('log_errors', '1');
$logsDir = getenv('LOG_PATH') ?: (__DIR__ . '/logs');
if (!is_dir($logsDir)) {
    // Fallback to /tmp if not writable
    if (!@mkdir($logsDir, 0755, true)) {
        $logsDir = '/tmp/dt-logs';
        @mkdir($logsDir, 0755, true);
    }
}
ini_set('error_log', $logsDir . '/php-errors.log');

// Set timezone
date_default_timezone_set('Pacific/Auckland');

// Custom error handler for uncaught exceptions
set_exception_handler(function($exception) {
    error_log('Uncaught Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    
    // Return JSON error for API requests
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Internal server error']);
        exit;
    }
});

// Custom error handler for PHP errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile:$errline");
    return false; // Let PHP handle it
});
