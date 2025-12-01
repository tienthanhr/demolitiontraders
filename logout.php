<?php
/**
 * Logout Page
 * Logs out user and redirects to appropriate page
 */
session_start();

// Store user role before clearing session
$wasAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect based on previous role
if ($wasAdmin) {
    // Redirect admin to admin login page
    header('Location: /demolitiontraders/frontend/admin-login.php');
} else {
    // Redirect customer to main login page
    header('Location: /demolitiontraders/frontend/login.php');
}
exit;
