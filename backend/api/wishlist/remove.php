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
    
    // Remove from wishlist
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
            'message' => 'Product not in wishlist'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
