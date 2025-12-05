<?php
/**
 * Admin Restore User API
 */
require_once '../../core/bootstrap.php'; // Ensures session is started securely
require_once 'csrf_middleware.php';   // Handles admin auth and CSRF validation

header('Content-Type: application/json');
try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['email']) || empty($data['first_name']) || empty($data['last_name'])) {
        throw new Exception('Email, first name, and last name are required');
    }
    
    $db = Database::getInstance();
    
    // Check if email already exists
    $existing = $db->fetchOne(
        "SELECT id FROM users WHERE email = :email",
        ['email' => $data['email']]
    );
    
    if ($existing) {
        throw new Exception('A user with this email already exists');
    }
    
    // Prepare user data
    $userData = [
        'email' => $data['email'],
        'password' => password_hash('Welcome123!', PASSWORD_DEFAULT), // Default password
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'phone' => $data['phone'] ?? null,
        'role' => $data['role'] ?? 'customer',
        'status' => !empty($data['status']) ? $data['status'] : 'active'
    ];
    
    // If original_id is provided, try to restore with same ID
    if (!empty($data['original_id'])) {
        $originalId = (int)$data['original_id'];
        
        // Check if ID is available
        $idExists = $db->fetchOne(
            "SELECT id FROM users WHERE id = :id",
            ['id' => $originalId]
        );
        
        if (!$idExists) {
            // Insert with specific ID
            $db->query(
                "INSERT INTO users (id, email, password, first_name, last_name, phone, role, status, created_at) 
                 VALUES (:id, :email, :password, :first_name, :last_name, :phone, :role, :status, NOW())",
                [
                    'id' => $originalId,
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'phone' => $userData['phone'],
                    'role' => $userData['role'],
                    'status' => $userData['status']
                ]
            );
            $userId = $originalId;
        } else {
            // ID taken, insert normally
            $userId = $db->insert('users', $userData);
        }
    } else {
        // Normal insert
        $userId = $db->insert('users', $userData);
    }
    
    // Log the action
    error_log("Admin " . $_SESSION['user_id'] . " restored user: " . $userData['email'] . " (ID: $userId)");
    
    echo json_encode([
        'success' => true,
        'message' => 'User account restored successfully',
        'user_id' => $userId,
        'note' => 'Password reset to default: Welcome123!'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
