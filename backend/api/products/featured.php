<?php
// backend/api/products/featured.php
require_once __DIR__ . '/../../controllers/ProductController.php';
header('Content-Type: application/json');

try {
    $controller = new ProductController();
    // Get 8 featured products, order by id desc
    $params = [
        'featured' => 1,
        'per_page' => 8,
        'page' => 1,
        'sort' => 'id_desc',
    ];
    $result = $controller->index($params);
    // Only return the data array (products)
    echo json_encode([
        'success' => true,
        'products' => $result['data']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
