<?php
/**
 * Admin Header Component
 * Include this at the top of all admin pages
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../admin/auth-check.php';

// Ensure a CSRF token exists for admin actions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> - Demolition Traders</title>
    <?php if (!empty($_SESSION['csrf_token'])): ?>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
    <script>window.CSRF_TOKEN = '<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>';</script>
    <?php endif; ?>
    <base href="<?php echo rtrim(FRONTEND_URL, '/'); ?>/">
    
    <script src="<?php echo FRONTEND_URL; ?>/assets/js/api-helper.js?v=1"></script>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/admin/admin-style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>
<body>
    <div class="admin-wrapper">
        <?php include __DIR__ . '/../../admin/sidebar.php'; ?>
        
        <div class="main-content">
