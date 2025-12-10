<?php
// Simulate POST /api/index.php?request=orders/{id}/resend-email with JSON body
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['request'] = 'orders/40/resend-email';
// Simulate admin session
session_start();
$_SESSION['is_admin'] = true;
$_SESSION['user_id'] = 1;
// Prepare raw input JSON with a log_id (most recent)
// Usage: php -f backend/test_api_resend_email.php [logId] [to_email_override] [resend_reason]
$logId = $argv[1] ?? null;
$toOverride = $argv[2] ?? null;
$resendReasonOverride = $argv[3] ?? null;
if (!$logId) {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance();
    $row = $db->fetchOne('SELECT id FROM email_logs WHERE order_id = :id ORDER BY id DESC LIMIT 1', ['id' => 40]);
    $logId = $row['id'] ?? null;
}
if (!$logId) {
    echo "No log found for order 40\n";
    exit(1);
}
// Attach an example resend_reason and optional to_email override for testing
$payload = json_encode(['log_id' => $logId, 'resend_reason' => ($resendReasonOverride ?: 'Test resend: Admin noticed attachment missing'), 'to_email' => $toOverride] );
$GLOBALS['mock_input'] = $payload; // hack: set global for file_get_contents
// Override file_get_contents by wrapper? Simpler: set php://input using stream
$stdin = fopen('php://memory', 'r+');
fwrite($stdin, $payload);
rewind($stdin);
// Check: the index.php uses file_get_contents('php://input'), not reading $stdin
// So this test uses the global trick; index.php will still read php://input. To ensure it sees our payload, we write to php://input via php://memory -> not possible.
// Instead, we set $_POST to payload
$_POST = ['log_id' => $logId, 'resend_reason' => ($resendReasonOverride ?: 'Test resend: Admin noticed attachment missing'), 'to_email' => $toOverride];
require_once __DIR__ . '/api/index.php';
