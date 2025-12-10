<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance();
$rows = $db->fetchAll('SELECT * FROM email_logs ORDER BY id DESC LIMIT 10');
header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT);
