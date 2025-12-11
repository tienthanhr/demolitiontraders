<?php
/**
 * Admin Authentication & Authorization Check
 * Include this file at the top of every admin page
 * 
 * Usage: require_once 'auth-check.php';
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to redirect to login
function redirectToLogin() {
    // Clear any partial session data
    session_unset();
    session_destroy();
    
    // Use SITE_URL constant for proper URL (not file path!)
    if (defined('SITE_URL')) {
        $loginUrl = SITE_URL . '/frontend/admin-login.php';
    } else {
        // Fallback: build URL from server variables
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        
        // Extract base path from REQUEST_URI
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $basePath = str_replace('/admin', '', $scriptName);
        
        $loginUrl = $protocol . $host . $basePath . '/frontend/admin-login.php';
    }
    
    // Redirect
    header('Location: ' . $loginUrl);
    exit;
}

// Log authentication attempt
$requestUri = $_SERVER['REQUEST_URI'] ?? 'unknown';
error_log("=== ADMIN AUTH CHECK START ===");
error_log("Requested URI: " . $requestUri);
error_log("Session ID: " . session_id());
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Session role: " . ($_SESSION['role'] ?? 'NOT SET'));
error_log("Session user_role: " . ($_SESSION['user_role'] ?? 'NOT SET'));
error_log("Session is_admin: " . (($_SESSION['is_admin'] ?? false) ? 'true' : 'false'));

// Check 1: User must be logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("❌ AUTH FAILED: No user_id in session");
    error_log("=== ADMIN AUTH CHECK END (FAILED) ===");
    redirectToLogin();
}

// Check 2: User must be admin
$isAdmin = (
    ($_SESSION['role'] ?? '') === 'admin' || 
    ($_SESSION['user_role'] ?? '') === 'admin' || 
    ($_SESSION['is_admin'] ?? false) === true
);

if (!$isAdmin) {
    error_log("❌ AUTH FAILED: User " . $_SESSION['user_id'] . " is not admin");
    error_log("Available session keys: " . implode(', ', array_keys($_SESSION)));
    error_log("=== ADMIN AUTH CHECK END (UNAUTHORIZED) ===");
    redirectToLogin();
}

// Success
error_log("✅ AUTH SUCCESS: User " . $_SESSION['user_id'] . " authorized as admin");
error_log("=== ADMIN AUTH CHECK END (SUCCESS) ===");

// Set a flag that auth passed (optional, for debugging)
define('ADMIN_AUTH_PASSED', true);
?>