<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/services/EmailService.php';

$db = Database::getInstance();
$orderId = $argv[1] ?? null;
if (!$orderId) {
    $row = $db->fetchOne('SELECT * FROM orders ORDER BY created_at DESC LIMIT 1');
    $orderId = $row['id'] ?? null;
}
if (!$orderId) {
    echo "No order found. Create an order first.\n";
    exit(1);
}
$order = $db->fetchOne('SELECT * FROM orders WHERE id = :id', ['id' => $orderId]);
// Decode JSON fields
if (isset($order['billing_address']) && is_string($order['billing_address'])) {
    $order['billing_address'] = json_decode($order['billing_address'], true);
}
if (isset($order['shipping_address']) && is_string($order['shipping_address'])) {
    $order['shipping_address'] = json_decode($order['shipping_address'], true);
}
$orderItems = $db->fetchAll('SELECT * FROM order_items WHERE order_id = :order_id', ['order_id' => $orderId]);
$order['items'] = $orderItems;

$toEmail = $order['billing_address']['email'] ?? $order['guest_email'] ?? null;
if (!$toEmail) {
    echo "No email found for order $orderId\n";
    exit(1);
}

$emailService = new EmailService();
$result = $emailService->sendTaxInvoice($order, $toEmail, true, $argv[2] ?? null);
var_export($result);

// show recent email logs for the order
$logs = $db->fetchAll('SELECT * FROM email_logs WHERE order_id = :id ORDER BY id DESC LIMIT 10', ['id' => $orderId]);
header('Content-Type: text/plain');
echo "\nEmail logs for order: $orderId\n";
foreach ($logs as $l) {
    echo "[{$l['created_at']}] {$l['type']} to {$l['to_email']} via {$l['send_method']} status={$l['status']} error={$l['error_message']}\n";
}
