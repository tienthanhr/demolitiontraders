<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'user_id' => $_SESSION['user_id'] ?? 'not set',
    'role' => $_SESSION['role'] ?? 'not set',
    'user_role' => $_SESSION['user_role'] ?? 'not set',
    'is_admin' => $_SESSION['is_admin'] ?? 'not set',
    'all_session' => $_SESSION
]);
