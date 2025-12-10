<?php
/**
 * Frontend Session Setup
 * Matches the session configuration of the backend API to ensure session sharing.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Detect localhost
    $isLocalhost = (
        ($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || 
        ($_SERVER['SERVER_NAME'] ?? '') === '127.0.0.1' ||
        strpos(($_SERVER['SERVER_NAME'] ?? ''), 'localhost') !== false
    );

    // 1. Force session to only use cookies
    ini_set('session.use_only_cookies', 1);

    // 2. Set session save path for Railway
    if (!$isLocalhost) {
        ini_set('session.save_path', '/tmp');
    }

    // 2. Set secure cookie parameters (Must match backend/core/bootstrap.php)
    $is_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $domain = '';
    if (!$isLocalhost) {
        $domain = '';
    }
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => $domain,          
        'secure' => $is_secure, // Match protocol
        'httponly' => true,      
        'samesite' => 'Lax'      
    ]);

    // 3. Ensure a stable, writable session store
    // Adjust path relative to this file (frontend/session_setup.php -> ../cache/sessions)
    $sessionPath = realpath(__DIR__ . '/../cache/sessions');
    if ($sessionPath && is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    } else {
        // For production environments like Railway, use default session path
        // Don't set session_save_path to avoid issues
    }

    // 4. Use the same session name as the backend
    session_name('dt_session');

    // 5. Start the session
    session_start();
}
