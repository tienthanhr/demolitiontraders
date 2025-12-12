<?php
/**
 * List Sell to Us Submissions (Admin Only)
 * GET /backend/api/sell-to-us/list.php
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../../core/bootstrap.php';
}

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || 
           ($_SESSION['user_role'] ?? '') === 'admin' || 
           ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get filter parameters
    $status = $_GET['status'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Build query
    $where = [];
    $params = [];
    
    if ($status && $status !== 'all') {
        $where[] = "status = :status";
        $params['status'] = $status;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    try {
        // New table
        $submissions = $db->fetchAll("
            SELECT * FROM sell_to_us_submissions
            $whereClause
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ", array_merge($params, ['limit' => $limit, 'offset' => $offset]));
        
        $totalCount = $db->fetchOne("
            SELECT COUNT(*) as count FROM sell_to_us_submissions
            $whereClause
        ", $params)['count'];
        
        foreach ($submissions as &$submission) {
            if (!empty($submission['photos'])) {
                $submission['photos'] = json_decode($submission['photos'], true);
            }
        }
    } catch (Exception $newTableError) {
        // Fallback to legacy sell_items table if new table missing
        $submissions = $db->fetchAll("
            SELECT id, name, email, phone, description, images, status, created_at
            FROM sell_items
            " . ($whereClause ? $whereClause : '') . "
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ", array_merge($params, ['limit' => $limit, 'offset' => $offset]));
        
        $totalCount = $db->fetchOne("
            SELECT COUNT(*) as count FROM sell_items
            " . ($whereClause ? $whereClause : '') . "
        ", $params)['count'];
        
        // Map legacy fields to match new schema expectations
        foreach ($submissions as &$submission) {
            $submission['photos'] = !empty($submission['images']) ? json_decode($submission['images'], true) : [];
            $submission['item_name'] = '';
            $submission['quantity'] = '';
            $submission['pickup_delivery'] = '';
            $submission['pickup_date'] = null;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $submissions,
        'total' => (int)$totalCount,
        'limit' => $limit,
        'offset' => $offset
    ]);
    
} catch (Exception $e) {
    error_log("Sell to Us List Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch submissions']);
}
