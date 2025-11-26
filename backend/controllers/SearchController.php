<?php
/**
 * Search Controller
 */

class SearchController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Search products
     */
    public function search($params) {
        $query = $params['q'] ?? '';
        $limit = min(50, intval($params['limit'] ?? 20));
        
        if (empty($query)) {
            return ['data' => []];
        }
        
        $searchTerm = '%' . $query . '%';
        
        $sql = "SELECT p.*, c.name as category_name,
                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1
                AND (
                    p.name LIKE :search 
                    OR p.description LIKE :search 
                    OR p.sku LIKE :search
                    OR c.name LIKE :search
                )
                ORDER BY 
                    CASE 
                        WHEN p.name LIKE :search THEN 1
                        WHEN p.sku LIKE :search THEN 2
                        ELSE 3
                    END,
                    p.is_featured DESC,
                    p.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->query($sql, [
            'search' => $searchTerm,
            'limit' => $limit
        ]);
        
        $results = $stmt->fetchAll();
        
        return ['data' => $results];
    }
}
