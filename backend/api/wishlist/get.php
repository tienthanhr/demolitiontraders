<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

try {
    // Get wishlist from session
    $wishlist = isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];

    // Nếu wishlist rỗng thì trả về luôn
    if (empty($wishlist)) {
        echo json_encode([
            'success' => true,
            'wishlist' => [],
            'wishlist_count' => 0
        ]);
        exit;
    }

    // Lấy thông tin chi tiết sản phẩm từ DB
    require_once __DIR__ . '/../../config/database.php';
    $db = Database::getInstance();

    // Chuẩn bị câu truy vấn lấy nhiều sản phẩm theo ID
    $placeholders = implode(',', array_fill(0, count($wishlist), '?'));
    $sql = "SELECT p.id as product_id, p.name, p.price, c.name as category, 
                (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id IN ($placeholders) AND p.is_active = 1";
    $stmt = $db->getConnection()->prepare($sql);
    $stmt->execute($wishlist);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'wishlist' => $products,
        'wishlist_count' => count($products)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
