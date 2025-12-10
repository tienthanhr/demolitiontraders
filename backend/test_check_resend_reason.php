<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();
$rows = $db->fetchAll('SELECT id, order_id, type, to_email, status, resend_reason, created_at FROM email_logs WHERE resend_reason IS NOT NULL ORDER BY id DESC LIMIT 10');
foreach ($rows as $r) {
    echo "[{$r['created_at']}] id={$r['id']} order={$r['order_id']} type={$r['type']} to={$r['to_email']} status={$r['status']} reason={$r['resend_reason']}\n";
}
