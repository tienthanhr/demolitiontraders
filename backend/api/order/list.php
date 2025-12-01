<?php
// Get order history for logged in user
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];
$conn = getDb();
$stmt = $conn->prepare('SELECT id, order_number, status, payment_status, total_amount, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'orders' => $orders]);
