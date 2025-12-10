<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();
try {
	$rows = $db->fetchAll('SELECT * FROM email_logs ORDER BY id DESC LIMIT 10');
	header('Content-Type: application/json');
	echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Exception $e) {
	header('Content-Type: application/json');
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Failed to fetch email logs: ' . $e->getMessage()], JSON_PRETTY_PRINT);
}
