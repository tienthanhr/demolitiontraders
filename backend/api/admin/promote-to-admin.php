<?php
/**
 * Admin Promote User to Admin API
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Check if user is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email'])) {
        throw new Exception('Email is required');
    }
    
    $email = trim($data['email']);
    
    $db = Database::getInstance();
    
    // Find user by email
    $user = $db->fetchOne(
        "SELECT id, first_name, last_name, email, role, status FROM users WHERE email = :email",
        ['email' => $email]
    );
    
    if (!$user) {
        throw new Exception('User not found with this email');
    }
    
    // Check if already admin
    if ($user['role'] === 'admin') {
        throw new Exception('This user is already an admin');
    }
    
    // Check if active
    if ($user['status'] !== 'active') {
        throw new Exception('Cannot promote inactive or suspended users');
    }
    
    // Promote to admin
    $db->update(
        'users',
        ['role' => 'admin'],
        'id = :id',
        ['id' => $user['id']]
    );
    
    // Log the action
    error_log("Admin " . $_SESSION['user_id'] . " promoted user " . $user['id'] . " (" . $email . ") to admin");
    
    echo json_encode([
        'success' => true,
        'message' => $user['first_name'] . ' ' . $user['last_name'] . ' has been promoted to Admin'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
