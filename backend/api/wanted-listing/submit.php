<?php
/**
 * Wanted Listing Submission API
 * Handles wanted listing submissions
 * - Saves to database
 * - Adds to wishlist if user is logged in
 * - Sends email notification to admin and user
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../services/EmailService.php';

header('Content-Type: application/json');
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
    $description = htmlspecialchars(trim($data['description']));
    $quantity = isset($data['quantity']) ? htmlspecialchars(trim($data['quantity'])) : '';
    $notify = isset($data['notify']) && $data['notify'] === 'on';
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Check if user is logged in
    session_start();
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    
    // Store wanted listing in database
    $stmt = $db->prepare("
        INSERT INTO wanted_listings 
        (user_id, name, email, phone, category, description, quantity, notify_enabled, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->execute([$userId, $name, $email, $phone, $category, $description, $quantity, $notify ? 1 : 0]);
    $wantedListingId = $db->lastInsertId();
    
    // If user is logged in, try to find matching products and add to wishlist
    $matchedProducts = [];
    if ($userId) {
        // Search for matching products based on description and category
        $searchTerms = explode(' ', $description);
        $searchQuery = "SELECT p.* FROM products p WHERE p.status = 'active' AND p.stock_quantity > 0 AND (";
        $searchConditions = [];
        $searchParams = [];
        
        foreach ($searchTerms as $term) {
            if (strlen($term) > 3) {
                $searchConditions[] = "p.name LIKE ? OR p.description LIKE ?";
                $searchParams[] = "%$term%";
                $searchParams[] = "%$term%";
            }
        }
        
        if (!empty($category)) {
            $searchConditions[] = "p.category = ?";
            $searchParams[] = $category;
        }
        
        if (!empty($searchConditions)) {
            $searchQuery .= implode(' OR ', $searchConditions) . ") LIMIT 10";
            $stmt = $db->prepare($searchQuery);
            $stmt->execute($searchParams);
            $matchedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add matched products to wishlist
            if (!empty($matchedProducts)) {
                $wishlistStmt = $db->prepare("
                    INSERT IGNORE INTO wishlist (user_id, product_id, created_at)
                    VALUES (?, ?, NOW())
                ");
                
                foreach ($matchedProducts as $product) {
                    $wishlistStmt->execute([$userId, $product['id']]);
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
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Wanted listing form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
