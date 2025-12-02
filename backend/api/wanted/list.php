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
    
    // Get all wanted listings first
    $query = "SELECT * FROM wanted_listings ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $listings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enrich with username and match count
    foreach ($listings as &$listing) {
        // Get user name if user_id exists
        if ($listing['user_id']) {
            $userQuery = "SELECT first_name, last_name FROM users WHERE id = ?";
            $userStmt = $db->prepare($userQuery);
            $userStmt->execute([$listing['user_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            $listing['username'] = $user ? trim($user['first_name'] . ' ' . $user['last_name']) : null;
        } else {
            $listing['username'] = null;
        }
        
        // Get match count
        $matchQuery = "SELECT COUNT(*) as count FROM wanted_listing_matches WHERE wanted_listing_id = ?";
        $matchStmt = $db->prepare($matchQuery);
        $matchStmt->execute([$listing['id']]);
        $matchResult = $matchStmt->fetch(PDO::FETCH_ASSOC);
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
