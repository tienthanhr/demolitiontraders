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
        // Check if admin context (for admin panel)
        $isAdmin = ($_SESSION['role'] ?? '') === 'admin' || 
                   ($_SESSION['user_role'] ?? '') === 'admin' || 
                   ($_SESSION['is_admin'] ?? false) === true;
        
        if ($isAdmin) {
            // Admin sees all categories
            $categories = $this->db->fetchAll(
                "SELECT * FROM categories ORDER BY display_order ASC, name ASC"
            );
        } else {
            // Public sees only active categories
            $categories = $this->db->fetchAll(
                "SELECT * FROM categories 
                 WHERE is_active = 1 
                 ORDER BY display_order ASC, name ASC"
            );
        }
        
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
    
    /**
     * Create new category
     */
    public function create($data) {
        // Validate required fields
        if (empty($data['name'])) {
            throw new Exception('Category name is required');
        }
        
        // Generate slug if not provided
        $slug = $data['slug'] ?? $this->generateSlug($data['name']);
        
        // Check if slug already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM categories WHERE slug = :slug",
            ['slug' => $slug]
        );
        
        if ($existing) {
            throw new Exception('A category with this slug already exists');
        }
        
        // Insert category
        $categoryId = $this->db->insert('categories', [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'display_order' => $data['display_order'] ?? 0,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
        
        return [
            'success' => true,
            'message' => 'Category created successfully',
            'id' => $categoryId
        ];
    }
    
    /**
     * Update category
     */
    public function update($id, $data) {
        // Check if category exists
        $category = $this->db->fetchOne(
            "SELECT * FROM categories WHERE id = :id",
            ['id' => $id]
        );
        
        if (!$category) {
            throw new Exception('Category not found');
        }
        
        // Generate slug if name changed
        if (!empty($data['name']) && $data['name'] !== $category['name']) {
            $slug = $data['slug'] ?? $this->generateSlug($data['name']);
            
            // Check if new slug conflicts with other categories
            $existing = $this->db->fetchOne(
                "SELECT id FROM categories WHERE slug = :slug AND id != :id",
                ['slug' => $slug, 'id' => $id]
            );
            
            if ($existing) {
                throw new Exception('A category with this slug already exists');
            }
        } else {
            $slug = $category['slug'];
        }
        
        // Update category
        $updateData = [
            'name' => $data['name'] ?? $category['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? $category['description'],
            'parent_id' => $data['parent_id'] ?? $category['parent_id'],
            'display_order' => $data['display_order'] ?? $category['display_order'],
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : $category['is_active']
        ];
        
        $this->db->update('categories', $updateData, 'id = :id', ['id' => $id]);
        
        return [
            'success' => true,
            'message' => 'Category updated successfully'
        ];
    }
    
    /**
     * Delete category
     */
    public function delete($id) {
        // Check if category exists
        $category = $this->db->fetchOne(
            "SELECT * FROM categories WHERE id = :id",
            ['id' => $id]
        );
        
        if (!$category) {
            throw new Exception('Category not found');
        }
        
        // Check if category has products
        $productCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM products WHERE category_id = :id",
            ['id' => $id]
        );
        
        if ($productCount && $productCount['count'] > 0) {
            // Instead of hard delete, set products to uncategorized
            $this->db->query(
                "UPDATE products SET category_id = NULL WHERE category_id = :id",
                ['id' => $id]
            );
        }
        
        // Delete category
        $this->db->delete('categories', 'id = :id', ['id' => $id]);
        
        return [
            'success' => true,
            'message' => 'Category deleted successfully'
        ];
    }
    
    /**
     * Generate slug from name
     */
    private function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
}

