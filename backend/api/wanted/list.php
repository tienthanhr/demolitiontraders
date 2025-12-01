<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../config/database.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $query = "SELECT w.*, 
              u.username, u.email as user_email,
              COUNT(DISTINCT wm.id) as match_count
              FROM wanted_listings w
              LEFT JOIN users u ON w.user_id = u.id
              LEFT JOIN wanted_listing_matches wm ON w.id = wm.wanted_listing_id
              GROUP BY w.id
              ORDER BY w.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $listings
    ]);
    
} catch (Exception $e) {
    error_log("Wanted listings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load wanted listings'
    ]);
}
