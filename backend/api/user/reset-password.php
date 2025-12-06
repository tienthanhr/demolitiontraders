<?php
/**
 * Reset Password API
 */
require_once __DIR__ . '/../../core/bootstrap.php';
header('Content-Type: application/json');

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['token']) || empty($data['password'])) {
        throw new Exception('Token and new password are required.');
    }

    $db = Database::getInstance();
    
    // Find the token in the database
    $tokenData = $db->fetchOne("SELECT * FROM password_reset_tokens WHERE token = :token", ['token' => $data['token']]);

    if (!$tokenData || new DateTime() > new DateTime($tokenData['expires_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid or expired token.']);
        exit;
    }
    
    // Validate password strength
    if (strlen($data['password']) < 8) {
        throw new Exception('Password must be at least 8 characters.');
    }

    // Update the user's password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $db->update('users', ['password' => $hashedPassword], 'id = :id', ['id' => $tokenData['user_id']]);

    // Delete the used token
    $db->delete('password_reset_tokens', 'id = :id', ['id' => $tokenData['id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Password has been reset successfully. You can now login with your new password.'
    ]);

} catch (Exception $e) {
    error_log("Reset Password Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred.'
    ]);
}
