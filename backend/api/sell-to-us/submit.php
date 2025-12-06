<?php
/**
 * Sell To Us Form Submission API
 */
require_once __DIR__ . '/../../core/bootstrap.php';
header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../services/EmailService.php';

try {
    // Basic validation
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['description'])) {
        throw new Exception('Name, email, and description are required fields.');
    }

    $db = Database::getInstance();
    
    // Handle file uploads
    $imagePaths = [];
    if (!empty($_FILES['images'])) {
        $uploadDir = dirname(__DIR__, 3) . '/uploads/sell-to-us/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . '-' . basename($_FILES['images']['name'][$key]);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    // Store the web-accessible path
                    $imagePaths[] = '/uploads/sell-to-us/' . $fileName;
                }
            }
        }
    }

    // Insert into database
    $sellItemId = $db->insert('sell_items', [
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'] ?? null,
        'category' => $_POST['category'] ?? null,
        'description' => $_POST['description'],
        'images' => json_encode($imagePaths),
        'status' => 'pending'
    ]);

    // Send notification email
    $emailService = new EmailService();
    $emailService->sendSellToUsSubmissionEmail($_POST, $sellItemId);

    echo json_encode([
        'success' => true,
        'message' => 'Your submission has been received. We will get back to you shortly.'
    ]);

} catch (Exception $e) {
    error_log("Sell To Us Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while submitting your request.'
    ]);
}
