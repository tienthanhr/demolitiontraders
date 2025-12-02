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
    
    $db = Database::getInstance()->getConnection();
    
    $placeholders = str_repeat('?,', count($data['ids']) - 1) . '?';
    $query = "UPDATE contact_submissions SET status = 'resolved' WHERE id IN ($placeholders)";
    
    $stmt = $db->prepare($query);
    $stmt->execute($data['ids']);
    
    echo json_encode(['success' => true, 'message' => count($data['ids']) . ' messages archived', 'count' => $stmt->rowCount()]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
