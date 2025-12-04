<?php
/**
 * Admin Update User Status API
 */
require_once '../../api/bootstrap.php'; // Ensures session is started securely
require_once 'csrf_middleware.php';   // Handles admin auth and CSRF validation

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['user_id']) || empty($data['status'])) {
        throw new Exception('User ID and status are required');
    }
    
    $userId = (int)$data['user_id'];
    $status = $data['status'];
    
    // Validate status
    $validStatuses = ['active', 'inactive', 'suspended'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status value');
    }
    
    // Prevent admin from suspending themselves
    if ($userId === $_SESSION['user_id'] && $status === 'suspended') {
        throw new Exception('You cannot suspend your own account');
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
    
    // Update status
    $db->update(
        'users',
        ['status' => $status],
        'id = :id',
        ['id' => $userId]
    );
    
    // Log the action
    error_log("Admin " . $_SESSION['user_id'] . " changed status of user " . $userId . " to " . $status);
    
    echo json_encode([
        'success' => true,
        'message' => 'User status updated to ' . $status
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
