<?php
require_once __DIR__ . '/config.php';

// Redirect to the main user-facing storefront.
header('Location: ' . FRONTEND_PATH . 'user/index.php');
exit;
?>
