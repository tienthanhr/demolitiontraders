<?php
/**
 * Wishlist Controller
 */

class WishlistController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        // Session already started in API index.php
    }
    
    /**
     * Get user wishlist
     */
    public function index() {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('Authentication required');
        }
        
        $items = $this->db->fetchAll(
            "SELECT w.*, p.name, p.slug, p.price, p.stock_quantity,
             (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
             FROM wishlist w
             JOIN products p ON w.product_id = p.id
             WHERE w.user_id = :user_id
             ORDER BY w.created_at DESC",
            ['user_id' => $userId]
        );
        
        return $items;
    }
    
    /**
     * Add to wishlist
     */
    public function add($data) {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('Authentication required');
        }
        
        if (empty($data['product_id'])) {
            throw new Exception('Product ID is required');
        }
        
        // Check if already in wishlist
        $existing = $this->db->fetchOne(
            "SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id",
            ['user_id' => $userId, 'product_id' => $data['product_id']]
        );
        
        if ($existing) {
            throw new Exception('Product already in wishlist');
        }
        
        // Add to wishlist
        $this->db->insert('wishlist', [
            'user_id' => $userId,
            'product_id' => $data['product_id']
        ]);
        
        return ['message' => 'Added to wishlist'];
    }
    
    /**
     * Remove from wishlist
     */
    public function remove($productId) {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            throw new Exception('Authentication required');
        }
        
        $this->db->delete(
            'wishlist',
            'user_id = :user_id AND product_id = :product_id',
            ['user_id' => $userId, 'product_id' => $productId]
        );
        
        return ['message' => 'Removed from wishlist'];
    }
}
