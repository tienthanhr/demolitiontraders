<?php
// Test Cart API - Debug PostgreSQL compatibility
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Test 1: Check connection
    $result = ['tests' => []];
    $result['tests'][] = [
        'name' => 'Database Connection',
        'status' => 'success',
        'driver' => $db->getAttribute(PDO::ATTR_DRIVER_NAME)
    ];
    
    // Test 2: Check cart table exists
    $stmt = $db->query("SELECT COUNT(*) FROM cart");
    $result['tests'][] = [
        'name' => 'Cart Table Exists',
        'status' => 'success',
        'count' => $stmt->fetchColumn()
    ];
    
    // Test 3: Try to select from cart with products
    $query = "SELECT c.*, p.name, p.price, p.slug 
              FROM cart c 
              LEFT JOIN products p ON c.product_id = p.id 
              WHERE c.session_id = :session_id OR c.user_id = :user_id
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute([
        'session_id' => session_id(),
        'user_id' => null
    ]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result['tests'][] = [
        'name' => 'Cart Query',
        'status' => 'success',
        'items_found' => count($items)
    ];
    
    // Test 4: Check session
    $result['session_id'] = session_id();
    $result['session_status'] = session_status();
    
    $result['overall_status'] = 'success';
    echo json_encode($result, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'overall_status' => 'error',
        'error_message' => $e->getMessage(),
        'error_trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
