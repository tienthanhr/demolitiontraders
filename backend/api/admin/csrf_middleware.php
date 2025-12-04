<?php
/**
 * CSRF & Admin Auth Middleware
 *
 * This script provides protection for admin-only API endpoints.
 * It performs two main checks:
 * 1. Verifies that the user is logged in and has an 'admin' role.
 * 2. For state-changing requests (POST, PUT, DELETE), validates a CSRF token.
 */

// Session is expected to be started by a central bootstrap file before this script is included.
if (session_status() === PHP_SESSION_NONE) {
    http_response_code(500); // Internal Server Error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session not initialized.']);
    exit;
}

// --- 1. Admin Authentication Check ---
$isAdmin = ($_SESSION['is_admin'] ?? false) === true || ($_SESSION['role'] ?? '') === 'admin';

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    http_response_code(401); // Unauthorized
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication error: Admin access required.']);
    exit;
}

// --- 2. CSRF Token Validation for state-changing methods ---
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$stateChangingMethods = ['POST', 'PUT', 'DELETE'];

if (in_array($requestMethod, $stateChangingMethods)) {
    // Get the token sent by the client from the 'X-CSRF-Token' header.
    $clientToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($clientToken)) {
        http_response_code(403); // Forbidden
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'CSRF token is missing from request header.']);
        exit;
    }

    // Get the expected token from the user's session.
    $serverToken = $_SESSION['csrf_token'] ?? '';

    if (empty($serverToken) || !hash_equals($serverToken, $clientToken)) {
        http_response_code(403); // Forbidden
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh and try again.']);
        exit;
    }
}

// If all checks pass, the script that included this middleware will continue its execution.
