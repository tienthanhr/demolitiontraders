<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('Listing ID is required');
    }
    
    $db = Database::getInstance();
    
    $deleteNote = "Deleted by admin " . $_SESSION['user_id'] . " at " . date('Y-m-d H:i:s');
    $query = "UPDATE wanted_listings SET notes = CONCAT(COALESCE(notes, ''), '\n[DELETED] ', :note), status = 'cancelled' WHERE id = :id";
    $db->query($query, [':id' => $data['id'], ':note' => $deleteNote]);
    
    echo json_encode(['success' => true, 'message' => 'Listing marked as deleted']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
