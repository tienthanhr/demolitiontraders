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
    
    $updateData = [
        'status' => $data['status'] ?? 'active',
        'notes' => $data['notes'] ?? '',
        'notify_enabled' => isset($data['notify_enabled']) ? (int)$data['notify_enabled'] : 1
    ];
    
    $db->update('wanted_listings', $updateData, 'id = :id', ['id' => $data['id']]);
    
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
