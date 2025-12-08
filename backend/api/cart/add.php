<?php
// Configure session for Render environment
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
$quantity = intval($data['quantity'] ?? 1);

if ($product_id < 1 || $quantity < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product or quantity'
    ]);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Check if product exists and is active
    $product = $db->fetchOne(
        "SELECT id, name, price, stock_quantity FROM products WHERE id = ? AND is_active = 1",
        [$product_id]
    );
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found or unavailable'
        ]);
        exit;
    }
    
    // Check stock
    if ($product['stock_quantity'] < $quantity) {
        echo json_encode([
            'success' => false,
            'message' => 'Insufficient stock available'
        ]);
        exit;
    }
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user
        $user_id = $_SESSION['user_id'];
        
        // Check if product already in cart
        $existing = $db->fetchOne(
            "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?",
            [$user_id, $product_id]
        );
        
        if ($existing) {
            // Update quantity
            $newQty = $existing['quantity'] + $quantity;
            if ($newQty > $product['stock_quantity']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Cannot add more - stock limit reached'
                ]);
                exit;
            }
            
            $db->query(
                "UPDATE cart SET quantity = ? WHERE id = ?",
                [$newQty, $existing['id']]
            );
        } else {
            // Insert new
            $db->query(
                "INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, ?)",
                [$user_id, $product_id, $quantity, date('Y-m-d H:i:s')]
            );
        }
        
        // Get updated cart
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
        
        // Calculate summary
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += floatval($item['price']) * intval($item['quantity']);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart',
            'items' => $items,
            'summary' => [
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'total' => number_format($subtotal, 2, '.', ''),
                'item_count' => count($items)
            ]
        ]);
        exit;
    }
    
    // Guest user - use session_id
    $session_id = session_id();
    
    error_log("Guest cart add - Session ID: $session_id, Product: $product_id, Qty: $quantity");
    
    $existing = $db->fetchOne(
        "SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?",
        [$session_id, $product_id]
    );
    
    if ($existing) {
        error_log("Guest cart - Updating existing item ID: {$existing['id']}");
        $newQty = $existing['quantity'] + $quantity;
        if ($newQty > $product['stock_quantity']) {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot add more - stock limit reached'
            ]);
            exit;
        }
        
        $db->query(
            "UPDATE cart SET quantity = ? WHERE id = ?",
            [$newQty, $existing['id']]
        );
    } else {
        error_log("Guest cart - Inserting new item");
        $db->query(
            "INSERT INTO cart (session_id, product_id, quantity, created_at) VALUES (?, ?, ?, ?)",
            [$session_id, $product_id, $quantity, date('Y-m-d H:i:s')]
        );
        error_log("Guest cart - Insert complete");
    }
    
    // Get updated cart
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
    
    // Calculate summary
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += floatval($item['price']) * intval($item['quantity']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Product added to cart',
        'items' => $items,
        'summary' => [
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'total' => number_format($subtotal, 2, '.', ''),
            'item_count' => count($items)
        ],
        'session_id' => $session_id
    ]);
    
} catch (Exception $e) {
    error_log('Cart add error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to add product to cart'
    ]);
}
