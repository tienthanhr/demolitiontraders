<?php
// User login API
// Configure session for Render
ini_set('session.save_path', '/tmp');
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        throw new Exception('Email and password are required.');
    }

    $db = Database::getInstance();
    
    // Try to find user (case-insensitive email, check status)
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE LOWER(email) = LOWER(:email) AND LOWER(status) = 'active'",
        ['email' => $email]
    );

    if (!$user) {
        throw new Exception('Invalid email or password.');
    }
    
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password.');
    }

    // Update last login
    $db->update(
        'users',
        ['last_login' => date('Y-m-d H:i:s')],
        'id = :id',
        ['id' => $user['id']]
    );

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['is_admin'] = ($user['role'] === 'admin');

    unset($user['password']);

    echo json_encode([
        'success' => true,
        'user' => $user,
        'message' => 'Login successful'
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
