<?php
/**
 * Wanted Listing Submission API
 * Handles wanted listing submissions
 * - Saves to database
 * - Adds to wishlist if user is logged in
 * - Sends email notification to admin and user
 */

// Start output buffering and clean any existing output
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Suppress all errors from being displayed
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(0);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/EmailService.php';

// Set headers before any output
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['name']) || empty($data['email']) || empty($data['description'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Sanitize input
    $name = htmlspecialchars(trim($data['name']));
    $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
    $phone = isset($data['phone']) ? htmlspecialchars(trim($data['phone'])) : '';
    $category = isset($data['category']) ? htmlspecialchars(trim($data['category'])) : '';
    $itemName = isset($data['item_name']) ? htmlspecialchars(trim($data['item_name'])) : '';
    $description = htmlspecialchars(trim($data['description']));
    $quantity = isset($data['quantity']) ? htmlspecialchars(trim($data['quantity'])) : '';
    $notify = isset($data['notify']) && $data['notify'] === 'on';
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Check if user is logged in
    require_once __DIR__ . '/../../core/bootstrap.php';
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    
    // Store wanted listing in database
    $wantedListingId = $db->insert('wanted_listings', [
        'user_id' => $userId,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'category' => $category,
        'description' => $description . ($itemName ? "\nItem Name: $itemName" : ""),
        'quantity' => $quantity,
        'notify_enabled' => $notify ? 1 : 0,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // If user is logged in, try to find matching products and add to wishlist
    $matchedProducts = [];
    if ($userId) {
        // Search for matching products based on description and category
        $searchTerms = explode(' ', $description);
        $searchQuery = "SELECT p.* FROM products p WHERE p.stock_quantity > 0 AND (";
        $searchConditions = [];
        $searchParams = [];
        
        foreach ($searchTerms as $term) {
            if (strlen($term) > 3) {
                $searchConditions[] = "p.name LIKE ? OR p.description LIKE ?";
                $searchParams[] = "%$term%";
                $searchParams[] = "%$term%";
            }
        }
        
        if (!empty($searchConditions)) {
            $searchQuery .= implode(' OR ', $searchConditions) . ") LIMIT 10";
            $matchedProducts = $db->fetchAll($searchQuery, $searchParams);
            
            // Add matched products to wishlist
            if (!empty($matchedProducts)) {
                $wishlistSql = "INSERT IGNORE INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, ?)";
                $now = date('Y-m-d H:i:s');
                
                foreach ($matchedProducts as $product) {
                    $db->query($wishlistSql, [$userId, $product['id'], $now]);
                }
            }
        }
    }
    
    // Send email to admin
    $emailService = new EmailService();
    $emailResult = $emailService->sendWantedListingEmail([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'category' => $category,
        'description' => $description,
        'quantity' => $quantity,
        'notify' => $notify,
        'user_id' => $userId,
        'wanted_listing_id' => $wantedListingId
    ]);
    
    if (!$emailResult['success']) {
        error_log("Wanted listing email failed: " . $emailResult['error']);
    }
    
    // Send confirmation email to user if they want notifications
    if ($notify) {
        $emailService->sendWantedListingConfirmationEmail($email, $name, $description);
    }
    
    $response = [
        'success' => true,
        'message' => 'Thank you! We will notify you when we have a match.'
    ];
    
    if ($userId && !empty($matchedProducts)) {
        $response['matched_products'] = count($matchedProducts);
        $response['message'] .= " We found " . count($matchedProducts) . " similar items and added them to your wishlist.";
    }
    
    // Clean buffer and output JSON
    $output = ob_get_clean();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
    exit;
    
} catch (Exception $e) {
    error_log("Wanted listing form error: " . $e->getMessage());
    error_log("Wanted listing stack trace: " . $e->getTraceAsString());
    
    // Clean buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'An error occurred. Please try again.',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
    exit;
}
