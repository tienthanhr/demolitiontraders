<?php
/**
 * Global Bootstrap File
 *
 * Handles secure session initialization and other global settings.
 * This file should be included at the very beginning of any script that needs session access.
 */

// Do not allow direct access to this file.
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    die('Access Denied');
}

// --- Secure Session Configuration ---

// 1. Force session to only use cookies, preventing session fixation through URL parameters.
ini_set('session.use_only_cookies', 1);

// 2. Set secure cookie parameters.
$is_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => $is_secure, // Send cookie only over HTTPS.
    'httponly' => true,      // Prevent JavaScript access to the session cookie.
    'samesite' => 'Lax'      // CSRF protection. 'Lax' is a good balance.
]);

// 3. Start the session if it hasn't been started already.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
