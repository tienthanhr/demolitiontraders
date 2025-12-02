<?php
/**
 * Get All Users API
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';

try {
    // Check if user is admin
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;
    
    if (!isset($_SESSION['user_id']) || !$isAdmin) {
        throw new Exception('Unauthorized access');
    }
    
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
