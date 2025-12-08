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
    
    $db = Database::getInstance();
    
    // Get the submission before deleting to store in notes
    $submission = $db->fetchOne("SELECT * FROM sell_to_us_submissions WHERE id = :id", [':id' => $data['id']]);
    
    if (!$submission) {
        throw new Exception('Submission not found');
    }
    
    // Store deletion info in admin notes before deleting
    $deleteNote = "Deleted by admin " . $_SESSION['user_id'] . " at " . date('Y-m-d H:i:s');
    $updateQuery = "UPDATE sell_to_us_submissions SET notes = CONCAT(COALESCE(notes, ''), '\n[DELETED] ', :note), status = 'declined' WHERE id = :id";
    
    $db->query($updateQuery, [
        ':id' => $data['id'],
        ':note' => $deleteNote
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Submission marked as deleted (can be restored)',
        'data' => $submission
    ]);
    
} catch (Exception $e) {
    error_log("Soft delete sell-to-us error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
