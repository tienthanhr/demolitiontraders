<?php
// Quick test runner to call API index and simulate a GET to orders/{id}/email-logs
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['request'] = 'orders/40/email-logs';
// Simulate admin session
session_start();
$_SESSION['is_admin'] = true;
$_SESSION['user_id'] = 1;
// include the API router
require_once __DIR__ . '/api/index.php';
