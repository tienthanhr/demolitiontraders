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
    
    // Get all wanted listings first
    $query = "SELECT * FROM wanted_listings ORDER BY created_at DESC";
    $listings = $db->fetchAll($query);
    
    // Enrich with username and match count
    foreach ($listings as &$listing) {
        // Get user name if user_id exists
        if ($listing['user_id']) {
            $userQuery = "SELECT first_name, last_name FROM users WHERE id = :id";
            $user = $db->fetchOne($userQuery, ['id' => $listing['user_id']]);
            $listing['username'] = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : null;
        } else {
            $listing['username'] = null;
        }
        
        // Get match count
        $matchQuery = "SELECT COUNT(*) as count FROM wanted_listing_matches WHERE wanted_listing_id = :id";
        $matchResult = $db->fetchOne($matchQuery, ['id' => $listing['id']]);
        $listing['match_count'] = $matchResult['count'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $listings
    ]);
    
} catch (Exception $e) {
    error_log("Wanted listings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load wanted listings',
        'debug' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
