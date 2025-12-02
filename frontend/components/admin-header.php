<?php
/**
 * Admin Header Component
 * Include this at the top of all admin pages
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.save_path', '/tmp');
    session_start();
}

// Check if user is admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin' || 
           ($_SESSION['user_role'] ?? '') === 'admin' || 
           ($_SESSION['is_admin'] ?? false) === true;

if (!isset($_SESSION['user_id']) || !$isAdmin) {
    require_once __DIR__ . '/../config.php';
    header('Location: ' . BASE_PATH . 'admin-login');
    exit;
}

require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - Demolition Traders</title>
    <base href="<?php echo FRONTEND_PATH; ?>">
    
    <script src="<?php echo asset('assets/js/api-helper.js?v=1'); ?>"></script>
    <link rel="stylesheet" href="<?php echo asset('admin/admin-style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>
<body>
    <div class="admin-wrapper">
        <?php include __DIR__ . '/../admin/sidebar.php'; ?>
        
        <div class="main-content">
