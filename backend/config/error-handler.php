<?php
/**
 * Production Error Handler
 * Disable error display and log to file instead
 */

// Disable error display in production
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// Enable error logging
ini_set('log_errors', '1');

// Prefer writing to a logs file under this config dir when possible,
// otherwise fall back to STDERR (container-friendly) to avoid PHP warnings
$logsDir = __DIR__ . '/logs';
$logFile = $logsDir . '/php-errors.log';

// Try to ensure the logs directory exists. Use error-suppression and
// handle failure explicitly to avoid permission warnings in read-only
// or restricted environments (e.g. some container platforms).
if (!is_dir($logsDir)) {
    // suppress warning, check result
    if (!@mkdir($logsDir, 0755, true) && !is_dir($logsDir)) {
        // cannot create logs dir — fall back to STDERR
        ini_set('error_log', 'php://stderr');
        error_log("Warning: could not create logs directory ($logsDir), logging to STDERR");
    } else {
        ini_set('error_log', $logFile);
    }
} else {
    // directory exists — if writable, use file; otherwise fall back to STDERR
    if (is_writable($logsDir)) {
        ini_set('error_log', $logFile);
    } else {
        ini_set('error_log', 'php://stderr');
        error_log("Warning: logs directory exists but is not writable ($logsDir), logging to STDERR");
    }
}

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
