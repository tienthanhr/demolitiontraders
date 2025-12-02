<?php
/**
 * Admin Delete User API
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
    
    if (empty($data['user_id'])) {
        throw new Exception('User ID is required');
    }
    
    $userId = (int)$data['user_id'];
    
    // Prevent admin from deleting themselves
    if ($userId === $_SESSION['user_id']) {
        throw new Exception('You cannot delete your own account');
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
    
    // Prevent deleting other admins (optional security)
    if ($user['role'] === 'admin') {
        throw new Exception('Cannot delete admin accounts. Please change role first.');
    }
    
    // Delete user (CASCADE will delete related data: addresses, cart, wishlist, orders)
    $db->query(
        "DELETE FROM users WHERE id = :id",
        ['id' => $userId]
    );
    
    // Log the action
    error_log("Admin " . $_SESSION['user_id'] . " deleted user " . $userId . " (" . $user['email'] . ")");
    
    echo json_encode([
        'success' => true,
        'message' => 'User account deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
