<?php
// Quick test runner to call API index and simulate a GET to orders/{id}/email-logs without admin
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['request'] = 'orders/40/email-logs';
// Simulate session as regular user
session_start();
$_SESSION['is_admin'] = false;
$_SESSION['user_id'] = 2;
// include the API router
require_once __DIR__ . '/api/index.php';
