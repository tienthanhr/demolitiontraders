<?php
/**
 * Admin Update User Role API
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/bootstrap.php'; // Ensures session is started securely
require_once __DIR__ . '/csrf_middleware.php';   // Handles admin auth and CSRF validation

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Invalid JSON payload');
    }
    
    if (empty($data['user_id']) || empty($data['role'])) {
        throw new Exception('User ID and role are required');
    }
    
    $userId = (int)$data['user_id'];
    $role = $data['role'];
    
    // Validate role - only customer and admin allowed
    $validRoles = ['customer', 'admin'];
    if (!in_array($role, $validRoles, true)) {
        throw new Exception('Invalid role. Only customer and admin roles are allowed.');
    }
    
    // Prevent admin from changing their own role
    if ($userId === ($_SESSION['user_id'] ?? null)) {
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
    error_log("Admin " . ($_SESSION['user_id'] ?? 'unknown') . " changed role of user " . $userId . " from " . ($user['role'] ?? 'unknown') . " to " . $role);
    
    echo json_encode([
        'success' => true,
        'message' => 'User role updated to ' . $role
    ]);
    
} catch (Throwable $e) {
    $status = ($e instanceof Exception) ? 400 : 500;
    http_response_code($status);
    error_log('update-user-role error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
