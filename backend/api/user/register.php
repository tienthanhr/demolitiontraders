<?php
// User registration API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $phone = trim($data['phone'] ?? '');

    if (!$first_name || !$last_name || !$email || !$password) {
        throw new Exception('First name, last name, email and password are required.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address.');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters.');
    }

    $db = Database::getInstance();
    
    // Check if user exists
    $existing = $db->fetchOne(
        "SELECT id FROM users WHERE email = :email",
        ['email' => $email]
    );
    
    if ($existing) {
        throw new Exception('Email already registered.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    error_log("Attempting to register user: $email");
    
    $userId = $db->insert('users', [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'password' => $hash,
        'phone' => $phone ?: null,
        'role' => 'customer',
        'status' => 'active'
    ]);
    
    error_log("User registered successfully: ID = $userId, Email = $email");
    
    // Link pending guest order to new user account
    $order_id = $data['order_id'] ?? null;
    if ($order_id && is_numeric($order_id)) {
        error_log("Linking order #$order_id to new user #$userId");
        try {
            $db->update(
                'orders',
                [
                    'user_id' => $userId,
                    'guest_email' => null
                ],
                'id = :order_id AND user_id IS NULL',
                ['order_id' => $order_id]
            );
            error_log("Order #$order_id linked successfully to user #$userId");
        } catch (Exception $e) {
            error_log("Failed to link order: " . $e->getMessage());
            // Don't fail registration if order linking fails
        }
    }
    
    // Auto login after registration
    ini_set('session.save_path', '/tmp');
    session_start();
    header('Access-Control-Allow-Credentials: true');
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = 'customer';
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!',
        'user' => [
            'id' => $userId,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
