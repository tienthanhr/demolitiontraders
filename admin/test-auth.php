<?php
/**
 * Admin Authentication & Authorization Check
 * Must be included AFTER config.php
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to redirect to login
function redirectToLogin() {
    session_unset();
    session_destroy();
    
    // Build redirect URL - handle case where BASE_PATH might not be defined yet
    if (!defined('BASE_PATH')) {
        // Fallback: try to determine base path from current script
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('/frontend/admin/', '/', dirname($scriptName));
        $basePath = str_replace('/admin/', '/', $basePath);
        if (!str_ends_with($basePath, '/')) {
            $basePath .= '/';
        }
    } else {
        $basePath = BASE_PATH;
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $redirectPath = rtrim($basePath, '/') . '/admin-login';
    
    header('Location: ' . $protocol . $host . $redirectPath);
    exit;
}

// Log authentication attempt
error_log("=== ADMIN AUTH CHECK START ===");
error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log("Session role: " . ($_SESSION['role'] ?? 'NOT SET'));

// Check 1: User must be logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("❌ AUTH FAILED: No user_id in session");
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
    redirectToLogin();
}

// Success
error_log("✅ AUTH SUCCESS: User " . $_SESSION['user_id'] . " authorized as admin");
define('ADMIN_AUTH_PASSED', true);
?>