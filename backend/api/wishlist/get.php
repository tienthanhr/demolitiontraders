<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $db = Database::getInstance();
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user - get from database
        $user_id = $_SESSION['user_id'];
        
        $count = $db->fetchOne(
            "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?",
            [$user_id]
        );
        
        $wishlist = $db->fetchAll(
            "SELECT w.product_id, p.name, p.price, c.name as category,
                    (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = TRUE LIMIT 1) as image
             FROM wishlist w
             JOIN products p ON w.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             WHERE w.user_id = ? AND p.is_active = TRUE",
            [$user_id]
        );
        
        echo json_encode([
            'success' => true,
            'wishlist' => $wishlist,
            'wishlist_count' => $count['count']
        ]);
        exit;
    }
    
    // Guest user - get from session
    $sessionWishlist = isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];
    
    if (empty($sessionWishlist)) {
        echo json_encode([
            'success' => true,
            'wishlist' => [],
            'wishlist_count' => 0
        ]);
        exit;
    }
    
    // Get product details
    $placeholders = implode(',', array_fill(0, count($sessionWishlist), '?'));
    $products = $db->fetchAll(
        "SELECT p.id as product_id, p.name, p.price, c.name as category,
                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = TRUE LIMIT 1) as image
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.id IN ($placeholders) AND p.is_active = TRUE",
        $sessionWishlist
    );
    
    echo json_encode([
        'success' => true,
        'wishlist' => $products,
        'wishlist_count' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log('Wishlist get error: ' . $e->getMessage());
    error_log('Wishlist get trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading wishlist',
        'error_detail' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'wishlist' => [],
        'wishlist_count' => 0
    ]);
}
