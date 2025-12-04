<?php
/**
 * Update User Profile API
 */
require_once __DIR__ . '/../../core/bootstrap.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['first_name', 'last_name', 'email'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }
    
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    $db = Database::getInstance();
    
    // Check if email is already used by another user
    $existingUser = $db->fetchOne(
        "SELECT id FROM users WHERE email = :email AND id != :user_id",
        ['email' => $data['email'], 'user_id' => $userId]
    );
    
    if ($existingUser) {
        throw new Exception('Email already in use by another account');
    }
    
    // Update user
    $db->update(
        'users',
        [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null
        ],
        'id = :id',
        ['id' => $userId]
    );
    
    // Update session
    $_SESSION['user_email'] = $data['email'];
    $_SESSION['first_name'] = $data['first_name'];
    $_SESSION['last_name'] = $data['last_name'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
