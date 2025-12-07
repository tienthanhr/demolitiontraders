<?php
// User login API
// Configure session for Render
require_once __DIR__ . '/../../core/bootstrap.php';

// CORS headers with credentials support
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

require_once '../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (!$email || !$password) {
        throw new Exception('Email and password are required.');
    }

    $db = Database::getInstance();
    
    // Try to find user (case-insensitive email, check status)
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE LOWER(email) = LOWER(:email) AND LOWER(status) = 'active'",
        ['email' => $email]
    );

    if (!$user) {
        throw new Exception('Invalid email or password.');
    }
    
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password.');
    }

    // Update last login
    $db->update(
        'users',
        ['last_login' => date('Y-m-d H:i:s')],
        'id = :id',
        ['id' => $user['id']]
    );

    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['is_admin'] = ($user['role'] === 'admin');

    // Merge guest cart (session-based) into user cart after login
    $sessionId = session_id();
    $guestCartItems = $db->fetchAll(
        "SELECT product_id, quantity FROM cart WHERE session_id = :sid",
        ['sid' => $sessionId]
    );

    foreach ($guestCartItems as $item) {
        $productId = $item['product_id'];
        $guestQty = (int)$item['quantity'];

        // Check current stock
        $stock = $db->fetchOne(
            "SELECT stock_quantity FROM products WHERE id = :pid",
            ['pid' => $productId]
        );
        if (!$stock) {
            continue; // product no longer exists
        }

        // Check if user already has this product in cart
        $existing = $db->fetchOne(
            "SELECT id, quantity FROM cart WHERE user_id = :uid AND product_id = :pid",
            ['uid' => $user['id'], 'pid' => $productId]
        );

        if ($existing) {
            $newQty = min((int)$stock['stock_quantity'], (int)$existing['quantity'] + $guestQty);
            $db->query(
                "UPDATE cart SET quantity = :qty WHERE id = :id",
                ['qty' => $newQty, 'id' => $existing['id']]
            );
        } else {
            $newQty = min((int)$stock['stock_quantity'], $guestQty);
            $db->query(
                "UPDATE cart SET user_id = :uid, session_id = NULL, quantity = :qty WHERE session_id = :sid AND product_id = :pid",
                ['uid' => $user['id'], 'qty' => $newQty, 'sid' => $sessionId, 'pid' => $productId]
            );
            // If no row was updated (race), insert
            if ($db->rowCount() === 0) {
                $db->query(
                    "INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (:uid, :pid, :qty, NOW())",
                    ['uid' => $user['id'], 'pid' => $productId, 'qty' => $newQty]
                );
            }
        }
    }

    // Clean up guest cart rows
    $db->query("DELETE FROM cart WHERE session_id = :sid", ['sid' => $sessionId]);

    // Merge guest wishlist (session) into user wishlist
    if (!empty($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
        foreach ($_SESSION['wishlist'] as $pid) {
            $db->query(
                "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (:uid, :pid)",
                ['uid' => $user['id'], 'pid' => $pid]
            );
        }
        unset($_SESSION['wishlist']);
    }

    unset($user['password']);

    echo json_encode([
        'success' => true,
        'user' => $user,
        'message' => 'Login successful'
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ]);
}
