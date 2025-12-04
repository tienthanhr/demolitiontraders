<?php
/**
 * Get User Addresses API
 */
require_once __DIR__ . '/../../core/bootstrap.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }
    
    $userId = $_SESSION['user_id'];
    $db = Database::getInstance();
    
    // Get user addresses
    $addresses = $db->fetchAll(
        "SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC",
        ['user_id' => $userId]
    );
    
    echo json_encode([
        'success' => true,
        'addresses' => $addresses
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
