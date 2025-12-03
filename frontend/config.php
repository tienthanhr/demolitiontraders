<?php
/**
 * Frontend Configuration
 * Auto-detect base path for localhost vs production
 */

// Detect if running on localhost
$isLocalhost = (
    $_SERVER['SERVER_NAME'] === 'localhost' || 
    $_SERVER['SERVER_NAME'] === '127.0.0.1' ||
    strpos($_SERVER['SERVER_NAME'], 'localhost') !== false
);

// Set base path
if ($isLocalhost) {
    define('BASE_PATH', '/demolitiontraders/');
    define('FRONTEND_PATH', '/demolitiontraders/frontend/');
    define('API_BASE', '/demolitiontraders/backend/api/');
} else {
    // Production (Render, etc.)
    define('BASE_PATH', '/');
    define('FRONTEND_PATH', '/');  // Changed from /frontend/ to / for <base> tag
    define('API_BASE', '/backend/api/');
}

// Helper function to get asset path
function asset($path) {
    return FRONTEND_PATH . ltrim($path, '/');
}

// Helper function to get user page URL
function userUrl($page) {
    // With .htaccess rewrite, user pages are at root level
    return BASE_PATH . ltrim($page, '/');
}

// Helper function to get full URL
function url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || $_SERVER['SERVER_PORT'] == 443 
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        ? 'https://' : 'http://';
    
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host . BASE_PATH . ltrim($path, '/');
}
