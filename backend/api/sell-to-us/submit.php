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
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone'])) {
        throw new Exception('Name, email, and phone are required fields.');
    }

    // Check if either description or photos are provided
    $hasDescription = !empty($_POST['description']);
    $hasPhotos = !empty($_FILES['photos']['name'][0]);

    if (!$hasDescription && !$hasPhotos) {
        throw new Exception('Please provide either a description or at least one photo.');
    }

    $db = Database::getInstance();
    
    // Handle file uploads
    $imagePaths = [];
    if ($hasPhotos) {
        $uploadDir = dirname(__DIR__, 3) . '/uploads/sell-to-us/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = uniqid() . '-' . basename($_FILES['photos']['name'][$key]);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    // Store the web-accessible path
                    $imagePaths[] = '/uploads/sell-to-us/' . $fileName;
                }
            }
        }
    }

    // Prepare data for insertion
    $insertData = [
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone'],
        'location' => $_POST['location'] ?? null,
        'item_name' => $_POST['item_name'] ?? '',
        'quantity' => $_POST['quantity'] ?? '',
        'pickup_date' => !empty($_POST['pickup_date']) ? $_POST['pickup_date'] : null,
        'pickup_delivery' => $_POST['pickup_delivery'] ?? '',
        'description' => $_POST['description'] ?? '',
        'photos' => json_encode($imagePaths),
        'status' => 'new'
    ];

    // Insert into database
    // Try to insert into sell_to_us_submissions first (new table)
    try {
        $sellItemId = $db->insert('sell_to_us_submissions', $insertData);
    } catch (Exception $dbEx) {
        // Fallback to sell_items (old table) if new table doesn't exist
        // Map fields to old schema
        $oldData = [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'description' => $_POST['description'] . "\n\nItem: " . ($_POST['item_name']??'') . "\nQty: " . ($_POST['quantity']??'') . "\nPickup: " . ($_POST['pickup_delivery']??''),
            'images' => json_encode($imagePaths),
            'status' => 'pending'
        ];
        $sellItemId = $db->insert('sell_items', $oldData);
    }

    // Prepare data for email (needs 'photos' as array, not JSON string)
    $emailData = $insertData;
    $emailData['photos'] = $imagePaths;

    // Send notification email
    $emailService = new EmailService();
    // Check if method exists (it should, based on my read)
    if (method_exists($emailService, 'sendSellToUsSubmissionEmail')) {
        $emailService->sendSellToUsSubmissionEmail($emailData, $sellItemId);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Your submission has been received. We will get back to you shortly.'
    ]);

} catch (Exception $e) {
    error_log("Sell To Us Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
