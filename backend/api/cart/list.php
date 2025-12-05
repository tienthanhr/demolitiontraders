<?php
require_once __DIR__ . '/../../core/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/database.php';

try {
    $db = Database::getInstance();
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user
        $user_id = $_SESSION['user_id'];
        
        $items = $db->fetchAll(
            "SELECT c.product_id, c.quantity, p.name, p.price, p.stock_quantity,
                    cat.name as category_name,
                    pi.image_url as image
             FROM cart c
             JOIN products p ON c.product_id = p.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
             WHERE c.user_id = ? AND p.is_active = 1
             ORDER BY c.created_at DESC",
            [$user_id]
        );
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'cart_count' => count($items)
        ]);
        exit;
    }
    
    // Guest user
    $session_id = session_id();
    
    $items = $db->fetchAll(
        "SELECT c.product_id, c.quantity, p.name, p.price, p.stock_quantity,
                cat.name as category_name,
                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
         FROM cart c
         JOIN products p ON c.product_id = p.id
         LEFT JOIN categories cat ON p.category_id = cat.id
         WHERE c.session_id = ? AND p.is_active = 1
         ORDER BY c.created_at DESC",
        [$session_id]
    );
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'cart_count' => count($items)
    ]);
    
} catch (Exception $e) {
    error_log('Cart list error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading cart',
        'items' => [],
        'cart_count' => 0
    ]);
}
