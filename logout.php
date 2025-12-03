<?php
/**
 * Logout Page
 * Logs out user and redirects to appropriate page
 */
require_once __DIR__ . '/frontend/config.php';
session_start();

// Store user role before clearing session
$wasAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') || 
            (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ||
            (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true);

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Check if admin parameter is passed (from admin panel logout)
if (isset($_GET['admin']) && $_GET['admin'] == '1') {
    // Don't redirect when called via fetch - let JavaScript handle it
    exit;
}

// Redirect based on previous role (for direct browser access)
if ($wasAdmin) {
    // Redirect admin to admin login page
    header('Location: ' . BASE_PATH . 'admin-login');
} else {
    // Redirect customer to main login page
    header('Location: ' . BASE_PATH . 'login.php');
}
exit;
