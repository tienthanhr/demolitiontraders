<?php
/**
 * Update Sell to Us Submission (Admin Only)
 * POST /backend/api/sell-to-us/update.php
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
    
    // Build update fields
    $fields = [];
    $params = ['id' => $data['id']];
    
    if (isset($data['status'])) {
        $fields[] = "status = :status";
        $params['status'] = $data['status'];
    }
    
    if (isset($data['pickup_date'])) {
        $fields[] = "pickup_date = :pickup_date";
        $params['pickup_date'] = $data['pickup_date'] ?: null;
    }
    
    if (isset($data['notes'])) {
        $fields[] = "notes = :notes";
        $params['notes'] = $data['notes'];
    }
    
    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update']);
        exit;
    }
    
    // Update submission
    $stmt = $db->query("
        UPDATE sell_to_us_submissions 
        SET " . implode(', ', $fields) . "
        WHERE id = :id
    ", $params);
    
    if ($stmt->rowCount() > 0) {
        // Get updated submission
        $submission = $db->fetchOne(
            "SELECT * FROM sell_to_us_submissions WHERE id = :id",
            ['id' => $data['id']]
        );
        
        if ($submission['photos']) {
            $submission['photos'] = json_decode($submission['photos'], true);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Submission updated successfully',
            'data' => $submission
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Submission not found']);
    }
    
} catch (Exception $e) {
    error_log("Sell to Us Update Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update submission']);
}
