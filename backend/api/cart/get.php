<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $db = Database::getInstance();
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user - get from database
        $user_id = $_SESSION['user_id'];
        
        $stmt = $db->prepare(
            "SELECT c.product_id, c.quantity, p.name, p.price, p.stock_quantity,
                    cat.name as category_name,
                    pi.image_url as image
             FROM cart c
             JOIN products p ON c.product_id = p.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
             WHERE c.user_id = ? AND p.is_active = TRUE
             ORDER BY c.created_at DESC"
        );
        $stmt->execute([$user_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate summary
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'summary' => [
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'total' => number_format($subtotal, 2, '.', ''),
                'item_count' => count($items)
            ]
        ]);
        exit;
    }
    
    // Guest user - get from session (if using session-based cart)
    // OR get from database using session_id
    $session_id = session_id();
    
    error_log("Guest cart get - Session ID: $session_id");
    
    $stmt = $db->prepare(
        "SELECT c.product_id, c.quantity, p.name, p.price, p.stock_quantity,
                cat.name as category_name,
                pi.image_url as image
         FROM cart c
         JOIN products p ON c.product_id = p.id
         LEFT JOIN categories cat ON p.category_id = cat.id
         LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
         WHERE (c.session_id = ? OR (c.user_id IS NULL AND c.session_id IS NULL)) AND p.is_active = TRUE
         ORDER BY c.created_at DESC"
    );
    $stmt->execute([$session_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Guest cart get - Items found: " . count($items));
    
    // Calculate summary
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'summary' => [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'total' => number_format($subtotal, 2, '.', ''),
            'item_count' => count($items)
        ],
        'debug' => [
            'session_id' => $session_id,
            'query_count' => count($items)
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Cart get error: ' . $e->getMessage());
    error_log('Cart get trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading cart',
        'error_detail' => $e->getMessage(),
        'error_file' => $e->getFile(),
        'error_line' => $e->getLine(),
        'items' => [],
        'summary' => [
            'subtotal' => '0.00',
            'total' => '0.00',
            'item_count' => 0
        ]
    ]);
}
