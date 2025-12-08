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
    
    $updateData = [
        'status' => $data['status'] ?? 'new'
    ];
    
    $db->update('contact_submissions', $updateData, 'id = :id', ['id' => $data['id']]);
    
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
