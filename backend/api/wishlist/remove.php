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

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID is required'
    ]);
    exit;
}
$product_id = intval($data['product_id']);

if (isset($_SESSION['user_id'])) {
    // Logged in user - remove from database
    $user_id = $_SESSION['user_id'];
    
    try {
        $db = Database::getInstance();
        
        $result = $db->query(
            "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?",
            [$user_id, $product_id]
        );
        
        // Get updated count
        $count = $db->fetchOne(
            "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?",
            [$user_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Product removed from wishlist',
            'wishlist_count' => $count['count']
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log('Wishlist remove error: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove from wishlist'
        ]);
        exit;
    }
}

// Guest user - remove from session
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

$key = array_search((string)$product_id, $_SESSION['wishlist']);
if ($key !== false) {
    unset($_SESSION['wishlist'][$key]);
    $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Re-index array
    echo json_encode([
        'success' => true,
        'message' => 'Product removed from wishlist',
        'wishlist_count' => count($_SESSION['wishlist'])
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found in wishlist'
    ]);
}
