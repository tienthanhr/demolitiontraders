<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        throw new Exception('Listing ID is required');
    }
    
    $db = Database::getInstance()->getConnection();
    
    $query = "UPDATE wanted_listings 
              SET notes = REPLACE(notes, SUBSTRING(notes, LOCATE('[DELETED]', notes), LOCATE('\\n', notes, LOCATE('[DELETED]', notes)) - LOCATE('[DELETED]', notes) + 1), ''),
                  status = 'active'
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $data['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Listing restored']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
