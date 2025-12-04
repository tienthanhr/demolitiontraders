<?php
require_once __DIR__ . '/../../core/bootstrap.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productIds = $input['product_ids'] ?? [];

if (empty($productIds)) {
    echo json_encode(['success' => true, 'recommendations' => []]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get category IDs from cart products - SIMPLE approach
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    $stmt = $db->prepare("
        SELECT DISTINCT category_id
        FROM products
        WHERE id IN ($placeholders)
        AND category_id IS NOT NULL
    ");
    $stmt->execute($productIds);
    $categoryIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($categoryIds)) {
        // No categories found, return random products
        $stmt = $db->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                p.stock_quantity,
                pi.image_url as image
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.id NOT IN ($placeholders)
            AND p.stock_quantity > 0
            AND p.is_active = 1
            ORDER BY RAND()
            LIMIT 4
        ");
        $stmt->execute($productIds);
    } else {
        // Get products from same categories
        $catPlaceholders = str_repeat('?,', count($categoryIds) - 1) . '?';
        $allParams = array_merge($productIds, $categoryIds);
        
        $stmt = $db->prepare("
            SELECT 
                p.id,
                p.name,
                p.price,
                p.stock_quantity,
                pi.image_url as image
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE p.id NOT IN ($placeholders)
            AND p.category_id IN ($catPlaceholders)
            AND p.stock_quantity > 0
            AND p.is_active = 1
            ORDER BY RAND()
            LIMIT 4
        ");
        $stmt->execute($allParams);
    }
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'debug' => [
            'category_ids' => $categoryIds,
            'product_ids_excluded' => $productIds
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching recommendations: ' . $e->getMessage()
    ]);
}
