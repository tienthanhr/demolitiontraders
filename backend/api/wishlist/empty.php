<?php
// Configure session for Render
ini_set('session.save_path', '/tmp');
session_start();

// CORS headers with credentials support
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    $db = Database::getInstance();
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user - empty database wishlist
        $user_id = $_SESSION['user_id'];
        
        $result = $db->query(
            "DELETE FROM wishlist WHERE user_id = ?",
            [$user_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Wishlist cleared successfully',
            'wishlist_count' => 0
        ]);
        exit;
    }
    
    // Guest user - empty session wishlist
    $_SESSION['wishlist'] = [];
    
    echo json_encode([
        'success' => true,
        'message' => 'Wishlist cleared successfully',
        'wishlist_count' => 0
    ]);
    
} catch (Exception $e) {
    error_log('Wishlist empty error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to clear wishlist'
    ]);
}
