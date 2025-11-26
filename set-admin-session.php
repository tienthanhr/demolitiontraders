<?php
session_start();

// Manually set admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['user_role'] = 'admin';
$_SESSION['is_admin'] = true;
$_SESSION['user_email'] = 'admin@demolitiontraders.co.nz';
$_SESSION['first_name'] = 'Admin';
$_SESSION['last_name'] = 'User';

echo "Admin session created!<br>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";
echo "Is Admin: " . ($_SESSION['is_admin'] ? 'true' : 'false') . "<br><br>";

echo '<a href="/demolitiontraders/frontend/admin/orders.php">Go to Orders Page</a><br>';
echo '<a href="/demolitiontraders/check-session.php">Check Session</a>';
