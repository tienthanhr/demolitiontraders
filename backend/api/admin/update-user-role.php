<?php
/**
 * Admin Update User Role API
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Check if user is admin
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;
    
    if (!isset($_SESSION['user_id']) || !$isAdmin) {
        throw new Exception('Unauthorized access');
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['role'])) {
        throw new Exception('User ID and role are required');
    }
    
    $userId = (int)$data['user_id'];
    $role = $data['role'];
    
    // Validate role - only customer and admin allowed
    $validRoles = ['customer', 'admin'];
    if (!in_array($role, $validRoles)) {
        throw new Exception('Invalid role. Only customer and admin roles are allowed.');
    }
    
    // Prevent admin from changing their own role
    if ($userId === $_SESSION['user_id']) {
        throw new Exception('You cannot change your own role');
    }
    
    $db = Database::getInstance();
    
    // Check if user exists
    $user = $db->fetchOne(
        "SELECT id, first_name, last_name, email, role FROM users WHERE id = :id",
        ['id' => $userId]
    );
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Update role
    $db->update(
        'users',
        ['role' => $role],
        'id = :id',
        ['id' => $userId]
    );
    
    // Log the action
    error_log("Admin " . $_SESSION['user_id'] . " changed role of user " . $userId . " from " . $user['role'] . " to " . $role);
    
    echo json_encode([
        'success' => true,
        'message' => 'User role updated to ' . $role
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
