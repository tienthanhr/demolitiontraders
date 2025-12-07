<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    $db = Database::getInstance();
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user - get from database
        $user_id = $_SESSION['user_id'];
        
        // Query that gets first product image
        $items = $db->fetchAll(
            "SELECT c.product_id, c.quantity, p.name, p.price, p.stock_quantity,
                    cat.name as category_name,
                    COALESCE(MIN(pi.image_url), 'assets/images/logo.png') as image
             FROM cart c
             JOIN products p ON c.product_id = p.id
             LEFT JOIN categories cat ON p.category_id = cat.id
             LEFT JOIN product_images pi ON p.id = pi.product_id
             WHERE c.user_id = ?
             GROUP BY c.product_id, c.quantity, p.name, p.price, p.stock_quantity, cat.name
             ORDER BY c.id DESC",
            [$user_id]
        );

        // If user cart empty but there are session cart rows, merge them into the user cart
        if (count($items) === 0) {
            $session_id = session_id();
            $guestItems = $db->fetchAll(
                "SELECT product_id, quantity FROM cart WHERE session_id = ?",
                [$session_id]
            );

            foreach ($guestItems as $gItem) {
                $pid = $gItem['product_id'];
                $gQty = (int)$gItem['quantity'];

                // Check stock
                $stock = $db->fetchOne(
                    "SELECT stock_quantity FROM products WHERE id = ?",
                    [$pid]
                );
                if (!$stock) {
                    continue;
                }

                // Check if already exists for user
                $existing = $db->fetchOne(
                    "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
                    [$user_id, $pid]
                );

                $allowedQty = min((int)$stock['stock_quantity'], $gQty + (int)($existing['quantity'] ?? 0));

                if ($existing) {
                    $db->query(
                        "UPDATE cart SET quantity = ? WHERE id = ?",
                        [$allowedQty, $existing['id']]
                    );
                } else {
                    $db->query(
                        "INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())",
                        [$user_id, $pid, $allowedQty]
                    );
                }
            }

            // Clean up session cart rows after merge
            if (!empty($guestItems)) {
                $db->query("DELETE FROM cart WHERE session_id = ?", [$session_id]);
            }

            // Re-fetch items for user after merge
            $items = $db->fetchAll(
                "SELECT c.product_id, c.quantity, p.name, p.price, p.stock_quantity,
                        cat.name as category_name,
                        COALESCE(MIN(pi.image_url), 'assets/images/logo.png') as image
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 LEFT JOIN categories cat ON p.category_id = cat.id
                 LEFT JOIN product_images pi ON p.id = pi.product_id
                 WHERE c.user_id = ?
                 GROUP BY c.product_id, c.quantity, p.name, p.price, p.stock_quantity, cat.name
                 ORDER BY c.id DESC",
                [$user_id]
            );
        }
        
        // Calculate summary
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += floatval($item['price']) * intval($item['quantity']);
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
    
    // Query that gets first product image
    $items = $db->fetchAll(
        "SELECT c.product_id, c.quantity, p.name, p.price, p.stock_quantity,
                cat.name as category_name,
                COALESCE(MIN(pi.image_url), 'assets/images/logo.png') as image
         FROM cart c
         JOIN products p ON c.product_id = p.id
         LEFT JOIN categories cat ON p.category_id = cat.id
         LEFT JOIN product_images pi ON p.id = pi.product_id
         WHERE c.session_id = ?
         GROUP BY c.product_id, c.quantity, p.name, p.price, p.stock_quantity, cat.name
         ORDER BY c.id DESC",
        [$session_id]
    );
    
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
