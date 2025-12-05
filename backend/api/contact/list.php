<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance();
    
    $query = "SELECT * FROM contact_submissions 
              ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $contacts
    ]);
    
} catch (Exception $e) {
    error_log("Contact messages error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load contact messages'
    ]);
}
