<?php
// Basic test script to invoke the API router for the revenue endpoint as admin
session_start();
$_SESSION['is_admin'] = true;
$_SESSION['user_id'] = 1;
// Simulate GET request
$_SERVER['REQUEST_METHOD'] = 'GET';
// Place period as query param to match API path GET /api/index.php?request=orders&action=revenue
$_GET['request'] = 'orders';
$_GET['action'] = 'revenue';
// Accept optional args
$argv = $_SERVER['argv'] ?? [];
$period = $argv[1] ?? 'all';
$from = $argv[2] ?? null;
$to = $argv[3] ?? null;
if ($period !== 'all') $_GET['period'] = $period;
if ($from) $_GET['from'] = $from;
if ($to) $_GET['to'] = $to;
require_once __DIR__ . '/api/index.php';

?>
