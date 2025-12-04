<?php
/**
 * Get Current User API
 */
// Configure session for Render
require_once __DIR__ . '/../../core/bootstrap.php';

// CORS headers with credentials support
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../../config/database.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Not authenticated',
            'session_id' => session_id(),
            'debug' => $_SESSION
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    
    $user = $db->fetchOne(
        "SELECT id, first_name, last_name, email, phone, role, status, created_at, last_login 
         FROM users 
         WHERE id = :id",
        ['id' => $_SESSION['user_id']]
    );
    
    if (!$user) {
        // User not found, clear session
        session_destroy();
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log("me.php error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage() // Temporary debug
    ]);
}
