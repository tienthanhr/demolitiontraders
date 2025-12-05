<?php
/**
 * Get All Users API
 */
require_once '../../core/bootstrap.php'; // Ensures session is started securely
require_once 'csrf_middleware.php';   // Handles admin auth and CSRF validation

header('Content-Type: application/json');

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
