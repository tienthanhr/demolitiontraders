<?php
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user - get from database
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare(
            "SELECT w.product_id, p.name, p.price, c.name as category,
                    pi.image_url as image
             FROM wishlist w
             JOIN products p ON w.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE w.user_id = ? AND p.is_active = 1
             ORDER BY w.created_at DESC"
        );
        $stmt->execute([$user_id]);
        $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'wishlist' => $wishlist,
            'wishlist_count' => count($wishlist)
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
    
    // Get product details for session wishlist
    $placeholders = implode(',', array_fill(0, count($sessionWishlist), '?'));
    $products = $db->fetchAll(
        "SELECT p.id as product_id, p.name, p.price, c.name as category,
                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.id IN ($placeholders) AND p.is_active = 1",
        $sessionWishlist
    );
    
    echo json_encode([
        'success' => true,
        'wishlist' => $products,
        'wishlist_count' => count($products)
    ]);
    
} catch (Exception $e) {
    error_log('Wishlist list error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading wishlist'
    ]);
}
