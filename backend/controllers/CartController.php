<?php
/**
 * Cart Controller
 * Manages shopping cart operations
 */

class CartController {
    private $db;
    private $sessionId;
    
    public function __construct() {
        $this->db = Database::getInstance();
        // Session already started in API index.php
        
        if (!isset($_SESSION['cart_id'])) {
            $_SESSION['cart_id'] = uniqid('cart_', true);
        }
        
        $this->sessionId = $_SESSION['cart_id'];
    }
    
    /**
     * Get cart contents
     */
    public function get() {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            $items = $this->db->fetchAll(
                "SELECT c.*, p.name, p.price, p.stock_quantity, p.slug,
                 (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 WHERE c.user_id = :user_id",
                ['user_id' => $userId]
            );
        } else {
            $items = $this->db->fetchAll(
                "SELECT c.*, p.name, p.price, p.stock_quantity, p.slug,
                 (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
                 FROM cart c
                 JOIN products p ON c.product_id = p.id
                 WHERE c.session_id = :session_id",
                ['session_id' => $this->sessionId]
            );
        }
        
        $subtotal = 0;
        foreach ($items as &$item) {
            $item['total'] = $item['price'] * $item['quantity'];
            $subtotal += $item['total'];
        }
        
        // Prices already include GST, no additional tax calculation needed
        $total = $subtotal;
        
        return [
            'items' => $items,
            'summary' => [
                'subtotal' => number_format($subtotal, 2, '.', ''),
                'total' => number_format($total, 2, '.', ''),
                'item_count' => count($items)
            ]
        ];
    }
    
    /**
     * Add item to cart
     */
    public function add($data) {
        if (empty($data['product_id'])) {
            throw new Exception('Product ID is required');
        }
        
        $productId = $data['product_id'];
        $quantity = max(1, intval($data['quantity'] ?? 1));
        $userId = $_SESSION['user_id'] ?? null;
        
        // Check product exists and has stock
        $product = $this->db->fetchOne(
            "SELECT * FROM products WHERE id = :id AND is_active = 1",
            ['id' => $productId]
        );
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock');
        }
        
        // Check if item already in cart
        if ($userId) {
            $existing = $this->db->fetchOne(
                "SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id",
                ['user_id' => $userId, 'product_id' => $productId]
            );
        } else {
            $existing = $this->db->fetchOne(
                "SELECT * FROM cart WHERE session_id = :session_id AND product_id = :product_id",
                ['session_id' => $this->sessionId, 'product_id' => $productId]
            );
        }
        
        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            
            if ($product['stock_quantity'] < $newQuantity) {
                throw new Exception('Insufficient stock');
            }
            
            $this->db->update(
                'cart',
                ['quantity' => $newQuantity],
                'id = :id',
                ['id' => $existing['id']]
            );
        } else {
            // Add new item
            $this->db->insert('cart', [
                'user_id' => $userId,
                'session_id' => $userId ? null : $this->sessionId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }
        
        return $this->get();
    }
    
    /**
     * Update cart item quantity
     */
    public function update($data) {
        if (empty($data['product_id'])) {
            throw new Exception('Product ID is required');
        }
        
        $productId = $data['product_id'];
        $quantity = max(0, intval($data['quantity'] ?? 1));
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($quantity === 0) {
            return $this->remove($productId);
        }
        
        // Check stock
        $product = $this->db->fetchOne(
            "SELECT stock_quantity FROM products WHERE id = :id",
            ['id' => $productId]
        );
        
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock');
        }
        
        // Update cart
        if ($userId) {
            $this->db->update(
                'cart',
                ['quantity' => $quantity],
                'user_id = :user_id AND product_id = :product_id',
                ['user_id' => $userId, 'product_id' => $productId]
            );
        } else {
            $this->db->update(
                'cart',
                ['quantity' => $quantity],
                'session_id = :session_id AND product_id = :product_id',
                ['session_id' => $this->sessionId, 'product_id' => $productId]
            );
        }
        
        return $this->get();
    }
    
    /**
     * Remove item from cart
     */
    public function remove($productId) {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            $this->db->delete(
                'cart',
                'user_id = :user_id AND product_id = :product_id',
                ['user_id' => $userId, 'product_id' => $productId]
            );
        } else {
            $this->db->delete(
                'cart',
                'session_id = :session_id AND product_id = :product_id',
                ['session_id' => $this->sessionId, 'product_id' => $productId]
            );
        }
        
        return $this->get();
    }
    
    /**
     * Clear entire cart
     */
    public function clear() {
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($userId) {
            $this->db->delete('cart', 'user_id = :user_id', ['user_id' => $userId]);
        } else {
            $this->db->delete('cart', 'session_id = :session_id', ['session_id' => $this->sessionId]);
        }
        
        return ['message' => 'Cart cleared successfully'];
    }
}
