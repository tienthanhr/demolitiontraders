<?php
/**
 * Reset Password API
 * Resets user password using token
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['token']) || empty($data['password'])) {
        throw new Exception('Token and password are required');
    }
    
    $token = trim($data['token']);
    $password = $data['password'];
    
    // Validate password strength
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }
    
    $db = Database::getInstance();
    
    // Verify token
    $resetToken = $db->fetchOne(
        "SELECT * FROM password_reset_tokens 
         WHERE token = :token 
         AND used = 0 
         AND expires_at > NOW()",
        ['token' => $token]
    );
    
    if (!$resetToken) {
        throw new Exception('Invalid or expired reset token');
    }
    
    // Update user password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $db->update(
        'users',
        ['password' => $hashedPassword],
        'id = :id',
        ['id' => $resetToken['user_id']]
    );
    
    // Mark token as used
    $db->update(
        'password_reset_tokens',
        ['used' => 1],
        'id = :id',
        ['id' => $resetToken['id']]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Password has been reset successfully. You can now login with your new password.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
