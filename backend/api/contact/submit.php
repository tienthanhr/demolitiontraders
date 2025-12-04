<?php
/**
 * Contact Form Submission API
 * Handles contact form submissions and sends email to admin
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
    if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Sanitize input
    $name = htmlspecialchars(trim($data['name']));
    $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
    $phone = isset($data['phone']) ? htmlspecialchars(trim($data['phone'])) : '';
    $subject = isset($data['subject']) ? htmlspecialchars(trim($data['subject'])) : 'General Enquiry';
    $message = htmlspecialchars(trim($data['message']));
    
    if (!$email) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        exit;
    }
    
    // Store in database
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO contact_submissions (name, email, phone, subject, message, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$name, $email, $phone, $subject, $message]);
    
    // Send email to admin
    $emailService = new EmailService();
    $emailResult = $emailService->sendContactFormEmail([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'message' => $message
    ]);
    
    if (!$emailResult['success']) {
        error_log("Contact form email failed: " . $emailResult['error']);
    }
    
    // Clean buffer and output JSON
    $output = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.'
    ], JSON_UNESCAPED_UNICODE);
    
    exit;
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    error_log("Contact form stack trace: " . $e->getTraceAsString());
    
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
