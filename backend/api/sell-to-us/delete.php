<?php
/**
 * Delete Sell to Us Submission (Admin Only)
 * DELETE /backend/api/sell-to-us/delete.php
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Submission ID is required']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Get submission first to delete photos
    $submission = $db->fetchOne(
        "SELECT photos FROM sell_to_us_submissions WHERE id = :id",
        ['id' => $data['id']]
    );
    
    if (!$submission) {
        http_response_code(404);
        echo json_encode(['error' => 'Submission not found']);
        exit;
    }
    
    // Delete photos from filesystem
    if ($submission['photos']) {
        $photos = json_decode($submission['photos'], true);
        foreach ($photos as $photo) {
            $filePath = __DIR__ . '/../../../uploads/sell-to-us/' . basename($photo);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }
    
    // Delete submission
    $result = $db->execute(
        "DELETE FROM sell_to_us_submissions WHERE id = :id",
        ['id' => $data['id']]
    );
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Submission deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete submission']);
    }
    
} catch (Exception $e) {
    error_log("Sell to Us Delete Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete submission']);
}
