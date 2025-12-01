<?php
// User login API
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        throw new Exception('Email and password are required.');
    }

    $db = Database::getInstance();
    
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE email = :email AND status = 'active'",
        ['email' => $email]
    );

    if (!$user || !password_verify($password, $user['password'])) {
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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
