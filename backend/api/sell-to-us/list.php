<?php
/**
 * List Sell to Us Submissions (Admin Only)
 * GET /backend/api/sell-to-us/list.php
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

ini_set('session.save_path', '/tmp');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    
    // Get submissions
    $submissions = $db->fetchAll("
        SELECT * FROM sell_to_us_submissions
        $whereClause
        ORDER BY created_at DESC
        LIMIT :limit OFFSET :offset
    ", array_merge($params, ['limit' => $limit, 'offset' => $offset]));
    
    // Get total count
    $totalCount = $db->fetchOne("
        SELECT COUNT(*) as count FROM sell_to_us_submissions
        $whereClause
    ", $params)['count'];
    
    // Parse JSON photos
    foreach ($submissions as &$submission) {
        if ($submission['photos']) {
            $submission['photos'] = json_decode($submission['photos'], true);
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
