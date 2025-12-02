<?php
// Check users in database
require_once __DIR__ . '/../backend/config/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    $users = $db->fetchAll("SELECT id, email, role, status, created_at FROM users LIMIT 10");
    
    echo json_encode([
        'success' => true,
        'count' => count($users),
        'users' => $users
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
