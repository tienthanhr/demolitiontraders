<?php
/**
 * Change Password API
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
    
    if (empty($data['current_password']) || empty($data['new_password'])) {
        throw new Exception('Current password and new password are required');
    }
    
    // Validate new password strength
    if (strlen($data['new_password']) < 8) {
        throw new Exception('New password must be at least 8 characters');
    }
    
    $db = Database::getInstance();
    
    // Get current password hash
    $user = $db->fetchOne(
        "SELECT password FROM users WHERE id = :id",
        ['id' => $userId]
    );
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Verify current password
    if (!password_verify($data['current_password'], $user['password'])) {
        throw new Exception('Current password is incorrect');
    }
    
    // Update password
    $hashedPassword = password_hash($data['new_password'], PASSWORD_DEFAULT);
    
    $db->update(
        'users',
        ['password' => $hashedPassword],
        'id = :id',
        ['id' => $userId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
