<?php
/**
 * Get Current User API
 */
// Configure session for Render
ini_set('session.save_path', '/tmp');
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}
