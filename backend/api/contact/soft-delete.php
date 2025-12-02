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
        throw new Exception('Contact ID is required');
    }
    
    $db = Database::getInstance()->getConnection();
    
    $deleteNote = "Deleted by admin " . $_SESSION['user_id'] . " at " . date('Y-m-d H:i:s');
    $query = "UPDATE contact_submissions SET status = 'resolved' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $data['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Message archived']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
