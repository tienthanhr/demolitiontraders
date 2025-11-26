<?php
/**
 * Product Controller
 * Handles product-related operations
 */

class ProductController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all products with filters
     */
    public function index($params = []) {
        $where = ['p.is_active = 1'];
        $queryParams = [];
        $plywoodCategoryId = null;

        // Width/Height filter from description (expects format like '1800 x 2000' or 'W x H')
        if (!empty($params['min_width']) || !empty($params['max_width']) || !empty($params['min_height']) || !empty($params['max_height'])) {
            // Use REGEXP_SUBSTR for MySQL 8+ or SUBSTRING_INDEX for MySQL 5.x
            // Extract width (first number) and height (second number) from description
            // Example: description = '1800 x 2000' => width = 1800, height = 2000
            if (!empty($params['min_width'])) {
                $where[] = "CAST(TRIM(SUBSTRING_INDEX(p.description, 'x', 1)) AS UNSIGNED) >= :min_width";
                $queryParams['min_width'] = $params['min_width'];
            }
            if (!empty($params['max_width'])) {
                $where[] = "CAST(TRIM(SUBSTRING_INDEX(p.description, 'x', 1)) AS UNSIGNED) <= :max_width";
                $queryParams['max_width'] = $params['max_width'];
            }
            if (!empty($params['min_height'])) {
                $where[] = "CAST(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.description, 'x', -1), ' ', 1)) AS UNSIGNED) >= :min_height";
                $queryParams['min_height'] = $params['min_height'];
            }
            if (!empty($params['max_height'])) {
                $where[] = "CAST(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(p.description, 'x', -1), ' ', 1)) AS UNSIGNED) <= :max_height";
                $queryParams['max_height'] = $params['max_height'];
            }
        }
        
        // Category filter - handle both slug and ID
        if (!empty($params['category'])) {
            // Check if category is numeric (ID) or string (slug)
            if (is_numeric($params['category'])) {
                $where[] = 'p.category_id = :category_id';
                $queryParams['category_id'] = $params['category'];
                // Check if this is plywood category
                $cat = $this->db->fetchOne("SELECT slug FROM categories WHERE id = :id", ['id' => $params['category']]);
                if ($cat && $cat['slug'] === 'plywood') {
                    $plywoodCategoryId = $params['category'];
                }
            } else {
                // It's a slug, need to lookup category ID
                $categorySql = "SELECT id, slug FROM categories WHERE slug = :category_slug";
                $category = $this->db->fetchOne($categorySql, ['category_slug' => $params['category']]);
                if ($category) {
                    $where[] = 'p.category_id = :category_id';
                    $queryParams['category_id'] = $category['id'];
                    if ($category['slug'] === 'plywood') {
                        $plywoodCategoryId = $category['id'];
                    }
                } else {
                    // Invalid slug, return empty results
                    return [
                        'data' => [],
                        'pagination' => [
                            'total' => 0,
                            'per_page' => 20,
                            'current_page' => 1,
                            'total_pages' => 0
                        ]
                    ];
                }
            }
        }
        
        // Search filter
        if (!empty($params['search'])) {
            $where[] = '(p.name LIKE :search_name OR p.description LIKE :search_desc OR p.sku LIKE :search_sku)';
            $searchTerm = '%' . $params['search'] . '%';
            $queryParams['search_name'] = $searchTerm;
            $queryParams['search_desc'] = $searchTerm;
            $queryParams['search_sku'] = $searchTerm;
        }
        
        // Condition filter
        if (!empty($params['treatment'])) {
            // Nếu là plywood thì lọc theo description
            if ($plywoodCategoryId) {
                if ($params['treatment'] === 'treated') {
                    $where[] = "(LOWER(p.description) LIKE '%treated%' AND LOWER(p.description) NOT LIKE '%untreated%')";
                } elseif ($params['treatment'] === 'untreated') {
                    $where[] = "LOWER(p.description) LIKE '%untreated%'";
                }
            }
        }
        // Condition filter (giữ nguyên cho các loại khác)
        if (!empty($params['condition']) && !$plywoodCategoryId) {
            $where[] = 'p.condition_type = :condition';
            $queryParams['condition'] = $params['condition'];
        }
        
        // Featured filter
        if (isset($params['featured']) && $params['featured'] == '1') {
            $where[] = 'p.is_featured = 1';
        }
        
        // Price range
        if (!empty($params['min_price'])) {
            $where[] = 'p.price >= :min_price';
            $queryParams['min_price'] = $params['min_price'];
        }
        if (!empty($params['max_price'])) {
            $where[] = 'p.price <= :max_price';
            $queryParams['max_price'] = $params['max_price'];
        }
        
        // Build WHERE clause
        $whereClause = implode(' AND ', $where);
        
        // Sorting
        $orderBy = 'p.created_at DESC';
        if (!empty($params['sort'])) {
            switch ($params['sort']) {
                case 'price_asc':
                    $orderBy = 'p.price ASC';
                    break;
                case 'price_desc':
                    $orderBy = 'p.price DESC';
                    break;
                case 'name_asc':
                    $orderBy = 'p.name ASC';
                    break;
                case 'name_desc':
                    $orderBy = 'p.name DESC';
                    break;
            }
        }
        
        // Pagination
        $page = max(1, intval($params['page'] ?? 1));
        $perPage = min(100, intval($params['per_page'] ?? 20));
        $offset = ($page - 1) * $perPage;
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM products p WHERE {$whereClause}";
        $total = $this->db->fetchOne($countSql, $queryParams)['total'];
        
        // Get products
        $sql = "SELECT p.*, c.name as category_name, 
                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$perPage} OFFSET {$offset}";
        
        $products = $this->db->fetchAll($sql, $queryParams);
        
        return [
            'data' => $products,
            'pagination' => [
                'total' => intval($total),
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
    
    /**
     * Get single product
     */
    public function show($id) {
        // Get by ID or slug
        if (is_numeric($id)) {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.id = :id AND p.is_active = 1";
            $product = $this->db->fetchOne($sql, ['id' => $id]);
        } else {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.slug = :slug AND p.is_active = 1";
            $product = $this->db->fetchOne($sql, ['slug' => $id]);
        }
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Get images
        $product['images'] = $this->db->fetchAll(
            "SELECT * FROM product_images WHERE product_id = :id ORDER BY display_order, id",
            ['id' => $product['id']]
        );
        
        // Get related products (same category)
        $product['related'] = $this->db->fetchAll(
            "SELECT p.*, 
             (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
             FROM products p
             WHERE p.category_id = :category_id AND p.id != :product_id AND p.is_active = 1
             LIMIT 4",
            ['category_id' => $product['category_id'], 'product_id' => $product['id']]
        );
        
        return $product;
    }
    
    /**
     * Create product (Admin only)
     */
    public function store($data) {
        // Validate required fields
        $required = ['sku', 'name', 'price'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Field '{$field}' is required");
            }
        }
        
        // Generate slug
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        // Insert product
        $productData = [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? '',
            'short_description' => $data['short_description'] ?? '',
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? null,
            'compare_at_price' => $data['compare_at_price'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'condition_type' => $data['condition_type'] ?? 'new',
            'is_featured' => $data['is_featured'] ?? 0,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        $productId = $this->db->insert('products', $productData);
        
        return $this->show($productId);
    }
    
    /**
     * Update product (Admin only)
     */
    public function update($id, $data) {
        $product = $this->db->fetchOne("SELECT * FROM products WHERE id = :id", ['id' => $id]);
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Update only provided fields
        $updateData = [];
        $allowedFields = ['sku', 'name', 'slug', 'description', 'short_description', 'price', 
                          'cost_price', 'compare_at_price', 'category_id', 'stock_quantity', 
                          'condition_type', 'is_featured', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (!empty($updateData)) {
            $this->db->update('products', $updateData, 'id = :id', ['id' => $id]);
        }
        
        return $this->show($id);
    }
    
    /**
     * Delete product (Admin only)
     */
    public function delete($id) {
        $this->db->delete('products', 'id = :id', ['id' => $id]);
        return ['message' => 'Product deleted successfully'];
    }
    
    /**
     * Generate URL slug
     */
    private function generateSlug($string) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
        
        // Check if slug exists
        $exists = $this->db->fetchOne("SELECT id FROM products WHERE slug = :slug", ['slug' => $slug]);
        
        if ($exists) {
            $slug .= '-' . time();
        }
        
        return $slug;
    }
}
