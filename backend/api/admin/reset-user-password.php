<?php
/**
 * Admin Reset User Password API
 */
require_once 'csrf_middleware.php'; // Handles session start, admin auth, and CSRF token validation.
header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['new_password'])) {
        throw new Exception('User ID and new password are required');
    }
    
    $userId = (int)$data['user_id'];
    $newPassword = $data['new_password'];
    
    // Validate password strength
    if (strlen($newPassword) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }
    
    $db = Database::getInstance();
    
    // Check if user exists
    $user = $db->fetchOne(
        "SELECT id, first_name, last_name, email FROM users WHERE id = :id",
        ['id' => $userId]
    );
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $db->update(
        'users',
        ['password' => $hashedPassword],
        'id = :id',
        ['id' => $userId]
    );
    
    // Log the action
    error_log("Admin " . $_SESSION['user_id'] . " reset password for user " . $userId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully for ' . $user['first_name'] . ' ' . $user['last_name']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
