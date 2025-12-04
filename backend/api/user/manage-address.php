<?php
/**
 * Manage User Address API (Create/Update/Delete)
 */
require_once __DIR__ . '/../../core/bootstrap.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../config/database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Create new address
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['address', 'city', 'postcode'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Check if address already exists
            $existingAddress = $db->fetchOne(
                "SELECT * FROM addresses 
                 WHERE user_id = ? 
                 AND LOWER(TRIM(street_address)) = LOWER(TRIM(?))
                 AND LOWER(TRIM(city)) = LOWER(TRIM(?))
                 AND LOWER(TRIM(postcode)) = LOWER(TRIM(?))",
                [$userId, $data['address'], $data['city'], $data['postcode']]
            );
            
            if ($existingAddress) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'This address already exists in your account'
                ]);
                exit;
            }
            
            // Check address limit (max 5)
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM addresses WHERE user_id = ?", [$userId]);
            if ($count && $count['count'] >= 5) {
                throw new Exception('Maximum 5 addresses allowed per user');
            }
            
            // If this is the first address or set as default, make it default
            $isDefault = !empty($data['is_default']) || $count['count'] == 0;
            
            // If setting as default, unset other defaults
            if ($isDefault) {
                $db->query("UPDATE addresses SET is_default = 0 WHERE user_id = ?", [$userId]);
            }
            
            $addressId = $db->insert('addresses', [
                'user_id' => $userId,
                'address_type' => 'billing',
                'street_address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'] ?? null,
                'postcode' => $data['postcode'],
                'country' => $data['country'] ?? 'New Zealand',
                'suburb' => $data['suburb'] ?? null,
                'is_default' => $isDefault ? 1 : 0
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Address added successfully',
                'address_id' => $addressId
            ]);
            break;
            
        case 'PUT':
            // Update address
            $data = json_decode(file_get_contents('php://input'), true);
            $addressId = $data['id'] ?? null;
            
            if (!$addressId) {
                throw new Exception('Address ID is required');
            }
            
            // Verify ownership
            $address = $db->fetchOne(
                "SELECT * FROM addresses WHERE id = ? AND user_id = ?",
                [$addressId, $userId]
            );
            
            if (!$address) {
                throw new Exception('Address not found or unauthorized');
            }
            
            // If setting as default, unset other defaults
            if (!empty($data['is_default'])) {
                $db->query("UPDATE addresses SET is_default = 0 WHERE user_id = ?", [$userId]);
            }
            
            $updateData = [];
            $allowedFields = ['address_type', 'suburb', 'city', 'state', 'postcode', 'country', 'is_default'];
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            // Map 'address' to 'street_address'
            if (isset($data['address'])) {
                $updateData['street_address'] = $data['address'];
            }
            
            $db->update('addresses', $updateData, 'id = :id AND user_id = :user_id', [
                'id' => $addressId,
                'user_id' => $userId
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Address updated successfully'
            ]);
            break;
            
        case 'DELETE':
            // Delete address
            $data = json_decode(file_get_contents('php://input'), true);
            $addressId = $data['id'] ?? $_GET['id'] ?? null;
            
            if (!$addressId) {
                throw new Exception('Address ID is required');
            }
            
            // Verify ownership
            $address = $db->fetchOne(
                "SELECT * FROM addresses WHERE id = ? AND user_id = ?",
                [$addressId, $userId]
            );
            
            if (!$address) {
                throw new Exception('Address not found or unauthorized');
            }
            
            $db->delete('addresses', 'id = :id AND user_id = :user_id', [
                'id' => $addressId,
                'user_id' => $userId
            ]);
            
            // If deleted address was default, set another as default
            if ($address['is_default']) {
                $db->query(
                    "UPDATE addresses SET is_default = 1 WHERE user_id = ? ORDER BY created_at DESC LIMIT 1",
                    [$userId]
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
