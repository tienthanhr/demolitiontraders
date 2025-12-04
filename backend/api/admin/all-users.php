<?php
/**
 * Get All Users API
 */
require_once 'csrf_middleware.php'; // Handles session start, admin auth, and CSRF token validation.
header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    $db = Database::getInstance();
    
    // Get all users
    $users = $db->fetchAll(
        "SELECT id, first_name, last_name, email, phone, role, status, created_at, last_login
         FROM users
         ORDER BY 
            CASE role 
                WHEN 'admin' THEN 1 
                WHEN 'customer' THEN 2 
            END,
            created_at DESC"
    );
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
