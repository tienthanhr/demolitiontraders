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
    'domain' => '',          // Default to current domain.
    'secure' => false, // Send cookie only over HTTPS.
    'httponly' => true,      // Prevent JavaScript access to the session cookie.
    'samesite' => 'Lax'      // CSRF protection. 'Lax' is a good balance.
]);

// 3. Ensure a stable, writable session store (helps on Windows/XAMPP)
// Use project-local session path to avoid env defaults being unwritable or cleared
$sessionPath = realpath(__DIR__ . '/../../cache/sessions');
if ($sessionPath && is_dir($sessionPath) && is_writable($sessionPath)) {
    session_save_path($sessionPath);
}

// Optional: use a distinct session name to prevent clashes
session_name('dt_session');

// 4. Start the session if it hasn't been started already.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
