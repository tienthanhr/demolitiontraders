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
    
    // Check stock availability
    $product = $db->fetchOne(
        "SELECT stock_quantity FROM products WHERE id = ? AND is_active = 1",
        [$product_id]
    );
    
    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    if ($quantity > $product['stock_quantity']) {
        echo json_encode([
            'success' => false,
            'message' => 'Requested quantity exceeds available stock'
        ]);
        exit;
    }
    
    if (isset($_SESSION['user_id'])) {
        // Logged in user
        $user_id = $_SESSION['user_id'];
        
        // Get current quantity in cart
        $currentItem = $db->fetchOne(
            "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?",
            [$user_id, $product_id]
        );
        
        if (!$currentItem) {
            echo json_encode([
                'success' => false,
                'message' => 'Item not found in cart'
            ]);
            exit;
        }
        
        // Calculate new quantity change
        $quantityDifference = $quantity - intval($currentItem['quantity']);
        
        // Get total quantity of this product in cart (in case of multiple cart entries - shouldn't happen but safety check)
        $totalInCart = $db->fetchOne(
            "SELECT SUM(quantity) as total FROM cart WHERE product_id = ? AND user_id = ?",
            [$product_id, $user_id]
        );
        
        $newTotal = intval($totalInCart['total']) + $quantityDifference;
        
        // Check if new total would exceed stock
        if ($newTotal > $product['stock_quantity']) {
            echo json_encode([
                'success' => false,
                'message' => 'Requested quantity exceeds available stock',
                'available' => $product['stock_quantity'] - (intval($totalInCart['total']) - intval($currentItem['quantity']))
            ]);
            exit;
        }
        
        $db->query(
            "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?",
            [$quantity, $user_id, $product_id]
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Cart updated'
        ]);
        exit;
    }
    
    // Guest user
    $session_id = session_id();
    
    // Get current quantity in cart
    $currentItem = $db->fetchOne(
        "SELECT quantity FROM cart WHERE session_id = ? AND product_id = ?",
        [$session_id, $product_id]
    );
    
    if (!$currentItem) {
        echo json_encode([
            'success' => false,
            'message' => 'Item not found in cart'
        ]);
        exit;
    }
    
    // Calculate new quantity change
    $quantityDifference = $quantity - intval($currentItem['quantity']);
    
    // Get total quantity of this product in cart
    $totalInCart = $db->fetchOne(
        "SELECT SUM(quantity) as total FROM cart WHERE product_id = ? AND session_id = ?",
        [$product_id, $session_id]
    );
    
    $newTotal = intval($totalInCart['total']) + $quantityDifference;
    
    // Check if new total would exceed stock
    if ($newTotal > $product['stock_quantity']) {
        echo json_encode([
            'success' => false,
            'message' => 'Requested quantity exceeds available stock',
            'available' => $product['stock_quantity'] - (intval($totalInCart['total']) - intval($currentItem['quantity']))
        ]);
        exit;
    }
    
    $db->query(
        "UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?",
        [$quantity, $session_id, $product_id]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Cart updated'
    ]);
    
} catch (Exception $e) {
    error_log('Cart update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update cart'
    ]);
}
