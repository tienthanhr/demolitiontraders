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
        throw new Exception('Contact ID is required');
    }
    
    $db = Database::getInstance();
    
    $query = "UPDATE contact_submissions SET 
              status = :status
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':id' => $data['id'],
        ':status' => $data['status'] ?? 'new'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact status updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Update contact error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
