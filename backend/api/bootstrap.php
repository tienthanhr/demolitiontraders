<?php
/**
 * API Bootstrap
 * Include this at the top of all API files
 */

// Include error handler first
require_once __DIR__ . '/../config/error-handler.php';

// CORS headers with credentials support
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_path', '/tmp');
    ini_set('session.cookie_samesite', 'Lax');
require_once __DIR__ . '/../core/bootstrap.php';
}

// Include database
require_once __DIR__ . '/../config/database.php';
