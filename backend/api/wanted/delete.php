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
    
    $db = Database::getInstance()->getConnection();
    
    // Delete listing (matches will be cascade deleted)
    $query = "DELETE FROM wanted_listings WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $data['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Wanted listing deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Delete wanted listing error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
