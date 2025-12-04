<?php
/**
 * Sell to Us Form Submission API
 * Handles sell-to-us form submissions with file uploads
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
    // Handle both multipart form data and JSON
    if (isset($_POST['name'])) {
        $data = $_POST;
    } else {
        $data = json_decode(file_get_contents('php://input'), true);
    }
    
    // Validate required fields
    if (empty($data['name']) || empty($data['email']) || empty($data['phone']) || 
        empty($data['item_name']) || empty($data['quantity']) || 
        empty($data['pickup_delivery']) || empty($data['description'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Sanitize input
    $name = htmlspecialchars(trim($data['name']));
    $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
    $phone = htmlspecialchars(trim($data['phone']));
    $location = isset($data['location']) ? htmlspecialchars(trim($data['location'])) : '';
    $itemName = htmlspecialchars(trim($data['item_name']));
    $quantity = htmlspecialchars(trim($data['quantity']));
    $pickupDate = isset($data['pickup_date']) ? htmlspecialchars(trim($data['pickup_date'])) : '';
    $pickupDelivery = htmlspecialchars(trim($data['pickup_delivery']));
    $description = htmlspecialchars(trim($data['description']));
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        exit;
    }
    
    // Handle file uploads
    $uploadedFiles = [];
    if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
        $uploadDir = __DIR__ . '/../../../uploads/sell-to-us/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileCount = count($_FILES['photos']['name']);
        for ($i = 0; $i < min($fileCount, 5); $i++) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['photos']['tmp_name'][$i];
                $fileName = uniqid() . '_' . basename($_FILES['photos']['name'][$i]);
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $filePath)) {
                    $uploadedFiles[] = 'uploads/sell-to-us/' . $fileName;
                }
            }
        }
    }
    
    // Store in database
    try {
        $db = Database::getInstance()->getConnection();
        if (!$db) {
            throw new Exception('Database connection failed');
        }
        $stmt = $db->prepare("
            INSERT INTO sell_to_us_submissions 
            (contact_name, email, phone, pickup_address, item_name, quantity, preferred_date, delivery_type, description, photos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . json_encode($db->errorInfo()));
        }
        $photosJson = !empty($uploadedFiles) ? json_encode($uploadedFiles) : null;
        // Convert empty pickup_date to NULL for PostgreSQL DATE column
        $preferredDate = !empty($pickupDate) ? $pickupDate : null;
        $result = $stmt->execute([$name, $email, $phone, $location, $itemName, $quantity, $preferredDate, $pickupDelivery, $description, $photosJson]);
        if (!$result) {
            throw new Exception('Failed to execute insert: ' . json_encode($stmt->errorInfo()));
        }
    } catch (Exception $dbError) {
        error_log("Database error in sell-to-us: " . $dbError->getMessage());
        // Log but don't break - we already confirmed success to user
    }
    
    // Send email to admin
    $emailService = new EmailService();
    $emailResult = $emailService->sendSellToUsEmail([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'location' => $location,
        'item_name' => $itemName,
        'quantity' => $quantity,
        'pickup_date' => $pickupDate,
        'pickup_delivery' => $pickupDelivery,
        'description' => $description,
        'photos' => $uploadedFiles
    ]);
    
    if (!$emailResult['success']) {
        error_log("Sell to us email failed: " . $emailResult['error']);
    }
    
    // Clean buffer and output JSON
    $output = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! We have received your submission and will contact you shortly.'
    ], JSON_UNESCAPED_UNICODE);
    
    exit;
    
} catch (Exception $e) {
    error_log("Sell to us form error: " . $e->getMessage());
    error_log("Sell to us stack trace: " . $e->getTraceAsString());
    
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
