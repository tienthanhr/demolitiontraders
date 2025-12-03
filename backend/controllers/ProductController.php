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
        $where = [];
        $queryParams = [];
        $plywoodCategoryId = null;

        // Status filter - check is_active parameter
        if (isset($params['is_active'])) {
            $where[] = 'p.is_active = :is_active';
            $queryParams['is_active'] = $params['is_active'];
        } else {
            // Default: only show active products for non-admin views
            $where[] = 'p.is_active = 1';
        }

        // Width/Height filter - disabled for PostgreSQL compatibility
        // TODO: Store dimensions in separate columns for proper filtering
        /*
        if (!empty($params['min_width']) || !empty($params['max_width']) || !empty($params['min_height']) || !empty($params['max_height'])) {
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
        */
        
        // Category filter
        if (!empty($params['category'])) {
            if (is_numeric($params['category'])) {
                $where[] = 'p.category_id = :category_id';
                $queryParams['category_id'] = $params['category'];
                $cat = $this->db->fetchOne("SELECT slug FROM categories WHERE id = :id", ['id' => $params['category']]);
                if ($cat && $cat['slug'] === 'plywood') {
                    $plywoodCategoryId = $params['category'];
                }
            } else {
                $categorySql = "SELECT id, slug FROM categories WHERE slug = :category_slug";
                $category = $this->db->fetchOne($categorySql, ['category_slug' => $params['category']]);
                if ($category) {
                    $where[] = 'p.category_id = :category_id';
                    $queryParams['category_id'] = $category['id'];
                    if ($category['slug'] === 'plywood') {
                        $plywoodCategoryId = $category['id'];
                    }
                } else {
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
        
        // Treatment filter for plywood
        if (!empty($params['treatment'])) {
            if ($plywoodCategoryId) {
                if ($params['treatment'] === 'treated') {
                    $where[] = "(LOWER(p.description) LIKE '%treated%' AND LOWER(p.description) NOT LIKE '%untreated%')";
                } elseif ($params['treatment'] === 'untreated') {
                    $where[] = "LOWER(p.description) LIKE '%untreated%'";
                }
            }
        }
        
        // Condition filter
        if (!empty($params['condition']) && !$plywoodCategoryId) {
            $where[] = 'p.condition_type = :condition';
            $queryParams['condition'] = $params['condition'];
        }
        
     // Featured filter - Support both 'featured' and 'is_featured'
if (isset($params['is_featured']) || isset($params['featured'])) {
    $featuredValue = $params['is_featured'] ?? $params['featured'];
    if ($featuredValue == '1' || $featuredValue === 1 || $featuredValue === true) {
        $where[] = 'p.is_featured = 1';
    }
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
        // Support both 'per_page' and 'limit' parameters, max 500 for recommendations
        $perPage = min(500, intval($params['per_page'] ?? $params['limit'] ?? 20));
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
        if (is_numeric($id)) {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.id = :id";
            $product = $this->db->fetchOne($sql, ['id' => $id]);
        } else {
            $sql = "SELECT p.*, c.name as category_name 
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.slug = :slug";
            $product = $this->db->fetchOne($sql, ['slug' => $id]);
        }
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Get images - FIX: Đổi tên field từ image_url thành image_path và trả về dưới key 'url'
        $images = $this->db->fetchAll(
            "SELECT id, image_url as url, is_primary FROM product_images WHERE product_id = :id ORDER BY is_primary DESC, display_order, id",
            ['id' => $product['id']]
        );
        $product['images'] = $images;
        
        // Get related products
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
        try {
            $input = !empty($data) ? $data : $_POST;
            
            // Log raw input
            $this->logDebug('[STORE START]', ['input' => $input, 'files' => $_FILES]);
            
            // Validate required fields
            $required = ['name', 'price', 'category_id'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Get allowed columns from database
            $columnsResult = $this->db->fetchAll("DESCRIBE products");
            $allowedColumns = array_column($columnsResult, 'Field');
            
            // Prepare product data
            $productData = [];
            $skipColumns = ['id', 'created_at', 'updated_at'];
            
            foreach ($allowedColumns as $col) {
                if (in_array($col, $skipColumns)) continue;
                
                if (array_key_exists($col, $input) && $input[$col] !== '' && $input[$col] !== null) {
                    $productData[$col] = $input[$col];
                }
            }
            
            // Cast numeric fields
            if (isset($productData['price'])) $productData['price'] = (float)$productData['price'];
            if (isset($productData['cost_price'])) $productData['cost_price'] = (float)$productData['cost_price'];
            if (isset($productData['stock_quantity'])) $productData['stock_quantity'] = (int)$productData['stock_quantity'];
            if (isset($productData['category_id'])) $productData['category_id'] = (int)$productData['category_id'];
            if (isset($productData['is_active'])) $productData['is_active'] = (int)$productData['is_active'];
            if (isset($productData['is_featured'])) $productData['is_featured'] = (int)$productData['is_featured'];
            if (isset($productData['show_collection_options'])) $productData['show_collection_options'] = (int)$productData['show_collection_options'];
            
            // Generate slug if not provided
            if (empty($productData['slug'])) {
                $productData['slug'] = $this->generateSlug($input['name']);
            }
            
            // Handle SKU: use provided SKU or generate new one
            if (empty($productData['sku'])) {
                // Get the highest SKU number to generate next one
                $maxSkuResult = $this->db->fetchOne(
                    "SELECT sku FROM products WHERE sku LIKE 'DT-%' ORDER BY CAST(SUBSTRING_INDEX(sku, '-', -1) AS UNSIGNED) DESC LIMIT 1"
                );
                
                $nextSkuNumber = 1;
                if ($maxSkuResult && !empty($maxSkuResult['sku'])) {
                    // Extract number from SKU format: DT-categoryId-number
                    $parts = explode('-', $maxSkuResult['sku']);
                    if (count($parts) >= 3) {
                        $nextSkuNumber = (int)end($parts) + 1;
                    }
                }
                
                $productData['sku'] = 'DT-' . $productData['category_id'] . '-' . $nextSkuNumber;
            }
            
            $this->logDebug('[STORE PREPARED DATA]', $productData);
            
            // Insert product
            $productId = $this->db->insert('products', $productData);
            
            if (!$productId) {
                throw new Exception('Failed to create product - database did not return insert ID');
            }
            
            $this->logDebug('[STORE CREATED]', ['product_id' => $productId, 'sku' => $productData['sku']]);
            
            // Handle image uploads
            if (!empty($_FILES['product_images']['name'][0])) {
                $this->logDebug('[STORE PROCESSING IMAGES]', $_FILES['product_images']);
                $this->handleProductImages($productId, $_FILES['product_images'], true);
            }
            
            // Return created product
            $result = $this->show($productId);
            
            $this->logDebug('[STORE SUCCESS]', ['product_id' => $productId]);
            
            return [
                'success' => true,
                'message' => 'Product created successfully',
                'data' => $result
            ];
            
        } catch (Exception $e) {
            $this->logDebug('[STORE ERROR]', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }
    
    /**
     * Update product (Admin only)
     */
    public function update($id, $data) {
        try {
            $input = !empty($data) ? $data : $_POST;
            
            $this->logDebug('[UPDATE START]', ['id' => $id, 'input' => $input, 'files' => $_FILES]);
            
            // Check if product exists
            $product = $this->db->fetchOne("SELECT * FROM products WHERE id = :id", ['id' => $id]);
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            // Get allowed columns
            $columnsResult = $this->db->fetchAll("DESCRIBE products");
            $allowedColumns = array_column($columnsResult, 'Field');
            
            // Prepare update data
            $updateData = [];
            $skipColumns = ['id', 'created_at', 'updated_at'];
            
            foreach ($allowedColumns as $col) {
                if (in_array($col, $skipColumns)) continue;
                
                if (array_key_exists($col, $input) && $input[$col] !== '' && $input[$col] !== null) {
                    $updateData[$col] = $input[$col];
                }
            }
            
            // Cast numeric fields
            if (isset($updateData['price'])) $updateData['price'] = (float)$updateData['price'];
            if (isset($updateData['cost_price'])) $updateData['cost_price'] = (float)$updateData['cost_price'];
            if (isset($updateData['stock_quantity'])) $updateData['stock_quantity'] = (int)$updateData['stock_quantity'];
            if (isset($updateData['category_id'])) $updateData['category_id'] = (int)$updateData['category_id'];
            if (isset($updateData['is_active'])) $updateData['is_active'] = (int)$updateData['is_active'];
            if (isset($updateData['is_featured'])) $updateData['is_featured'] = (int)$updateData['is_featured'];
            if (isset($updateData['show_collection_options'])) $updateData['show_collection_options'] = (int)$updateData['show_collection_options'];
            
            // Check SKU uniqueness
            if (isset($updateData['sku'])) {
                $skuExists = $this->db->fetchOne(
                    'SELECT id FROM products WHERE sku = :sku AND id != :id', 
                    ['sku' => $updateData['sku'], 'id' => $id]
                );
                if ($skuExists) {
                    throw new Exception('SKU already exists');
                }
            }
            
            $this->logDebug('[UPDATE PREPARED DATA]', $updateData);
            
            // Update product
            if (!empty($updateData)) {
                $this->db->update('products', $updateData, 'id = :id', ['id' => $id]);
            }
            
            // Handle removed images
            if (!empty($input['removed_image_ids'])) {
                $ids = is_string($input['removed_image_ids']) 
                    ? json_decode($input['removed_image_ids'], true) 
                    : $input['removed_image_ids'];
                    
                if (is_array($ids) && !empty($ids)) {
                    $this->logDebug('[UPDATE REMOVING IMAGES]', $ids);
                    $this->removeProductImages($ids);
                }
            }
            
            // Handle new images
            if (!empty($_FILES['product_images']['name'][0])) {
                $this->logDebug('[UPDATE ADDING IMAGES]', $_FILES['product_images']);
                $this->handleProductImages($id, $_FILES['product_images'], false);
            }
            
            $result = $this->show($id);
            
            $this->logDebug('[UPDATE SUCCESS]', ['product_id' => $id]);
            
            return [
                'success' => true,
                'message' => 'Product updated successfully',
                'data' => $result
            ];
            
        } catch (Exception $e) {
            $this->logDebug('[UPDATE ERROR]', ['message' => $e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * Delete product (Admin only)
     */
    public function delete($id) {
        // Delete product images first
        $images = $this->db->fetchAll(
            "SELECT id FROM product_images WHERE product_id = :id",
            ['id' => $id]
        );
        
        if ($images) {
            $imageIds = array_column($images, 'id');
            $this->removeProductImages($imageIds);
        }
        
        // Delete product
        $this->db->delete('products', 'id = :id', ['id' => $id]);
        
        return [
            'success' => true,
            'message' => 'Product deleted successfully'
        ];
    }
    
    /**
     * Handle product image uploads
     */
    /**
     * FINAL FIX - handleProductImages method
     * Improved path handling and error logging
     */
    private function handleProductImages($productId, $files, $isPrimary = false) {
        // CRITICAL: Use correct path based on your project location
        // Your project is at: C:/xampp/htdocs/demolitiontraders/
        
        // Physical path on server
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/demolitiontraders/uploads/products/';
        
        // Create directory if doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
            $this->logDebug('[IMAGE DIR CREATED]', ['path' => $uploadDir]);
        }
        
        // Check writable
        if (!is_writable($uploadDir)) {
            $this->logDebug('[IMAGE DIR NOT WRITABLE]', ['path' => $uploadDir]);
            throw new Exception('Upload directory is not writable: ' . $uploadDir);
        }
        
        $uploadedImages = [];
        $order = 0;
        
        if (!isset($files['tmp_name']) || !is_array($files['tmp_name'])) {
            $this->logDebug('[IMAGE UPLOAD ERROR]', ['error' => 'Invalid files array']);
            return $uploadedImages;
        }
        
        foreach ($files['tmp_name'] as $idx => $tmpName) {
            // Check upload error
            if (!isset($files['error'][$idx]) || $files['error'][$idx] !== UPLOAD_ERR_OK) {
                $errorMsg = $files['error'][$idx] ?? 'Unknown';
                $this->logDebug('[IMAGE UPLOAD ERROR]', [
                    'index' => $idx,
                    'error' => $errorMsg,
                    'name' => $files['name'][$idx] ?? 'unknown'
                ]);
                continue;
            }
            
            // Validate uploaded file
            if (!is_uploaded_file($tmpName)) {
                $this->logDebug('[NOT UPLOADED FILE]', ['tmp' => $tmpName]);
                continue;
            }

            // --- Security Enhancement: File Size and MIME Type Validation ---

            // 1. Validate file size
            $maxSize = Config::get('MAX_UPLOAD_SIZE', 5242880); // Default 5MB
            if ($files['size'][$idx] > $maxSize) {
                $this->logDebug('[FILE TOO LARGE]', ['size' => $files['size'][$idx], 'max' => $maxSize]);
                continue;
            }

            // 2. Validate MIME type
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($tmpName);
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!in_array($mimeType, $allowedMimeTypes)) {
                $this->logDebug('[INVALID MIME TYPE]', ['mime' => $mimeType, 'file' => $files['name'][$idx]]);
                continue;
            }
            
            // Validate extension (as a secondary check)
            $originalName = $files['name'][$idx];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($ext, $allowedExts)) {
                $this->logDebug('[INVALID EXTENSION]', ['ext' => $ext, 'file' => $originalName]);
                continue;
            }
            
            // Generate filename
            $filename = 'prod_' . uniqid() . '.' . $ext;
            $fullPath = $uploadDir . $filename;
            
            // Database path - MUST match URL structure
            $dbPath = '/demolitiontraders/uploads/products/' . $filename;
            
            $this->logDebug('[ATTEMPTING UPLOAD]', [
                'original' => $originalName,
                'filename' => $filename,
                'tmp_name' => $tmpName,
                'full_path' => $fullPath,
                'db_path' => $dbPath
            ]);
            
            // Move file
            if (move_uploaded_file($tmpName, $fullPath)) {
                // Verify file exists
                if (!file_exists($fullPath)) {
                    $this->logDebug('[MOVE VERIFIED FAILED]', ['path' => $fullPath]);
                    continue;
                }
                
                // Set proper permissions
                chmod($fullPath, 0644);
                
                // Insert to database
                $insertId = $this->db->insert('product_images', [
                    'product_id' => $productId,
                    'image_url' => $dbPath,
                    'is_primary' => ($isPrimary && $order === 0) ? 1 : 0,
                    'display_order' => $order
                ]);
                
                if ($insertId) {
                    $uploadedImages[] = $dbPath;
                    $order++;
                    
                    $this->logDebug('[UPLOAD SUCCESS]', [
                        'db_path' => $dbPath,
                        'full_path' => $fullPath,
                        'size_kb' => round(filesize($fullPath) / 1024, 2),
                        'insert_id' => $insertId
                    ]);
                } else {
                    $this->logDebug('[DB INSERT FAILED]', ['path' => $dbPath]);
                    @unlink($fullPath);
                }
            } else {
                $lastError = error_get_last();
                $this->logDebug('[MOVE FAILED]', [
                    'from' => $tmpName,
                    'to' => $fullPath,
                    'last_error' => $lastError
                ]);
            }
        }
        
        return $uploadedImages;
    }
    
    /**
     * Remove product images
     */
    /**
     * Improved removeProductImages method
     * Avoids duplicate/unnecessary deletions and handles external images
     */
    private function removeProductImages($imageIds) {
        if (!is_array($imageIds) || empty($imageIds)) return;
        
        foreach ($imageIds as $imgId) {
            $img = $this->db->fetchOne(
                'SELECT image_url FROM product_images WHERE id = :id', 
                ['id' => $imgId]
            );
            
            if ($img && !empty($img['image_url'])) {
                // Build correct file path
                $urlPath = $img['image_url'];
                
                // If path starts with http or https, skip deletion (external image)
                if (preg_match('/^https?:\/\//', $urlPath)) {
                    $this->logDebug('[SKIP EXTERNAL IMAGE]', ['url' => $urlPath]);
                } else {
                    // Remove leading slash and build full path
                    $relativePath = ltrim($urlPath, '/');
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $relativePath;
                    
                    if (file_exists($fullPath)) {
                        if (@unlink($fullPath)) {
                            $this->logDebug('[IMAGE DELETED]', ['path' => $fullPath]);
                        } else {
                            $this->logDebug('[IMAGE DELETE FAILED]', ['path' => $fullPath]);
                        }
                    } else {
                        $this->logDebug('[IMAGE NOT FOUND FOR DELETE]', ['path' => $fullPath]);
                    }
                }
            }
            
            // Delete from database
            $this->db->delete('product_images', 'id = :id', ['id' => $imgId]);
        }
    }
    
    /**
     * Generate URL slug
     */
    private function generateSlug($string) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
        
        $exists = $this->db->fetchOne("SELECT id FROM products WHERE slug = :slug", ['slug' => $slug]);
        
        if ($exists) {
            $slug .= '-' . time();
        }
        
        return $slug;
    }
    
    /**
     * Debug logging helper
     */
    private function logDebug($label, $data) {
        $logDir = __DIR__ . '/../../logs/';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . 'product_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $logData = is_array($data) ? json_encode($data, JSON_PRETTY_PRINT) : $data;
        
        @file_put_contents(
            $logFile, 
            "\n[{$timestamp}] {$label}\n{$logData}\n", 
            FILE_APPEND
        );
    }
}