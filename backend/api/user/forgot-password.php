<?php
/**
 * Forgot Password API
 */
require_once __DIR__ . '/../../core/bootstrap.php';
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../services/EmailService.php';

// ... (rest of the file)
try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['email'])) {
        throw new Exception('Email is required.');
    }

    $db = Database::getInstance();
    $user = $db->fetchOne("SELECT id, email, first_name FROM users WHERE email = :email", ['email' => $data['email']]);

    if ($user) {
        // Generate a unique, secure token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // Token expires in 1 hour

        // Store the token in the database
        $db->insert('password_reset_tokens', [
            'user_id' => $user['id'],
            'token' => $token,
            'expires_at' => $expires
        ]);

        // Send the password reset email
        $emailService = new EmailService();
        $emailService->sendPasswordResetEmail($user, $token);
    }

    // Always return a success message to prevent user enumeration
    echo json_encode([
        'success' => true,
        'message' => 'If an account with that email exists, a password reset link has been sent.'
    ]);

} catch (Exception $e) {
    error_log("Forgot Password Error: " . $e->getMessage());
    // Generic error to avoid leaking information
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again later.'
    ]);
}
