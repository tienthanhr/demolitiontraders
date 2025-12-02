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
        throw new Exception('Submission ID is required');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Restore by removing [DELETED] tag from notes and changing status back
    $query = "UPDATE sell_to_us_submissions 
              SET notes = REPLACE(notes, SUBSTRING(notes, LOCATE('[DELETED]', notes), LOCATE('\\n', notes, LOCATE('[DELETED]', notes)) - LOCATE('[DELETED]', notes) + 1), ''),
                  status = 'new'
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $data['id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Submission restored successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Restore sell-to-us error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
