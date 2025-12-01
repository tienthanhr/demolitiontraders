<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
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
    // Logged in, save to database
    $user_id = $_SESSION['user_id'];
    
    try {
        $db = Database::getInstance();
        
        // Check if already in wishlist
        $existing = $db->fetchOne(
            "SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?",
            [$user_id, $product_id]
        );
        
        if ($existing) {
            echo json_encode([
                'success' => false, 
                'message' => 'Product already in wishlist'
            ]);
            exit;
        }
        
        // Add to wishlist
        $db->query(
            "INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)",
            [$user_id, $product_id]
        );
        
        // Get updated count
        $count = $db->fetchOne(
            "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?",
            [$user_id]
        );
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to wishlist',
            'wishlist_count' => $count['count']
        ]);
        exit;
        
    } catch (Exception $e) {
        error_log('Wishlist add error: ' . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to add to wishlist'
        ]);
        exit;
    }
}

// Not logged in, save to session
if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

if (in_array($product_id, $_SESSION['wishlist'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product already in wishlist'
    ]);
    exit;
}

$_SESSION['wishlist'][] = (string)$product_id;
echo json_encode([
    'success' => true,
    'message' => 'Product added to wishlist',
    'wishlist_count' => count($_SESSION['wishlist'])
]);
