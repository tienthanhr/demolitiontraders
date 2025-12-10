<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['request'] = 'orders/40';
session_start();
$_SESSION['is_admin'] = false;
$_SESSION['user_id'] = 2;
require_once __DIR__ . '/api/index.php';
