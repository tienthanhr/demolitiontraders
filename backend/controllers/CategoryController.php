<?php
/**
 * Category Controller
 */

class CategoryController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all categories
     */
    public function index() {
        $categories = $this->db->fetchAll(
            "SELECT * FROM categories 
             WHERE is_active = 1 
             ORDER BY display_order ASC, name ASC"
        );
        
        return $categories;
    }
    
    /**
     * Get single category
     */
    public function show($id) {
        // Get by ID or slug
        if (is_numeric($id)) {
            $category = $this->db->fetchOne(
                "SELECT * FROM categories WHERE id = :id AND is_active = 1",
                ['id' => $id]
            );
        } else {
            $category = $this->db->fetchOne(
                "SELECT * FROM categories WHERE slug = :slug AND is_active = 1",
                ['slug' => $id]
            );
        }
        
        if (!$category) {
            throw new Exception('Category not found');
        }
        
        // Get products in this category
        $category['products'] = $this->db->fetchAll(
            "SELECT p.*, 
             (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
             FROM products p
             WHERE p.category_id = :category_id AND p.is_active = 1
             ORDER BY p.created_at DESC
             LIMIT 20",
            ['category_id' => $category['id']]
        );
        
        return $category;
    }
}
