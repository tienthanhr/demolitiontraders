<?php
/**
 * Check if Email Exists API
 */
require_once __DIR__ . '/../../core/bootstrap.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email'])) {
        throw new Exception('Email is required');
    }
    
    $email = trim($data['email']);
    $db = Database::getInstance();
    
    // Log the check
    error_log("Checking email: " . $email);
    
    // Check if email exists - use positional parameter
    $user = $db->fetchOne(
        "SELECT id FROM users WHERE email = ?",
        [$email]
    );
    
    // PDO fetch() returns false when no row found, not null
    $exists = !empty($user);
    
    error_log("Email exists result: " . ($exists ? 'true' : 'false'));
    error_log("Query result: " . json_encode($user));
    
    echo json_encode([
        'success' => true,
        'exists' => $exists,
        'email' => $email,
        'debug' => [
            'checked_email' => $email,
            'found_user' => $exists,
            'user_data' => $user
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
