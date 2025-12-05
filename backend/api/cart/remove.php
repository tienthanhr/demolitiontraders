<?php
// Configure session for Render
require_once __DIR__ . '/../../core/bootstrap.php';

// CORS headers with credentials support
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$product_id = intval($data['product_id'] ?? 0);

if ($product_id < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user
        $user_id = $_SESSION['user_id'];
        
        $db->query(
            "DELETE FROM cart WHERE user_id = ? AND product_id = ?",
            [$user_id, $product_id]
        );
        
        // Get updated count
        $count = $db->fetchOne(
            "SELECT COUNT(*) as count FROM cart WHERE user_id = ?",
            [$user_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Product removed from cart',
            'cart_count' => $count['count']
        ]);
        exit;
    }
    
    // Guest user
    $session_id = session_id();
    
    $db->query(
        "DELETE FROM cart WHERE session_id = ? AND product_id = ?",
        [$session_id, $product_id]
    );
    
    $count = $db->fetchOne(
        "SELECT COUNT(*) as count FROM cart WHERE session_id = ?",
        [$session_id]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Product removed from cart',
        'cart_count' => $count['count']
    ]);
    
} catch (Exception $e) {
    error_log('Cart remove error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to remove product from cart'
    ]);
}
