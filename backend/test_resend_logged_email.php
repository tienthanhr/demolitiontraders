<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/services/EmailService.php';

$db = Database::getInstance();
$logId = $argv[1] ?? null;
if (!$logId) {
    $row = $db->fetchOne('SELECT * FROM email_logs ORDER BY id DESC LIMIT 1');
    $logId = $row['id'] ?? null;
}
if (!$logId) {
    echo "No log found. Run a test send first.\n";
    exit(1);
}

$log = $db->fetchOne('SELECT * FROM email_logs WHERE id = :id', ['id' => $logId]);
if (!$log) {
    echo "Log $logId not found.\n";
    exit(1);
}

echo "Resending log#$logId: type={$log['type']} to={$log['to_email']} order_id={$log['order_id']}\n";

$service = new EmailService();
$order = null;
if ($log['order_id']) {
    $order = $db->fetchOne('SELECT * FROM orders WHERE id = :id', ['id' => $log['order_id']]);
    if ($order) {
        if (isset($order['billing_address']) && is_string($order['billing_address'])) {
            $order['billing_address'] = json_decode($order['billing_address'], true);
        }
    }
}

$triggeredBy = 1; // test as admin
$result = ['success'=>false];
if ($log['type'] === 'tax_invoice') {
    $result = $service->sendTaxInvoice($order, $log['to_email'], true, $triggeredBy);
} elseif ($log['type'] === 'receipt') {
    $result = $service->sendReceipt($order, $log['to_email'], true, $triggeredBy);
} else {
    $ok = $service->sendEmail($log['to_email'], $log['subject'] ?? 'Resend', $log['response'] ?? 'Resend');
    $result = ['success' => $ok];
}

var_export($result);

$logs = $db->fetchAll('SELECT * FROM email_logs WHERE order_id = :id ORDER BY id DESC LIMIT 10', ['id' => $log['order_id']]);
foreach ($logs as $l) {
    echo "[{$l['created_at']}] {$l['type']} to {$l['to_email']} via {$l['send_method']} status={$l['status']} error={$l['error_message']}\n";
}
