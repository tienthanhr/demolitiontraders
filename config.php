<?php
/**
 * Main Configuration Bootstrap
 * Used by both frontend and admin entrypoints.
 */

// Load the Config helper (reads from environment/ini)
require_once __DIR__ . '/backend/config/config.php';
Config::load();

// Path configuration
define('BASE_PATH', __DIR__);
define('ADMIN_PATH', BASE_PATH . '/admin');
define('FRONTEND_PATH', BASE_PATH . '/frontend');
define('BACKEND_PATH', BASE_PATH . '/backend');

// Site configuration
define('SITE_URL', rtrim(Config::get('APP_URL', 'http://localhost/demolitiontraders'), '/'));
define('ADMIN_URL', SITE_URL . '/admin');
define('FRONTEND_URL', SITE_URL . '/frontend');

// Database configuration
define('DB_HOST', Config::get('DB_HOST', 'localhost'));
define('DB_PORT', Config::get('DB_PORT', '3306'));
define('DB_USER', Config::get('DB_USER', 'root'));
define('DB_PASS', Config::get('DB_PASS', ''));
define('DB_NAME', Config::get('DB_NAME', 'demolitiontraders'));

// App configuration
define('APP_ENV', Config::get('APP_ENV', 'production'));
define('APP_DEBUG', Config::get('APP_DEBUG', false));

// Session configuration (shared across app)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', Config::get('SESSION_SECURE', 0));

    session_name('dt_session');
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (bool) Config::get('SESSION_SECURE', 0),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Use the shared writable session store if available
    $sessionPath = realpath(__DIR__ . '/cache/sessions');
    if ($sessionPath && is_dir($sessionPath) && is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }

    session_start();
}

// Database connection (legacy direct usage)
// On some hosts (e.g., minimal PHP builds) mysqli extension may be unavailable.
$conn = null;
if (class_exists('mysqli')) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
        if ($conn->connect_error) {
            if (APP_DEBUG) {
                die("Connection failed: " . $conn->connect_error);
            } else {
                die("Database connection error. Please contact administrator.");
            }
        }
        $conn->set_charset("utf8mb4");
    } catch (Exception $e) {
        if (APP_DEBUG) {
            die("Database error: " . $e->getMessage());
        } else {
            die("Database connection error. Please contact administrator.");
        }
    }
} else {
    error_log('mysqli extension not available; skipping direct mysqli connection. API will use its own DB layer.');
}

// Timezone
date_default_timezone_set(Config::get('APP_TIMEZONE', 'Pacific/Auckland'));

// Error reporting based on environment
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
