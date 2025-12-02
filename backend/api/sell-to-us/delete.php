<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Submission ID is required']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Get submission first to delete photos
    $stmt = $db->prepare("SELECT photos FROM sell_to_us_submissions WHERE id = :id");
    $stmt->execute(['id' => $data['id']]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$submission) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Submission not found']);
        exit;
    }
    
    // Delete photos from filesystem
    if ($submission['photos']) {
        $photos = json_decode($submission['photos'], true);
        if (is_array($photos)) {
            foreach ($photos as $photo) {
                $filePath = __DIR__ . '/../../../' . $photo;
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
        }
    }
    
    // Delete submission
    $stmt = $db->prepare("DELETE FROM sell_to_us_submissions WHERE id = :id");
    $result = $stmt->execute(['id' => $data['id']]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Submission deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to delete submission']);
    }
    
} catch (Exception $e) {
    error_log("Sell to Us Delete Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Failed to delete submission',
        'debug' => $e->getMessage()
    ]);
}
