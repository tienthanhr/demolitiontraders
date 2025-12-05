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
    
    $query = "UPDATE wanted_listings SET 
              status = :status,
              notes = :notes,
              notify_enabled = :notify_enabled
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':id' => $data['id'],
        ':status' => $data['status'] ?? 'active',
        ':notes' => $data['notes'] ?? '',
        ':notify_enabled' => isset($data['notify_enabled']) ? (int)$data['notify_enabled'] : 1
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Wanted listing updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Update wanted listing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
