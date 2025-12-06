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

// --- Global Security Headers ---
// Note: These headers are applied to all API responses.

// Content Security Policy (CSP) - A strict policy for APIs as they shouldn't render content.
header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none';");

// Prevent Clickjacking
header("X-Frame-Options: DENY");

// Prevent MIME-type sniffing
header("X-Content-Type-Options: nosniff");

// Control referrer information
header("Referrer-Policy: no-referrer-when-downgrade");

// Permissions Policy
header("Permissions-Policy: geolocation=()");

// HTTP Strict Transport Security (HSTS) - Only send if on HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=63072000");
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
