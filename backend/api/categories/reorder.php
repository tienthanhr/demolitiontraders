<?php
/**
 * API: Reorder Categories
 * POST /api/categories/reorder.php
 */

// Initialize session via bootstrap (must be first)
require_once '../../core/bootstrap.php';
require_once '../../../frontend/config.php';
require_once '../../utils/security.php';
require_once '../../config/database.php';

// Debug: Log session data
error_log("Reorder.php - Session ID: " . session_id());
error_log("Reorder.php - SESSION data: " . json_encode($_SESSION));
error_log("Reorder.php - user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Reorder.php - role: " . ($_SESSION['role'] ?? 'NOT SET'));
error_log("Reorder.php - user_role: " . ($_SESSION['user_role'] ?? 'NOT SET'));

// Check authentication
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);
$orders = $input['orders'] ?? [];

if (empty($orders)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No orders provided']);
    exit;
}

try {
    $db = Database::getInstance();
    $updated = 0;
    $errors = [];
    
    foreach ($orders as $order) {
        $categoryId = $order['id'] ?? null;
        $position = $order['position'] ?? null;
        
        if (!$categoryId || $position === null) {
            $errors[] = "Invalid order data: id=$categoryId, position=$position";
            continue;
        }
        
        try {
            $result = $db->exec(
                "UPDATE categories SET position = :position WHERE id = :id",
                [
                    'position' => intval($position),
                    'id' => intval($categoryId)
                ]
            );
            $updated++;
        } catch (Exception $e) {
            $errors[] = "Failed to update category $categoryId: " . $e->getMessage();
        }
    }
    
    $response = [
        'success' => true,
        'message' => "Updated $updated categories",
        'updated' => $updated,
        'errors' => $errors
    ];
    
    if (!empty($errors)) {
        $response['success'] = false;
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
