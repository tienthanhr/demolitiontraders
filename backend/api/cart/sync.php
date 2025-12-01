<?php
/**
 * Cart Sync API
 * Syncs guest cart from localStorage to user cart in database after login
 */
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/database.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }
    
    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['cart']) || !is_array($data['cart'])) {
        echo json_encode(['success' => true, 'message' => 'No cart to sync']);
        exit;
    }
    
    $db = Database::getInstance();
    $synced = 0;
    
    // Get existing cart items for user
    $existingCart = $db->fetchAll(
        "SELECT product_id, quantity FROM cart WHERE user_id = :user_id",
        ['user_id' => $userId]
    );
    
    $existingProducts = [];
    foreach ($existingCart as $item) {
        $existingProducts[$item['product_id']] = $item['quantity'];
    }
    
    // Sync each cart item
    foreach ($data['cart'] as $productId => $item) {
        $productId = (int)$productId;
        $quantity = (int)($item['quantity'] ?? $item);
        
        if ($productId <= 0 || $quantity <= 0) {
            continue;
        }
        
        // Verify product exists
        $product = $db->fetchOne(
            "SELECT id FROM products WHERE id = :id AND is_active = 1",
            ['id' => $productId]
        );
        
        if (!$product) {
            continue;
        }
        
        if (isset($existingProducts[$productId])) {
            // Update quantity (add to existing)
            $newQuantity = $existingProducts[$productId] + $quantity;
            $db->update(
                'cart',
                ['quantity' => $newQuantity],
                'user_id = :user_id AND product_id = :product_id',
                ['user_id' => $userId, 'product_id' => $productId]
            );
        } else {
            // Insert new cart item
            $db->insert('cart', [
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }
        
        $synced++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Synced {$synced} items to cart",
        'synced' => $synced
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
