<?php
// Get order detail for logged in user
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id'] ?? 0);
if ($order_id < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid order id']);
    exit;
}
$conn = getDb();
$stmt = $conn->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}
$stmt2 = $conn->prepare('SELECT * FROM order_items WHERE order_id = ?');
$stmt2->execute([$order_id]);
$items = $stmt2->fetchAll(PDO::FETCH_ASSOC);
$order['items'] = $items;
echo json_encode(['success' => true, 'order' => $order]);
