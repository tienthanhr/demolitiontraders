<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['ids']) || !is_array($data['ids'])) {
        throw new Exception('IDs array is required');
    }
    
    $db = Database::getInstance();
    
    $deleteNote = "Bulk deleted by admin " . $_SESSION['user_id'] . " at " . date('Y-m-d H:i:s');
    $placeholders = str_repeat('?,', count($data['ids']) - 1) . '?';
    
    $query = "UPDATE wanted_listings 
              SET notes = CONCAT(COALESCE(notes, ''), '\n[DELETED] ', ?), 
                  status = 'cancelled' 
              WHERE id IN ($placeholders)";
    
    $params = array_merge([$deleteNote], $data['ids']);
    $stmt = $db->query($query, $params);
    
    echo json_encode(['success' => true, 'message' => count($data['ids']) . ' listings marked as deleted', 'count' => $stmt->rowCount()]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
