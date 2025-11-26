<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

try {
    // Get POST data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['product_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Product ID is required'
        ]);
        exit;
    }
    
    $product_id = $data['product_id'];
    
    // Initialize wishlist in session if not exists
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    // Check if product already in wishlist
    if (in_array($product_id, $_SESSION['wishlist'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Product already in wishlist'
        ]);
        exit;
    }
    
    // Add to wishlist (store as string for consistency)
    $_SESSION['wishlist'][] = (string)$product_id;
    
    echo json_encode([
        'success' => true,
        'message' => 'Product added to wishlist',
        'wishlist_count' => count($_SESSION['wishlist'])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
